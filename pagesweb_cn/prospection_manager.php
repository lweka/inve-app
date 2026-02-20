<?php
session_start();
$runnerMode = defined('PROSPECTION_RUNNER_MODE') && PROSPECTION_RUNNER_MODE === true;
if (!$runnerMode && (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true)) {
    header('Location: admin_login_form');
    exit;
}

require_once __DIR__ . '/../configUrlcn.php';
require_once __DIR__ . '/connectDb.php';
require_once __DIR__ . '/send_email.php';

if (!defined('BASE_URL')) {
    define('BASE_URL', '/');
}

if (!function_exists('str_contains')) {
    function str_contains($haystack, $needle)
    {
        $haystack = (string)$haystack;
        $needle = (string)$needle;
        if ($needle === '') return true;
        return mb_strpos($haystack, $needle) !== false;
    }
}
if (!function_exists('str_starts_with')) {
    function str_starts_with($haystack, $needle)
    {
        $haystack = (string)$haystack;
        $needle = (string)$needle;
        if ($needle === '') return true;
        return substr($haystack, 0, strlen($needle)) === $needle;
    }
}
if (!function_exists('str_ends_with')) {
    function str_ends_with($haystack, $needle)
    {
        $haystack = (string)$haystack;
        $needle = (string)$needle;
        if ($needle === '') return true;
        $len = strlen($needle);
        return substr($haystack, -$len) === $needle;
    }
}

$adhesionSqlPath = dirname(__DIR__) . '/prospection/adhesion.sql';

