<?php
require_once __DIR__ . '/auth.php';

function page_head(string $title = 'HANU', ?array $u = null): void {
    $theme = $u['theme'] ?? cfg('default_theme', 'blue');
    echo '<!doctype html><html lang="zh-CN"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
    echo '<title>' . h($title) . ' · ' . h(app_name()) . '</title>';
    echo '<link rel="icon" href="../ICO/favicon.ico">';
    echo '<link rel="stylesheet" href="../assets/css/app.css">';
    echo '</head><body data-theme="' . h($theme) . '"><div class="orb one"></div><div class="orb two"></div>';
}
function avatar_html(array $u, string $class = 'av'): string {
    if (!empty($u['avatar_path'])) return '<div class="' . h($class) . ' img"><img src="../' . h($u['avatar_path']) . '" alt=""></div>';
    return '<div class="' . h($class) . '">' . h($u['avatar_text'] ?? 'HU') . '</div>';
}
function title_badge(?array $u): string {
    if (!$u || empty($u['title_name'])) return '';
    $color = preg_match('/^#[0-9a-fA-F]{6}$/', (string)$u['title_color']) ? $u['title_color'] : '#3b82f6';
    return '<span class="title-badge" style="--title:' . h($color) . '">' . h($u['title_name']) . '</span>';
}
function page_end(): void { echo '<script src="../assets/js/app.js"></script></body></html>'; }
function shell(string $active): array {
    $u = require_user();
    page_head($active, $u);
    echo '<main class="app"><aside class="side glass">';
    echo '<div class="me" onclick="location.href=\'profile.php\'">' . avatar_html($u, 'av') . '<div><b>' . h($u['username']) . '</b>' . title_badge($u) . '<span>ID ' . h($u['id']) . ' · Lv.' . h($u['level'] ?? 1) . ' · ' . h($u['points'] ?? 0) . '积分</span></div></div>';
    echo '<div class="site-head"><b>' . h(app_name()) . '</b><span>' . h(version_label()) . '</span></div>'; echo '<nav class="nav">';
    $items = [
      'home'=>t('home'), 'messages'=>t('messages'), 'groups'=>'群聊',
      'friends'=>t('friends'), 'posts'=>t('feed'), 'boards'=>t('boards'),
      'checkin'=>'签到', 'titles'=>'称号', 'security'=>'安全', 'about'=>'关于', 'notifications'=>t('notifications'), 'profile'=>t('profile')
    ];
    foreach ($items as $key=>$label) echo '<a class="' . ($active===$key?'on':'') . '" href="' . $key . '.php">' . h($label) . '</a>';
    if (is_admin($u)) { echo '<a class="' . ($active==='admin'?'on':'') . '" href="admin.php">' . h(t('admin')) . '</a>'; echo '<a class="' . ($active==='update'?'on':'') . '" href="update.php">更新</a>'; }
    echo '</nav><div class="list">';
    return $u;
}
function shell_mid(): void { echo '</div></aside><section class="main glass">'; }
function shell_end(): void { echo '</section></main>'; page_end(); }
function row_item(string $href, string $avatar, string $title, string $sub = ''): void {
    echo '<a class="row" href="' . h($href) . '"><div class="av sm">' . h($avatar) . '</div><div class="row-copy"><b>' . h($title) . '</b><span>' . h($sub) . '</span></div></a>';
}
