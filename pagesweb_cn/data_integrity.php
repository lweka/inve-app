<?php

/**
 * Data integrity helpers.
 * Keep deletions consistent and remove orphan rows left by legacy data.
 */

function di_table_exists(PDO $pdo, string $tableName): bool
{
    static $cache = [];
    if (array_key_exists($tableName, $cache)) {
        return $cache[$tableName];
    }

    $stmt = $pdo->prepare("
        SELECT 1
        FROM information_schema.tables
        WHERE table_schema = DATABASE() AND table_name = ?
        LIMIT 1
    ");
    $stmt->execute([$tableName]);
    $cache[$tableName] = (bool)$stmt->fetchColumn();
    return $cache[$tableName];
}

function di_constraint_exists(PDO $pdo, string $tableName, string $constraintName): bool
{
    $stmt = $pdo->prepare("
        SELECT 1
        FROM information_schema.table_constraints
        WHERE table_schema = DATABASE()
          AND table_name = ?
          AND constraint_name = ?
          AND constraint_type = 'FOREIGN KEY'
        LIMIT 1
    ");
    $stmt->execute([$tableName, $constraintName]);
    return (bool)$stmt->fetchColumn();
}

function di_index_exists(PDO $pdo, string $tableName, string $indexName): bool
{
    $stmt = $pdo->prepare("
        SELECT 1
        FROM information_schema.statistics
        WHERE table_schema = DATABASE()
          AND table_name = ?
          AND index_name = ?
        LIMIT 1
    ");
    $stmt->execute([$tableName, $indexName]);
    return (bool)$stmt->fetchColumn();
}

function di_cleanup_orphans(PDO $pdo): array
{
    $stats = [];

    $run = static function (PDO $pdo, array &$stats, string $name, string $sql): void {
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $stats[$name] = $stmt->rowCount();
    };

    if (di_table_exists($pdo, 'product_movements') && di_table_exists($pdo, 'houses')) {
        $run(
            $pdo,
            $stats,
            'product_movements_missing_house',
            "DELETE pm
             FROM product_movements pm
             LEFT JOIN houses h ON h.id = pm.house_id
             WHERE h.id IS NULL"
        );
    }

    if (di_table_exists($pdo, 'product_movements') && di_table_exists($pdo, 'products')) {
        $run(
            $pdo,
            $stats,
            'product_movements_missing_product',
            "DELETE pm
             FROM product_movements pm
             LEFT JOIN products p ON p.id = pm.product_id
             WHERE pm.product_id > 0 AND p.id IS NULL"
        );
    }

    if (di_table_exists($pdo, 'product_movements') && di_table_exists($pdo, 'agents')) {
        $run(
            $pdo,
            $stats,
            'product_movements_nullify_missing_agent',
            "UPDATE product_movements pm
             LEFT JOIN agents a ON a.id = pm.agent_id
             SET pm.agent_id = NULL
             WHERE pm.agent_id IS NOT NULL AND a.id IS NULL"
        );
    }

    if (di_table_exists($pdo, 'house_stock') && di_table_exists($pdo, 'houses') && di_table_exists($pdo, 'products')) {
        $run(
            $pdo,
            $stats,
            'house_stock_orphans',
            "DELETE hs
             FROM house_stock hs
             LEFT JOIN houses h ON h.id = hs.house_id
             LEFT JOIN products p ON p.id = hs.product_id
             WHERE h.id IS NULL OR p.id IS NULL"
        );
    }

    if (di_table_exists($pdo, 'agent_stock') && di_table_exists($pdo, 'agents') && di_table_exists($pdo, 'houses') && di_table_exists($pdo, 'products')) {
        $run(
            $pdo,
            $stats,
            'agent_stock_orphans',
            "DELETE ast
             FROM agent_stock ast
             LEFT JOIN agents a ON a.id = ast.agent_id
             LEFT JOIN houses h ON h.id = ast.house_id
             LEFT JOIN products p ON p.id = ast.product_id
             WHERE a.id IS NULL OR h.id IS NULL OR p.id IS NULL"
        );
    }

    if (di_table_exists($pdo, 'seller_stock') && di_table_exists($pdo, 'agents') && di_table_exists($pdo, 'houses') && di_table_exists($pdo, 'products')) {
        $run(
            $pdo,
            $stats,
            'seller_stock_orphans',
            "DELETE ss
             FROM seller_stock ss
             LEFT JOIN agents a ON a.id = ss.seller_id
             LEFT JOIN houses h ON h.id = ss.house_id
             LEFT JOIN products p ON p.id = ss.product_id
             WHERE a.id IS NULL OR h.id IS NULL OR p.id IS NULL"
        );
    }

    if (di_table_exists($pdo, 'stock_movements') && di_table_exists($pdo, 'houses') && di_table_exists($pdo, 'products')) {
        $run(
            $pdo,
            $stats,
            'stock_movements_orphans',
            "DELETE sm
             FROM stock_movements sm
             LEFT JOIN houses h ON h.id = sm.house_id
             LEFT JOIN products p ON p.id = sm.product_id
             WHERE h.id IS NULL OR p.id IS NULL"
        );
    }

    if (di_table_exists($pdo, 'sales') && di_table_exists($pdo, 'houses') && di_table_exists($pdo, 'agents')) {
        $run(
            $pdo,
            $stats,
            'sales_orphans',
            "DELETE s
             FROM sales s
             LEFT JOIN houses h ON h.id = s.house_id
             LEFT JOIN agents a ON a.id = s.agent_id
             WHERE h.id IS NULL OR a.id IS NULL"
        );
    }

    if (di_table_exists($pdo, 'sale_items') && di_table_exists($pdo, 'sales') && di_table_exists($pdo, 'products')) {
        $run(
            $pdo,
            $stats,
            'sale_items_orphans',
            "DELETE si
             FROM sale_items si
             LEFT JOIN sales s ON s.id = si.sale_id
             LEFT JOIN products p ON p.id = si.product_id
             WHERE s.id IS NULL OR p.id IS NULL"
        );
    }

    if (di_table_exists($pdo, 'exchange_rate') && di_table_exists($pdo, 'houses')) {
        $run(
            $pdo,
            $stats,
            'exchange_rate_orphans',
            "DELETE er
             FROM exchange_rate er
             LEFT JOIN houses h ON h.id = er.house_id
             WHERE h.id IS NULL"
        );
    }

    if (di_table_exists($pdo, 'ticket_print_logs') && di_table_exists($pdo, 'houses') && di_table_exists($pdo, 'agents')) {
        $run(
            $pdo,
            $stats,
            'ticket_print_logs_orphans',
            "DELETE tpl
             FROM ticket_print_logs tpl
             LEFT JOIN houses h ON h.id = tpl.house_id
             LEFT JOIN agents a ON a.id = tpl.agent_id
             WHERE h.id IS NULL OR a.id IS NULL"
        );
    }

    if (di_table_exists($pdo, 'house_delete_requests') && di_table_exists($pdo, 'houses')) {
        $run(
            $pdo,
            $stats,
            'house_delete_requests_orphans',
            "DELETE hdr
             FROM house_delete_requests hdr
             LEFT JOIN houses h ON h.id = hdr.house_id
             WHERE h.id IS NULL"
        );
    }

    if (di_table_exists($pdo, 'products') && di_table_exists($pdo, 'houses')) {
        $run(
            $pdo,
            $stats,
            'products_orphans',
            "DELETE p
             FROM products p
             LEFT JOIN houses h ON h.id = p.house_id
             WHERE h.id IS NULL"
        );
    }

    if (di_table_exists($pdo, 'agents') && di_table_exists($pdo, 'houses')) {
        $run(
            $pdo,
            $stats,
            'agents_orphans',
            "DELETE a
             FROM agents a
             LEFT JOIN houses h ON h.id = a.house_id
             WHERE h.id IS NULL"
        );
    }

    return $stats;
}

function di_cleanup_orphans_safe(PDO $pdo): array
{
    $ownsTransaction = !$pdo->inTransaction();
    if ($ownsTransaction) {
        $pdo->beginTransaction();
    }

    try {
        $stats = di_cleanup_orphans($pdo);
        if ($ownsTransaction) {
            $pdo->commit();
        }
        return $stats;
    } catch (Throwable $e) {
        if ($ownsTransaction && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

function di_add_fk_if_missing(PDO $pdo, string $tableName, string $constraintName, string $sqlFragment): bool
{
    if (!di_table_exists($pdo, $tableName) || di_constraint_exists($pdo, $tableName, $constraintName)) {
        return false;
    }

    $pdo->exec("ALTER TABLE `{$tableName}` ADD CONSTRAINT `{$constraintName}` {$sqlFragment}");
    return true;
}

function di_add_index_if_missing(PDO $pdo, string $tableName, string $indexName, string $columnsSql): bool
{
    if (!di_table_exists($pdo, $tableName) || di_index_exists($pdo, $tableName, $indexName)) {
        return false;
    }

    $pdo->exec("ALTER TABLE `{$tableName}` ADD INDEX `{$indexName}` {$columnsSql}");
    return true;
}

function di_enforce_foreign_keys(PDO $pdo): array
{
    $applied = [];

    // Ensure optional legacy table has indexes before FK additions.
    di_add_index_if_missing($pdo, 'seller_stock', 'idx_seller_stock_seller_id', '(seller_id)');
    di_add_index_if_missing($pdo, 'seller_stock', 'idx_seller_stock_house_id', '(house_id)');
    di_add_index_if_missing($pdo, 'seller_stock', 'idx_seller_stock_product_id', '(product_id)');

    $pairs = [
        ['products', 'fk_products_house_id', 'FOREIGN KEY (`house_id`) REFERENCES `houses`(`id`) ON DELETE CASCADE ON UPDATE CASCADE'],
        ['agents', 'fk_agents_house_id', 'FOREIGN KEY (`house_id`) REFERENCES `houses`(`id`) ON DELETE CASCADE ON UPDATE CASCADE'],

        ['product_movements', 'fk_pm_house_id', 'FOREIGN KEY (`house_id`) REFERENCES `houses`(`id`) ON DELETE CASCADE ON UPDATE CASCADE'],
        ['product_movements', 'fk_pm_agent_id', 'FOREIGN KEY (`agent_id`) REFERENCES `agents`(`id`) ON DELETE SET NULL ON UPDATE CASCADE'],

        ['house_stock', 'fk_house_stock_house_id', 'FOREIGN KEY (`house_id`) REFERENCES `houses`(`id`) ON DELETE CASCADE ON UPDATE CASCADE'],
        ['house_stock', 'fk_house_stock_product_id', 'FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE ON UPDATE CASCADE'],

        ['agent_stock', 'fk_agent_stock_agent_id', 'FOREIGN KEY (`agent_id`) REFERENCES `agents`(`id`) ON DELETE CASCADE ON UPDATE CASCADE'],
        ['agent_stock', 'fk_agent_stock_house_id', 'FOREIGN KEY (`house_id`) REFERENCES `houses`(`id`) ON DELETE CASCADE ON UPDATE CASCADE'],
        ['agent_stock', 'fk_agent_stock_product_id', 'FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE ON UPDATE CASCADE'],

        ['stock_movements', 'fk_stock_movements_house_id', 'FOREIGN KEY (`house_id`) REFERENCES `houses`(`id`) ON DELETE CASCADE ON UPDATE CASCADE'],
        ['stock_movements', 'fk_stock_movements_product_id', 'FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE ON UPDATE CASCADE'],

        ['sales', 'fk_sales_house_id', 'FOREIGN KEY (`house_id`) REFERENCES `houses`(`id`) ON DELETE CASCADE ON UPDATE CASCADE'],
        ['sales', 'fk_sales_agent_id', 'FOREIGN KEY (`agent_id`) REFERENCES `agents`(`id`) ON DELETE CASCADE ON UPDATE CASCADE'],

        ['sale_items', 'fk_sale_items_sale_id', 'FOREIGN KEY (`sale_id`) REFERENCES `sales`(`id`) ON DELETE CASCADE ON UPDATE CASCADE'],
        ['sale_items', 'fk_sale_items_product_id', 'FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE ON UPDATE CASCADE'],

        ['exchange_rate', 'fk_exchange_rate_house_id', 'FOREIGN KEY (`house_id`) REFERENCES `houses`(`id`) ON DELETE CASCADE ON UPDATE CASCADE'],

        ['ticket_print_logs', 'fk_ticket_logs_house_id', 'FOREIGN KEY (`house_id`) REFERENCES `houses`(`id`) ON DELETE CASCADE ON UPDATE CASCADE'],
        ['ticket_print_logs', 'fk_ticket_logs_agent_id', 'FOREIGN KEY (`agent_id`) REFERENCES `agents`(`id`) ON DELETE CASCADE ON UPDATE CASCADE'],

        ['house_delete_requests', 'fk_house_delete_requests_house_id', 'FOREIGN KEY (`house_id`) REFERENCES `houses`(`id`) ON DELETE CASCADE ON UPDATE CASCADE'],
    ];

    foreach ($pairs as [$table, $constraint, $fragment]) {
        if (di_add_fk_if_missing($pdo, $table, $constraint, $fragment)) {
            $applied[] = $constraint;
        }
    }

    return $applied;
}

