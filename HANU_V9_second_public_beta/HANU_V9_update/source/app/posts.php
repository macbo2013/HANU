<?php
require_once __DIR__ . '/../includes/layout.php';
$u=shell('posts');
$msg='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    try{
        $content=clean($_POST['content']??'',2000);
        waf_check_text($content,'post',(int)$u['id']);
        $board=(int)($_POST['board_id']??0);
        $mediaPath=null;$mediaType=null;
        if(isset($_FILES['media_file']) && ($_FILES['media_file']['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_NO_FILE){
            $name=$_FILES['media_file']['name']??'';
            $ext=strtolower(pathinfo($name,PATHINFO_EXTENSION));
            if(in_array($ext,['jpg','jpeg','png','gif','webp'],true)){
                $mediaPath=upload_file('media_file',['jpg','jpeg','png','gif','webp'],10*1024*1024,'posts');
                $mediaPath=resize_image_file($mediaPath,1600,1200,false);
                $mediaType='image';
            }elseif(in_array($ext,['mp4','webm','mov'],true)){
                $mediaPath=upload_file('media_file',['mp4','webm','mov'],100*1024*1024,'videos');
                $mediaType='video';
            }else throw new RuntimeException('不支持的媒体类型');
        }
        if($content!=='' || $mediaPath){
            q_exec("INSERT INTO ".table_name('posts')."(board_id,user_id,content,media_type,media_path,created_at) VALUES(?,?,?,?,?,?)",[$board?:null,$u['id'],$content,$mediaType,$mediaPath,now_ts()]);
            add_points((int)$u['id'],2,'发布动态');
        }
    }catch(HanuWafException $e){redirect_to('waf_block.php?id='.$e->blockId);}catch(Throwable $e){$msg=$e->getMessage();}
}
$boards=q_all("SELECT * FROM ".table_name('boards')." ORDER BY sort_order ASC");
foreach($boards as $b) row_item('boards.php?id='.$b['id'],'板',$b['name'],$b['description']);
shell_mid();
$posts=q_all("SELECT p.*,u.username,u.avatar_text,u.avatar_path,u.current_title_id,tt.name title_name,tt.color title_color,b.name board_name FROM ".table_name('posts')." p JOIN ".table_name('users')." u ON u.id=p.user_id LEFT JOIN ".table_name('titles')." tt ON tt.id=u.current_title_id LEFT JOIN ".table_name('boards')." b ON b.id=p.board_id WHERE p.is_deleted=0 ORDER BY p.created_at DESC LIMIT 100");
?>
<h1><?=h(t('feed'))?></h1>
<?php if($msg):?><div class="card bad"><?=h($msg)?></div><?php endif;?>
<form class="card" method="post" action="posts.php" enctype="multipart/form-data">
<div class="field"><select name="board_id"><option value="0">不选择板块</option><?php foreach($boards as $b):?><option value="<?=h($b['id'])?>"><?=h($b['name'])?></option><?php endforeach;?></select></div>
<div class="field"><textarea name="content" placeholder="发布动态，链接会自动变成蓝色并进入安全跳转页"></textarea></div>
<div class="field"><label>上传图片或视频</label><input type="file" name="media_file" accept="image/*,video/mp4,video/webm,video/quicktime"></div>
<button class="btn"><?=h(t('post'))?></button>
</form>
<?php foreach($posts as $p):?>
<div class="card">
  <b><?=h($p['username'])?></b>
  <?php if(!empty($p['title_name'])):?><span class="title-badge" style="--title:<?=h($p['title_color'])?>"><?=h($p['title_name'])?></span><?php endif;?>
  <span class="muted"><?=h($p['board_name'] ?? '动态')?> · <?=date('m/d H:i',$p['created_at'])?></span>
  <p><?=render_text_with_links($p['content'],'post',(int)$p['id'])?></p>
  <?php if(!empty($p['media_path']) && $p['media_type']==='image'):?><div class="media"><img src="../<?=h($p['media_path'])?>" alt=""></div><?php endif;?>
  <?php if(!empty($p['media_path']) && $p['media_type']==='video'):?><div class="media"><video src="../<?=h($p['media_path'])?>" controls playsinline></video></div><?php endif;?>
</div>
<?php endforeach;?>
<?php shell_end(); ?>
