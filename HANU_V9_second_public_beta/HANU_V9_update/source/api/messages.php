<?php
require_once __DIR__ . '/../includes/auth.php';
$u=require_user();
$target=(int)($_GET['target']??0);
if($target){
    if(!are_friends((int)$u['id'],$target)) json_out(['ok'=>false,'error'=>'请先添加好友']);
    $rows=q_all("SELECT m.*,u.username FROM ".table_name('messages')." m JOIN ".table_name('users')." u ON u.id=m.sender_id WHERE m.type='private' AND ((sender_id=? AND receiver_id=?) OR (sender_id=? AND receiver_id=?)) ORDER BY m.created_at DESC LIMIT 80",[$u['id'],$target,$target,$u['id']]);
}else{
    $rows=q_all("SELECT m.*,u.username FROM ".table_name('messages')." m JOIN ".table_name('users')." u ON u.id=m.sender_id WHERE m.type='public' ORDER BY m.created_at DESC LIMIT 80");
}
$rows=array_reverse($rows);
$out=[];
foreach($rows as $r) $out[]=['id'=>(int)$r['id'],'username'=>$r['username'],'content'=>$r['content'],'html'=>render_text_with_links($r['content'],'message',(int)$r['id']),'time'=>date('m/d H:i',$r['created_at']),'mine'=>(int)$r['sender_id']===(int)$u['id']];
json_out(['ok'=>true,'rows'=>$out]);
