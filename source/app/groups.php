<?php
require_once __DIR__ . '/../includes/layout.php';
$u=shell('groups');
$msg='';

if($_SERVER['REQUEST_METHOD']==='POST'){
    $act=$_POST['act']??'';

    if($act==='create'){
        $name=clean($_POST['name']??'',80);
        $desc=clean($_POST['description']??'',255);
        $password=(string)($_POST['group_password']??'');

        if($name===''){
            $msg='请输入群聊名称';
        }else{
            $no=strtoupper(substr(bin2hex(random_bytes(4)),0,8));
            $hash=$password!=='' ? password_hash($password,PASSWORD_DEFAULT) : null;
            $joinMode=$password!=='' ? 'password' : 'open';

            q_exec("INSERT INTO ".table_name('groups')."(group_no,name,description,password_hash,join_mode,owner_id,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?)",[$no,$name,$desc,$hash,$joinMode,$u['id'],now_ts(),now_ts()]);
            $gid=(int)db()->lastInsertId();
            q_exec("INSERT INTO ".table_name('group_members')."(group_id,user_id,role,joined_at) VALUES(?,?,?,?)",[$gid,$u['id'],'owner',now_ts()]);
            redirect_to('group.php?id='.$gid);
        }

    }elseif($act==='join'){
        $no=strtoupper(clean($_POST['group_no']??'',32));
        $password=(string)($_POST['group_password']??'');
        $g=q_one("SELECT * FROM ".table_name('groups')." WHERE group_no=?",[$no]);

        if(!$g){
            $msg='群聊不存在';
        }elseif(!empty($g['password_hash']) && !password_verify($password,$g['password_hash'])){
            $msg='群聊密码错误';
        }else{
            q_exec("INSERT IGNORE INTO ".table_name('group_members')."(group_id,user_id,role,joined_at) VALUES(?,?,?,?)",[$g['id'],$u['id'],'member',now_ts()]);
            redirect_to('group.php?id='.$g['id']);
        }
    }
}

$groups=q_all("SELECT g.* FROM ".table_name('groups')." g JOIN ".table_name('group_members')." gm ON gm.group_id=g.id WHERE gm.user_id=? ORDER BY g.updated_at DESC",[$u['id']]);
foreach($groups as $g) row_item('group.php?id='.$g['id'],'群',$g['name'],'群号 '.$g['group_no'].(!empty($g['password_hash'])?' · 密码群':''));
shell_mid();
?>
<h1>群聊</h1>
<?php if($msg):?><div class="card bad"><?=h($msg)?></div><?php endif;?>

<div class="grid">
<form class="card" method="post" action="groups.php">
<input type="hidden" name="act" value="create">
<h2>创建群聊</h2>
<div class="field"><input name="name" placeholder="群聊名称"></div>
<div class="field"><input name="description" placeholder="群聊介绍"></div>
<div class="field"><input name="group_password" type="password" placeholder="群聊密码，可留空"></div>
<p class="muted">设置密码后，其他用户需要输入密码才能加入。</p>
<button class="btn">创建</button>
</form>

<form class="card" method="post" action="groups.php">
<input type="hidden" name="act" value="join">
<h2>加入群聊</h2>
<div class="field"><input name="group_no" placeholder="输入群号"></div>
<div class="field"><input name="group_password" type="password" placeholder="群聊密码，没有可留空"></div>
<button class="btn">加入</button>
</form>
</div>

<h2>我的群聊</h2>
<?php foreach($groups as $g):?>
<a class="card" href="group.php?id=<?=h($g['id'])?>">
  <h3><?=h($g['name'])?></h3>
  <p class="muted">群号 <?=h($g['group_no'])?><?=!empty($g['password_hash'])?' · 密码群':''?> · <?=h($g['description'])?></p>
</a>
<?php endforeach;?>
<?php shell_end(); ?>
