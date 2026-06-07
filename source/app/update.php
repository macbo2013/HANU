<?php
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/update.php';
require_once __DIR__ . '/../includes/migrations.php';

$u = require_admin();
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['act'] ?? '';

    if ($act === 'save_repo') {
        set_site_setting('update_repo', clean($_POST['update_repo'] ?? hanu_update_repo(), 120));
        set_site_setting('update_branch', clean($_POST['update_branch'] ?? hanu_update_branch(), 80));
        $msg = '更新源设置已保存。';
    } elseif ($act === 'migrate') {
        try {
            $ran = hanu_run_builtin_migrations();
            $msg = $ran ? '数据库升级完成：' . implode(', ', $ran) : '数据库已经是最新结构。';
        } catch (Throwable $e) {
            $msg = '数据库升级失败：' . $e->getMessage();
        }
    }
}

$check = hanu_check_latest_version();

shell('update');
row_item('admin.php', '管', '管理后台', '返回管理面板');
row_item('update.php', '更', '更新中心', '检测版本和升级数据库');
shell_mid();
?>
<h1>更新中心</h1>

<div class="card">
  <b>管理员专用</b>
  <p class="muted">只有管理员可以访问更新中心。更新不是强制更新，系统只提醒新版本，不会自动更新；是否更新由管理员决定。</p>
</div>

<?php if($msg): ?><div class="card"><?=h($msg)?></div><?php endif; ?>

<div class="grid">
  <div class="card"><h2>当前版本</h2><p class="muted"><?=h(hanu_current_version())?></p></div>
  <div class="card">
    <h2>最新版本</h2>
    <?php if($check['ok']): ?>
      <p class="muted"><?=h($check['latest'])?> · <?=$check['has_update'] ? '发现新版本' : '已经是最新'?></p>
    <?php else: ?>
      <p class="muted"><?=h($check['error'] ?? '暂时无法连接 GitHub')?></p>
    <?php endif; ?>
  </div>
</div>

<?php if(!empty($check['ok']) && !empty($check['has_update'])): ?>
<div class="card">
  <h2>发现新版本</h2>
  <p class="muted">这不是强制更新。请先备份网站和数据库，再在服务器运行 update.sh。更新会保留 config、data、ICO，不会清空用户数据。</p>
  <pre><code>cd <?=h(HANU_ROOT)?>
sudo bash update.sh</code></pre>
  <?php if(!empty($check['release_url'])): ?><a class="btn ghost" href="<?=h($check['release_url'])?>" target="_blank">查看 GitHub 发布页</a><?php endif; ?>
</div>
<?php endif; ?>

<div class="card">
  <h2>数据库兼容升级</h2>
  <p class="muted">这个按钮只会补充缺失的数据表和字段，不会删除旧表，不会清空旧数据。</p>
  <form method="post" action="update.php">
    <input type="hidden" name="act" value="migrate">
    <button class="btn">运行数据库兼容升级</button>
  </form>
</div>

<form class="card" method="post" action="update.php">
  <input type="hidden" name="act" value="save_repo">
  <h2>更新源设置</h2>
  <div class="field"><label>GitHub 仓库</label><input name="update_repo" value="<?=h(hanu_update_repo())?>"></div>
  <div class="field"><label>分支</label><input name="update_branch" value="<?=h(hanu_update_branch())?>"></div>
  <button class="btn">保存更新源设置</button>
</form>

<div class="card">
  <h2>保留数据更新规则</h2>
  <p class="muted">更新代码时必须保留这些目录和文件：</p>
  <pre><code>config/config.php
data/
ICO/</code></pre>
  <p class="muted">新增功能需要数据库结构时，通过迁移系统创建新表/新字段，兼容旧数据库。</p>
</div>

<?php if(!empty($check['ok']) && !empty($check['notes'])): ?>
<div class="card"><h2>版本说明</h2><pre><?=h($check['notes'])?></pre></div>
<?php endif; ?>

<?php shell_end(); ?>
