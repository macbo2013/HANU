<?php
require_once __DIR__ . '/../includes/layout.php';
$u=current_user();
$raw=$_GET['u']??'';
$url=base64_decode($raw,true) ?: '';
$from=clean($_GET['from']??'content',32);
$id=(int)($_GET['id']??0);
if(!preg_match('~^https?://~i',$url)){ http_response_code(400); echo 'Invalid URL'; exit; }
$action=$_GET['action']??'view';
q_exec("INSERT INTO ".table_name('outbound_logs')."(user_id,url,source_type,source_id,action,ip,user_agent,created_at) VALUES(?,?,?,?,?,?,?,?)",[$u['id']??null,$url,$from,$id,$action==='go'?'visited':'opened',client_ip(),substr($_SERVER['HTTP_USER_AGENT']??'',0,255),now_ts()]);
if($action==='go'){ header('Location: '.$url); exit; }
page_head('外链提醒',$u);
?>
<div class="auth"><div class="card glass" style="width:min(620px,100%);padding:28px">
<h1>即将访问外部链接</h1>
<p class="muted">为了安全，HANU 会在离开站点前进行提醒并记录访问日志。</p>
<div class="card"><b><?=h($url)?></b></div>
<a class="btn" href="outbound.php?u=<?=h(rawurlencode(base64_encode($url)))?>&from=<?=h(rawurlencode($from))?>&id=<?=h($id)?>&action=go">继续访问</a>
<a class="btn ghost" href="home.php">取消</a>
</div></div>
<?php page_end(); ?>
