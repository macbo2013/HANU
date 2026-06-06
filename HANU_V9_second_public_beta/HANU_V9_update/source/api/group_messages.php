<?php
require_once __DIR__ . '/../includes/auth.php';
$u=require_user();
$id=(int)($_GET['id']??0);
if(!group_member($id,(int)$u['id'])) json_out(['ok'=>false,'error'=>'你不在该群聊中']);
$rows=q_all("SELECT m.*,u.username FROM ".table_name('group_messages')." m JOIN ".table_name('users')." u ON u.id=m.user_id WHERE m.group_id=? ORDER BY m.created_at DESC LIMIT 80",[$id]);
$rows=array_reverse($rows);
$out=[];
foreach($rows as $r) $out[]=['id'=>(int)$r['id'],'username'=>$r['username'],'content'=>$r['content'],'html'=>render_text_with_links($r['content'],'group_message',(int)$r['id']),'time'=>date('m/d H:i',$r['created_at']),'mine'=>(int)$r['user_id']===(int)$u['id']];
json_out(['ok'=>true,'rows'=>$out]);
