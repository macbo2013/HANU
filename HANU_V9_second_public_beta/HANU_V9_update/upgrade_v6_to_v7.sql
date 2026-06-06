ALTER TABLE `hanu_users` ADD COLUMN `waf_level` INT UNSIGNED NOT NULL DEFAULT 0;
CREATE TABLE IF NOT EXISTS `hanu_waf_blocks` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED DEFAULT NULL,
  `waf_log_id` INT UNSIGNED DEFAULT NULL,
  `penalty_level` INT UNSIGNED NOT NULL DEFAULT 0,
  `ban_seconds` INT UNSIGNED DEFAULT NULL,
  `message` VARCHAR(255) NOT NULL,
  `created_at` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user_time` (`user_id`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
