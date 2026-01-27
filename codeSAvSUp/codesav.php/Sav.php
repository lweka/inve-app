<?php
//product_stock_update 
require_once __DIR__ . '/connectDb.php';
header('Content-Type: application/json');

if(!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin'){
    echo json_encode(['ok'=>false,'message'=>'Non autorisé']);
    exit;
}

$product_id = (int)($_POST['product_id'] ?? 0);
$house_id   = (int)($_POST['house_id'] ?? 0);
$type       = $_POST['type'] ?? '';
$qty        = (int)($_POST['qty'] ?? 0);
$note       = trim($_POST['note'] ?? '');

if($product_id <= 0 || $house_id <= 0 || $qty <= 0 || !in_array($type, ['in','out'])){
    echo json_encode(['ok'=>false,'message'=>'Paramètres invalides']);
    exit;
}

$pdo->beginTransaction();

try {

    /* 🔒 verrou stock */
    $stmt = $pdo->prepare("
        SELECT id, qty 
        FROM house_stock 
        WHERE house_id = ? AND product_id = ?
        FOR UPDATE
    ");
    $stmt->execute([$house_id, $product_id]);
    $stock = $stmt->fetch();

    /* 🔹 récupérer prix figés */
    $stmt = $pdo->prepare("
        SELECT buy_price_cdf, sell_price_cdf 
        FROM products 
        WHERE id = ?
    ");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();

    if(!$product){
        throw new Exception('Produit introuvable');
    }

    /* 🔹 calcul nouveau stock */
    if(!$stock){
        if($type === 'out'){
            throw new Exception('Stock inexistant');
        }

        $pdo->prepare("
            INSERT INTO house_stock (house_id, product_id, qty)
            VALUES (?,?,?)
        ")->execute([$house_id, $product_id, $qty]);

        $newQty = $qty;

    } else {
        $current = (int)$stock['qty'];

        if($type === 'out' && $current < $qty){
            throw new Exception('Stock insuffisant');
        }

        $newQty = ($type === 'in')
            ? $current + $qty
            : $current - $qty;

        $pdo->prepare("
            UPDATE house_stock 
            SET qty = ?
            WHERE id = ?
        ")->execute([$newQty, $stock['id']]);
    }

    /* 🔹 UN SEUL mouvement */
    $stmt = $pdo->prepare("
        INSERT INTO product_movements (
            house_id,
            product_id,
            type,
            qty,
            unit_buy_price_cdf,
            unit_sell_price_cdf,
            note
        ) VALUES (?,?,?,?,?,?,?)
    ");
    $stmt->execute([
        $house_id,
        $product_id,
        $type,
        $qty,
        $product['buy_price_cdf'],
        $product['sell_price_cdf'],
        $note
    ]);

    $pdo->commit();
    echo json_encode(['ok'=>true,'new_qty'=>$newQty]);

} catch(Exception $e){
    $pdo->rollBack();
    echo json_encode(['ok'=>false,'message'=>$e->getMessage()]);
} 
//*****  */

<?php
// create_sale_fifo
require_once __DIR__ . '/../configUrlcn.php';
require_once __DIR__ . '/../defConstLiens.php';
require_once __DIR__ . '/connectDb.php';

if ($_SESSION['user_role'] !== 'agent') {
    echo json_encode(['ok'=>false,'message'=>'Accès refusé']);
    exit;
}

$house_id = (int)$_POST['house_id'];
$agent_id = (int)$_SESSION['user_id'];
$items = json_decode($_POST['items'], true);

if (!$house_id || !$items) {
    echo json_encode(['ok'=>false,'message'=>'Données invalides']);
    exit;
}

try {
    $pdo->beginTransaction();

    foreach ($items as $it) {
        $product_id = (int)$it['product_id'];
        $qtyToSell  = (int)$it['qty'];

        // récupérer lots FIFO
        $stmt = $pdo->prepare("
            SELECT id, qty, unit_buy_price_cdf, unit_sell_price_cdf
            FROM product_movements
            WHERE product_id = ?
              AND house_id = ?
              AND type = 'in'
              AND qty > 0
            ORDER BY created_at ASC
            FOR UPDATE
        ");
        $stmt->execute([$product_id, $house_id]);
        $lots = $stmt->fetchAll();

        foreach ($lots as $lot) {
            if ($qtyToSell <= 0) break;

            $consume = min($qtyToSell, $lot['qty']);

            // diminuer le lot
            $pdo->prepare("
                UPDATE product_movements
                SET qty = qty - ?
                WHERE id = ?
            ")->execute([$consume, $lot['id']]);

            // enregistrer la vente
            $pdo->prepare("
                INSERT INTO product_movements
                (house_id, product_id, type, qty, unit_buy_price_cdf, unit_sell_price_cdf, agent_id, note)
                VALUES (?,?,?,?,?,?,?,?)
            ")->execute([
                $house_id,
                $product_id,
                'sale',
                $consume,
                $lot['unit_buy_price_cdf'],
                $lot['unit_sell_price_cdf'],
                $agent_id,
                'Vente FIFO'
            ]);

            $qtyToSell -= $consume;
        }

        if ($qtyToSell > 0) {
            throw new Exception('Stock insuffisant (FIFO)');
        }

        // mise à jour stock global
        $pdo->prepare("
            UPDATE house_stock
            SET qty = qty - ?
            WHERE house_id = ? AND product_id = ?
        ")->execute([$it['qty'], $house_id, $product_id]);
    }

    $pdo->commit();
    echo json_encode(['ok'=>true]);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['ok'=>false,'message'=>$e->getMessage()]);
}
//******************** */


<?php
// code complet de pagesweb_cn/product_history.php :
require_once __DIR__ . '/../configUrlcn.php';
require_once __DIR__ . '/../defConstLiens.php';
require_once __DIR__ . '/connectDb.php';

if(!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin'){
    header("Location: connect-parse.php?role=admin");
    exit;
}

/* =========================
   PARAMÈTRES
========================= */
$product_id = (int)($_GET['product_id'] ?? 0);
$house_id   = (int)($_GET['house_id'] ?? 0);
$page       = max(1, (int)($_GET['page'] ?? 1));
$limit      = max(1, (int)($_GET['limit'] ?? 10));
$offset     = ($page - 1) * $limit;

$agent_id   = (int)($_GET['agent_id'] ?? 0);
$type       = $_GET['movement_type'] ?? '';
$start_date = $_GET['start_date'] ?? '';
$end_date   = $_GET['end_date'] ?? '';

if($product_id <= 0 || $house_id <= 0){
    die("Paramètres invalides");
}

/* =========================
   PRODUIT
========================= */
$stmt = $pdo->prepare("
    SELECT p.*, h.name AS house_name
    FROM products p
    JOIN houses h ON h.id = p.house_id
    WHERE p.id = ? AND p.house_id = ?
");
$stmt->execute([$product_id, $house_id]);
$product = $stmt->fetch();
if(!$product) die("Produit introuvable");

/* =========================
   FILTRES
========================= */
$where = " WHERE pm.product_id = ? AND pm.house_id = ? ";
$params = [$product_id, $house_id];

if($agent_id > 0){
    $where .= " AND pm.agent_id = ? ";
    $params[] = $agent_id;
}

if(in_array($type, ['in','sale','out'])){
    $where .= " AND pm.type = ? ";
    $params[] = $type;
}

if($start_date !== ''){
    $where .= " AND pm.created_at >= ? ";
    $params[] = $start_date . ' 00:00:00';
}

if($end_date !== ''){
    $where .= " AND pm.created_at <= ? ";
    $params[] = $end_date . ' 23:59:59';
}

/* =========================
   TOTAL
========================= */
$stmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM product_movements pm
    $where
");
$stmt->execute($params);
$totalRows = (int)$stmt->fetchColumn();
$totalPages = max(1, ceil($totalRows / $limit));

/* =========================
   MOUVEMENTS
========================= */
$sql = "
    SELECT 
        pm.*,
        a.fullname AS agent_name,
        (pm.unit_sell_price_cdf - pm.unit_buy_price_cdf) AS margin_unit,
        (pm.qty * (pm.unit_sell_price_cdf - pm.unit_buy_price_cdf)) AS margin_total
    FROM product_movements pm
    LEFT JOIN agents a ON a.id = pm.agent_id
    $where
    ORDER BY pm.created_at DESC
    LIMIT $limit OFFSET $offset
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

/* =========================
   AGENTS (FILTRE)
========================= */
$agents = $pdo->query("
    SELECT DISTINCT a.id, a.fullname
    FROM agents a
    JOIN product_movements pm ON pm.agent_id = a.id
    WHERE pm.product_id = $product_id
")->fetchAll();

/* =========================
   TAUX USD
========================= */
$usd_rate = $pdo->query("SELECT usd_rate FROM exchange_rate WHERE id=1")->fetchColumn();
if(!$usd_rate || $usd_rate <= 0) $usd_rate = 1;


// include header if exists
if(isset($headerPath) && is_file($headerPath)) require_once $headerPath;
?>



<div class="container" style="max-width:1200px">

<h4>Historique — <?= htmlspecialchars($product['name']) ?></h4>
<div class="text-muted mb-3">Maison : <?= htmlspecialchars($product['house_name']) ?></div>

<!-- FILTRES -->
<form method="GET" class="row g-2 mb-3">
<input type="hidden" name="product_id" value="<?= $product_id ?>">
<input type="hidden" name="house_id" value="<?= $house_id ?>">

<div class="col-md-2">
<select name="agent_id" class="form-select">
<option value="">Tous vendeurs</option>
<?php foreach($agents as $a): ?>
<option value="<?= $a['id'] ?>" <?= $agent_id==$a['id']?'selected':'' ?>>
<?= htmlspecialchars($a['fullname']) ?>
</option>
<?php endforeach; ?>
</select>
</div>

<div class="col-md-2">
<select name="movement_type" class="form-select">
<option value="">Tous types</option>
<option value="in" <?= $type==='in'?'selected':'' ?>>Entrée</option>
<option value="sale" <?= $type==='sale'?'selected':'' ?>>Vente</option>
<option value="out" <?= $type==='out'?'selected':'' ?>>Sortie</option>
</select>
</div>

<div class="col-md-2"><input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($start_date) ?>"></div>
<div class="col-md-2"><input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars($end_date) ?>"></div>

<div class="col-md-2"><button class="btn btn-primary w-100">Filtrer</button></div>
<div class="col-md-2"><a href="?product_id=<?= $product_id ?>&house_id=<?= $house_id ?>" class="btn btn-light w-100">Reset</a></div>
</form>

<!-- TABLE -->
<table class="table table-sm table-striped">
<thead>
<tr>
<th>Date</th>
<th>Type</th>
<th>Vendeur</th>
<th>Qté</th>
<th>Achat</th>
<th>Vente</th>
<th>Marge</th>
<th>Total Marge</th>
<th>Note</th>
</tr>
</thead>
<tbody>

<?php if(!$rows): ?>
<tr><td colspan="9">Aucun mouvement</td></tr>
<?php endif; ?>

<?php foreach($rows as $r): ?>
<tr>
<td><?= $r['created_at'] ?></td>
<td><?= strtoupper($r['type']) ?></td>
<td><?= htmlspecialchars($r['agent_name'] ?? '-') ?></td>
<td><?= $r['qty'] ?></td>
<td><?= number_format((float)($r['unit_buy_price_cdf'] ?? 0),0) ?> CDF</td>
<td><?= number_format((float)($r['unit_sell_price_cdf'] ?? 0),0) ?> CDF</td>
<td><?= number_format((float)($r['margin_unit'] ?? 0),0) ?> CDF</td>
<td><strong><?= number_format((float)($r['margin_total'] ?? 0),0) ?> CDF</strong></td>
<td><?= htmlspecialchars($r['note']) ?></td>
</tr>
<?php endforeach; ?>

</tbody>
</table>

<!-- PAGINATION -->
<nav>
<ul class="pagination">
<?php for($i=1;$i<=$totalPages;$i++): ?>
<li class="page-item <?= $i==$page?'active':'' ?>">
<a class="page-link" href="?product_id=<?= $product_id ?>&house_id=<?= $house_id ?>&page=<?= $i ?>"><?= $i ?></a>
</li>
<?php endfor; ?>
</ul>
</nav>

<a href="products.php?house_id=<?= $house_id ?>" class="btn btn-light mt-3">← Retour</a>

</div>


<?php 
if(isset($footerPath) && is_file($footerPath)) require_once $footerPath;
?>

