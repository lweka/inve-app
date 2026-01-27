<?php
require_once __DIR__ . '/../configUrlcn.php';
require_once __DIR__ . '/../defConstLiens.php';
require_once __DIR__ . '/connectDb.php';

if(!isset($_SESSION['user_role']) || $_SESSION['user_role']!=='admin'){
  header("Location: connect-parse.php?role=admin"); exit;
}

// récupération des maisons
$houses = $pdo->query("SELECT * FROM houses ORDER BY id DESC")->fetchAll();

if($_SERVER['REQUEST_METHOD']==='POST'){
    $rate = floatval($_POST['usd_rate']);
    if($rate>0){
        $stmt = $pdo->prepare("
        INSERT INTO exchange_rate (id, usd_rate)
        VALUES (1,?)
        ON DUPLICATE KEY UPDATE usd_rate=VALUES(usd_rate)
        ");
        $stmt->execute([$rate]);
        header("Location: exchange_rate_manage.php?ok=1");
        exit;
    }
}

$usd_rate = $pdo->query("SELECT usd_rate FROM exchange_rate WHERE id=1")->fetchColumn();
if(!$usd_rate || $usd_rate<=0) $usd_rate=1;

/* filtres */
$type = $_GET['type'] ?? '';
$from = $_GET['from'] ?? '';
$to   = $_GET['to'] ?? '';

$where=[]; $params=[];

if(in_array($type,['in','out','sale'])){
  $where[]="pm.type=?";
  $params[]=$type;
}
if($from){
  $where[]="pm.created_at>=?";
  $params[]=$from." 00:00:00";
}
if($to){
  $where[]="pm.created_at<=?";
  $params[]=$to." 23:59:59";
}

$sql="
SELECT 
 pm.*, 
 p.name AS product_name,
 p.buy_price_cdf,
 p.sell_price_cdf,
 a.fullname AS agent_name,
 h.name AS house_name
FROM product_movements pm
JOIN products p ON p.id=pm.product_id
JOIN houses h ON h.id=pm.house_id
LEFT JOIN agents a ON a.id=pm.agent_id
".($where?" WHERE ".implode(" AND ",$where):"")."
ORDER BY pm.created_at DESC";

$stmt=$pdo->prepare($sql);
$stmt->execute($params);
$rows=$stmt->fetchAll();

// include header if exists
if(isset($headerPath) && is_file($headerPath)) require_once $headerPath;
?>



    <div class="container-fluid">
    <h4>Historique global des produits</h4>
     <?php foreach($houses as $h): ?>
        <a href="<?=PRODUCTS_MANAGE?>?house_id=<?= $h['id']?>" class="btn btn-light ">← Retour</a>
      <?php endforeach; ?>

    <form class="row g-2 mb-3">
    <div class="col-md-3">
        <select name="type" class="form-select">
        <option value="">Tous types</option>
        <option value="sale">Vente</option>
        <option value="in">Entrée</option>
        <option value="out">Sortie</option>
        </select>
    </div>
    <div class="col-md-3"><input type="date" name="from" class="form-control"></div>
    <div class="col-md-3"><input type="date" name="to" class="form-control"></div>
    <div class="col-md-3"><button class="btn btn-primary w-100">Filtrer</button></div>
    </form>

    <table class="table table-sm table-striped">
    <thead>
    <tr>
    <th>Date</th><th>Produit</th><th>Type</th><th>Qté</th>
    <th>Total CDF</th><th>Total USD</th><th>Marge</th>
    <th>Vendeur</th><th>Maison</th>
    </tr>
    </thead>
    <tbody>

    <?php foreach($rows as $r):
    $total=$r['qty']*$r['sell_price_cdf'];
    $marge=($r['type']=='sale')
        ? ($r['sell_price_cdf']-$r['buy_price_cdf'])*$r['qty']
        : 0;
    ?>
    <tr>
    <td><?=$r['created_at']?></td>
    <td><?=htmlspecialchars($r['product_name'])?></td>
    <td><?=strtoupper($r['type'])?></td>
    <td><?=$r['qty']?></td>
    <td><?=number_format($total,0)?> CDF</td>
    <td><?=number_format($total/$usd_rate,2)?> USD</td>
    <td class="<?= $marge<0?'text-danger':'text-success' ?>">
    <?=number_format($marge,0)?> CDF
    </td>
    <td><?=htmlspecialchars($r['agent_name']??'Admin')?></td>
    <td><?=htmlspecialchars($r['house_name'])?></td>
    </tr>
    <?php endforeach; ?>

    </tbody>
    </table>
    </div>


<?php 
if(isset($footerPath) && is_file($footerPath)) require_once $footerPath;
?>
