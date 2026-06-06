<?php
require_once __DIR__ . '/../includes/layout.php';
$u=shell('groups');
$msg='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    $act=$_POST['act']??'';
    if($act==='create'){
        $name=clean($_POST['name']??'',80);
        $desc=clean($_POST['description']??'',255);
        if($name==='') $msg='请输入群聊名称';
        else{
            $no=strtoupper(substr(bin2hex(random_bytes(4)),0,8));
            q_exec("INSERT INTO ".table_name('groups')."(group_no,name,description,owner_id,created_at,updated_at) VALUES(?,?,?,?,?,?)",[$no,$name,$desc,$u['id'],now_ts(),now_ts()]);
            $gid=(int)db()->lastInsertId();
            q_exec("INSERT INTO ".table_name('group_members')."(group_id,user_id,role,joined_at) VALUES(?,?,?,?)",[$gid,$u['id'],'owner',now_ts()]);
            redirect_to('group.php?id='.$gid);
        }
    }elseif($act==='join'){
        $no=strtoupper(clean($_POST['group_no']??'',32));
        $g=q_one("SELECT * FROM ".table_name('groups')." WHERE group_no=?",[$no]);
        if(!$g)$msg='群聊不存在';
        else{
            q_exec("INSERT IGNORE INTO ".table_name('group_members')."(group_id,user_id,role,joined_at) VALUES(?,?,?,?)",[$g['id'],$u['id'],'member',now_ts()]);
            redirect_to('group.php?id='.$g['id']);
        }
    }
}
$groups=q_all("SELECT g.* FROM ".table_name('groups')." g JOIN ".table_name('group_members')." gm ON gm.group_id=g.id WHERE gm.user_id=? ORDER BY g.updated_at DESC",[$u['id']]);
foreach($groups as $g) row_item('group.php?id='.$g['id'],'群',$g['name'],'群号 '.$g['group_no']);
shell_mid();
?>
<h1>群聊</h1>
<?php if($msg):?><div class="card bad"><?=h($msg)?></div><?php endif;?>
<div class="grid">
<form class="card" method="post" action="groups.php">
<input type="hidden" name="act" value="create"><h2>创建群聊</h2>
<div class="field"><input name="name" placeholder="群聊名称"></div>
<div class="field"><input name="description" placeholder="群聊介绍"></div>
<button class="btn">创建</button>
</form>
<form class="card" method="post" action="groups.php">
<input type="hidden" name="act" value="join"><h2>加入群聊</h2>
<div class="field"><input name="group_no" placeholder="输入群号"></div>
<button class="btn">加入</button>
</form>
</div>
<h2>我的群聊</h2>
<?php foreach($groups as $g):?><a class="card" href="group.php?id=<?=h($g['id'])?>"><h3><?=h($g['name'])?></h3><p class="muted">群号 <?=h($g['group_no'])?> · <?=h($g['description'])?></p></a><?php endforeach;?>
<?php shell_end(); ?>
