<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$app = require $root . '/bootstrap/app.php';

/** @var \PDO $pdo */
$pdo = $app['database']->connect();
/** @var \PDO $secondConnection */
$secondConnection = $app['database']->connect();

$checks = [];

$checks['pdo_connection'] = (bool) $pdo->query('SELECT 1')->fetchColumn();
$checks['database_name'] = $pdo->query('SELECT DATABASE()')->fetchColumn() === 'restomanager';
$checks['server_version'] = (string) $pdo->query('SELECT VERSION()')->fetchColumn();

$tables = $pdo->query(
    "SELECT TABLE_NAME, ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() ORDER BY TABLE_NAME"
)->fetchAll();
$checks['eight_core_tables'] = count($tables) === 8;
$checks['all_innodb'] = count(array_filter($tables, static fn (array $row): bool => $row['ENGINE'] === 'InnoDB')) === 8;

$foreignKeys = (int) $pdo->query(
    "SELECT COUNT(*) FROM information_schema.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE()"
)->fetchColumn();
$checks['foreign_keys'] = $foreignKeys >= 7;

$checks['seed_users'] = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role IN ('admin', 'staff')")->fetchColumn() === 2;
$checks['seed_tables'] = (int) $pdo->query('SELECT COUNT(*) FROM dining_tables')->fetchColumn() === 6;
$checks['seed_categories'] = (int) $pdo->query('SELECT COUNT(*) FROM categories')->fetchColumn() === 3;
$checks['seed_menu'] = (int) $pdo->query('SELECT COUNT(*) FROM menu_items')->fetchColumn() === 10;

$seedHashes = $pdo->query("SELECT email, password_hash FROM users WHERE role IN ('admin', 'staff')")->fetchAll();
$seedHashes = array_column($seedHashes, 'password_hash', 'email');
$checks['seed_password_hashes'] = password_verify('Admin123!', $seedHashes['admin@restomanager.local'] ?? '')
    && password_verify('Staff123!', $seedHashes['staff@restomanager.local'] ?? '');

$activeVisitIndexes = (int) $pdo->query(
    "SELECT COUNT(DISTINCT INDEX_NAME) FROM information_schema.STATISTICS
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME = 'visits'
       AND NON_UNIQUE = 0
       AND INDEX_NAME IN ('uq_visits_active_user', 'uq_visits_active_table')"
)->fetchColumn();
$checks['active_visit_unique_indexes'] = $activeVisitIndexes === 2;

$temporaryCategory = 'TEMP-ROLLBACK-' . bin2hex(random_bytes(4));
$pdo->beginTransaction();
$insert = $pdo->prepare('INSERT INTO categories (name, is_active) VALUES (:name, 1)');
$insert->execute(['name' => $temporaryCategory]);
$pdo->rollBack();
$find = $pdo->prepare('SELECT COUNT(*) FROM categories WHERE name = :name');
$find->execute(['name' => $temporaryCategory]);
$checks['transaction_rollback'] = (int) $find->fetchColumn() === 0;

$foreignKeyRejected = false;
$pdo->beginTransaction();
try {
    $statement = $pdo->prepare(
        "INSERT INTO visits (visit_code, user_id, table_id, customer_name_snapshot, status) VALUES (:code, 999999999, 999999999, 'Uji FK', 'active')"
    );
    $statement->execute(['code' => 'FK-' . bin2hex(random_bytes(6))]);
} catch (\PDOException) {
    $foreignKeyRejected = true;
} finally {
    $pdo->rollBack();
}
$checks['foreign_key_enforced'] = $foreignKeyRejected;

$rowLockBlocked = false;
$pdo->beginTransaction();
$pdo->query('SELECT id FROM dining_tables WHERE id = 1 FOR UPDATE')->fetchColumn();
$secondConnection->exec('SET innodb_lock_wait_timeout = 1');
$secondConnection->beginTransaction();
try {
    $secondConnection->exec("UPDATE dining_tables SET table_code = table_code WHERE id = 1");
} catch (\PDOException $exception) {
    $rowLockBlocked = str_contains($exception->getMessage(), 'Lock wait timeout');
} finally {
    if ($secondConnection->inTransaction()) {
        $secondConnection->rollBack();
    }
    $pdo->rollBack();
}
$checks['row_locking'] = $rowLockBlocked;

$failed = false;
foreach ($checks as $name => $result) {
    $passed = $name === 'server_version' ? $result !== '' : $result === true;
    printf("[%s] %s%s\n", $passed ? 'PASS' : 'FAIL', $name, $name === 'server_version' ? ': ' . $result : '');
    $failed = $failed || !$passed;
}

exit($failed ? 1 : 0);
