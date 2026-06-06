<?php
require_once __DIR__ . '/../includes/auth.php';
$u=require_user();
$target=(int)($_POST['target']??0);
$content=clean($_POST['content']??'',1000);
try{ waf_check_text($content,'message',(int)$u['id']); }
catch(HanuWafException $e){ json_out(['ok'=>false,'waf'=>true,'redirect'=>'waf_block.php?id='.$e->blockId]); }
catch(Throwable $e){ json_out(['ok'=>false,'error'=>$e->getMessage()]); }
if($content==='') json_out(['ok'=>false,'error'=>'内容不能为空']);
if($target){
    if(!are_friends((int)$u['id'],$target)) json_out(['ok'=>false,'error'=>'请先添加好友']);
    q_exec("INSERT INTO ".table_name('messages')."(sender_id,receiver_id,type,content,created_at) VALUES(?,?,?,?,?)",[$u['id'],$target,'private',$content,now_ts()]);
    notice_user($target,'新的私信',$u['username'].'：'.text_cut($content,0,60),'message');
}else{
    q_exec("INSERT INTO ".table_name('messages')."(sender_id,type,content,created_at) VALUES(?,?,?,?)",[$u['id'],'public',$content,now_ts()]);
}
json_out(['ok'=>true]);
