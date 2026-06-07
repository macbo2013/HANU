<?php
require_once __DIR__ . '/../includes/layout.php';

if (file_exists(__DIR__ . '/../includes/update.php')) {
    require_once __DIR__ . '/../includes/update.php';
}

if (!function_exists('hanu_current_version')) {
    function hanu_current_version(): string {
        $file = HANU_ROOT . '/VERSION';
        if (file_exists($file)) {
            return trim((string)file_get_contents($file));
        }
        if (function_exists('site_setting')) {
            return (string)site_setting('app_version', cfg('app_version', 'unknown'));
        }
        return (string)cfg('app_version', 'unknown');
    }
}

$u = shell('about');

row_item('home.php', '首', '返回首页', '查看动态和快捷入口');

if (is_admin($u)) {
    row_item('update.php', '更', '更新中心', '管理员可检测版本更新');
}

shell_mid();
?>
<h1><?=h(app_name())?></h1>

<div class="card">
  <h2><?=h(version_label())?></h2>
  <p class="muted">这是 HANU 的公测版本。当前页面用于展示系统版本、反馈渠道和更新信息。</p>
</div>

<div class="grid">
  <div class="card">
    <h2>版本号</h2>
    <p class="muted"><?=h(hanu_current_version())?></p>
  </div>

  <div class="card">
    <h2>反馈邮箱</h2>
    <p class="muted"><?=h(support_email())?></p>
  </div>
</div>

<div class="card">
  <h2>说明</h2>
  <p class="muted">如果后台提示有新版本，只有管理员可以进入更新中心查看。更新不是强制更新，站主可以自行决定更新时间。</p>
</div>

<?php shell_end(); ?>
