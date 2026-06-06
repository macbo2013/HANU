<?php
require_once __DIR__ . '/../includes/layout.php';
$u=shell('security');
$logs=q_all("SELECT * FROM ".table_name('waf_logs')." WHERE user_id=? ORDER BY created_at DESC LIMIT 20",[$u['id']]);
$blocks=q_all("SELECT * FROM ".table_name('waf_blocks')." WHERE user_id=? ORDER BY created_at DESC LIMIT 10",[$u['id']]);
foreach($logs as $l) row_item('security.php','安',$l['rule'],date('m/d H:i',$l['created_at']));
shell_mid();
?>
<h1><?=h(t('security_center'))?></h1>
<div class="grid">
  <div class="card"><b><?=h(t('current_status'))?></b><p class="muted"><?=$u['is_banned']?'受限':'正常'?></p></div>
  <div class="card"><b><?=h(t('waf_counter'))?></b><p class="muted"><?=count($logs)?> 条近期记录</p></div>
</div>
<div class="card">
  <h2>公测反馈</h2>
  <p class="muted">如果你在公测中遇到误封、页面异常或功能问题，可以反馈到：<?=h(support_email())?></p>
  <a class="btn ghost" href="mailto:<?=h(support_email())?>">发送反馈</a>
</div>
<h2>我的安全记录</h2>
<?php foreach($logs as $l):?>
<div class="card"><b><?=h($l['rule'])?></b><p class="muted"><?=date('Y-m-d H:i:s',$l['created_at'])?> · <?=h($l['ip'])?></p><p><?=h(text_cut($l['content'],0,160))?></p></div>
<?php endforeach; if(!$logs)echo '<div class="card">暂无安全记录</div>';?>
<h2>处罚记录</h2>
<?php foreach($blocks as $b):?><div class="card"><b><?=h($b['message'])?></b><p class="muted"><?=date('Y-m-d H:i:s',$b['created_at'])?></p></div><?php endforeach; if(!$blocks)echo '<div class="card">暂无处罚记录</div>';?>
<?php shell_end(); ?>
