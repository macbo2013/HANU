<?php
require_once __DIR__ . '/../includes/update.php';

$u = require_admin();

$check = hanu_check_latest_version();
$check['admin_only'] = true;
$check['forced'] = false;
$check['message'] = $check['has_update'] ?? false
    ? '检测到新版本，可在合适时间手动更新。'
    : '当前已经是最新版本。';

json_out($check);
