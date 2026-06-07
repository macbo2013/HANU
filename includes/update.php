<?php
require_once __DIR__ . '/auth.php';

if (!function_exists('hanu_current_version')) {
function hanu_current_version(): string {
    $file = HANU_ROOT . '/VERSION';
    if (file_exists($file)) return trim((string)file_get_contents($file));
    return (string)site_setting('app_version', cfg('app_version', '0.0.0'));
}
}


function hanu_update_repo(): string {
    return (string)site_setting('update_repo', cfg('update_repo', 'macbo2013/HANU'));
}

function hanu_update_branch(): string {
    return (string)site_setting('update_branch', cfg('update_branch', 'main'));
}

function hanu_http_get_json(string $url): ?array {
    $body = null;
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTPHEADER => [
                'User-Agent: HANU-Updater',
                'Accept: application/vnd.github+json, application/json'
            ],
        ]);
        $body = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);
        if ($body === false || $code >= 400) $body = null;
    } elseif (ini_get('allow_url_fopen')) {
        $ctx = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 15,
                'header' => "User-Agent: HANU-Updater\r\nAccept: application/json\r\n"
            ]
        ]);
        $body = @file_get_contents($url, false, $ctx);
    }
    if (!$body) return null;
    $json = json_decode($body, true);
    return is_array($json) ? $json : null;
}

function hanu_normalize_version(string $v): string {
    $v = trim($v);
    $v = preg_replace('/^[vV]/', '', $v);
    return $v ?: '0.0.0';
}

function hanu_check_latest_version(): array {
    $repo = hanu_update_repo();
    $branch = hanu_update_branch();
    $current = hanu_current_version();

    $latest = null;
    $source = null;
    $notes = '';
    $releaseUrl = 'https://github.com/' . $repo . '/releases';

    $release = hanu_http_get_json('https://api.github.com/repos/' . $repo . '/releases/latest');
    if ($release && !empty($release['tag_name'])) {
        $latest = hanu_normalize_version((string)$release['tag_name']);
        $source = 'github_release';
        $notes = (string)($release['body'] ?? '');
        $releaseUrl = (string)($release['html_url'] ?? $releaseUrl);
    }

    if (!$latest) {
        $meta = hanu_http_get_json('https://raw.githubusercontent.com/' . $repo . '/' . $branch . '/VERSION.json');
        if ($meta && !empty($meta['version'])) {
            $latest = hanu_normalize_version((string)$meta['version']);
            $source = 'version_json';
            $notes = is_array($meta['notes'] ?? null) ? implode("\n", $meta['notes']) : (string)($meta['notes'] ?? '');
            $releaseUrl = (string)($meta['release_url'] ?? $releaseUrl);
        }
    }

    if (!$latest) {
        return [
            'ok' => false,
            'error' => '无法连接 GitHub 或没有找到版本信息。',
            'current' => $current,
            'latest' => null,
            'has_update' => false
        ];
    }

    return [
        'ok' => true,
        'current' => hanu_normalize_version($current),
        'latest' => $latest,
        'has_update' => version_compare(hanu_normalize_version($current), $latest, '<'),
        'source' => $source,
        'notes' => $notes,
        'release_url' => $releaseUrl,
        'repo' => $repo,
        'branch' => $branch
    ];
}


if (!function_exists('hanu_rrmdir')) {
function hanu_rrmdir(string $dir): void {
    if (!is_dir($dir)) return;
    $items = scandir($dir);
    if (!$items) return;
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        $path = $dir . '/' . $item;
        if (is_dir($path) && !is_link($path)) hanu_rrmdir($path);
        else @unlink($path);
    }
    @rmdir($dir);
}
}

if (!function_exists('hanu_copy_dir_safe')) {
function hanu_copy_dir_safe(string $src, string $dst, array $excludeTop = []): void {
    if (!is_dir($src)) throw new RuntimeException('源码目录不存在：' . $src);
    if (!is_dir($dst)) mkdir($dst, 0777, true);

    $items = scandir($src);
    if (!$items) return;

    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        if (in_array($item, $excludeTop, true)) continue;

        $from = $src . '/' . $item;
        $to = $dst . '/' . $item;

        if (is_dir($from) && !is_link($from)) {
            if (!is_dir($to)) mkdir($to, 0777, true);
            hanu_copy_dir_safe($from, $to, []);
        } else {
            copy($from, $to);
        }
    }
}
}

