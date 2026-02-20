<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin_login_form');
    exit;
}

require_once __DIR__ . '/../configUrlcn.php';
require_once __DIR__ . '/connectDb.php';
require_once __DIR__ . '/send_email.php';

if (!defined('BASE_URL')) {
    define('BASE_URL', '/');
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

$daysFr = ['monday' => 'Lundi', 'tuesday' => 'Mardi', 'wednesday' => 'Mercredi', 'thursday' => 'Jeudi', 'friday' => 'Vendredi', 'saturday' => 'Samedi', 'sunday' => 'Dimanche'];
$today = dayKey();
$theme = 'conversion';
$limit = 50;
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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'import_contacts') {
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

$contacts = mergeContacts($parsed['rows'], $extra);
$seg = buildSeg($contacts);

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

try { ensureLogTable($pdo); } catch (Throwable $e) { if ($alert['msg'] === '') $alert = ['type' => 'danger', 'msg' => 'Erreur table log prospection.']; }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'send_daily_email') {
    $theme = (string)($_POST['campaign_theme'] ?? 'conversion');
    if (!in_array($theme, ['conversion', 'social_proof', 'urgency'], true)) $theme = 'conversion';
    $limit = (int)($_POST['daily_limit'] ?? 50);
    if ($limit < 1) $limit = 1;
    if ($limit > 300) $limit = 300;
    $dayReq = (string)($_POST['day_key'] ?? '');
    if (!isset($daysFr[$dayReq])) {
        $alert = ['type' => 'danger', 'msg' => 'Jour invalide.'];
    } elseif ($dayReq !== $today) {
        $alert = ['type' => 'danger', 'msg' => 'Seul le bouton du jour est autorise (1 mail/jour/contact).'];
    } else {
        $already = [];
        $st = $pdo->prepare("SELECT DISTINCT target_email FROM prospection_email_logs WHERE campaign_date = ? AND status = 'sent'");
        $st->execute([date('Y-m-d')]);
        foreach ($st->fetchAll(PDO::FETCH_COLUMN) as $e) $already[strtolower((string)$e)] = true;
        $queue = [];
        foreach ($seg['email_marketing']['rows'] as $c) {
            $e = strtolower((string)$c['email']);
            if ($e === '' || isset($already[$e])) continue;
            $queue[] = $c;
            if (count($queue) >= $limit) break;
        }
        if (!$queue) {
            $alert = ['type' => 'warning', 'msg' => 'Aucun nouveau contact email a traiter aujourd hui.'];
        } else {
            @set_time_limit(300);
            $okN = 0; $koN = 0;
            $ins = $pdo->prepare("INSERT INTO prospection_email_logs (campaign_date,campaign_day,campaign_theme,target_name,target_email,subject_line,status,error_text,created_at) VALUES (?,?,?,?,?,?,?,?,NOW())");
            foreach ($queue as $c) {
                $mail = buildMail($dayReq, $theme, (string)$c['full']);
                $ok = sendProspectionEmail((string)$c['email'], (string)$c['full'], (string)$mail['subject'], (string)$mail['html'], (string)$mail['alt']);
                if ($ok) { $okN++; $ins->execute([date('Y-m-d'), $dayReq, $theme, (string)$c['full'], (string)$c['email'], (string)$mail['subject'], 'sent', null]); }
                else { $koN++; $ins->execute([date('Y-m-d'), $dayReq, $theme, (string)$c['full'], (string)$c['email'], (string)$mail['subject'], 'failed', 'Erreur SMTP']); }
            }
            $alert = ['type' => $koN > 0 ? 'warning' : 'success', 'msg' => 'Campagne ' . $daysFr[$dayReq] . ': ' . $okN . ' envoye(s), ' . $koN . ' echec(s).'];
        }
    }
}

