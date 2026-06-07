<?php
require_once __DIR__ . '/../includes/layout.php';
$u=shell('status');
$msg='';

if($_SERVER['REQUEST_METHOD']==='POST'){
    $act=$_POST['act']??'';
    try{
        if($act==='save'){
            $emoji=clean($_POST['emoji']??'🙂',16);
            $text=clean($_POST['status_text']??'',160);
            $visibility=$_POST['visibility']??'public';
            if(!in_array($visibility,['public','friends','private'],true)) $visibility='public';
            $hours=max(1,min(168,(int)($_POST['hours']??24)));
            if($text==='') throw new RuntimeException('状态内容不能为空');
            waf_check_text($text,'status',(int)$u['id']);

            $existing=q_one("SELECT id FROM ".table_name('user_statuses')." WHERE user_id=? ORDER BY id DESC LIMIT 1",[$u['id']]);
            $expires=now_ts()+$hours*3600;

            if($existing){
                q_exec("UPDATE ".table_name('user_statuses')." SET emoji=?,status_text=?,visibility=?,expires_at=?,updated_at=? WHERE id=?",[$emoji,$text,$visibility,$expires,now_ts(),$existing['id']]);
            }else{
                q_exec("INSERT INTO ".table_name('user_statuses')."(user_id,emoji,status_text,visibility,expires_at,created_at,updated_at) VALUES(?,?,?,?,?,?,?)",[$u['id'],$emoji,$text,$visibility,$expires,now_ts(),now_ts()]);
            }
            $msg='状态已更新';
        }elseif($act==='clear'){
            q_exec("DELETE FROM ".table_name('user_statuses')." WHERE user_id=?",[$u['id']]);
            $msg='状态已清除';
        }
    }catch(HanuWafException $e){
        redirect_to('waf_block.php?id='.$e->blockId);
    }catch(Throwable $e){
        $msg=$e->getMessage();
    }
}

$my=q_one("SELECT * FROM ".table_name('user_statuses')." WHERE user_id=? ORDER BY id DESC LIMIT 1",[$u['id']]);

$rows=q_all("SELECT s.*,u.username,u.avatar_text,u.avatar_path,tt.name title_name,tt.color title_color
FROM ".table_name('user_statuses')." s
JOIN ".table_name('users')." u ON u.id=s.user_id
LEFT JOIN ".table_name('titles')." tt ON tt.id=u.current_title_id
WHERE s.visibility='public' AND (s.expires_at IS NULL OR s.expires_at>?)
ORDER BY s.updated_at DESC LIMIT 80",[now_ts()]);

foreach($rows as $r) row_item('status.php',$r['emoji'],$r['username'],$r['status_text']);
shell_mid();
?>
<h1>状态</h1>
<?php if($msg):?><div class="card"><?=h($msg)?></div><?php endif;?>

<form class="card" method="post" action="status.php">
<input type="hidden" name="act" value="save">
<h2>我的状态</h2>
<div class="grid">
  <div class="field"><label>状态图标</label><input name="emoji" value="<?=h($my['emoji']??'🙂')?>" placeholder="🙂"></div>
  <div class="field"><label>过期时间</label><select name="hours">
    <option value="6">6 小时</option>
    <option value="24" selected>24 小时</option>
    <option value="72">3 天</option>
    <option value="168">7 天</option>
  </select></div>
</div>
<div class="field"><label>状态内容</label><input name="status_text" value="<?=h($my['status_text']??'')?>" placeholder="今天想说点什么？"></div>
<div class="field"><label>可见范围</label><select name="visibility">
  <option value="public" <?=($my['visibility']??'public')==='public'?'selected':''?>>公开</option>
  <option value="friends" <?=($my['visibility']??'')==='friends'?'selected':''?>>仅好友</option>
  <option value="private" <?=($my['visibility']??'')==='private'?'selected':''?>>仅自己</option>
</select></div>
<button class="btn">保存状态</button>
</form>

<?php if($my): ?>
<form class="card" method="post" action="status.php">
<input type="hidden" name="act" value="clear">
<h2>当前状态</h2>
<p><b><?=h($my['emoji'])?></b> <?=h($my['status_text'])?></p>
<p class="muted">可见性：<?=h($my['visibility'])?> · 过期：<?=h($my['expires_at']?date('Y-m-d H:i',$my['expires_at']):'不过期')?></p>
<button class="btn ghost">清除状态</button>
</form>
<?php endif; ?>

<h2>公开状态广场</h2>
<?php foreach($rows as $r): ?>
<div class="card">
  <h3><?=h($r['emoji'])?> <?=h($r['username'])?> <?php if(!empty($r['title_name'])):?><span class="title-badge" style="--title:<?=h($r['title_color'])?>"><?=h($r['title_name'])?></span><?php endif;?></h3>
  <p><?=render_text_with_links($r['status_text'],'status',(int)$r['id'])?></p>
  <p class="muted"><?=date('m/d H:i',$r['updated_at'])?> · <?=h($r['expires_at']?('过期：'.date('m/d H:i',$r['expires_at'])):'不过期')?></p>
</div>
<?php endforeach; if(!$rows) echo '<div class="card">暂时还没有公开状态。</div>'; ?>

<?php shell_end(); ?>