function dsv(string $v): string
{
    $v = str_replace(["\\'", '\\"', '\\\\'], ["'", '"', '\\'], $v);
    return trim(html_entity_decode($v, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
}

function em(string $raw): string
{
    $raw = strtolower(trim((string)preg_replace('/\s+/', '', $raw)));
    return filter_var($raw, FILTER_VALIDATE_EMAIL) ? $raw : '';
}

function emailDomain(string $email): string
{
    $at = strrpos($email, '@');
    if ($at === false) return '';
    $domain = strtolower(trim(substr($email, $at + 1)));
    $domain = trim($domain, " \t\n\r\0\x0B.");
    if ($domain === '') return '';
    if (function_exists('idn_to_ascii')) {
        $ascii = @idn_to_ascii($domain);
        if (is_string($ascii) && $ascii !== '') {
            $domain = strtolower($ascii);
        }
    }
    return $domain;
}

function hasMxRecord(string $domain): bool
{
    static $cache = [];
    $domain = strtolower(trim($domain));
    if ($domain === '') return false;
    if (array_key_exists($domain, $cache)) {
        return $cache[$domain];
    }

    $ok = false;
    if (function_exists('checkdnsrr')) {
        $ok = (bool)@checkdnsrr($domain, 'MX');
    }
    if (!$ok && function_exists('dns_get_record')) {
        $mx = @dns_get_record($domain, DNS_MX);
        $ok = is_array($mx) && count($mx) > 0;
    }
    $cache[$domain] = $ok;
    return $ok;
}

function isSendableEmail(string $email, ?string &$reason = null): bool
{
    $email = em($email);
    if ($email === '') {
        $reason = 'Format email invalide';
        return false;
    }
    $domain = emailDomain($email);
    if ($domain === '') {
        $reason = 'Domaine email invalide';
        return false;
    }
    if (!hasMxRecord($domain)) {
        $reason = 'Domaine sans MX (boite mail non recevable)';
        return false;
    }
    return true;
}

function splitSendableEmailRows(array $rows): array
{
    $valid = [];
    $invalid = [];
    $emailCache = [];

    foreach ($rows as $row) {
        $email = strtolower((string)($row['email'] ?? ''));
        if ($email === '') continue;

        if (!isset($emailCache[$email])) {
            $reason = '';
            $ok = isSendableEmail($email, $reason);
            $emailCache[$email] = ['ok' => $ok, 'reason' => $reason];
        }

        if ($emailCache[$email]['ok']) {
            $valid[] = $row;
        } else {
            $row['validation_reason'] = (string)($emailCache[$email]['reason'] ?? 'Adresse exclue');
            $invalid[] = $row;
        }
    }

    return ['valid' => $valid, 'invalid' => $invalid];
}

function ph(string $raw): string
{
    $parts = preg_split('/[;,|\/]+/', $raw) ?: [$raw];
    foreach ($parts as $p) {
        $d = preg_replace('/\D+/', '', (string)$p) ?? '';
        if ($d === '') continue;
        if (str_starts_with($d, '00')) $d = substr($d, 2);
        if (strlen($d) === 9) $d = '243' . $d;
        if (strlen($d) === 10 && str_starts_with($d, '0')) $d = '243' . substr($d, 1);
        if (strlen($d) >= 8) return $d;
    }
    return '';
}

function parseAdhesion(string $path): array
{
    if (!is_file($path)) return ['rows' => [], 'raw' => 0];
    $sql = file_get_contents($path);
    if ($sql === false) return ['rows' => [], 'raw' => 0];

    $p = "/\\((\\d+),\\s*'((?:\\\\.|[^'])*)',\\s*'((?:\\\\.|[^'])*)',\\s*'((?:\\\\.|[^'])*)',\\s*'((?:\\\\.|[^'])*)',\\s*'((?:\\\\.|[^'])*)',\\s*'((?:\\\\.|[^'])*)'\\)/u";
    preg_match_all($p, $sql, $m, PREG_SET_ORDER);

    $rows = [];
    $seen = [];
    foreach ($m as $r) {
        $nom = dsv((string)$r[2]);
        $pre = dsv((string)$r[3]);
        $qua = dsv((string)$r[4]);
        $mailRaw = dsv((string)$r[5]);
        $phoneRaw = dsv((string)$r[6]);
        $dateIns = dsv((string)$r[7]);
        $full = trim($nom . ' ' . $pre);
        if ($full === '') $full = 'Prospect #' . (string)$r[1];
        $mail = em($mailRaw);
        $phone = ph($phoneRaw);
        $k = $mail !== '' ? 'e:' . $mail : ($phone !== '' ? 'p:' . $phone : 'n:' . strtolower(preg_replace('/\s+/', '', $full) ?? $full));
        if (isset($seen[$k])) continue;
        $seen[$k] = true;
        $rows[] = [
            'id' => (int)$r[1], 'nom' => $nom, 'prenom' => $pre, 'full' => $full,
            'qualite' => $qua, 'email' => $mail, 'phone' => $phone,
            'date' => $dateIns, 'key' => $k
        ];
    }
    return ['rows' => $rows, 'raw' => count($m)];
}

function hasKw(string $txt, array $kws): bool
{
    $t = strtolower($txt);
    foreach ($kws as $k) if (str_contains($t, strtolower($k))) return true;
    return false;
}

function buildSeg(array $contacts): array
{
    $seg = [
        'email_marketing' => ['title' => 'Prospection par email', 'icon' => 'fa-envelope', 'desc' => 'Envoi automatique de campagnes commerciales.', 'tpl' => 'Objet: Demo Cartelplus Congo', 'rows' => []],
        'sms_marketing' => ['title' => 'Prospection par SMS', 'icon' => 'fa-comment-sms', 'desc' => 'Relance rapide par message court.', 'tpl' => 'SMS: Demo gratuite Cartelplus Congo', 'rows' => []],
        'whatsapp_marketing' => ['title' => 'Prospection WhatsApp', 'icon' => 'fa-brands fa-whatsapp', 'desc' => 'Messages WhatsApp avec CTA.', 'tpl' => 'WhatsApp: Souhaitez-vous une demo ?', 'rows' => []],
        'phone_calls' => ['title' => 'Prospection par appel', 'icon' => 'fa-phone', 'desc' => 'Script d appel de conversion.', 'tpl' => 'Script appel: probleme > solution > rdv', 'rows' => []],
        'linkedin_b2b' => ['title' => 'Prospection LinkedIn B2B', 'icon' => 'fa-brands fa-linkedin', 'desc' => 'Ciblage profils pro.', 'tpl' => 'LinkedIn: digitalisez vos ventes', 'rows' => []],
        'facebook_messenger' => ['title' => 'Prospection Messenger', 'icon' => 'fa-brands fa-facebook-messenger', 'desc' => 'Conversation Facebook/Messenger.', 'tpl' => 'Messenger: demo en 15 min', 'rows' => []],
        'telegram_outreach' => ['title' => 'Prospection Telegram', 'icon' => 'fa-brands fa-telegram', 'desc' => 'Campagnes Telegram.', 'tpl' => 'Telegram: essai gratuit 7 jours', 'rows' => []],
        'referral_network' => ['title' => 'Prospection reseau/parrainage', 'icon' => 'fa-users', 'desc' => 'Activation influenceurs et relais.', 'tpl' => 'Parrainage: recommandez et gagnez', 'rows' => []],
        'field_visits' => ['title' => 'Prospection terrain', 'icon' => 'fa-map-location-dot', 'desc' => 'Plan de visites et demo locale.', 'tpl' => 'Checklist terrain: qualification + demo', 'rows' => []],
    ];
    $pro = ['avocat', 'magistrat', 'professeur', 'chercheur', 'assistant', 'enseignant', 'fonctionnaire', 'juriste'];

    $push = static function (array &$s, string $k, array $c): void {
        static $idx = [];
        $kk = $k . ':' . $c['key'];
        if (isset($idx[$kk])) return;
        $idx[$kk] = true;
        $s[$k]['rows'][] = $c;
    };

    foreach ($contacts as $c) {
        $hasEmail = $c['email'] !== '';
        $hasPhone = $c['phone'] !== '';
        $isPro = hasKw((string)$c['qualite'], $pro);
        if ($hasEmail) $push($seg, 'email_marketing', $c);
        if ($hasPhone) {
            $push($seg, 'sms_marketing', $c);
            $push($seg, 'whatsapp_marketing', $c);
            $push($seg, 'phone_calls', $c);
            $push($seg, 'telegram_outreach', $c);
        }
        if ($hasEmail || $hasPhone) $push($seg, 'facebook_messenger', $c);
        if ($isPro) {
            $push($seg, 'linkedin_b2b', $c);
            $push($seg, 'referral_network', $c);
        }
        $push($seg, 'field_visits', $c);
    }
    return $seg;
}

function ensureLogTable(PDO $pdo): void
{
    $pdo->exec("CREATE TABLE IF NOT EXISTS prospection_email_logs (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        campaign_date DATE NOT NULL,
        campaign_day VARCHAR(20) NOT NULL,
        campaign_theme VARCHAR(30) NOT NULL,
        target_name VARCHAR(255) NOT NULL DEFAULT '',
        target_email VARCHAR(255) NOT NULL,
        subject_line VARCHAR(255) NOT NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'sent',
        error_text TEXT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_campaign_date (campaign_date),
        INDEX idx_target_email (target_email),
        INDEX idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function dayKey(): string
{
    $k = strtolower(date('l'));
    $ok = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
    return in_array($k, $ok, true) ? $k : 'monday';
}

function buildMail(string $day, string $theme, string $name): array
{
    $days = [
        'monday' => ['Lundi', 'Demarrez la semaine avec un suivi clair de vos ventes.'],
        'tuesday' => ['Mardi', 'Evitez les ruptures grace au suivi de stock en temps reel.'],
        'wednesday' => ['Mercredi', 'Pilotez vos marges CDF et USD depuis un tableau unique.'],
        'thursday' => ['Jeudi', 'Controlez vendeurs, tickets et performances sans confusion.'],
        'friday' => ['Vendredi', 'Preparez vos rapports de fin de semaine en quelques clics.'],
        'saturday' => ['Samedi', 'Activez un mode vente rapide pour les jours de fort trafic.'],
        'sunday' => ['Dimanche', 'Planifiez votre prochaine semaine avec des donnees propres.'],
    ];
    $themes = [
        'conversion' => ['Conversion', 'Essayez Cartelplus Congo gratuitement pendant 7 jours.', 'Demarrer mon essai'],
        'social_proof' => ['Preuve sociale', 'Des equipes utilisent deja Cartelplus Congo pour mieux piloter ventes et stock.', 'Voir une demo'],
        'urgency' => ['Urgence', 'Chaque jour sans suivi fiable vous coute des opportunites de marge.', 'Reserver ma demo'],
    ];
    $d = $days[$day] ?? $days['monday'];
    $t = $themes[$theme] ?? $themes['conversion'];
    $n = htmlspecialchars(trim($name) !== '' ? $name : 'Cher client', ENT_QUOTES, 'UTF-8');
    $subject = '[' . $d[0] . '] ' . $t[0] . ' - Cartelplus Congo';
    $link = 'https://inve-app.cartelplus.site/pagesweb_cn/trial_form';
    $html = '<!doctype html><html><body style="font-family:Arial;background:#f5f7fb;padding:16px;">'
        . '<div style="max-width:620px;margin:0 auto;background:#fff;border:1px solid #e1e8f0;border-radius:12px;overflow:hidden;">'
        . '<div style="background:linear-gradient(135deg,#0070e0,#003087);color:#fff;padding:20px;"><h2 style="margin:0;">Cartelplus Congo</h2><div style="opacity:.9;">' . $d[0] . ' - ' . $t[0] . '</div></div>'
        . '<div style="padding:20px;color:#0b1f3a;"><p>Bonjour <strong>' . $n . '</strong>,</p><p>' . $d[1] . '</p><p>' . $t[1] . '</p>'
        . '<p style="background:#eef6ff;border-left:4px solid #0070e0;padding:10px;">Suivi ventes et marges en temps reel, multi-maisons, tickets propres et rapports rapides.</p>'
        . '<p><a href="' . $link . '" style="display:inline-block;background:#0070e0;color:#fff;text-decoration:none;padding:10px 16px;border-radius:8px;font-weight:700;">' . $t[2] . '</a></p>'
        . '<p style="font-size:12px;color:#6b7a90;">Support: support@cartelplus.cd</p></div></div></body></html>';
    $alt = 'Bonjour ' . strip_tags($n) . '. ' . $d[1] . ' ' . $t[1] . ' ' . $link;
    return ['subject' => $subject, 'html' => $html, 'alt' => $alt];
}

function isHourlyLimitError(string $message): bool
{
    $m = strtolower(trim($message));
    if ($m === '') return false;
    $needles = [
        'hourly sending limit',
        'hourly limit',
        'sending limit',
        'you have reached your account',
        "you've reached your hourly sending limit",
        'too many messages',
        'rate limit'
    ];
    foreach ($needles as $n) {
        if (str_contains($m, $n)) return true;
    }
    return false;
}

function normalizeTheme(string $theme): string
{
    $allowed = ['conversion', 'social_proof', 'urgency'];
    return in_array($theme, $allowed, true) ? $theme : 'conversion';
}

function normalizeScheduleDay(string $day): string
{
    $allowed = ['daily', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
    $day = strtolower(trim($day));
    return in_array($day, $allowed, true) ? $day : 'daily';
}

function scheduleMatchesToday(string $scheduleDay, string $today): bool
{
    $scheduleDay = normalizeScheduleDay($scheduleDay);
    return $scheduleDay === 'daily' || $scheduleDay === $today;
}

function sendEmailCampaign(
    PDO $pdo,
    array $emailRows,
    array $daysFr,
    string $dayReq,
    string $theme,
    int $limit,
    int $hourlySoftLimit,
    bool $sequenceModeActive = false,
    int $sequenceSlot = 0,
    int $sequenceBatchSize = 20
): array {
    $theme = normalizeTheme($theme);
    if ($limit < 1) $limit = 1;
    if ($limit > 10000) $limit = 10000;
    if ($hourlySoftLimit < 1) $hourlySoftLimit = 1;
    if ($hourlySoftLimit > 500) $hourlySoftLimit = 500;

    $todayDate = date('Y-m-d');
    $already = [];
    $st = $pdo->prepare("SELECT DISTINCT target_email FROM prospection_email_logs WHERE campaign_date = ? AND status = 'sent'");
    $st->execute([$todayDate]);
    foreach ($st->fetchAll(PDO::FETCH_COLUMN) as $e) {
        $already[strtolower((string)$e)] = true;
    }

    $qHour = $pdo->query("SELECT COUNT(DISTINCT target_email) FROM prospection_email_logs WHERE status='sent' AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)");
    $sentLastHour = (int)$qHour->fetchColumn();
    $availableHourly = max(0, $hourlySoftLimit - $sentLastHour);
    if ($availableHourly <= 0) {
        return [
            'ok' => 0,
            'ko' => 0,
            'sent_last_hour' => $sentLastHour,
            'effective_limit' => 0,
            'alert' => [
                'type' => 'warning',
                'msg' => 'Limite horaire locale atteinte (' . $sentLastHour . '/' . $hourlySoftLimit . '). Attendez 1 heure avant un nouvel envoi.'
            ],
        ];
    }

    $effectiveLimit = min($limit, $availableHourly);
    $queue = [];
    foreach ($emailRows as $c) {
        $e = strtolower((string)($c['email'] ?? ''));
        if ($e === '' || isset($already[$e])) continue;
        $queue[] = $c;
        if (count($queue) >= $effectiveLimit) break;
    }
    if (!$queue) {
        return [
            'ok' => 0,
            'ko' => 0,
            'sent_last_hour' => $sentLastHour,
            'effective_limit' => $effectiveLimit,
            'alert' => ['type' => 'warning', 'msg' => 'Aucun nouveau contact email a traiter aujourd hui.'],
        ];
    }

    @set_time_limit(300);
    $okN = 0;
    $koN = 0;
    $hourlyBlocked = false;
    $hourlyBlockMsg = '';
    $ins = $pdo->prepare("INSERT INTO prospection_email_logs (campaign_date,campaign_day,campaign_theme,target_name,target_email,subject_line,status,error_text,created_at) VALUES (?,?,?,?,?,?,?,?,NOW())");
    foreach ($queue as $c) {
        $mail = buildMail($dayReq, $theme, (string)($c['full'] ?? ''));
        $smtpErr = null;
        $ok = sendProspectionEmail((string)$c['email'], (string)($c['full'] ?? ''), (string)$mail['subject'], (string)$mail['html'], (string)$mail['alt'], $smtpErr);
        if ($ok) {
            $okN++;
            $ins->execute([$todayDate, $dayReq, $theme, (string)($c['full'] ?? ''), (string)$c['email'], (string)$mail['subject'], 'sent', null]);
        } else {
            $koN++;
            $errText = trim((string)$smtpErr);
            if ($errText === '') $errText = 'Erreur SMTP';
            $ins->execute([$todayDate, $dayReq, $theme, (string)($c['full'] ?? ''), (string)$c['email'], (string)$mail['subject'], 'failed', $errText]);
            if (isHourlyLimitError($errText)) {
                $hourlyBlocked = true;
                $hourlyBlockMsg = $errText;
                break;
            }
        }
    }

    if ($hourlyBlocked) {
        $alert = [
            'type' => 'warning',
            'msg' => 'Campagne interrompue: limite horaire SMTP detectee. Envoyes: ' . $okN . ', echecs: ' . $koN . '. Detail: ' . $hourlyBlockMsg
        ];
    } else {
        if ($sequenceModeActive) {
            $msg = 'Sequence ' . $sequenceSlot . ' (' . $sequenceBatchSize . '/clic): ' . $okN . ' envoye(s), ' . $koN . ' echec(s).';
        } else {
            $msg = 'Campagne ' . ($daysFr[$dayReq] ?? ucfirst($dayReq)) . ': ' . $okN . ' envoye(s), ' . $koN . ' echec(s).';
        }
        if ($effectiveLimit < $limit) {
            $msg .= ' Envoi limite a ' . $effectiveLimit . ' pour respecter le plafond horaire (' . $hourlySoftLimit . '/h).';
        }
        $alert = ['type' => $koN > 0 ? 'warning' : 'success', 'msg' => $msg];
    }

    return [
        'ok' => $okN,
        'ko' => $koN,
        'sent_last_hour' => $sentLastHour,
        'effective_limit' => $effectiveLimit,
        'alert' => $alert,
    ];
}

function ensureScheduleTable(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS prospection_email_schedules (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            label VARCHAR(120) NOT NULL DEFAULT '',
            day_key VARCHAR(20) NOT NULL DEFAULT 'daily',
            send_time TIME NOT NULL,
            campaign_theme VARCHAR(30) NOT NULL DEFAULT 'conversion',
            daily_limit INT NOT NULL DEFAULT 20,
            hourly_soft_limit INT NOT NULL DEFAULT 35,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            last_run_date DATE NULL,
            last_run_at DATETIME NULL,
            last_run_status VARCHAR(20) NULL,
            last_run_summary VARCHAR(255) NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_active_time (is_active, send_time),
            INDEX idx_last_run_date (last_run_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
}

function scheduleRows(PDO $pdo): array
{
    return $pdo->query("SELECT * FROM prospection_email_schedules ORDER BY is_active DESC, send_time ASC, id ASC")->fetchAll(PDO::FETCH_ASSOC);
}

function runDueSchedules(PDO $pdo, array $emailRows, array $daysFr, string $today): array
{
    $runs = [];
    $due = $pdo->query("
        SELECT * FROM prospection_email_schedules
        WHERE is_active = 1
          AND send_time <= CURTIME()
          AND (last_run_date IS NULL OR last_run_date < CURDATE())
        ORDER BY send_time ASC, id ASC
    ")->fetchAll(PDO::FETCH_ASSOC);

    if (!$due) {
        return ['count' => 0, 'runs' => []];
    }

    $upd = $pdo->prepare("
        UPDATE prospection_email_schedules
        SET last_run_date = CURDATE(),
            last_run_at = NOW(),
            last_run_status = ?,
            last_run_summary = ?
        WHERE id = ?
    ");

    foreach ($due as $s) {
        $dayKey = normalizeScheduleDay((string)($s['day_key'] ?? 'daily'));
        if (!scheduleMatchesToday($dayKey, $today)) {
            continue;
        }
        $theme = normalizeTheme((string)($s['campaign_theme'] ?? 'conversion'));
        $limit = max(1, min(10000, (int)($s['daily_limit'] ?? 20)));
        $hourly = max(1, min(500, (int)($s['hourly_soft_limit'] ?? 35)));
        $label = trim((string)($s['label'] ?? 'Programmation'));
        if ($label === '') $label = 'Programmation #' . (int)($s['id'] ?? 0);

        $result = sendEmailCampaign($pdo, $emailRows, $daysFr, $today, $theme, $limit, $hourly, false, 0, 20);
        $status = (string)($result['alert']['type'] ?? 'info');
        $summary = '[' . $label . '] ' . (string)($result['alert']['msg'] ?? '');
        $upd->execute([$status, mb_substr($summary, 0, 250), (int)$s['id']]);
        $runs[] = [
            'id' => (int)$s['id'],
            'label' => $label,
            'status' => $status,
            'summary' => $summary,
            'ok' => (int)($result['ok'] ?? 0),
            'ko' => (int)($result['ko'] ?? 0),
        ];
    }

    return ['count' => count($runs), 'runs' => $runs];
}

$daysFr = ['monday' => 'Lundi', 'tuesday' => 'Mardi', 'wednesday' => 'Mercredi', 'thursday' => 'Jeudi', 'friday' => 'Vendredi', 'saturday' => 'Samedi', 'sunday' => 'Dimanche'];
$today = dayKey();
$requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$theme = 'conversion';
$limit = 1000;
$hourlySoftLimit = 35;
$sequenceBatchSize = 20;
$sequenceModeActive = false;
$sequenceSlot = 0;
$alert = ['type' => '', 'msg' => ''];

function nh(string $label): string
{
    $label = trim($label);
    $asAscii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $label);
    if ($asAscii !== false && $asAscii !== '') {
        $label = $asAscii;
    }
    $label = strtolower($label);
    return preg_replace('/[^a-z0-9]+/', '', $label) ?? '';
}

function csvRows(string $path): array
{
    $fp = @fopen($path, 'r');
    if (!$fp) return [];
    $first = fgets($fp);
    if ($first === false) {
        fclose($fp);
        return [];
    }
    $delims = [';' => substr_count($first, ';'), ',' => substr_count($first, ','), "\t" => substr_count($first, "\t"), '|' => substr_count($first, '|')];
    arsort($delims);
    $delimiter = (string)array_key_first($delims);
    rewind($fp);
    $rows = [];
    while (($data = fgetcsv($fp, 0, $delimiter)) !== false) {
        if (!$data) continue;
        $data[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string)$data[0]) ?? (string)$data[0];
        if (count($data) === 1 && trim((string)$data[0]) === '') continue;
        $rows[] = $data;
    }
    fclose($fp);
    return $rows;
}

function colToIdx(string $letters): int
{
    $letters = strtoupper($letters);
    $idx = 0;
    for ($i = 0; $i < strlen($letters); $i++) {
        $idx = ($idx * 26) + (ord($letters[$i]) - 64);
    }
    return max(0, $idx - 1);
}

function xlsxRows(string $path): array
{
    if (!class_exists('ZipArchive')) return [];
    $zip = new ZipArchive();
    if ($zip->open($path) !== true) return [];

    $shared = [];
    $ssXml = $zip->getFromName('xl/sharedStrings.xml');
    if ($ssXml !== false) {
        $ss = @simplexml_load_string($ssXml);
        if ($ss && isset($ss->si)) {
            foreach ($ss->si as $si) {
                if (isset($si->t)) {
                    $shared[] = trim((string)$si->t);
                    continue;
                }
                $txt = '';
                if (isset($si->r)) {
                    foreach ($si->r as $run) {
                        $txt .= (string)$run->t;
                    }
                }
                $shared[] = trim($txt);
            }
        }
    }

    $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
    if ($sheetXml === false) {
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = (string)$zip->getNameIndex($i);
            if (str_starts_with($name, 'xl/worksheets/sheet') && str_ends_with($name, '.xml')) {
                $sheetXml = $zip->getFromName($name);
                break;
            }
        }
    }
    if ($sheetXml === false) {
        $zip->close();
        return [];
    }

    $sheet = @simplexml_load_string($sheetXml);
    $rows = [];
    if ($sheet && isset($sheet->sheetData->row)) {
        foreach ($sheet->sheetData->row as $row) {
            $cells = [];
            foreach ($row->c as $c) {
                $ref = (string)$c['r'];
                $letters = '';
                if (preg_match('/[A-Z]+/i', $ref, $mm)) $letters = (string)$mm[0];
                $idx = $letters !== '' ? colToIdx($letters) : count($cells);
                $type = (string)$c['t'];
                $val = '';
                if ($type === 's') {
                    $si = (int)((string)$c->v);
                    $val = $shared[$si] ?? '';
                } elseif ($type === 'inlineStr') {
                    $val = (string)$c->is->t;
                } else {
                    $val = (string)$c->v;
                }
                $cells[$idx] = trim((string)$val);
            }
            if ($cells) {
                ksort($cells);
                $rows[] = array_values($cells);
            }
        }
    }
    $zip->close();
    return $rows;
}

function parseImportRows(array $rows): array
{
    if (!$rows) return ['contacts' => [], 'invalid' => 0];

    $first = $rows[0];
    $map = [];
    $score = 0;
    foreach ($first as $idx => $cell) {
        $h = nh((string)$cell);
        if ($h === '') continue;
        if (str_contains($h, 'email') || str_contains($h, 'mail')) { $map[$idx] = 'email'; $score++; continue; }
        if ($h === 'nomcomplet' || $h === 'fullname' || $h === 'name') { $map[$idx] = 'full'; $score++; continue; }
        if ($h === 'nom' || str_contains($h, 'lastname')) { $map[$idx] = 'nom'; $score++; continue; }
        if (str_contains($h, 'prenom') || str_contains($h, 'firstname')) { $map[$idx] = 'prenom'; $score++; continue; }
        if (str_contains($h, 'telephone') || str_contains($h, 'phone') || $h === 'tel' || str_contains($h, 'numero') || str_contains($h, 'whatsapp')) { $map[$idx] = 'phone'; $score++; continue; }
        if (str_contains($h, 'qualite') || str_contains($h, 'profil') || str_contains($h, 'fonction') || str_contains($h, 'metier') || str_contains($h, 'occupation')) { $map[$idx] = 'qualite'; $score++; continue; }
    }
    $hasHeader = ($score > 0);
    if (!$hasHeader) {
        $map = [0 => 'full', 1 => 'email', 2 => 'phone', 3 => 'qualite'];
    }

    $start = $hasHeader ? 1 : 0;
    $invalid = 0;
    $contacts = [];
    $seen = [];

    for ($i = $start; $i < count($rows); $i++) {
        $row = $rows[$i];
        $nom = '';
        $prenom = '';
        $full = '';
        $emailRaw = '';
        $phoneRaw = '';
        $qualite = '';

        foreach ($map as $idx => $field) {
            $val = trim((string)($row[$idx] ?? ''));
            if ($field === 'nom') $nom = $val;
            elseif ($field === 'prenom') $prenom = $val;
            elseif ($field === 'full') $full = $val;
            elseif ($field === 'email') $emailRaw = $val;
            elseif ($field === 'phone') $phoneRaw = $val;
            elseif ($field === 'qualite') $qualite = $val;
        }

        if ($full === '') $full = trim($nom . ' ' . $prenom);
        $email = em($emailRaw);
        $phone = ph($phoneRaw);
        if ($email === '') {
            $invalid++;
            continue;
        }
        if ($full === '') {
            $local = strstr($email, '@', true);
            $full = $local !== false ? ucfirst($local) : 'Prospect';
        }
        $key = 'e:' . $email;
        if (isset($seen[$key])) continue;
        $seen[$key] = true;

        $contacts[] = [
            'id' => 0,
            'nom' => $nom,
            'prenom' => $prenom,
            'full' => $full,
            'qualite' => $qualite,
            'email' => $email,
            'phone' => $phone,
            'date' => date('Y-m-d H:i:s'),
            'key' => $key,
        ];
    }

    return ['contacts' => $contacts, 'invalid' => $invalid];
}

function ensureExtraTable(PDO $pdo): void
{
    $pdo->exec("CREATE TABLE IF NOT EXISTS prospection_extra_contacts (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        source_file VARCHAR(255) NOT NULL DEFAULT '',
        full_name VARCHAR(255) NOT NULL DEFAULT '',
        first_name VARCHAR(120) NULL,
        last_name VARCHAR(120) NULL,
        qualite VARCHAR(255) NULL,
        email VARCHAR(255) NULL,
        phone VARCHAR(40) NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uq_extra_email (email),
        UNIQUE KEY uq_extra_phone (phone),
        INDEX idx_extra_created_at (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function saveExtraContacts(PDO $pdo, array $contacts, string $source): array
{
    $added = 0;
    $updated = 0;
    $stmt = $pdo->prepare("
        INSERT INTO prospection_extra_contacts
        (source_file, full_name, first_name, last_name, qualite, email, phone, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ON DUPLICATE KEY UPDATE
            source_file = VALUES(source_file),
            full_name = IF(VALUES(full_name) <> '', VALUES(full_name), full_name),
            first_name = IF(VALUES(first_name) <> '', VALUES(first_name), first_name),
            last_name = IF(VALUES(last_name) <> '', VALUES(last_name), last_name),
            qualite = IF(VALUES(qualite) <> '', VALUES(qualite), qualite),
            phone = IF(VALUES(phone) IS NOT NULL AND VALUES(phone) <> '', VALUES(phone), phone),
            updated_at = NOW()
    ");

    foreach ($contacts as $c) {
        $stmt->execute([
            $source,
            (string)($c['full'] ?? ''),
            (string)($c['prenom'] ?? ''),
            (string)($c['nom'] ?? ''),
            (string)($c['qualite'] ?? ''),
            (string)($c['email'] ?? '') !== '' ? (string)$c['email'] : null,
            (string)($c['phone'] ?? '') !== '' ? (string)$c['phone'] : null,
        ]);
        $rc = $stmt->rowCount();
        if ($rc === 1) $added++;
        elseif ($rc >= 2) $updated++;
    }

    return ['added' => $added, 'updated' => $updated];
}

function extraContacts(PDO $pdo): array
{
    $rows = $pdo->query("SELECT id, full_name, first_name, last_name, qualite, email, phone, created_at FROM prospection_extra_contacts ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
    $out = [];
    foreach ($rows as $r) {
        $full = trim((string)($r['full_name'] ?? ''));
        if ($full === '') $full = trim((string)($r['last_name'] ?? '') . ' ' . (string)($r['first_name'] ?? ''));
        if ($full === '') $full = 'Prospect importe #' . (int)($r['id'] ?? 0);
        $email = em((string)($r['email'] ?? ''));
        $phone = ph((string)($r['phone'] ?? ''));
        $key = $email !== '' ? 'e:' . $email : ($phone !== '' ? 'p:' . $phone : 'n:' . strtolower(preg_replace('/\s+/', '', $full) ?? $full));
        $out[] = [
            'id' => (int)($r['id'] ?? 0),
            'nom' => (string)($r['last_name'] ?? ''),
            'prenom' => (string)($r['first_name'] ?? ''),
            'full' => $full,
            'qualite' => (string)($r['qualite'] ?? ''),
            'email' => $email,
            'phone' => $phone,
            'date' => (string)($r['created_at'] ?? ''),
            'key' => $key,
        ];
    }
    return $out;
}

function mergeContacts(array ...$lists): array
{
    $out = [];
    $seen = [];
    foreach ($lists as $list) {
        foreach ($list as $c) {
            $k = (string)($c['key'] ?? '');
            if ($k === '') $k = (string)($c['email'] ?? '');
            if ($k === '') continue;
            if (isset($seen[$k])) continue;
            $seen[$k] = true;
            $out[] = $c;
        }
    }
    return $out;
}

$parsed = parseAdhesion($adhesionSqlPath);
$rawCount = (int)$parsed['raw'];
$extraCount = 0;

try {
    ensureExtraTable($pdo);
} catch (Throwable $e) {
    $alert = ['type' => 'danger', 'msg' => 'Impossible de preparer la table des imports.'];
}

if ($requestMethod === 'POST' && ($_POST['action'] ?? '') === 'import_contacts') {
    $source = trim((string)($_POST['source_label'] ?? 'Import manuel'));
    if ($source === '') $source = 'Import manuel';
    $file = $_FILES['prospection_file'] ?? null;

    if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        $alert = ['type' => 'danger', 'msg' => 'Veuillez choisir un fichier Excel (.xlsx) ou CSV.'];
    } elseif (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        $alert = ['type' => 'danger', 'msg' => 'Le fichier n a pas pu etre charge correctement.'];
    } else {
        $ext = strtolower(pathinfo((string)$file['name'], PATHINFO_EXTENSION));
        $rows = [];
        if ($ext === 'csv') {
            $rows = csvRows((string)$file['tmp_name']);
        } elseif ($ext === 'xlsx') {
            $rows = xlsxRows((string)$file['tmp_name']);
        } else {
            $alert = ['type' => 'danger', 'msg' => 'Format non supporte. Utilisez .xlsx ou .csv'];
        }

        if ($alert['msg'] === '') {
            if (!$rows) {
                $alert = ['type' => 'warning', 'msg' => 'Aucune ligne lisible detectee dans le fichier.'];
            } else {
                $parsedImport = parseImportRows($rows);
                $toSave = $parsedImport['contacts'];
                $invalid = (int)$parsedImport['invalid'];
                if (!$toSave) {
                    $alert = ['type' => 'warning', 'msg' => 'Aucun email valide trouve dans le fichier.'];
                } else {
                    $saved = saveExtraContacts($pdo, $toSave, $source . ' (' . (string)$file['name'] . ')');
                    $alert = [
                        'type' => 'success',
                        'msg' => 'Import termine: ' . count($toSave) . ' email(s) valides, ' . $saved['added'] . ' ajoute(s), ' . $saved['updated'] . ' mis a jour, ' . $invalid . ' ligne(s) ignoree(s).',
                    ];
                }
            }
        }
    }
}

$extra = [];
try {
    $extra = extraContacts($pdo);
} catch (Throwable $e) {
    if ($alert['msg'] === '') $alert = ['type' => 'warning', 'msg' => 'Lecture des contacts importes indisponible.'];
}
$extraCount = count($extra);

$priorityEmail = 'lwekajonathan@gmail.com';
$priorityContact = [
    'id' => 0,
    'nom' => 'Lweka',
    'prenom' => 'Jonathan',
    'full' => 'Jonathan Lweka',
    'qualite' => 'Pilotage prospection',
    'email' => $priorityEmail,
    'phone' => '',
    'date' => date('Y-m-d H:i:s'),
    'key' => 'e:' . strtolower($priorityEmail),
];

$contacts = mergeContacts([$priorityContact], $parsed['rows'], $extra);
$seg = buildSeg($contacts);
$emailSplit = splitSendableEmailRows($seg['email_marketing']['rows'] ?? []);
$seg['email_marketing']['rows'] = $emailSplit['valid'];
$invalidEmailRows = $emailSplit['invalid'];
$invalidEmailCount = count($invalidEmailRows);
$emailReachableCount = count($seg['email_marketing']['rows'] ?? []);

$activeModelKey = '';
if (isset($_GET['model'], $seg[(string)$_GET['model']])) {
    $activeModelKey = (string)$_GET['model'];
}
$isModelView = ($activeModelKey !== '');
$defaultPreviewSegment = $isModelView ? $activeModelKey : 'email_marketing';
$previewSegment = isset($_GET['preview_segment'], $seg[(string)$_GET['preview_segment']]) ? (string)$_GET['preview_segment'] : $defaultPreviewSegment;
if ($isModelView) {
    $previewSegment = $activeModelKey;
}
$previewDay = isset($_GET['preview_day'], $daysFr[(string)$_GET['preview_day']]) ? (string)$_GET['preview_day'] : $today;
$previewTheme = normalizeTheme((string)($_GET['preview_theme'] ?? $theme));
$previewRows = $seg[$previewSegment]['rows'] ?? [];
$previewMail = null;
if ($previewSegment === 'email_marketing') {
    $previewMail = buildMail($previewDay, $previewTheme, 'Prospect Apercu');
}
$segmentsToDisplay = $isModelView ? [$activeModelKey => $seg[$activeModelKey]] : $seg;
$isEmailModelView = $isModelView && $activeModelKey === 'email_marketing';
$prospectionManagerUrl = BASE_URL . 'pagesweb_cn/prospection_manager';

$scheduleRuns = ['count' => 0, 'runs' => []];
$schedules = [];

try { ensureLogTable($pdo); } catch (Throwable $e) { if ($alert['msg'] === '') $alert = ['type' => 'danger', 'msg' => 'Erreur table log prospection.']; }
try { ensureScheduleTable($pdo); } catch (Throwable $e) { if ($alert['msg'] === '') $alert = ['type' => 'danger', 'msg' => 'Erreur table programmation automatique.']; }

if ($requestMethod === 'POST' && ($_POST['action'] ?? '') === 'add_schedule') {
    $label = trim((string)($_POST['schedule_label'] ?? ''));
    if ($label === '') $label = 'Programmation auto';
    $dayKey = normalizeScheduleDay((string)($_POST['schedule_day'] ?? 'daily'));
    $sendTime = trim((string)($_POST['schedule_time'] ?? ''));
    if (!preg_match('/^\d{2}:\d{2}$/', $sendTime)) {
        $alert = ['type' => 'danger', 'msg' => 'Heure de programmation invalide. Format attendu: HH:MM.'];
    } else {
        $sendTime = $sendTime . ':00';
        $sTheme = normalizeTheme((string)($_POST['schedule_theme'] ?? 'conversion'));
        $sLimit = max(1, min(10000, (int)($_POST['schedule_limit'] ?? 20)));
        $sHourly = max(1, min(500, (int)($_POST['schedule_hourly'] ?? 35)));
        $insSc = $pdo->prepare("INSERT INTO prospection_email_schedules (label, day_key, send_time, campaign_theme, daily_limit, hourly_soft_limit, is_active, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, 1, NOW(), NOW())");
        $insSc->execute([$label, $dayKey, $sendTime, $sTheme, $sLimit, $sHourly]);
        $alert = ['type' => 'success', 'msg' => 'Programmation ajoutee: ' . $label . ' (' . $dayKey . ' a ' . $sendTime . ').'];
    }
}

if ($requestMethod === 'POST' && ($_POST['action'] ?? '') === 'toggle_schedule') {
    $id = (int)($_POST['schedule_id'] ?? 0);
    if ($id > 0) {
        $pdo->prepare("UPDATE prospection_email_schedules SET is_active = IF(is_active=1,0,1), updated_at = NOW() WHERE id = ?")->execute([$id]);
        $alert = ['type' => 'success', 'msg' => 'Statut de la programmation mis a jour.'];
    }
}

if ($requestMethod === 'POST' && ($_POST['action'] ?? '') === 'delete_schedule') {
    $id = (int)($_POST['schedule_id'] ?? 0);
    if ($id > 0) {
        $pdo->prepare("DELETE FROM prospection_email_schedules WHERE id = ?")->execute([$id]);
        $alert = ['type' => 'success', 'msg' => 'Programmation supprimee.'];
    }
}

if ($requestMethod === 'POST' && ($_POST['action'] ?? '') === 'run_due_schedules') {
    $scheduleRuns = runDueSchedules($pdo, $seg['email_marketing']['rows'] ?? [], $daysFr, $today);
    if ($scheduleRuns['count'] > 0) {
        $alert = ['type' => 'success', 'msg' => 'Executions automatiques lancees: ' . $scheduleRuns['count'] . '.'];
    } else {
        $alert = ['type' => 'info', 'msg' => 'Aucune programmation due pour le moment.'];
    }
}

if ($runnerMode) {
    $scheduleRuns = runDueSchedules($pdo, $seg['email_marketing']['rows'] ?? [], $daysFr, $today);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'ok' => true,
        'runner' => true,
        'executed_count' => (int)($scheduleRuns['count'] ?? 0),
        'runs' => $scheduleRuns['runs'] ?? [],
        'run_at' => date('Y-m-d H:i:s'),
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if (isset($_GET['export_invalid']) && (string)$_GET['export_invalid'] === 'email_marketing') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="prospection_invalid_email_' . date('Ymd_His') . '.csv"');
    $out = fopen('php://output', 'w');
    if ($out !== false) {
        fputcsv($out, ['Nom complet', 'Email', 'Raison exclusion']);
        foreach ($invalidEmailRows as $c) {
            fputcsv($out, [
                (string)($c['full'] ?? ''),
                (string)($c['email'] ?? ''),
                (string)($c['validation_reason'] ?? 'Adresse exclue'),
            ]);
        }
        fclose($out);
    }
    exit;
}

if (isset($_GET['export']) && isset($seg[(string)$_GET['export']])) {
    $k = (string)$_GET['export'];
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="prospection_' . $k . '_' . date('Ymd_His') . '.csv"');
    $out = fopen('php://output', 'w');
    if ($out !== false) {
        fputcsv($out, ['Nom complet', 'Nom', 'Prenom', 'Qualite', 'Email', 'Telephone', 'Date inscription']);
        foreach ($seg[$k]['rows'] as $c) fputcsv($out, [$c['full'], $c['nom'], $c['prenom'], $c['qualite'], $c['email'], $c['phone'], $c['date']]);
        fclose($out);
    }
    exit;
}

if ($requestMethod === 'POST' && ($_POST['action'] ?? '') === 'send_daily_email') {
    $theme = (string)($_POST['campaign_theme'] ?? 'conversion');
    $theme = normalizeTheme($theme);
    $limit = (int)($_POST['daily_limit'] ?? 1000);
    if ($limit < 1) $limit = 1;
    if ($limit > 10000) $limit = 10000;
    $sequenceModeActive = isset($_POST['sequence_mode']) && trim((string)$_POST['sequence_mode']) !== '';
    if ($sequenceModeActive) {
        $sequenceSlot = (int)$_POST['sequence_mode'];
        if ($sequenceSlot < 1) $sequenceSlot = 1;
        if ($sequenceSlot > 50) $sequenceSlot = 50;
        $limit = $sequenceBatchSize;
    }
    $hourlySoftLimit = (int)($_POST['hourly_soft_limit'] ?? $hourlySoftLimit);
    if ($hourlySoftLimit < 1) $hourlySoftLimit = 1;
    if ($hourlySoftLimit > 500) $hourlySoftLimit = 500;
    $dayReq = (string)($_POST['day_key'] ?? '');
    if ($dayReq === '' && $sequenceModeActive) {
        $dayReq = $today;
    }
    if (!isset($daysFr[$dayReq])) {
        $alert = ['type' => 'danger', 'msg' => 'Jour invalide.'];
    } elseif ($dayReq !== $today) {
        $alert = ['type' => 'danger', 'msg' => 'Seul le bouton du jour est autorise (1 mail/jour/contact).'];
    } else {
        $result = sendEmailCampaign(
            $pdo,
            $seg['email_marketing']['rows'] ?? [],
            $daysFr,
            $dayReq,
            $theme,
            $limit,
            $hourlySoftLimit,
            $sequenceModeActive,
            $sequenceSlot,
            $sequenceBatchSize
        );
        $alert = $result['alert'] ?? ['type' => 'info', 'msg' => 'Execution terminee.'];
    }
}

try {
    $schedules = scheduleRows($pdo);
} catch (Throwable $e) {
    if ($alert['msg'] === '') {
        $alert = ['type' => 'warning', 'msg' => 'Lecture des programmations indisponible.'];
    }
    $schedules = [];
}

$goal = 100;
$sent21 = 0; $sentToday = 0; $sentLastHour = 0; $logs = [];
try {
    $sent21 = (int)$pdo->query("SELECT COUNT(DISTINCT target_email) FROM prospection_email_logs WHERE status='sent' AND campaign_date >= DATE_SUB(CURDATE(), INTERVAL 21 DAY)")->fetchColumn();
    $q = $pdo->prepare("SELECT COUNT(DISTINCT target_email) FROM prospection_email_logs WHERE status='sent' AND campaign_date = CURDATE()");
    $q->execute(); $sentToday = (int)$q->fetchColumn();
    $sentLastHour = (int)$pdo->query("SELECT COUNT(DISTINCT target_email) FROM prospection_email_logs WHERE status='sent' AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)")->fetchColumn();
    $logs = $pdo->query("SELECT campaign_day,campaign_theme,target_name,target_email,status,created_at FROM prospection_email_logs ORDER BY id DESC LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {}
$pct = $goal > 0 ? min(100, (int)round(($sent21 / $goal) * 100)) : 0;
$nextSequence = (int)floor($sentToday / max(1, $sequenceBatchSize)) + 1;

$w0 = new DateTimeImmutable('monday this week');
$wk = []; $ord = ['monday','tuesday','wednesday','thursday','friday','saturday','sunday'];
foreach ($ord as $i => $k) $wk[$k] = ['l' => $daysFr[$k], 'd' => $w0->modify('+' . $i . ' day')->format('d/m')];
$scheduleDayLabels = array_merge(['daily' => 'Tous les jours'], $daysFr);
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Prospections | Cartelplus Congo</title>
  <link href="../css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
  <style>
    body { background:#f4f8fc; font-family:Segoe UI,Arial,sans-serif; color:#0b1f3a; padding:16px; } .wrap{max-width:1280px;margin:auto}
    .head{background:linear-gradient(135deg,#0070e0,#003087);color:#fff;border-radius:14px;padding:18px;display:flex;justify-content:space-between;flex-wrap:wrap;gap:10px}
    .hbtn{border:1px solid rgba(255,255,255,.35);background:rgba(255,255,255,.15);color:#fff;text-decoration:none;padding:9px 12px;border-radius:9px;font-weight:600}
    .grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px;margin-top:12px}
    .cardx{background:#fff;border:1px solid #deebf8;border-radius:12px;padding:14px}
    .v{font-size:28px;font-weight:700}.m{font-size:13px;color:#5d6d86}
    .seg{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:12px}
    .shead{display:flex;justify-content:space-between;gap:8px}.sc{font-size:22px;font-weight:700;color:#003087}
    .sdesc{font-size:13px;color:#5d6d86;min-height:34px}.prev{font-size:12px;background:#f7faff;border:1px solid #e5eef9;border-radius:8px;padding:8px;margin-top:8px;min-height:64px}
    .act{display:flex;gap:7px;flex-wrap:wrap;margin-top:8px}.btn-sm2{font-size:12px;border:1px solid #d4e1ef;background:#fff;padding:6px 9px;border-radius:8px;font-weight:600}
    .day{display:grid;grid-template-columns:repeat(auto-fit,minmax(145px,1fr));gap:8px}.day button{border:1px solid #cfe0f5;background:#fff;padding:9px;border-radius:9px;font-weight:700}
    .day .on{background:#eef6ff;border-color:#0a67ca}.tb{overflow:auto}.tb table{width:100%;min-width:760px;border-collapse:collapse;font-size:13px}.tb th,.tb td{padding:8px;border-bottom:1px solid #ecf2fa}
    .preview-grid{display:grid;grid-template-columns:1.15fr .85fr;gap:12px}
    .preview-box{background:#f8fbff;border:1px solid #dce8f7;border-radius:10px;padding:10px}
    .preview-box iframe{width:100%;height:420px;border:1px solid #d6e3f5;border-radius:8px;background:#fff}
    .mini-note{font-size:12px;color:#5f6e86}
    .recipient-search{max-width:360px}
    .sched-actions{display:flex;gap:8px;flex-wrap:wrap}
    @media (max-width: 992px){.preview-grid{grid-template-columns:1fr}}
  </style>
</head>
<body>
<div class="wrap">
  <div class="head">
    <div><h3 style="margin:0;"><i class="fa-solid fa-bullhorn"></i> Prospections commerciales</h3><div style="opacity:.9;font-size:13px;">Objectif: 100 utilisateurs en 3 semaines</div></div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
      <?php if ($isModelView): ?>
        <a class="hbtn" href="<?= htmlspecialchars($prospectionManagerUrl) ?>"><i class="fa-solid fa-arrow-left"></i> Retour modeles</a>
      <?php endif; ?>
      <a class="hbtn" href="<?= BASE_URL ?>pagesweb_cn/admin_subscription_manager">Retour abonnements</a>
      <a class="hbtn" href="<?= BASE_URL ?>pagesweb_cn/logout.php">Deconnexion</a>
    </div>
  </div>

  <div class="grid">
    <div class="cardx"><div class="m">Contacts bruts (adhesion.sql)</div><div class="v"><?= number_format($rawCount) ?></div></div>
    <div class="cardx"><div class="m">Contacts importes (Excel/CSV)</div><div class="v"><?= number_format($extraCount) ?></div></div>
    <div class="cardx"><div class="m">Contacts uniques</div><div class="v"><?= number_format(count($contacts)) ?></div></div>
    <div class="cardx"><div class="m">Emails valides (lot envoi)</div><div class="v"><?= number_format($emailReachableCount) ?></div></div>
    <div class="cardx"><div class="m">Emails exclus (non recevables)</div><div class="v"><?= number_format($invalidEmailCount) ?></div></div>
    <div class="cardx"><div class="m">Emails envoyes aujourd hui</div><div class="v"><?= number_format($sentToday) ?></div></div>
    <div class="cardx"><div class="m">Emails envoyes derniere heure</div><div class="v"><?= number_format($sentLastHour) ?></div></div>
    <div class="cardx"><div class="m">Progression 21 jours</div><div class="v"><?= number_format($sent21) ?>/<?= number_format($goal) ?></div><div class="progress mt-2"><div class="progress-bar" style="width:<?= (int)$pct ?>%"></div></div></div>
  </div>

  <?php if ($alert['msg'] !== ''): ?><div class="alert alert-<?= htmlspecialchars($alert['type'] !== '' ? $alert['type'] : 'info') ?> mt-3"><?= htmlspecialchars($alert['msg']) ?></div><?php endif; ?>
  <?php if ($invalidEmailCount > 0): ?><div class="alert alert-warning mt-3">Filtrage qualite actif: <?= number_format($invalidEmailCount) ?> adresse(s) email exclue(s) automatiquement (domaine sans service mail/MX).</div><?php endif; ?>

  <?php if ($isModelView): ?>
    <div class="cardx mt-3">
      <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
          <strong><i class="<?= htmlspecialchars((string)($seg[$activeModelKey]['icon'] ?? 'fa-solid fa-bullhorn')) ?>"></i> Espace modele: <?= htmlspecialchars((string)($seg[$activeModelKey]['title'] ?? $activeModelKey)) ?></strong>
          <div class="mini-note"><?= htmlspecialchars((string)($seg[$activeModelKey]['desc'] ?? '')) ?></div>
        </div>
        <a class="btn btn-outline-primary btn-sm" href="<?= htmlspecialchars($prospectionManagerUrl) ?>"><i class="fa-solid fa-arrow-left"></i> Retour a l accueil principal</a>
      </div>
    </div>
  <?php endif; ?>

  <?php if (!$isModelView): ?>
    <h5 class="mt-3 mb-2"><i class="fa-solid fa-file-import"></i> Ajouter des emails depuis Excel/CSV</h5>
    <div class="cardx">
      <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="import_contacts">
        <div class="row g-2">
          <div class="col-md-5">
            <label class="form-label">Fichier contacts</label>
            <input class="form-control" type="file" name="prospection_file" accept=".xlsx,.csv" required>
            <div class="form-text">Formats supportes: Excel `.xlsx` et CSV. Colonnes reconnues: nom, prenom, email, telephone, qualite.</div>
          </div>
          <div class="col-md-4">
            <label class="form-label">Source import</label>
            <input class="form-control" type="text" name="source_label" placeholder="Ex: Campagne universite semaine 1">
          </div>
          <div class="col-md-3 d-flex align-items-end">
            <button type="submit" class="btn btn-primary w-100"><i class="fa-solid fa-upload"></i> Importer et fusionner</button>
          </div>
        </div>
      </form>
      <div class="form-text mt-2">Les emails importes sont ajoutes directement au lot de prospection et pris en compte dans les envois automatiques du jour.</div>
    </div>
  <?php endif; ?>

  <h5 class="mt-3 mb-2"><i class="fa-solid fa-layer-group"></i> <?= $isModelView ? 'Modele de prospection actif' : '9 systemes de prospection' ?></h5>
  <div class="seg">
    <?php foreach ($segmentsToDisplay as $k => $s): $pv = array_slice($s['rows'], 0, 4); ?>
      <div class="cardx">
        <div class="shead"><div><strong><i class="<?= htmlspecialchars($s['icon']) ?>"></i> <?= htmlspecialchars($s['title']) ?></strong><div class="sdesc"><?= htmlspecialchars($s['desc']) ?></div></div><div class="sc"><?= number_format(count($s['rows'])) ?></div></div>
        <div class="prev"><?php if ($pv) { foreach ($pv as $i => $c) echo htmlspecialchars($c['full']) . ($i < count($pv) - 1 ? ' | ' : ''); } else { echo 'Aucun contact'; } ?></div>
        <div class="act">
          <a class="btn-sm2" href="?export=<?= urlencode($k) ?>">Export CSV</a>
          <?php if ($isModelView): ?>
            <button type="button" class="btn-sm2 cp" data-t="<?= htmlspecialchars($s['tpl']) ?>">Copier modele</button>
            <a class="btn-sm2" href="?model=<?= urlencode($k) ?>&preview_segment=<?= urlencode($k) ?>&preview_day=<?= urlencode($previewDay) ?>&preview_theme=<?= urlencode($previewTheme) ?>#preview-zone">Apercu</a>
            <?php if ($k === 'email_marketing'): ?><a class="btn-sm2" href="#campagne">Aller aux envois</a><?php endif; ?>
            <?php if ($k === 'email_marketing' && $invalidEmailCount > 0): ?><a class="btn-sm2" href="?export_invalid=email_marketing">Export emails exclus</a><?php endif; ?>
          <?php else: ?>
            <a class="btn-sm2" href="?model=<?= urlencode($k) ?>&preview_segment=<?= urlencode($k) ?>#preview-zone">Ouvrir espace</a>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <?php if ($isModelView): ?>
  <h5 id="preview-zone" class="mt-3 mb-2"><i class="fa-solid fa-eye"></i> Apercu + liste des destinataires</h5>
  <div class="cardx">
    <form method="GET" action="<?= htmlspecialchars($prospectionManagerUrl) ?>" class="row g-2 align-items-end">
      <input type="hidden" name="model" value="<?= htmlspecialchars($activeModelKey) ?>">
      <input type="hidden" name="preview_segment" value="<?= htmlspecialchars($activeModelKey) ?>">
      <div class="col-md-4">
        <label class="form-label">Modele actif</label>
        <input type="text" class="form-control" value="<?= htmlspecialchars((string)($seg[$activeModelKey]['title'] ?? $activeModelKey)) ?>" disabled>
      </div>
      <div class="col-md-3">
        <label class="form-label">Jour (apercu email)</label>
        <select class="form-select" name="preview_day" <?= $isEmailModelView ? '' : 'disabled' ?>>
          <?php foreach ($daysFr as $k => $lab): ?>
            <option value="<?= htmlspecialchars($k) ?>" <?= $previewDay === $k ? 'selected' : '' ?>><?= htmlspecialchars($lab) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label">Theme (apercu email)</label>
        <select class="form-select" name="preview_theme" <?= $isEmailModelView ? '' : 'disabled' ?>>
          <option value="conversion" <?= $previewTheme === 'conversion' ? 'selected' : '' ?>>Offre conversion</option>
          <option value="social_proof" <?= $previewTheme === 'social_proof' ? 'selected' : '' ?>>Preuve sociale</option>
          <option value="urgency" <?= $previewTheme === 'urgency' ? 'selected' : '' ?>>Urgence commerciale</option>
        </select>
      </div>
      <div class="col-md-2">
        <button class="btn btn-outline-primary w-100" type="submit">Actualiser</button>
      </div>
    </form>
    <div class="preview-grid mt-3">
      <div class="preview-box">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <strong>Message affiche au prospect</strong>
          <span class="mini-note"><?= htmlspecialchars($seg[$previewSegment]['title'] ?? $previewSegment) ?></span>
        </div>
        <?php if ($previewSegment === 'email_marketing' && $previewMail): ?>
          <div class="mini-note mb-2"><strong>Objet:</strong> <?= htmlspecialchars((string)$previewMail['subject']) ?></div>
          <iframe srcdoc="<?= htmlspecialchars((string)$previewMail['html']) ?>"></iframe>
        <?php else: ?>
          <div class="mini-note mb-2">Apercu textuel du script/canal selectionne.</div>
          <pre class="mb-0" style="white-space:pre-wrap;word-break:break-word;background:#fff;border:1px solid #d6e3f5;border-radius:8px;padding:12px;min-height:180px;"><?= htmlspecialchars((string)($seg[$previewSegment]['tpl'] ?? 'Aucun modele.')) ?></pre>
        <?php endif; ?>
      </div>
      <div class="preview-box">
        <?php $emailOnlyView = ($previewSegment === 'email_marketing'); ?>
        <div class="d-flex justify-content-between align-items-center mb-2">
          <strong>Destinataires de ce message</strong>
          <span class="mini-note"><?= number_format(count($previewRows)) ?> contact(s)</span>
        </div>
        <input type="text" id="recipientFilter" class="form-control form-control-sm recipient-search mb-2" placeholder="<?= $emailOnlyView ? 'Filtrer numero, email, nom' : 'Filtrer nom, email, telephone' ?>">
        <div class="tb" style="max-height:460px;">
          <table>
            <thead>
              <tr>
                <th>N°</th>
                <?php if ($emailOnlyView): ?>
                  <th>Email</th>
                  <th>Nom</th>
                <?php else: ?>
                  <th>Nom</th>
                  <th>Email</th>
                  <th>Telephone</th>
                  <th>Qualite</th>
                <?php endif; ?>
              </tr>
            </thead>
            <tbody id="recipientTbody">
              <?php if (!$previewRows): ?>
                <tr><td colspan="<?= $emailOnlyView ? '3' : '5' ?>" class="text-muted">Aucun contact pour ce type de prospection.</td></tr>
              <?php else: ?>
                <?php foreach ($previewRows as $i => $c): ?>
                  <?php $searchRow = strtolower((string)($c['full'] ?? '') . ' ' . (string)($c['email'] ?? '') . ' ' . (string)($c['phone'] ?? '') . ' ' . (string)($c['qualite'] ?? '')); ?>
                  <tr class="recipient-row" data-search="<?= htmlspecialchars($searchRow) ?>">
                    <td><?= (int)$i + 1 ?></td>
                    <?php if ($emailOnlyView): ?>
                      <td><?= htmlspecialchars((string)($c['email'] ?? '-')) ?></td>
                      <td><?= htmlspecialchars((string)($c['full'] ?? '')) ?></td>
                    <?php else: ?>
                      <td><?= htmlspecialchars((string)($c['full'] ?? '')) ?></td>
                      <td><?= htmlspecialchars((string)($c['email'] ?? '-')) ?></td>
                      <td><?= htmlspecialchars((string)($c['phone'] ?? '-')) ?></td>
                      <td><?= htmlspecialchars((string)($c['qualite'] ?? '-')) ?></td>
                    <?php endif; ?>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
  <?php if ($isEmailModelView && $invalidEmailCount > 0): ?>
    <div class="cardx mt-3">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <strong>Adresses exclues automatiquement</strong>
        <span class="mini-note"><?= number_format($invalidEmailCount) ?> adresse(s)</span>
      </div>
      <div class="form-text">Ces adresses ne seront pas envoyees: domaine sans service mail (MX) ou format invalide.</div>
      <div class="tb mt-2" style="max-height:280px;">
        <table>
          <thead>
            <tr>
              <th>N&deg;</th>
              <th>Email</th>
              <th>Nom</th>
              <th>Raison</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach (array_slice($invalidEmailRows, 0, 200) as $idx => $bad): ?>
              <tr>
                <td><?= (int)$idx + 1 ?></td>
                <td><?= htmlspecialchars((string)($bad['email'] ?? '')) ?></td>
                <td><?= htmlspecialchars((string)($bad['full'] ?? '')) ?></td>
                <td><?= htmlspecialchars((string)($bad['validation_reason'] ?? 'Adresse exclue')) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php if ($invalidEmailCount > 200): ?><div class="mini-note mt-2">Affichage limite aux 200 premieres adresses. Utilisez "Export emails exclus" pour la liste complete.</div><?php endif; ?>
    </div>
  <?php endif; ?>
  <?php endif; ?>

  <?php if ($isEmailModelView): ?>
  <h5 id="campagne" class="mt-3 mb-2"><i class="fa-solid fa-envelope-open-text"></i> Campagne email automatique</h5>
  <div class="cardx">
    <form method="POST">
      <input type="hidden" name="action" value="send_daily_email">
      <div class="row g-2">
        <div class="col-md-6">
          <label class="form-label">Type de mail commercial</label>
          <select class="form-select" name="campaign_theme">
            <option value="conversion" <?= $theme === 'conversion' ? 'selected' : '' ?>>Offre conversion</option>
            <option value="social_proof" <?= $theme === 'social_proof' ? 'selected' : '' ?>>Preuve sociale</option>
            <option value="urgency" <?= $theme === 'urgency' ? 'selected' : '' ?>>Urgence commerciale</option>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">Volume du jour</label>
          <input class="form-control" type="number" min="1" max="10000" name="daily_limit" value="<?= (int)$limit ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label">Plafond horaire (soft)</label>
          <input class="form-control" type="number" min="1" max="500" name="hourly_soft_limit" value="<?= (int)$hourlySoftLimit ?>">
        </div>
      </div>
      <div class="form-text mt-2">Frequence forcee: 1 email maximum par jour et par contact. Le systeme bloque aussi au plafond horaire pour eviter l'erreur Titan.</div>
      <div class="form-text">Adresse prioritaire en tete de lot: <?= htmlspecialchars($priorityEmail) ?>.</div>
      <div class="day mt-2">
        <?php foreach ($wk as $k => $b): $on = ($k === $today); ?>
          <button type="submit" name="day_key" value="<?= htmlspecialchars($k) ?>" class="<?= $on ? 'on' : '' ?>" <?= $on ? '' : 'disabled' ?>><?= htmlspecialchars($b['l']) ?><br><small><?= htmlspecialchars($b['d']) ?></small></button>
        <?php endforeach; ?>
      </div>

      <div class="form-text mt-3">Mode sequence: 20 emails par bouton (envoi progressif sans doublons sur la journee).</div>
      <div class="d-flex flex-wrap gap-2 mt-2">
        <?php for ($i = 1; $i <= 10; $i++): ?>
          <button
            type="submit"
            name="sequence_mode"
            value="<?= $i ?>"
            class="btn btn-outline-primary btn-sm"
            title="Envoie la prochaine tranche de <?= (int)$sequenceBatchSize ?> emails"
          >
            Sequence <?= $i ?> (<?= (int)$sequenceBatchSize ?>)
          </button>
        <?php endfor; ?>
      </div>
      <div class="form-text mt-2">
        Prochaine sequence recommandee: <?= (int)$nextSequence ?>.
      </div>
    </form>
  </div>

  <h5 class="mt-3 mb-2"><i class="fa-solid fa-calendar-check"></i> Programmation automatique des emails</h5>
  <div class="cardx">
    <form method="POST" class="row g-2 align-items-end">
      <input type="hidden" name="action" value="add_schedule">
      <div class="col-md-3">
        <label class="form-label">Libelle</label>
        <input class="form-control" type="text" name="schedule_label" placeholder="Ex: Relance matin" required>
      </div>
      <div class="col-md-2">
        <label class="form-label">Jour</label>
        <select class="form-select" name="schedule_day">
          <?php foreach ($scheduleDayLabels as $k => $lab): ?>
            <option value="<?= htmlspecialchars($k) ?>"><?= htmlspecialchars($lab) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label">Heure</label>
        <input class="form-control" type="time" name="schedule_time" required>
      </div>
      <div class="col-md-2">
        <label class="form-label">Theme</label>
        <select class="form-select" name="schedule_theme">
          <option value="conversion">Offre conversion</option>
          <option value="social_proof">Preuve sociale</option>
          <option value="urgency">Urgence commerciale</option>
        </select>
      </div>
      <div class="col-md-1">
        <label class="form-label">Volume</label>
        <input class="form-control" type="number" min="1" max="10000" name="schedule_limit" value="20">
      </div>
      <div class="col-md-1">
        <label class="form-label">/h</label>
        <input class="form-control" type="number" min="1" max="500" name="schedule_hourly" value="35">
      </div>
      <div class="col-md-1">
        <button class="btn btn-primary w-100" type="submit"><i class="fa-solid fa-plus"></i></button>
      </div>
    </form>

    <div class="sched-actions mt-3">
      <form method="POST">
        <input type="hidden" name="action" value="run_due_schedules">
        <button type="submit" class="btn btn-outline-primary btn-sm"><i class="fa-solid fa-play"></i> Executer les programmations dues</button>
      </form>
      <span class="mini-note">Astuce: pour l execution sans presence, configurez un cron vers <code>pagesweb_cn/prospection_scheduler_runner.php?token=VOTRE_TOKEN</code> toutes les 5 minutes (token = variable serveur <code>PROSPECTION_RUNNER_TOKEN</code>).</span>
    </div>

    <?php if (($scheduleRuns['count'] ?? 0) > 0): ?>
      <div class="alert alert-info mt-3 mb-2">
        Derniere execution: <?= (int)$scheduleRuns['count'] ?> programmation(s) traitee(s).
      </div>
      <ul class="mb-3">
        <?php foreach (($scheduleRuns['runs'] ?? []) as $r): ?>
          <li><?= htmlspecialchars((string)$r['label']) ?>: <?= htmlspecialchars((string)$r['summary']) ?></li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>

    <div class="tb mt-2">
      <?php if (!$schedules): ?>
        <div class="text-muted">Aucune programmation enregistree.</div>
      <?php else: ?>
        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>Libelle</th>
              <th>Jour</th>
              <th>Heure</th>
              <th>Theme</th>
              <th>Volume</th>
              <th>Plafond/h</th>
              <th>Statut</th>
              <th>Derniere execution</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($schedules as $s): ?>
              <tr>
                <td><?= (int)($s['id'] ?? 0) ?></td>
                <td><?= htmlspecialchars((string)($s['label'] ?? '')) ?></td>
                <td><?= htmlspecialchars((string)($scheduleDayLabels[(string)($s['day_key'] ?? 'daily')] ?? (string)($s['day_key'] ?? 'daily'))) ?></td>
                <td><?= htmlspecialchars(substr((string)($s['send_time'] ?? ''), 0, 5)) ?></td>
                <td><?= htmlspecialchars((string)($s['campaign_theme'] ?? '')) ?></td>
                <td><?= (int)($s['daily_limit'] ?? 0) ?></td>
                <td><?= (int)($s['hourly_soft_limit'] ?? 0) ?></td>
                <td><?= ((int)($s['is_active'] ?? 0) === 1) ? '<span class="badge bg-success">Actif</span>' : '<span class="badge bg-secondary">Pause</span>' ?></td>
                <td>
                  <?php if (!empty($s['last_run_at'])): ?>
                    <?= htmlspecialchars((string)$s['last_run_at']) ?><br>
                    <span class="mini-note"><?= htmlspecialchars((string)($s['last_run_summary'] ?? '')) ?></span>
                  <?php else: ?>
                    <span class="text-muted">Jamais</span>
                  <?php endif; ?>
                </td>
                <td>
                  <div class="d-flex flex-wrap gap-1">
                    <form method="POST">
                      <input type="hidden" name="action" value="toggle_schedule">
                      <input type="hidden" name="schedule_id" value="<?= (int)($s['id'] ?? 0) ?>">
                      <button type="submit" class="btn btn-outline-primary btn-sm"><?= ((int)($s['is_active'] ?? 0) === 1) ? 'Pause' : 'Activer' ?></button>
                    </form>
                    <form method="POST" onsubmit="return confirm('Supprimer cette programmation ?');">
                      <input type="hidden" name="action" value="delete_schedule">
                      <input type="hidden" name="schedule_id" value="<?= (int)($s['id'] ?? 0) ?>">
                      <button type="submit" class="btn btn-outline-danger btn-sm">Supprimer</button>
                    </form>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
  </div>

  <h5 class="mt-3 mb-2"><i class="fa-solid fa-clock-rotate-left"></i> Derniers envois</h5>
  <div class="cardx tb">
    <?php if (!$logs): ?><div class="text-muted">Aucun envoi enregistre.</div><?php else: ?>
      <table><thead><tr><th>Date</th><th>Jour</th><th>Theme</th><th>Nom</th><th>Email</th><th>Statut</th></tr></thead><tbody>
        <?php foreach ($logs as $l): ?>
          <tr><td><?= htmlspecialchars((string)$l['created_at']) ?></td><td><?= htmlspecialchars($daysFr[(string)$l['campaign_day']] ?? (string)$l['campaign_day']) ?></td><td><?= htmlspecialchars((string)$l['campaign_theme']) ?></td><td><?= htmlspecialchars((string)$l['target_name']) ?></td><td><?= htmlspecialchars((string)$l['target_email']) ?></td><td><?= (($l['status'] ?? '') === 'sent') ? '<span class="badge bg-success">Envoye</span>' : '<span class="badge bg-danger">Echec</span>' ?></td></tr>
        <?php endforeach; ?>
      </tbody></table>
    <?php endif; ?>
  </div>
  <?php elseif ($isModelView): ?>
    <div class="cardx mt-3">
      <strong>Actions automatiques</strong>
      <div class="mini-note mt-1">Ce canal n utilise pas l envoi email automatique. Utilisez l apercu, la liste des destinataires et le modele de message pour votre prospection.</div>
    </div>
  <?php endif; ?>
</div>

<script>
document.querySelectorAll('.cp').forEach((b)=>{b.addEventListener('click',async()=>{try{await navigator.clipboard.writeText(b.dataset.t||'');const o=b.textContent;b.textContent='Copie';setTimeout(()=>b.textContent=o,1200);}catch(e){alert('Copie impossible');}});});
const recipientFilter = document.getElementById('recipientFilter');
if (recipientFilter) {
  recipientFilter.addEventListener('input', function () {
    const q = (this.value || '').toLowerCase().trim();
    document.querySelectorAll('.recipient-row').forEach((row) => {
      const hay = (row.getAttribute('data-search') || '').toLowerCase();
      row.style.display = (q === '' || hay.includes(q)) ? '' : 'none';
    });
  });
}
</script>
</body>
</html>
