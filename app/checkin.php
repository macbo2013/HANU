<?php
require_once __DIR__ . '/../includes/layout.php';
$u=shell('checkin');
$msg='';
$pn=point_name();
$today=date('Y-m-d');
$checked=(bool)q_one("SELECT id FROM ".table_name('checkins')." WHERE user_id=? AND check_date=?",[$u['id'],$today]);

if($_SERVER['REQUEST_METHOD']==='POST' && !$checked){
    $yesterday=date('Y-m-d',time()-86400);
    $prev=q_one("SELECT streak FROM ".table_name('checkins')." WHERE user_id=? AND check_date=?",[$u['id'],$yesterday]);
    $streak=$prev?((int)$prev['streak']+1):1;
    $points=10+min(20,$streak-1);
    q_exec("INSERT INTO ".table_name('checkins')."(user_id,check_date,points,streak,created_at) VALUES(?,?,?,?,?)",[$u['id'],$today,$points,$streak,now_ts()]);
    add_points((int)$u['id'],$points,'每日签到');
    $msg='签到成功，获得 '.$points.' '.$pn.'，连续 '.$streak.' 天。';
    $checked=true;
    $u=current_user();
}
$recent=q_all("SELECT * FROM ".table_name('checkins')." WHERE user_id=? ORDER BY check_date DESC LIMIT 15",[$u['id']]);
shell_mid();
?>
<h1>签到</h1>
<?php if($msg):?><div class="card ok"><?=h($msg)?></div><?php endif;?>
<div class="stat">
  <div class="card"><b><?=h($u['points']??0)?></b><span class="muted"><?=h($pn)?></span></div>
  <div class="card"><b>Lv.<?=h($u['level']??1)?></b><span class="muted">等级</span></div>
  <div class="card"><b><?=$checked?'已签到':'未签到'?></b><span class="muted">今日</span></div>
</div>
<form class="card" method="post" action="checkin.php">
  <h2>每日签到</h2>
  <p class="muted">基础 10 <?=h($pn)?>，连续签到每天额外 +1，最多额外 +20。</p>
  <button class="btn" <?=$checked?'disabled':''?>><?=$checked?'今天已签到':'立即签到'?></button>
</form>
<h2>最近签到</h2>
<?php foreach($recent as $r):?><div class="card"><b><?=h($r['check_date'])?></b><span class="muted">+<?=h($r['points'])?> <?=h($pn)?> · 连续 <?=h($r['streak'])?> 天</span></div><?php endforeach;?>
<?php shell_end(); ?>
