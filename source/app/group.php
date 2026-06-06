<?php
require_once __DIR__ . '/../includes/layout.php';
$u=shell('groups');
$id=(int)($_GET['id']??0);
$g=q_one("SELECT * FROM ".table_name('groups')." WHERE id=?",[$id]);
if(!$g || !group_member($id,(int)$u['id'])){ shell_mid(); echo '<h1>无法进入群聊</h1><div class="card">群聊不存在或你还不是成员。</div>'; shell_end(); exit; }
$members=q_all("SELECT u.username,u.avatar_text FROM ".table_name('group_members')." gm JOIN ".table_name('users')." u ON u.id=gm.user_id WHERE gm.group_id=? LIMIT 50",[$id]);
foreach($members as $m) row_item('group.php?id='.$id,$m['avatar_text'],$m['username'],'群成员');
shell_mid();
?>
<h1><?=h($g['name'])?></h1>
<p class="muted">群号 <?=h($g['group_no'])?> · <?=h($g['description'])?></p>
<div id="msgs" class="msgs"></div>
<div class="composer"><input id="msgInput" placeholder="输入群消息"><button class="btn" onclick="sendGroupMessage('<?=h($id)?>')">发送</button></div>
<script>
addEventListener('load',()=>{loadGroupMessages('<?=h($id)?>');setInterval(()=>loadGroupMessages('<?=h($id)?>'),5000);document.getElementById('msgInput').addEventListener('keydown',e=>{if(e.key==='Enter')sendGroupMessage('<?=h($id)?>')})})
</script>
<?php shell_end(); ?>
