<?php
if (!file_exists(__DIR__ . '/data/install.lock') || !file_exists(__DIR__ . '/config/config.php')) {
    header('Location: index.php');
    exit;
}
header('Location: app/home.php');
exit;
