<?php
require_once __DIR__.'/connectDb.php';

$today = date('Y-m-d');

$global = $pdo->query("
SELECT
  SUM(qty * (unit_sell_price_cdf - unit_buy_price_cdf)) AS total_marge
FROM product_movements
WHERE type='sale'
")->fetchColumn();

$todayProfit = $pdo->prepare("
SELECT
  SUM(qty * (unit_sell_price_cdf - unit_buy_price_cdf))
FROM product_movements
WHERE type='sale'
AND DATE(created_at)=?
");
$todayProfit->execute([$today]);
$todayProfit = $todayProfit->fetchColumn();
