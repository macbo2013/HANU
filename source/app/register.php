<?php
require_once __DIR__ . '/../includes/layout.php';
if (current_user()) redirect_to('home.php');
$err='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $name=clean($_POST['username']??'',50); $pass=(string)($_POST['password']??'');
    if($name===''||$pass==='') $err='请输入用户名和密码';
    elseif(q_one("SELECT id FROM ".table_name('users')." WHERE username=?",[$name])) $err='用户名已存在';
    else{
        $g=q_one("SELECT id FROM ".table_name('user_groups')." ORDER BY id ASC LIMIT 1");
        q_exec("INSERT INTO ".table_name('users')."(username,password_hash,avatar_text,group_id,created_at,updated_at) VALUES(?,?,?,?,?,?)",[$name,password_hash($pass,PASSWORD_DEFAULT),text_cut($name,0,2),$g['id']??null,now_ts(),now_ts()]);
        redirect_to('login.php');
    }
}
page_head(t('register'));
?>
<div class="auth"><form class="card glass" method="post" action="register.php" style="width:min(460px,100%);padding:30px">
<div class="logo">HU</div><h1><?=h(t('register'))?> <?=h(app_name())?></h1>
<div class="field"><input name="username" placeholder="<?=h(t('username'))?>"></div>
<div class="field"><input name="password" type="password" placeholder="<?=h(t('password'))?>"></div>
<?php if($err):?><p class="bad"><?=h($err)?></p><?php endif;?>
<button class="btn"><?=h(t('register'))?></button> <a class="btn ghost" href="login.php"><?=h(t('login'))?></a>
</form></div><?php page_end(); ?>
