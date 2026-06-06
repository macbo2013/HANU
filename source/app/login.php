<?php
require_once __DIR__ . '/../includes/layout.php';
if (current_user()) redirect_to('home.php');
$err='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $u = q_one("SELECT * FROM " . table_name('users') . " WHERE username=?", [clean($_POST['username'] ?? '', 50)]);
    if (!$u || !password_verify((string)($_POST['password'] ?? ''), $u['password_hash'])) $err='用户名或密码错误';
    else { set_login((int)$u['id']); redirect_to('home.php'); }
}
page_head(t('login'));
?>
<div class="auth"><form class="card glass" method="post" action="login.php" style="width:min(460px,100%);padding:30px">
<div class="logo">HU</div><h1><?=h(t('login'))?> <?=h(app_name())?></h1>
<div class="field"><input name="username" placeholder="<?=h(t('username'))?>"></div>
<div class="field"><input name="password" type="password" placeholder="<?=h(t('password'))?>"></div>
<?php if($err):?><p class="bad"><?=h($err)?></p><?php endif;?>
<button class="btn"><?=h(t('login'))?></button> <a class="btn ghost" href="register.php"><?=h(t('register'))?></a>
</form></div><?php page_end(); ?>
