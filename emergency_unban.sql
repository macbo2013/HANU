UPDATE `hanu_users`
SET `is_banned`=0,
    `ban_reason`=NULL,
    `ban_until`=NULL,
    `waf_level`=0,
    `updated_at`=UNIX_TIMESTAMP()
WHERE `username`='admin';

DELETE FROM `hanu_waf_logs`
WHERE `user_id`=(SELECT `id` FROM `hanu_users` WHERE `username`='admin' LIMIT 1);

DELETE FROM `hanu_waf_blocks`
WHERE `user_id`=(SELECT `id` FROM `hanu_users` WHERE `username`='admin' LIMIT 1);
