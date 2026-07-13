<?php

$host = '127.0.0.1';
$port = 3306;
$user = 'root';
$pass = '';
$database = 'onyx_db_fixed';
$dump = __DIR__ . '/../legacy_app/u963586588_Business.sql';

$pdo = new PDO(
    "mysql:host={$host};port={$port}",
    $user,
    $pass,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$pdo->exec("CREATE DATABASE IF NOT EXISTS `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$pdo->exec("USE `{$database}`");
$pdo->exec('SET FOREIGN_KEY_CHECKS=0');

$sql = file_get_contents($dump);
$sql = preg_replace('/^--.*$/m', '', $sql);
$sql = preg_replace('/\/\*!.*?\*\//s', '', $sql);

foreach (array_filter(array_map('trim', explode(';', $sql))) as $statement) {
    if ($statement === '' || str_starts_with($statement, 'START TRANSACTION') || $statement === 'COMMIT') {
        continue;
    }

    $pdo->exec($statement);
}

$pdo->exec('SET FOREIGN_KEY_CHECKS=1');

$pdo->exec("ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `email_verified_at` timestamp NULL DEFAULT NULL AFTER `email`");
$pdo->exec("ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `remember_token` varchar(100) NULL DEFAULT NULL AFTER `two_factor_secret`");
$pdo->exec("ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `updated_at` timestamp NULL DEFAULT NULL AFTER `created_at`");

$pdo->exec("CREATE TABLE IF NOT EXISTS `password_reset_tokens` (
    `email` varchar(255) NOT NULL,
    `token` varchar(255) NOT NULL,
    `created_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

$pdo->exec("CREATE TABLE IF NOT EXISTS `sessions` (
    `id` varchar(255) NOT NULL,
    `user_id` bigint unsigned NULL,
    `ip_address` varchar(45) NULL,
    `user_agent` text NULL,
    `payload` longtext NOT NULL,
    `last_activity` int NOT NULL,
    PRIMARY KEY (`id`),
    KEY `sessions_user_id_index` (`user_id`),
    KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

$pdo->exec("CREATE TABLE IF NOT EXISTS `cache` (
    `key` varchar(255) NOT NULL,
    `value` mediumtext NOT NULL,
    `expiration` bigint NOT NULL,
    PRIMARY KEY (`key`),
    KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

$pdo->exec("CREATE TABLE IF NOT EXISTS `cache_locks` (
    `key` varchar(255) NOT NULL,
    `owner` varchar(255) NOT NULL,
    `expiration` bigint NOT NULL,
    PRIMARY KEY (`key`),
    KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

$pdo->exec("CREATE TABLE IF NOT EXISTS `jobs` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `queue` varchar(255) NOT NULL,
    `payload` longtext NOT NULL,
    `attempts` smallint unsigned NOT NULL,
    `reserved_at` int unsigned NULL,
    `available_at` int unsigned NOT NULL,
    `created_at` int unsigned NOT NULL,
    PRIMARY KEY (`id`),
    KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

$pdo->exec("CREATE TABLE IF NOT EXISTS `job_batches` (
    `id` varchar(255) NOT NULL,
    `name` varchar(255) NOT NULL,
    `total_jobs` int NOT NULL,
    `pending_jobs` int NOT NULL,
    `failed_jobs` int NOT NULL,
    `failed_job_ids` longtext NOT NULL,
    `options` mediumtext NULL,
    `cancelled_at` int NULL,
    `created_at` int NOT NULL,
    `finished_at` int NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

$pdo->exec("CREATE TABLE IF NOT EXISTS `failed_jobs` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `uuid` varchar(255) NOT NULL,
    `connection` varchar(255) NOT NULL,
    `queue` varchar(255) NOT NULL,
    `payload` longtext NOT NULL,
    `exception` longtext NOT NULL,
    `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`id`),
    UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`),
    KEY `failed_jobs_connection_queue_failed_at_index` (`connection`, `queue`, `failed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

$pdo->exec("CREATE TABLE IF NOT EXISTS `migrations` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `migration` varchar(255) NOT NULL,
    `batch` int NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

$migrations = [
    '0001_01_01_000000_create_users_table',
    '0001_01_01_000001_create_cache_table',
    '0001_01_01_000002_create_jobs_table',
];

$insertMigration = $pdo->prepare('INSERT IGNORE INTO `migrations` (`migration`, `batch`) VALUES (?, 1)');
foreach ($migrations as $migration) {
    $insertMigration->execute([$migration]);
}

$tenant = $pdo->prepare("INSERT INTO `tenants`
    (`company_name`, `slug`, `currency`, `fiscal_year_start`, `status`, `created_at`, `updated_at`)
    VALUES ('Clinic Test', 'clinic-test', 'UGX', '2026-01-01', 'trial', NOW(), NOW())
    ON DUPLICATE KEY UPDATE `company_name` = VALUES(`company_name`)");
$tenant->execute();

$tenantId = (int) $pdo->query("SELECT `id` FROM `tenants` WHERE `slug` = 'clinic-test' LIMIT 1")->fetchColumn();
$hash = password_hash('password', PASSWORD_BCRYPT);

$userExists = $pdo->prepare("SELECT `id` FROM `users` WHERE `email` = 'superadmin@clinic.test' LIMIT 1");
$userExists->execute();

if (! $userExists->fetchColumn()) {
    $insertUser = $pdo->prepare("INSERT INTO `users`
        (`tenant_id`, `name`, `email`, `email_verified_at`, `password`, `role`, `is_active`, `created_at`, `updated_at`)
        VALUES (?, 'Super Admin', 'superadmin@clinic.test', NOW(), ?, 'super_admin', 1, NOW(), NOW())");
    $insertUser->execute([$tenantId, $hash]);
}

echo "Fixed database is ready: {$database}" . PHP_EOL;
echo "Login: superadmin@clinic.test / password" . PHP_EOL;
