<?php
require_once __DIR__ . '/../includes/layout.php';
$u = shell('messages');
$target=(int)($_GET['target']??0);
row_item('messages.php','公','公共消息','所有成员可见');
$friends=q_all("SELECT f.*,CASE WHEN f.user1_id=? THEN u2.id ELSE u1.id END fid,CASE WHEN f.user1_id=? THEN u2.username ELSE u1.username END username,CASE WHEN f.user1_id=? THEN u2.avatar_text ELSE u1.avatar_text END avatar FROM ".table_name('friendships')." f JOIN ".table_name('users')." u1 ON u1.id=f.user1_id JOIN ".table_name('users')." u2 ON u2.id=f.user2_id WHERE f.user1_id=? OR f.user2_id=?",[$u['id'],$u['id'],$u['id'],$u['id'],$u['id']]);
foreach($friends as $f) row_item('messages.php?target='.$f['fid'],$f['avatar'],$f['username'],'好友私信');
shell_mid();
$title=$target?'好友私信':'公共消息';
?>
<h1><?=h($title)?></h1>
<div id="msgs" class="msgs"></div>
<div class="composer"><input id="msgInput" placeholder="<?=h(t('send'))?>"><button class="btn" onclick="sendMessage('<?=h($target)?>')"><?=h(t('send'))?></button></div>
<script>
addEventListener('load',()=>{loadMessages('<?=h($target)?>');setInterval(()=>loadMessages('<?=h($target)?>'),5000);document.getElementById('msgInput').addEventListener('keydown',e=>{if(e.key==='Enter')sendMessage('<?=h($target)?>')})})
</script>
<?php shell_end(); ?>
