<?php
require_once __DIR__ . '/bootstrap.php';

function hanu_db_name(): string {
    return (string)cfg('db_name');
}

function hanu_table_raw(string $name): string {
    return cfg('table_prefix', 'hanu_') . $name;
}

function hanu_table_exists(string $rawTable): bool {
    $r = q_one("SELECT COUNT(*) c FROM information_schema.TABLES WHERE TABLE_SCHEMA=? AND TABLE_NAME=?", [hanu_db_name(), $rawTable]);
    return ((int)($r['c'] ?? 0)) > 0;
}

function hanu_column_exists(string $rawTable, string $column): bool {
    $r = q_one("SELECT COUNT(*) c FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=? AND TABLE_NAME=? AND COLUMN_NAME=?", [hanu_db_name(), $rawTable, $column]);
    return ((int)($r['c'] ?? 0)) > 0;
}

function hanu_index_exists(string $rawTable, string $index): bool {
    $r = q_one("SELECT COUNT(*) c FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=? AND TABLE_NAME=? AND INDEX_NAME=?", [hanu_db_name(), $rawTable, $index]);
    return ((int)($r['c'] ?? 0)) > 0;
}

function hanu_ensure_migration_table(): void {
    $table = hanu_table_raw('schema_migrations');
    db()->exec("CREATE TABLE IF NOT EXISTS `{$table}` (
      `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
      `migration` VARCHAR(180) NOT NULL,
      `executed_at` INT UNSIGNED NOT NULL,
      PRIMARY KEY (`id`),
      UNIQUE KEY `uk_migration` (`migration`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function hanu_migration_done(string $name): bool {
    hanu_ensure_migration_table();
    $r = q_one("SELECT id FROM " . table_name('schema_migrations') . " WHERE migration=?", [$name]);
    return (bool)$r;
}

function hanu_mark_migration_done(string $name): void {
    hanu_ensure_migration_table();
    q_exec("INSERT IGNORE INTO " . table_name('schema_migrations') . "(migration,executed_at) VALUES(?,?)", [$name, now_ts()]);
}

function hanu_add_column_if_missing(string $rawTable, string $column, string $definition): void {
    if (!hanu_column_exists($rawTable, $column)) {
        db()->exec("ALTER TABLE `{$rawTable}` ADD COLUMN `{$column}` {$definition}");
    }
}

function hanu_run_builtin_migrations(): array {
    hanu_ensure_migration_table();
    $ran = [];

    $migration = '2026_06_06_000001_update_center';
    if (!hanu_migration_done($migration)) {
        $settings = hanu_table_raw('settings');
        if (hanu_table_exists($settings)) {
            q_exec("INSERT INTO " . table_name('settings') . "(name,value) VALUES(?,?) ON DUPLICATE KEY UPDATE value=VALUES(value)", ['app_version', '1.0.1-beta.3']);
            q_exec("INSERT INTO " . table_name('settings') . "(name,value) VALUES(?,?) ON DUPLICATE KEY UPDATE value=VALUES(value)", ['update_repo', 'macbo2013/HANU']);
            q_exec("INSERT INTO " . table_name('settings') . "(name,value) VALUES(?,?) ON DUPLICATE KEY UPDATE value=VALUES(value)", ['update_branch', 'main']);
        }
        hanu_mark_migration_done($migration);
        $ran[] = $migration;
    }

    $migration = '2026_06_06_000002_compat_v6_v8_columns';
    if (!hanu_migration_done($migration)) {
        $users = hanu_table_raw('users');
        if (hanu_table_exists($users)) {
            hanu_add_column_if_missing($users, 'avatar_path', "VARCHAR(255) DEFAULT NULL");
            hanu_add_column_if_missing($users, 'points', "INT UNSIGNED NOT NULL DEFAULT 0");
            hanu_add_column_if_missing($users, 'level', "INT UNSIGNED NOT NULL DEFAULT 1");
            hanu_add_column_if_missing($users, 'current_title_id', "INT UNSIGNED DEFAULT NULL");
            hanu_add_column_if_missing($users, 'waf_level', "INT UNSIGNED NOT NULL DEFAULT 0");
        }

        $posts = hanu_table_raw('posts');
        if (hanu_table_exists($posts)) {
            hanu_add_column_if_missing($posts, 'media_type', "VARCHAR(20) DEFAULT NULL");
            hanu_add_column_if_missing($posts, 'media_path', "VARCHAR(255) DEFAULT NULL");
        }

        hanu_mark_migration_done($migration);
        $ran[] = $migration;
    }

    $migration = '2026_06_06_000003_create_new_feature_tables';
    if (!hanu_migration_done($migration)) {
        $p = cfg('table_prefix', 'hanu_');

        db()->exec("CREATE TABLE IF NOT EXISTS `{$p}titles` (
          `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
          `name` VARCHAR(80) NOT NULL,
          `color` VARCHAR(20) NOT NULL DEFAULT '#3b82f6',
          `min_points` INT UNSIGNED NOT NULL DEFAULT 0,
          `is_active` TINYINT(1) NOT NULL DEFAULT 1,
          `created_at` INT UNSIGNED NOT NULL,
          PRIMARY KEY (`id`),
          KEY `idx_points` (`min_points`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        db()->exec("CREATE TABLE IF NOT EXISTS `{$p}checkins` (
          `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
          `user_id` INT UNSIGNED NOT NULL,
          `check_date` DATE NOT NULL,
          `points` INT UNSIGNED NOT NULL DEFAULT 10,
          `streak` INT UNSIGNED NOT NULL DEFAULT 1,
          `created_at` INT UNSIGNED NOT NULL,
          PRIMARY KEY (`id`),
          UNIQUE KEY `uk_user_date` (`user_id`,`check_date`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        db()->exec("CREATE TABLE IF NOT EXISTS `{$p}point_logs` (
          `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
          `user_id` INT UNSIGNED NOT NULL,
          `points` INT NOT NULL,
          `reason` VARCHAR(120) NOT NULL,
          `created_at` INT UNSIGNED NOT NULL,
          PRIMARY KEY (`id`),
          KEY `idx_user` (`user_id`,`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        db()->exec("CREATE TABLE IF NOT EXISTS `{$p}groups` (
          `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
          `group_no` VARCHAR(32) NOT NULL,
          `name` VARCHAR(80) NOT NULL,
          `description` VARCHAR(255) DEFAULT NULL,
          `owner_id` INT UNSIGNED NOT NULL,
          `created_at` INT UNSIGNED NOT NULL,
          `updated_at` INT UNSIGNED NOT NULL,
          PRIMARY KEY (`id`),
          UNIQUE KEY `uk_group_no` (`group_no`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        db()->exec("CREATE TABLE IF NOT EXISTS `{$p}group_members` (
          `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
          `group_id` INT UNSIGNED NOT NULL,
          `user_id` INT UNSIGNED NOT NULL,
          `role` ENUM('owner','admin','member') NOT NULL DEFAULT 'member',
          `joined_at` INT UNSIGNED NOT NULL,
          PRIMARY KEY (`id`),
          UNIQUE KEY `uk_group_user` (`group_id`,`user_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        db()->exec("CREATE TABLE IF NOT EXISTS `{$p}group_messages` (
          `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
          `group_id` INT UNSIGNED NOT NULL,
          `user_id` INT UNSIGNED NOT NULL,
          `content` TEXT NOT NULL,
          `created_at` INT UNSIGNED NOT NULL,
          PRIMARY KEY (`id`),
          KEY `idx_group_time` (`group_id`,`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        db()->exec("CREATE TABLE IF NOT EXISTS `{$p}waf_blocks` (
          `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
          `user_id` INT UNSIGNED DEFAULT NULL,
          `waf_log_id` INT UNSIGNED DEFAULT NULL,
          `penalty_level` INT UNSIGNED NOT NULL DEFAULT 0,
          `ban_seconds` INT UNSIGNED DEFAULT NULL,
          `message` VARCHAR(255) NOT NULL,
          `created_at` INT UNSIGNED NOT NULL,
          PRIMARY KEY (`id`),
          KEY `idx_user_time` (`user_id`,`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        db()->exec("CREATE TABLE IF NOT EXISTS `{$p}outbound_logs` (
          `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
          `user_id` INT UNSIGNED DEFAULT NULL,
          `url` TEXT NOT NULL,
          `source_type` VARCHAR(32) DEFAULT NULL,
          `source_id` INT UNSIGNED DEFAULT NULL,
          `action` VARCHAR(32) NOT NULL DEFAULT 'opened',
          `ip` VARCHAR(64) DEFAULT NULL,
          `user_agent` VARCHAR(255) DEFAULT NULL,
          `created_at` INT UNSIGNED NOT NULL,
          PRIMARY KEY (`id`),
          KEY `idx_time` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        db()->exec("CREATE TABLE IF NOT EXISTS `{$p}waf_logs` (
          `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
          `user_id` INT UNSIGNED DEFAULT NULL,
          `ip` VARCHAR(64) DEFAULT NULL,
          `rule` VARCHAR(120) NOT NULL,
          `content` TEXT,
          `created_at` INT UNSIGNED NOT NULL,
          PRIMARY KEY (`id`),
          KEY `idx_time` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        hanu_mark_migration_done($migration);
        $ran[] = $migration;
    }


    $migration = '2026_06_06_000004_v9_public_beta_announcement';
    if (!hanu_migration_done($migration)) {
        $settings = hanu_table_raw('settings');
        if (hanu_table_exists($settings)) {
            q_exec("INSERT INTO " . table_name('settings') . "(name,value) VALUES(?,?) ON DUPLICATE KEY UPDATE value=VALUES(value)", ['app_version', '1.0.1-beta.3']);
            q_exec("INSERT INTO " . table_name('settings') . "(name,value) VALUES(?,?) ON DUPLICATE KEY UPDATE value=VALUES(value)", ['version_label', 'V9 第二代公测版']);
            q_exec("INSERT INTO " . table_name('settings') . "(name,value) VALUES(?,?) ON DUPLICATE KEY UPDATE value=VALUES(value)", ['site_announcement', '欢迎使用 HANU V9 第二代公测版，感谢参与公测。']);
        }
        hanu_mark_migration_done($migration);
        $ran[] = $migration;
    }


    $migration = '2026_06_06_000005_v9_admin_update_safe';
    if (!hanu_migration_done($migration)) {
        $settings = hanu_table_raw('settings');
        if (hanu_table_exists($settings)) {
            q_exec("INSERT INTO " . table_name('settings') . "(name,value) VALUES(?,?) ON DUPLICATE KEY UPDATE value=VALUES(value)", ['app_version', '1.0.1-beta.3']);
            q_exec("INSERT INTO " . table_name('settings') . "(name,value) VALUES(?,?) ON DUPLICATE KEY UPDATE value=VALUES(value)", ['version_label', 'V9 第二代公测版']);
            q_exec("INSERT INTO " . table_name('settings') . "(name,value) VALUES(?,?) ON DUPLICATE KEY UPDATE value=VALUES(value)", ['update_notice_admin_only', '1']);
            q_exec("INSERT INTO " . table_name('settings') . "(name,value) VALUES(?,?) ON DUPLICATE KEY UPDATE value=VALUES(value)", ['update_force', '0']);
        }
        hanu_mark_migration_done($migration);
        $ran[] = $migration;
    }


    $migration = '2026_06_06_000006_v9_group_password_and_hotfix';
    if (!hanu_migration_done($migration)) {
        $groups = hanu_table_raw('groups');
        if (hanu_table_exists($groups)) {
            hanu_add_column_if_missing($groups, 'password_hash', "VARCHAR(255) DEFAULT NULL");
            hanu_add_column_if_missing($groups, 'join_mode', "VARCHAR(20) NOT NULL DEFAULT 'open'");
        }
        $settings = hanu_table_raw('settings');
        if (hanu_table_exists($settings)) {
            q_exec("INSERT INTO " . table_name('settings') . "(name,value) VALUES(?,?) ON DUPLICATE KEY UPDATE value=VALUES(value)", ['app_version', '1.0.1-beta.3']);
            q_exec("INSERT INTO " . table_name('settings') . "(name,value) VALUES(?,?) ON DUPLICATE KEY UPDATE value=VALUES(value)", ['version_label', 'HANU V9 第二代公测版 修复版']);
        }
        hanu_mark_migration_done($migration);
        $ran[] = $migration;
    }

    return $ran;
}
