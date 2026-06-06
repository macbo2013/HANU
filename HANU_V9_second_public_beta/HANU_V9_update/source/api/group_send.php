<?php
require_once __DIR__ . '/../includes/auth.php';
$u=require_user();
$id=(int)($_POST['id']??0);
$content=clean($_POST['content']??'',1000);
try{ waf_check_text($content,'group_message',(int)$u['id']); }
catch(HanuWafException $e){ json_out(['ok'=>false,'waf'=>true,'redirect'=>'waf_block.php?id='.$e->blockId]); }
catch(Throwable $e){ json_out(['ok'=>false,'error'=>$e->getMessage()]); }
if($content==='') json_out(['ok'=>false,'error'=>'内容不能为空']);
if(!group_member($id,(int)$u['id'])) json_out(['ok'=>false,'error'=>'你不在该群聊中']);
q_exec("INSERT INTO ".table_name('group_messages')."(group_id,user_id,content,created_at) VALUES(?,?,?,?)",[$id,$u['id'],$content,now_ts()]);
q_exec("UPDATE ".table_name('groups')." SET updated_at=? WHERE id=?",[now_ts(),$id]);
json_out(['ok'=>true]);