if (!function_exists('hanu_zip_extract_root')) {
function hanu_zip_extract_root(string $zipFile, string $targetDir): string {
    if (!class_exists('ZipArchive')) {
        throw new RuntimeException('服务器缺少 ZipArchive 扩展，无法执行网页一键更新。请安装 php-zip，或使用 update.sh。');
    }

    $zip = new ZipArchive();
    if ($zip->open($zipFile) !== true) {
        throw new RuntimeException('无法打开更新包。');
    }

    $zip->extractTo($targetDir);
    $zip->close();

    $dirs = array_values(array_filter(glob($targetDir . '/*'), 'is_dir'));
    if (!$dirs) throw new RuntimeException('更新包解压后没有找到源码目录。');

    return $dirs[0];
}
}

if (!function_exists('hanu_download_file')) {
function hanu_download_file(string $url, string $saveTo): void {
    $ok = false;

    if (function_exists('curl_init')) {
        $fp = fopen($saveTo, 'w');
        if (!$fp) throw new RuntimeException('无法写入临时更新文件。');

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_FILE => $fp,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 120,
            CURLOPT_HTTPHEADER => ['User-Agent: HANU-Web-Updater']
        ]);
        $ok = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        fclose($fp);

        if (!$ok || $code >= 400) {
            @unlink($saveTo);
            throw new RuntimeException('下载更新包失败：' . ($err ?: ('HTTP ' . $code)));
        }
        return;
    }

    if (ini_get('allow_url_fopen')) {
        $ctx = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 120,
                'header' => "User-Agent: HANU-Web-Updater\r\n"
            ]
        ]);
        $data = @file_get_contents($url, false, $ctx);
        if ($data === false) throw new RuntimeException('下载更新包失败。');
        file_put_contents($saveTo, $data);
        return;
    }

    throw new RuntimeException('服务器既没有 curl，也没有开启 allow_url_fopen，无法网页下载更新包。');
}
}

if (!function_exists('hanu_web_update_run')) {
function hanu_web_update_run(): array {
    $repo = hanu_update_repo();
    $branch = hanu_update_branch();

    if (!preg_match('/^[A-Za-z0-9_.-]+\/[A-Za-z0-9_.-]+$/', $repo)) {
        throw new RuntimeException('GitHub 仓库格式不正确，应为 owner/repo。');
    }
    if (!preg_match('/^[A-Za-z0-9_.\/-]+$/', $branch)) {
        throw new RuntimeException('分支名称不安全。');
    }

    $root = HANU_ROOT;
    $time = date('Ymd_His');
    $backupRoot = dirname($root) . '/hanu_web_backups';
    $tmpRoot = sys_get_temp_dir() . '/hanu_web_update_' . $time . '_' . bin2hex(random_bytes(3));
    $zipFile = $tmpRoot . '/source.zip';

    if (!is_dir($backupRoot)) mkdir($backupRoot, 0777, true);
    if (!is_dir($tmpRoot)) mkdir($tmpRoot, 0777, true);

    $backupDir = $backupRoot . '/hanu_files_' . $time;
    mkdir($backupDir, 0777, true);

    hanu_copy_dir_safe($root, $backupDir, ['data/cache']);

    $zipUrl = 'https://github.com/' . $repo . '/archive/refs/heads/' . rawurlencode($branch) . '.zip';
    hanu_download_file($zipUrl, $zipFile);

    $extractedRoot = hanu_zip_extract_root($zipFile, $tmpRoot . '/extract');

    $sourceRoot = is_dir($extractedRoot . '/source') ? $extractedRoot . '/source' : $extractedRoot;

    hanu_copy_dir_safe($sourceRoot, $root, [
        'config',
        'data',
        'ICO',
        '.git',
        '.github'
    ]);

    if (!is_dir($root . '/config')) mkdir($root . '/config', 0777, true);
    if (!is_dir($root . '/data')) mkdir($root . '/data', 0777, true);
    if (!is_dir($root . '/ICO')) mkdir($root . '/ICO', 0777, true);

    $ran = [];
    if (function_exists('hanu_run_builtin_migrations')) {
        $ran = hanu_run_builtin_migrations();
    }

    hanu_rrmdir($tmpRoot);

    return [
        'backup' => $backupDir,
        'repo' => $repo,
        'branch' => $branch,
        'migrations' => $ran,
        'message' => '网页一键更新完成'
    ];
}
}
