<?php
require_once __DIR__ . '/../includes/layout.php';
$u=shell('profile');
$msg='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    try{
        $theme=clean($_POST['theme']??'blue',30);
        $themes=['blue','green','pink','dark','sunset','cyber','mint','grape','gold','graphite'];
        if(!in_array($theme,$themes,true))$theme='blue';
        $avatarPath=$u['avatar_path'] ?? null;
        $uploaded=upload_file('avatar_file',['jpg','jpeg','png','gif','webp'],5*1024*1024,'avatars');
        if($uploaded){
            $avatarPath=resize_image_file($uploaded,512,512,true);
        }
        q_exec("UPDATE ".table_name('users')." SET avatar_text=?,avatar_path=?,signature=?,theme=?,updated_at=? WHERE id=?",[clean($_POST['avatar_text']??'HU',8),$avatarPath,clean($_POST['signature']??'',180),$theme,now_ts(),$u['id']]);
        redirect_to('profile.php');
    }catch(Throwable $e){$msg=$e->getMessage();}
}
shell_mid();
$themeList=[
 ['blue','蓝色'],['green','绿色'],['pink','粉色'],['dark','深色'],['sunset','日落'],['cyber','赛博'],['mint','薄荷'],['grape','葡萄'],['gold','金色'],['graphite','石墨']
];
?>
<h1><?=h(t('profile'))?></h1>
<?php if($msg):?><div class="card bad"><?=h($msg)?></div><?php endif;?>
<div class="card">
  <?=avatar_html($u,'av')?>
  <h2><?=h($u['username'])?></h2><?=title_badge($u)?>
  <p class="muted">ID <?=h($u['id'])?> · Lv.<?=h($u['level']??1)?> · <?=h($u['points']??0)?>积分 · <?=h($u['group_name']??'')?></p>
  <p><?=h($u['signature'])?></p>
</div>
<form class="card" method="post" action="profile.php" enctype="multipart/form-data">
<h2>头像、资料与主题</h2>
<div class="field"><label>头像图片，系统会自动裁切成正方形</label><input type="file" name="avatar_file" accept="image/*"></div>
<div class="field"><label>头像文字</label><input name="avatar_text" value="<?=h($u['avatar_text'])?>"></div>
<div class="field"><label>签名</label><input name="signature" value="<?=h($u['signature'])?>"></div>
<div class="field"><label>选择主题</label><div class="theme-grid">
<?php foreach($themeList as $t): ?>
<label class="card" style="margin:0"><input type="radio" name="theme" value="<?=h($t[0])?>" <?=$u['theme']===$t[0]?'checked':''?>> <?=h($t[1])?></label>
<?php endforeach; ?>
</div></div>
<button class="btn">保存</button> <a class="btn ghost" href="logout.php"><?=h(t('logout'))?></a>
</form>
<?php shell_end(); ?>
