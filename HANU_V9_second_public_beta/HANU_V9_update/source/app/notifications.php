<?php
require_once __DIR__ . '/../includes/layout.php';
$u=shell('notifications');
$rows=q_all("SELECT * FROM ".table_name('notifications')." WHERE user_id=? ORDER BY created_at DESC LIMIT 100",[$u['id']]);
foreach($rows as $r) row_item('notifications.php','通',$r['title'],date('m/d H:i',$r['created_at']));
shell_mid();
echo '<h1>'.h(t('notifications')).'</h1>';
foreach($rows as $r) echo '<div class="card"><h3>'.h($r['title']).'</h3><p>'.h($r['content']).'</p><span class="muted">'.date('m/d H:i',$r['created_at']).'</span></div>';
if(!$rows) echo '<div class="card">暂无通知</div>';
shell_end();
