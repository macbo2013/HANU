INSERT INTO `hanu_settings` (`name`,`value`) VALUES
('version_label','V8 第一代公测版'),
('support_email','qm66668888@qq.com')
ON DUPLICATE KEY UPDATE `value`=VALUES(`value`);
