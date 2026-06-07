<?php
require_once __DIR__ . '/../includes/layout.php';
$u = shell('home');
row_item('messages.php','公',t('messages'),'公共消息');
$boards = q_all("SELECT * FROM ".table_name('boards')." ORDER BY sort_order ASC");
foreach($boards as $b) row_item('boards.php?id='.$b['id'],'板',$b['name'],$b['description']);
$groups=q_all("SELECT g.* FROM ".table_name('groups')." g JOIN ".table_name('group_members')." gm ON gm.group_id=g.id WHERE gm.user_id=? ORDER BY g.updated_at DESC LIMIT 10",[$u['id']]);
foreach($groups as $g) row_item('group.php?id='.$g['id'],'群',$g['name'],'群号 '.$g['group_no']);
shell_mid();
$today=date('Y-m-d');
$checked=(bool)q_one("SELECT id FROM ".table_name('checkins')." WHERE user_id=? AND check_date=?",[$u['id'],$today]);
?>
<h1><?=h(app_name())?></h1>
<p class="muted"><?=h(cfg('site_desc','<?=h(app_name())?>'))?></p>
<div class="stat">
  <div class="card"><b><?=h($u['points']??0)?></b><span class="muted"><?=h(point_name())?></span></div>
  <div class="card"><b>Lv.<?=h($u['level']??1)?></b><span class="muted">等级</span></div>
  <div class="card"><b><?=$checked?'已签到':'未签到'?></b><span class="muted">今日状态</span></div>
</div>
<div class="grid">
<a class="card" href="checkin.php"><h2>签到领积分</h2><p class="muted">每日签到，连续签到有额外奖励。</p></a>
<a class="card" href="titles.php"><h2>称号中心</h2><p class="muted">用<?=h(point_name())?>解锁并佩戴称号。</p></a>
<a class="card" href="red_packets.php"><h2><?=h(point_name())?>红包</h2><p class="muted">发红包、抢红包，查看领取记录。</p></a>
<a class="card" href="groups.php"><h2>群聊</h2><p class="muted">创建群聊，邀请成员交流。</p></a>
<a class="card" href="posts.php"><h2><?=h(t('feed'))?></h2><p class="muted">动态、图片、视频、外链安全跳转。</p></a>
<a class="card" href="friends.php"><h2><?=h(t('friends'))?></h2><p class="muted">好友申请和私信。</p></a>
<a class="card" href="boards.php"><h2><?=h(t('boards'))?></h2><p class="muted">完整显示板块内容。</p></a>
</div>
<?php shell_end(); ?>