$goal = 100;
$sent21 = 0; $sentToday = 0; $logs = [];
try {
    $sent21 = (int)$pdo->query("SELECT COUNT(DISTINCT target_email) FROM prospection_email_logs WHERE status='sent' AND campaign_date >= DATE_SUB(CURDATE(), INTERVAL 21 DAY)")->fetchColumn();
    $q = $pdo->prepare("SELECT COUNT(DISTINCT target_email) FROM prospection_email_logs WHERE status='sent' AND campaign_date = CURDATE()");
    $q->execute(); $sentToday = (int)$q->fetchColumn();
    $logs = $pdo->query("SELECT campaign_day,campaign_theme,target_name,target_email,status,created_at FROM prospection_email_logs ORDER BY id DESC LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {}
$pct = $goal > 0 ? min(100, (int)round(($sent21 / $goal) * 100)) : 0;

$w0 = new DateTimeImmutable('monday this week');
$wk = []; $ord = ['monday','tuesday','wednesday','thursday','friday','saturday','sunday'];
foreach ($ord as $i => $k) $wk[$k] = ['l' => $daysFr[$k], 'd' => $w0->modify('+' . $i . ' day')->format('d/m')];
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
  </style>
</head>
<body>
<div class="wrap">
  <div class="head">
    <div><h3 style="margin:0;"><i class="fa-solid fa-bullhorn"></i> Prospections commerciales</h3><div style="opacity:.9;font-size:13px;">Objectif: 100 utilisateurs en 3 semaines</div></div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;"><a class="hbtn" href="<?= BASE_URL ?>pagesweb_cn/admin_subscription_manager">Retour abonnements</a><a class="hbtn" href="<?= BASE_URL ?>pagesweb_cn/logout.php">Deconnexion</a></div>
  </div>

  <div class="grid">
    <div class="cardx"><div class="m">Contacts bruts (adhesion.sql)</div><div class="v"><?= number_format($rawCount) ?></div></div>
    <div class="cardx"><div class="m">Contacts importes (Excel/CSV)</div><div class="v"><?= number_format($extraCount) ?></div></div>
    <div class="cardx"><div class="m">Contacts uniques</div><div class="v"><?= number_format(count($contacts)) ?></div></div>
    <div class="cardx"><div class="m">Emails envoyes aujourd hui</div><div class="v"><?= number_format($sentToday) ?></div></div>
    <div class="cardx"><div class="m">Progression 21 jours</div><div class="v"><?= number_format($sent21) ?>/<?= number_format($goal) ?></div><div class="progress mt-2"><div class="progress-bar" style="width:<?= (int)$pct ?>%"></div></div></div>
  </div>

  <?php if ($alert['msg'] !== ''): ?><div class="alert alert-<?= htmlspecialchars($alert['type'] !== '' ? $alert['type'] : 'info') ?> mt-3"><?= htmlspecialchars($alert['msg']) ?></div><?php endif; ?>

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

  <h5 class="mt-3 mb-2"><i class="fa-solid fa-layer-group"></i> 9 systemes de prospection</h5>
  <div class="seg">
    <?php foreach ($seg as $k => $s): $pv = array_slice($s['rows'], 0, 4); ?>
      <div class="cardx">
        <div class="shead"><div><strong><i class="<?= htmlspecialchars($s['icon']) ?>"></i> <?= htmlspecialchars($s['title']) ?></strong><div class="sdesc"><?= htmlspecialchars($s['desc']) ?></div></div><div class="sc"><?= number_format(count($s['rows'])) ?></div></div>
        <div class="prev"><?php if ($pv) { foreach ($pv as $i => $c) echo htmlspecialchars($c['full']) . ($i < count($pv) - 1 ? ' | ' : ''); } else { echo 'Aucun contact'; } ?></div>
        <div class="act"><a class="btn-sm2" href="?export=<?= urlencode($k) ?>">Export CSV</a><button type="button" class="btn-sm2 cp" data-t="<?= htmlspecialchars($s['tpl']) ?>">Copier modele</button><?php if ($k === 'email_marketing'): ?><a class="btn-sm2" href="#campagne">Aller aux envois</a><?php endif; ?></div>
      </div>
    <?php endforeach; ?>
  </div>

  <h5 id="campagne" class="mt-3 mb-2"><i class="fa-solid fa-envelope-open-text"></i> Campagne email automatique</h5>
  <div class="cardx">
    <form method="POST">
      <input type="hidden" name="action" value="send_daily_email">
      <div class="row g-2">
        <div class="col-md-8">
          <label class="form-label">Type de mail commercial</label>
          <select class="form-select" name="campaign_theme">
            <option value="conversion" <?= $theme === 'conversion' ? 'selected' : '' ?>>Offre conversion</option>
            <option value="social_proof" <?= $theme === 'social_proof' ? 'selected' : '' ?>>Preuve sociale</option>
            <option value="urgency" <?= $theme === 'urgency' ? 'selected' : '' ?>>Urgence commerciale</option>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label">Volume du jour</label>
          <input class="form-control" type="number" min="1" max="300" name="daily_limit" value="<?= (int)$limit ?>">
        </div>
      </div>
      <div class="form-text mt-2">Frequence forcee: 1 email maximum par jour et par contact. Seul le bouton du jour est actif.</div>
      <div class="day mt-2">
        <?php foreach ($wk as $k => $b): $on = ($k === $today); ?>
          <button type="submit" name="day_key" value="<?= htmlspecialchars($k) ?>" class="<?= $on ? 'on' : '' ?>" <?= $on ? '' : 'disabled' ?>><?= htmlspecialchars($b['l']) ?><br><small><?= htmlspecialchars($b['d']) ?></small></button>
        <?php endforeach; ?>
      </div>
    </form>
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
</div>

<script>
document.querySelectorAll('.cp').forEach((b)=>{b.addEventListener('click',async()=>{try{await navigator.clipboard.writeText(b.dataset.t||'');const o=b.textContent;b.textContent='Copie';setTimeout(()=>b.textContent=o,1200);}catch(e){alert('Copie impossible');}});});
</script>
</body>
</html>
