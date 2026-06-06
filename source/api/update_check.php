<?php
require_once __DIR__ . '/../includes/update.php';
$u = require_admin();
json_out(hanu_check_latest_version());
