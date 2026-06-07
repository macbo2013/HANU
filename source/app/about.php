<?php
require_once __DIR__ . '/../includes/layout.php';
$u=shell('about');
row_item('home.php','首','返回首页','查看动态和快捷入口');
row_item('update.php','更','更新中心','管理员可检测版本更新');
shell_mid();
?>
<h1><?=h(app_name())?></h1>
<div class="card">
  <h2><?=h(version_label())?></h2>
  <p class="muted">这是 HANU 的第二代公测版。当前版本主要用于测试更新中心、迁移系统、站点公告和公测反馈流程。</p>
</div>
<div class="grid">
  <div class="card"><h2>版本号</h2><p class="muted"><?=h(hanu_current_version())?></p></div>
  <div class="card"><h2>反馈邮箱</h2><p class="muted"><?=h(support_email())?></p></div>
</div>
<div class="card">
  <h2>更新说明</h2>
  <p class="muted">V9 新增站点公告、关于页面，并同步升级版本文件与数据库迁移系统。更新时会继续保留 config、data、ICO。</p>
</div>
<?php shell_end(); ?>
