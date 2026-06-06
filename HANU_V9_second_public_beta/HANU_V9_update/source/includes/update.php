<?php
require_once __DIR__ . '/auth.php';

function hanu_current_version(): string {
    $file = HANU_ROOT . '/VERSION';
    if (file_exists($file)) return trim((string)file_get_contents($file));
    return (string)site_setting('app_version', cfg('app_version', '0.0.0'));
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
