<?php
require_once __DIR__ . '/../includes/layout.php';
$u=require_admin();
shell('admin');
$msg='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    $act=$_POST['act']??'';
    if($act==='site_settings'){
        set_site_setting('site_name', clean($_POST['site_name']??app_name(),80));
        set_site_setting('site_desc', clean($_POST['site_desc']??cfg('site_desc',''),180));
        set_site_setting('support_email', clean($_POST['support_email']??support_email(),120));
        set_site_setting('site_announcement', clean($_POST['site_announcement']??'',255));
        $msg='站点设置已保存';
    }elseif($act==='reset_waf'){
        $id=(int)$_POST['id'];
        q_exec("UPDATE ".table_name('users')." SET is_banned=0,ban_reason=NULL,ban_until=NULL,waf_level=0,updated_at=? WHERE id=?",[now_ts(),$id]);
        $msg='已解除封禁并重置 WAF 等级';
    }elseif($act==='ban'){
        $id=(int)$_POST['id']; $target=q_one("SELECT * FROM ".table_name('users')." WHERE id=?",[$id]);
        if($target && $target['role']==='admin') $msg='不能封禁管理员';
        elseif($target){ q_exec("UPDATE ".table_name('users')." SET is_banned=1,ban_reason=?,ban_until=?,updated_at=? WHERE id=?",[clean($_POST['reason']??'管理员封禁',255),now_ts()+86400*7,now_ts(),$id]); $msg='已封禁'; }
    }elseif($act==='unban'){
        q_exec("UPDATE ".table_name('users')." SET is_banned=0,ban_reason=NULL,ban_until=NULL,updated_at=? WHERE id=?",[now_ts(),(int)$_POST['id']]); $msg='已解封';
    }elseif($act==='board'){
        q_exec("INSERT INTO ".table_name('boards')."(name,description,sort_order,created_at) VALUES(?,?,?,?)",[clean($_POST['name']??'',80),clean($_POST['description']??'',255),(int)($_POST['sort_order']??99),now_ts()]);
        $msg='板块已创建';
    }elseif($act==='group'){
        q_exec("INSERT INTO ".table_name('user_groups')."(name,level,can_manage_board,created_at) VALUES(?,?,?,?)",[clean($_POST['name']??'',80),(int)($_POST['level']??1),(int)($_POST['can_manage_board']??0),now_ts()]);
        $msg='成员组已创建';
    }elseif($act==='title'){
        $color=clean($_POST['color']??'#3b82f6',20);
        if(!preg_match('/^#[0-9a-fA-F]{6}$/',$color))$color='#3b82f6';
        q_exec("INSERT INTO ".table_name('titles')."(name,color,min_points,created_at) VALUES(?,?,?,?)",[clean($_POST['name']??'',80),$color,(int)($_POST['min_points']??0),now_ts()]);
        $msg='称号已创建';
    }
}
$users=q_all("SELECT * FROM ".table_name('users')." ORDER BY id DESC LIMIT 200");
$waf=q_all("SELECT w.*,u.username FROM ".table_name('waf_logs')." w LEFT JOIN ".table_name('users')." u ON u.id=w.user_id ORDER BY w.created_at DESC LIMIT 30");
$out=q_all("SELECT o.*,u.username FROM ".table_name('outbound_logs')." o LEFT JOIN ".table_name('users')." u ON u.id=o.user_id ORDER BY o.created_at DESC LIMIT 30");
foreach($users as $x) row_item('admin.php',$x['avatar_text'],$x['username'],$x['role'].' · '.$x['points'].'积分');
shell_mid();
?>
<h1><?=h(t('admin'))?></h1>
<?php if($msg):?><div class="card"><?=h($msg)?></div><?php endif;?>
<form class="card" method="post" action="admin.php">
<input type="hidden" name="act" value="site_settings">
<h2>站点设置</h2>
<div class="field"><label>网站名称</label><input name="site_name" value="<?=h(app_name())?>"></div>
<div class="field"><label>网站简介</label><input name="site_desc" value="<?=h(site_setting('site_desc', cfg('site_desc','')))?>"></div>
<div class="field"><label>反馈邮箱</label><input name="support_email" value="<?=h(support_email())?>"></div>
<div class="field"><label>首页公告</label><input name="site_announcement" value="<?=h(site_setting('site_announcement','欢迎使用 HANU V9 第二代公测版，感谢参与公测。'))?>"></div>
<button class="btn">保存站点设置</button>
</form>

<div class="grid">
<form class="card" method="post" action="admin.php"><input type="hidden" name="act" value="board"><h2>创建板块</h2><div class="field"><input name="name" placeholder="板块名"></div><div class="field"><input name="description" placeholder="介绍"></div><div class="field"><input name="sort_order" value="99"></div><button class="btn">创建</button></form>
<form class="card" method="post" action="admin.php"><input type="hidden" name="act" value="group"><h2>创建成员组</h2><div class="field"><input name="name" placeholder="成员组"></div><div class="field"><input name="level" value="1"></div><div class="field"><select name="can_manage_board"><option value="0">普通成员</option><option value="1">可管理板块</option></select></div><button class="btn">创建</button></form>
<form class="card" method="post" action="admin.php"><input type="hidden" name="act" value="title"><h2>创建称号</h2><div class="field"><input name="name" placeholder="称号名"></div><div class="field"><input name="color" value="#3b82f6"></div><div class="field"><input name="min_points" type="number" value="0"></div><button class="btn">创建</button></form>
</div>
<h2>用户管理</h2>
<?php foreach($users as $x):?>
<div class="card"><b><?=h($x['username'])?></b><p class="muted">ID <?=h($x['id'])?> · <?=h($x['role'])?> · <?=h($x['points'])?>积分 · <?=$x['is_banned']?'已封禁':'正常'?></p>
<?php if($x['role']==='admin'):?><button class="btn ghost" disabled>管理员不可封禁</button>
<?php else:?><form method="post" action="admin.php" style="display:inline"><input type="hidden" name="act" value="ban"><input type="hidden" name="id" value="<?=h($x['id'])?>"><button class="btn">封禁 7 天</button></form>
<form method="post" action="admin.php" style="display:inline"><input type="hidden" name="act" value="unban"><input type="hidden" name="id" value="<?=h($x['id'])?>"><button class="btn ghost">解封</button></form> <form method="post" action="admin.php" style="display:inline"><input type="hidden" name="act" value="reset_waf"><input type="hidden" name="id" value="<?=h($x['id'])?>"><button class="btn ghost">重置 WAF</button></form><?php endif;?>
</div>
<?php endforeach;?>
<h2>WAF 安全日志 / 拦截记录</h2>
<?php foreach($waf as $r):?><div class="card"><b><?=h($r['rule'])?></b><p class="muted"><?=h($r['username']??'游客')?> · <?=h($r['ip'])?> · <?=date('m/d H:i',$r['created_at'])?></p><p><?=h(text_cut($r['content'],0,180))?></p></div><?php endforeach; if(!$waf)echo '<div class="card">暂无 WAF 日志</div>';?>
<h2>外链访问日志</h2>
<?php foreach($out as $r):?><div class="card"><b><?=h($r['action'])?></b><p class="muted"><?=h($r['username']??'游客')?> · <?=h($r['ip'])?> · <?=date('m/d H:i',$r['created_at'])?></p><p><?=h(text_cut($r['url'],0,180))?></p></div><?php endforeach; if(!$out)echo '<div class="card">暂无外链日志</div>';?>
<?php shell_end(); ?>
