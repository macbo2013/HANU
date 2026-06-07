<?php
require_once __DIR__ . '/../includes/layout.php';
$u=shell('red_packets');
$msg='';
$pn=point_name();

function claim_packet(int $packetId, int $userId): string {
    $pdo=db();
    $pdo->beginTransaction();
    try{
        $packet=q_one("SELECT * FROM ".table_name('point_packets')." WHERE id=? FOR UPDATE",[$packetId]);
        if(!$packet) throw new RuntimeException('红包不存在');
        if($packet['status']!=='open') throw new RuntimeException('红包已经领完');
        if((int)$packet['user_id']===$userId) throw new RuntimeException('不能领取自己发的红包');
        $old=q_one("SELECT * FROM ".table_name('point_packet_claims')." WHERE packet_id=? AND user_id=?",[$packetId,$userId]);
        if($old) throw new RuntimeException('你已经领取过这个红包');

        $remainCount=(int)$packet['total_count']-(int)$packet['claimed_count'];
        $remainPoints=(int)$packet['total_points']-(int)$packet['claimed_points'];
        if($remainCount<=0 || $remainPoints<=0) throw new RuntimeException('红包已经领完');

        if($remainCount===1){
            $amount=$remainPoints;
        }else{
            $max=$remainPoints-($remainCount-1);
            $amount=random_int(1,max(1,$max));
        }

        q_exec("INSERT INTO ".table_name('point_packet_claims')."(packet_id,user_id,points,created_at) VALUES(?,?,?,?)",[$packetId,$userId,$amount,now_ts()]);
        $newCount=(int)$packet['claimed_count']+1;
        $newPoints=(int)$packet['claimed_points']+$amount;
        $status=$newCount >= (int)$packet['total_count'] ? 'done' : 'open';
        q_exec("UPDATE ".table_name('point_packets')." SET claimed_count=?,claimed_points=?,status=?,updated_at=? WHERE id=?",[$newCount,$newPoints,$status,now_ts(),$packetId]);
        q_exec("UPDATE ".table_name('users')." SET points=points+?,updated_at=? WHERE id=?",[$amount,now_ts(),$userId]);
        q_exec("INSERT INTO ".table_name('point_logs')."(user_id,points,reason,created_at) VALUES(?,?,?,?)",[$userId,$amount,'领取积分红包',now_ts()]);
        recalc_level($userId);
        $pdo->commit();
        return (string)$amount;
    }catch(Throwable $e){
        if($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $act=$_POST['act']??'';
    try{
        if($act==='create'){
            $title=clean($_POST['title']??'恭喜发财',120);
            $total=max(1,(int)($_POST['total_points']??0));
            $count=max(1,(int)($_POST['total_count']??0));
            if($count>100) throw new RuntimeException('单个红包最多 100 份');
            if($total<$count) throw new RuntimeException('总额不能小于红包份数');
            $fresh=current_user();
            if((int)$fresh['points']<$total) throw new RuntimeException($pn.'不足，无法发送红包');

            add_points((int)$fresh['id'],-$total,'发送积分红包');
            q_exec("INSERT INTO ".table_name('point_packets')."(user_id,title,total_points,total_count,created_at,updated_at) VALUES(?,?,?,?,?,?)",[$fresh['id'],$title,$total,$count,now_ts(),now_ts()]);
            $msg='红包发送成功';
            $u=current_user();
        }elseif($act==='claim'){
            $got=claim_packet((int)($_POST['packet_id']??0),(int)$u['id']);
            $msg='领取成功，获得 '.$got.' '.$pn;
            $u=current_user();
        }
    }catch(Throwable $e){
        $msg=$e->getMessage();
    }
}

$packets=q_all("SELECT p.*,u.username FROM ".table_name('point_packets')." p JOIN ".table_name('users')." u ON u.id=p.user_id ORDER BY p.created_at DESC LIMIT 80");
$claims=q_all("SELECT c.*,p.title,u.username FROM ".table_name('point_packet_claims')." c JOIN ".table_name('point_packets')." p ON p.id=c.packet_id JOIN ".table_name('users')." u ON u.id=c.user_id ORDER BY c.created_at DESC LIMIT 40");

foreach($packets as $p) row_item('red_packets.php','包',$p['title'],$p['username'].' · '.$p['claimed_count'].'/'.$p['total_count'].' · '.$p['claimed_points'].'/'.$p['total_points'].' '.$pn);
shell_mid();
?>
<h1><?=h($pn)?>红包</h1>
<?php if($msg):?><div class="card"><?=h($msg)?></div><?php endif;?>

<div class="stat">
  <div class="card"><b><?=h($u['points']??0)?></b><span class="muted">我的<?=h($pn)?></span></div>
  <div class="card"><b><?=h(count($packets))?></b><span class="muted">最近红包</span></div>
  <div class="card"><b><?=h(count($claims))?></b><span class="muted">最近领取</span></div>
</div>

<form class="card" method="post" action="red_packets.php">
<input type="hidden" name="act" value="create">
<h2>发<?=h($pn)?>红包</h2>
<div class="field"><label>红包标题</label><input name="title" value="恭喜发财"></div>
<div class="grid">
  <div class="field"><label>总额</label><input name="total_points" type="number" min="1" value="10"></div>
  <div class="field"><label>份数</label><input name="total_count" type="number" min="1" max="100" value="2"></div>
</div>
<p class="muted">发送后会立即从你的账户扣除对应<?=h($pn)?>，用户领取后自动入账。</p>
<button class="btn">发送红包</button>
</form>

<h2>红包大厅</h2>
<?php foreach($packets as $p): 
  $done=$p['status']!=='open' || (int)$p['claimed_count'] >= (int)$p['total_count'];
  $mine=(int)$p['user_id']===(int)$u['id'];
?>
<div class="card">
  <h3><?=h($p['title'])?></h3>
  <p class="muted">来自 <?=h($p['username'])?> · 已领 <?=h($p['claimed_count'])?>/<?=h($p['total_count'])?> 份 · <?=h($p['claimed_points'])?>/<?=h($p['total_points'])?> <?=h($pn)?></p>
  <?php if($done): ?>
    <button class="btn ghost" disabled>已领完</button>
  <?php elseif($mine): ?>
    <button class="btn ghost" disabled>自己的红包</button>
  <?php else: ?>
    <form method="post" action="red_packets.php">
      <input type="hidden" name="act" value="claim">
      <input type="hidden" name="packet_id" value="<?=h($p['id'])?>">
      <button class="btn">抢红包</button>
    </form>
  <?php endif; ?>
</div>
<?php endforeach; if(!$packets) echo '<div class="card">暂时还没有红包。</div>'; ?>

<h2>最近领取</h2>
<?php foreach($claims as $c): ?>
<div class="card"><b><?=h($c['username'])?></b><p class="muted">领取《<?=h($c['title'])?>》获得 <?=h($c['points'])?> <?=h($pn)?> · <?=date('m/d H:i',$c['created_at'])?></p></div>
<?php endforeach; if(!$claims) echo '<div class="card">暂无领取记录。</div>'; ?>

<?php shell_end(); ?>
