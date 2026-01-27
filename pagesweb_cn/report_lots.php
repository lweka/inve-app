<?php
require_once __DIR__.'/connectDb.php';

$sql = "
SELECT
    p.name,
    pm.created_at,
    pm.qty,
    pm.unit_buy_price_cdf,
    pm.unit_sell_price_cdf,
    (pm.unit_sell_price_cdf - pm.unit_buy_price_cdf) * pm.qty AS marge
FROM product_movements pm
JOIN products p ON p.id = pm.product_id
WHERE pm.type='sale'
ORDER BY pm.created_at DESC
";

$data = $pdo->query($sql)->fetchAll();
