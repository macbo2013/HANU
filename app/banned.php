<?php
require_once __DIR__ . '/../includes/layout.php';

$u = current_user();

if (!$u) {
    redirect_to('login.php');
}

$reason = $u['ban_reason'] ?? '账号触发安全策略';
$until = $u['ban_until'] ?? null;
$timeText = $until ? date('Y-m-d H:i:s', (int)$until) : '永久';

page_head(t('ban_notice'), $u);
?>
<div class="auth">
  <div class="card glass" style="width:min(760px,100%);padding:28px">
    <section class="ban-hero">
      <h1><?=h(t('ban_notice'))?></h1>
      <p>账号已被系统限制访问。</p>
    </section>

    <div class="card"><b>原因</b><p class="muted"><?=h($reason)?></p></div>
    <div class="card"><b>解封时间</b><p class="muted"><?=h($timeText)?></p></div>
    <div class="card feedback-box">
      <div><b>反馈渠道</b><p class="muted">如需反馈，请联系：<?=h(support_email())?></p></div>
      <a class="btn ghost" href="mailto:<?=h(support_email())?>">发送邮件</a>
    </div>
    <a class="btn" href="logout.php">退出登录</a>
  </div>
</div>
<?php page_end(); ?>
