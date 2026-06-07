<?php
require_once __DIR__ . '/../includes/layout.php';
$u=shell('titles');
$msg='';
$pn=point_name();

if($_SERVER['REQUEST_METHOD']==='POST'){
    $tid=(int)($_POST['title_id']??0);
    $t=q_one("SELECT * FROM ".table_name('titles')." WHERE id=? AND is_active=1",[$tid]);
    if($t && (int)$u['points'] >= (int)$t['min_points']){
        q_exec("UPDATE ".table_name('users')." SET current_title_id=?,updated_at=? WHERE id=?",[$tid,now_ts(),$u['id']]);
        $msg='称号已佩戴';
        $u=current_user();
    }else $msg=$pn.'不足，暂时不能佩戴该称号';
}
$titles=q_all("SELECT * FROM ".table_name('titles')." WHERE is_active=1 ORDER BY min_points ASC,id ASC");
foreach($titles as $t) row_item('titles.php','称',$t['name'],'需要 '.$t['min_points'].' '.$pn);
shell_mid();
?>
<h1>称号中心</h1>
<?php if($msg):?><div class="card"><?=h($msg)?></div><?php endif;?>
<p class="muted">你的<?=h($pn)?>：<?=h($u['points']??0)?>。管理员可以在后台自定义称号。</p>
<div class="grid">
<?php foreach($titles as $t): $ok=(int)$u['points'] >= (int)$t['min_points']; ?>
<div class="card">
  <span class="title-badge" style="--title:<?=h($t['color'])?>"><?=h($t['name'])?></span>
  <p class="muted">需要 <?=h($t['min_points'])?> <?=h($pn)?> · <?=$ok?'已解锁':'未解锁'?></p>
  <form method="post" action="titles.php"><input type="hidden" name="title_id" value="<?=h($t['id'])?>"><button class="btn" <?=$ok?'':'disabled'?>>佩戴</button></form>
</div>
<?php endforeach; ?>
</div>
<?php shell_end(); ?>
