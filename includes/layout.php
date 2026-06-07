<?php
require_once __DIR__ . '/auth.php';

function page_head(string $title='HANU', ?array $u=null): void {
    $theme = 'blue';
    if ($u && !empty($u['theme'])) {
        $theme = (string)$u['theme'];
    }

    echo '<!doctype html><html lang="zh-CN"><head>';
    echo '<meta charset="utf-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<title>' . h($title) . ' · ' . h(app_name()) . '</title>';
    echo '<link rel="icon" href="../ICO/favicon.ico">';
    echo '<link rel="stylesheet" href="../assets/css/app.css">';
    echo '<script>window.HANU_IS_ADMIN=' . (($u && is_admin($u)) ? 'true' : 'false') . ';</script>';
    echo '</head><body data-theme="' . h($theme) . '">';
    echo '<div class="orb one"></div><div class="orb two"></div>';
}

function page_end(): void {
    echo '<script src="../assets/js/app.js"></script>';
    echo '</body></html>';
}

function row_item(string $href, string $avatar, string $title, string $sub=''): void {
    echo '<a class="row" href="' . h($href) . '">';
    echo '<span class="avatar">' . h($avatar) . '</span>';
    echo '<span class="row-main"><b>' . h($title) . '</b>';
    if ($sub !== '') {
        echo '<small>' . h($sub) . '</small>';
    }
    echo '</span>';
    echo '</a>';
}

function shell(string $active='home'): array {
    $u = require_user();
    page_head(app_name(), $u);

    echo '<div class="app">';
    echo '<aside class="side glass">';

    echo '<div class="me">';
    if (!empty($u['avatar_path'])) {
        echo '<img class="avatar-img" src="../' . h($u['avatar_path']) . '" alt="">';
    } else {
        echo '<span class="avatar big">' . h($u['avatar_text'] ?? 'HU') . '</span>';
    }
    echo '<div>';
    echo '<b>' . h($u['username']) . '</b>';
    echo '<small>ID ' . h($u['id']) . ' · ' . h($u['points'] ?? 0) . h(function_exists("point_name") ? point_name() : "积分") . '</small>';
    echo '</div>';
    echo '</div>';

    echo '<div class="site-head"><b>' . h(app_name()) . '</b><span>' . h(version_label()) . '</span></div>';

    $nav = [
        'home' => ['首页', 'home.php'],
        'messages' => ['消息', 'messages.php'],
        'friends' => ['好友', 'friends.php'],
        'groups' => ['群聊', 'groups.php'],
        'boards' => ['板块', 'boards.php'],
        'posts' => ['动态', 'posts.php'],
        'checkin' => ['签到', 'checkin.php'],
        'titles' => ['称号', 'titles.php'],
        'red_packets' => ['红包', 'red_packets.php'],
        'status' => ['状态', 'status.php'],
        'security' => ['安全', 'security.php'],
        'about' => ['关于', 'about.php'],
        'notifications' => ['通知', 'notifications.php'],
        'profile' => ['我的', 'profile.php'],
    ];

    echo '<nav class="nav">';
    foreach ($nav as $key => $item) {
        [$label, $href] = $item;
        echo '<a class="' . ($active === $key ? 'on' : '') . '" href="' . h($href) . '">' . h($label) . '</a>';
    }

    if (is_admin($u)) {
        echo '<a class="' . ($active === 'admin' ? 'on' : '') . '" href="admin.php">管理</a>';
        echo '<a class="' . ($active === 'update' ? 'on' : '') . '" href="update.php">更新</a>';
    }

    echo '<a href="logout.php">退出</a>';
    echo '</nav>';

    echo '</aside>';
    echo '<section class="list glass">';

    return $u;
}

function shell_mid(): void {
    echo '</section><main class="main">';
}

function shell_end(): void {
    echo '</main></div>';
    page_end();
}
