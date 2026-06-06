<?php
if (PHP_SAPI !== 'cli') {
    echo "CLI only\n";
    exit(1);
}
require_once __DIR__ . '/../includes/migrations.php';

try {
    $ran = hanu_run_builtin_migrations();
    echo "HANU database migration finished.\n";
    if (!$ran) {
        echo "No new migrations.\n";
    } else {
        foreach ($ran as $m) echo "Migrated: {$m}\n";
    }
    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR, "Migration failed: " . $e->getMessage() . "\n");
    exit(1);
}
