<?php
// pagesweb_cn/sales.php
require_once __DIR__ . '/connectDb.php';
if(!isset($_SESSION['user_role']) || $_SESSION['user_role']!=='admin'){ header("Location: ./connect-parse.php?role=admin"); exit; }

$house_id = intval($_GET['house_id'] ?? 0);
$agent_id = intval($_GET['agent_id'] ?? 0);
$from = $_GET['from'] ?? '';
$to = $_GET['to'] ?? '';

$where = [];
$params = [];

if($house_id > 0){ $where[] = "s.house_id = ?"; $params[] = $house_id; }
if($agent_id > 0){ $where[] = "s.agent_id = ?"; $params[] = $agent_id; }
if($from){ $where[] = "s.created_at >= ?"; $params[] = $from . " 00:00:00"; }
if($to){ $where[] = "s.created_at <= ?"; $params[] = $to . " 23:59:59"; }

$sql = "SELECT s.*, a.fullname AS agent_name, h.name AS house_name FROM sales s JOIN houses h ON h.id=s.house_id LEFT JOIN agents a ON a.id=s.agent_id";
if($where) $sql .= " WHERE " . implode(" AND ", $where);
$sql .= " ORDER BY s.created_at DESC LIMIT 500";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$list = $stmt->fetchAll();

$houses = $pdo->query("SELECT id,name FROM houses ORDER BY name")->fetchAll();
$agents = $pdo->query("SELECT id,fullname FROM agents ORDER BY fullname")->fetchAll();
?>
<!doctype html><html lang="fr"><head><meta charset="utf-8"><title>Ventes</title><link href="../css/bootstrap.min.css" rel="stylesheet"></head><body class="p-3">
<div class="container"><h4>Ventes</h4>
<form class="row g-2 mb-3">
  <div class="col-auto"><select name="house_id" class="form-select"><option value="0">Toutes</option><?php foreach($houses as $h) echo "<option value='{$h['id']}' ".($h['id']==$house_id?'selected':'').">".htmlspecialchars($h['name'])."</option>";?></select></div>
  <div class="col-auto"><select name="agent_id" class="form-select"><option value="0">Tous les vendeurs</option><?php foreach($agents as $a) echo "<option value='{$a['id']}' ".($a['id']==$agent_id?'selected':'').">".htmlspecialchars($a['fullname'])."</option>";?></select></div>
  <div class="col-auto"><input type="date" name="from" class="form-control" value="<?=htmlspecialchars($from)?>"></div>
  <div class="col-auto"><input type="date" name="to" class="form-control" value="<?=htmlspecialchars($to)?>"></div>
  <div class="col-auto"><button class="btn btn-primary">Filtrer</button></div>
  <div class="col-auto"><a class="btn btn-secondary" href="?<?= http_build_query(array_merge($_GET,['export'=>'csv'])) ?>">Exporter CSV</a></div>
</form>

<table class="table table-sm"><thead><tr><th>ID</th><th>Date</th><th>Maison</th><th>Vendeur</th><th>Total</th><th></th></tr></thead><tbody>
<?php foreach($list as $s): ?>
  <tr>
    <td><?= $s['id'] ?></td>
    <td><?= $s['created_at'] ?></td>
    <td><?= htmlspecialchars($s['house_name']) ?></td>
    <td><?= htmlspecialchars($s['agent_name']) ?></td>
    <td><?= number_format($s['total_amount'],2) ?></td>
    <td><a class="btn btn-sm btn-outline-primary" href="sale_view.php?sale_id=<?= $s['id'] ?>">Voir</a></td>
  </tr>
<?php endforeach; ?>
</tbody></table>
</div></body></html>
