<?php
require_once __DIR__ . '/../includes/layout.php';
$u=shell('friends'); $msg='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    $act=$_POST['act']??'';
    if($act==='add'){
        $q=clean($_POST['q']??'',50);
        $t=ctype_digit($q)?q_one("SELECT * FROM ".table_name('users')." WHERE id=?",[(int)$q]):q_one("SELECT * FROM ".table_name('users')." WHERE username=?",[$q]);
        if(!$t)$msg='找不到用户';
        elseif((int)$t['id']===(int)$u['id'])$msg='不能添加自己';
        elseif(are_friends((int)$u['id'],(int)$t['id']))$msg='已经是好友';
        else{
            q_exec("INSERT INTO ".table_name('friend_requests')."(from_user_id,to_user_id,message,status,created_at) VALUES(?,?,?,?,?) ON DUPLICATE KEY UPDATE status='pending',message=VALUES(message),created_at=VALUES(created_at)",[$u['id'],$t['id'],clean($_POST['message']??'',200),'pending',now_ts()]);
            notice_user((int)$t['id'],'好友申请',$u['username'].' 想添加你为好友','friend');
            $msg='好友申请已发送';
        }
    }elseif($act==='accept'){
        $r=q_one("SELECT * FROM ".table_name('friend_requests')." WHERE id=? AND to_user_id=? AND status='pending'",[(int)$_POST['id'],$u['id']]);
        if($r){
            q_exec("UPDATE ".table_name('friend_requests')." SET status='accepted',handled_at=? WHERE id=?",[now_ts(),$r['id']]);
            $a=min((int)$r['from_user_id'],(int)$r['to_user_id']);$b=max((int)$r['from_user_id'],(int)$r['to_user_id']);
            q_exec("INSERT IGNORE INTO ".table_name('friendships')."(user1_id,user2_id,created_at) VALUES(?,?,?)",[$a,$b,now_ts()]);
        }
    }
}
$reqs=q_all("SELECT fr.*,u.username FROM ".table_name('friend_requests')." fr JOIN ".table_name('users')." u ON u.id=fr.from_user_id WHERE fr.to_user_id=? AND fr.status='pending'",[$u['id']]);
foreach($reqs as $r) row_item('friends.php','请',$r['username'],'请求添加你为好友');
shell_mid();
?>
<h1><?=h(t('friends'))?></h1>
<?php if($msg):?><div class="card"><?=h($msg)?></div><?php endif;?>
<form class="card" method="post" action="friends.php">
<input type="hidden" name="act" value="add"><h2>添加好友</h2>
<div class="field"><input name="q" placeholder="用户 ID 或用户名"></div>
<div class="field"><input name="message" placeholder="验证消息"></div><button class="btn">发送申请</button>
</form>
<h2>收到的申请</h2>
<?php foreach($reqs as $r):?>
<div class="card"><b><?=h($r['username'])?></b><p><?=h($r['message'])?></p><form method="post" action="friends.php"><input type="hidden" name="act" value="accept"><input type="hidden" name="id" value="<?=h($r['id'])?>"><button class="btn">同意</button></form></div>
<?php endforeach;?>
<?php shell_end(); ?>
