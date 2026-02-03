<?php
require_once __DIR__.'/connectDb.php';

if($_SESSION['user_role']!=='admin'){ exit; }

$from = $_GET['from'] ?? '';
$to   = $_GET['to'] ?? '';

$sql = "
SELECT
    p.name,
    SUM(pm.qty * (pm.unit_sell_price_cdf - pm.unit_buy_price_cdf)) AS marge_cdf
FROM product_movements pm
JOIN products p ON p.id = pm.product_id
WHERE pm.type='out' OR pm.type='sale'
";

$params = [];

if($from){
  $sql .= " AND pm.created_at >= ?";
  $params[] = $from.' 00:00:00';
}
if($to){
  $sql .= " AND pm.created_at <= ?";
  $params[] = $to.' 23:59:59';
}

$sql .= " GROUP BY p.id ORDER BY marge_cdf DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$data = $stmt->fetchAll();



foreach($data as $r){
  echo "<tr>
    <td>{$r['name']}</td>
    <td>".number_format($r['marge_cdf'],0)." CDF</td>
  </tr>";
}

