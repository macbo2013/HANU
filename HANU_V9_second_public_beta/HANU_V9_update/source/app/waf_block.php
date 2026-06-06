<?php
require_once __DIR__ . '/../includes/layout.php';
$u=current_user();
$id=(int)($_GET['id']??0);
$block=null;
if($id){
    $block=q_one("SELECT b.*,w.rule,w.content,w.ip FROM ".table_name('waf_blocks')." b LEFT JOIN ".table_name('waf_logs')." w ON w.id=b.waf_log_id WHERE b.id=?",[$id]);
}
page_head(t('waf_blocked'),$u);
?>
<div class="auth">
  <div class="card glass" style="width:min(720px,100%);padding:30px">
    <div class="logo">!</div>
    <h1><?=h(t('waf_blocked'))?></h1><p class="muted">当前页面：/app/waf_block.php</p>
    <p class="muted">本次提交包含可能影响站点安全的内容，已经被系统拦截并记录。</p>
    <?php if($block): ?>
      <div class="card">
        <b><?=h(t('waf_reason'))?>：<?=h($block['rule'] ?? '安全规则')?></b>
        <p class="muted"><?=h($block['message'])?></p>
      </div>
      <div class="card">
        <b><?=h(t('waf_policy'))?></b>
        <p class="muted">每日 5 次触发会封禁 1 小时；后续违规会依次升级为 1 周、1 月、1 年，最终永久封禁。</p>
      </div>
    <?php endif; ?>
    <a class="btn" href="home.php"><?=h(t('waf_back_home'))?></a>
  </div>
</div>
<?php page_end(); ?>
