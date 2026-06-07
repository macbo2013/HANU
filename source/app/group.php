<?php
require_once __DIR__ . '/../includes/layout.php';
$u=shell('groups');
$id=(int)($_GET['id']??0);
$msg='';

$g=q_one("SELECT * FROM ".table_name('groups')." WHERE id=?",[$id]);
if(!$g || !group_member($id,(int)$u['id'])){
    shell_mid();
    echo '<h1>无法进入群聊</h1><div class="card">群聊不存在，或你还不是成员。</div>';
    shell_end();
    exit;
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $content=clean($_POST['content']??'',1000);
    try{
        waf_check_text($content,'group_message',(int)$u['id']);
        if($content===''){
            $msg='消息不能为空';
        }else{
            q_exec("INSERT INTO ".table_name('group_messages')."(group_id,user_id,content,created_at) VALUES(?,?,?,?)",[$id,$u['id'],$content,now_ts()]);
            q_exec("UPDATE ".table_name('groups')." SET updated_at=? WHERE id=?",[now_ts(),$id]);
            redirect_to('group.php?id='.$id);
        }
    }catch(HanuWafException $e){
        redirect_to('waf_block.php?id='.$e->blockId);
    }catch(Throwable $e){
        $msg=$e->getMessage();
    }
}

$members=q_all("SELECT u.username,u.avatar_text FROM ".table_name('group_members')." gm JOIN ".table_name('users')." u ON u.id=gm.user_id WHERE gm.group_id=? LIMIT 50",[$id]);
foreach($members as $m) row_item('group.php?id='.$id,$m['avatar_text'],$m['username'],'群成员');

shell_mid();
?>
<h1><?=h($g['name'])?></h1>
<p class="muted">群号 <?=h($g['group_no'])?><?=!empty($g['password_hash'])?' · 密码群':''?> · <?=h($g['description'])?></p>

<?php if($msg):?><div class="card bad"><?=h($msg)?></div><?php endif;?>

<div id="msgs" class="msgs"></div>

<form class="composer" method="post" action="group.php?id=<?=h($id)?>" onsubmit="if(window.sendGroupMessage){event.preventDefault();sendGroupMessage('<?=h($id)?>');}">
  <input id="msgInput" name="content" placeholder="输入群消息">
  <button class="btn" type="submit">发送</button>
</form>

<noscript><div class="card">当前浏览器没有启用 JavaScript，已自动使用普通表单发送。</div></noscript>

<script>
addEventListener('load',()=>{
  loadGroupMessages('<?=h($id)?>');
  setInterval(()=>loadGroupMessages('<?=h($id)?>'),5000);
  const input=document.getElementById('msgInput');
  if(input){
    input.addEventListener('keydown',e=>{
      if(e.key==='Enter'){
        e.preventDefault();
        sendGroupMessage('<?=h($id)?>');
      }
    });
  }
});
</script>
<?php shell_end(); ?>
