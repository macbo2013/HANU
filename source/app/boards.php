<?php
require_once __DIR__ . '/../includes/layout.php';
$u=shell('boards');
$boards=q_all("SELECT * FROM ".table_name('boards')." ORDER BY sort_order ASC");
foreach($boards as $b) row_item('boards.php?id='.$b['id'],'板',$b['name'],$b['description']);
shell_mid();
$id=(int)($_GET['id']??0);
if($id){
    $b=q_one("SELECT * FROM ".table_name('boards')." WHERE id=?",[$id]);
    if(!$b){ echo '<h1>板块不存在</h1>'; shell_end(); exit; }
    echo '<h1>'.h($b['name']).'</h1><p class="muted">'.h($b['description']).'</p>';
    $posts=q_all("SELECT p.*,u.username,tt.name title_name,tt.color title_color FROM ".table_name('posts')." p JOIN ".table_name('users')." u ON u.id=p.user_id LEFT JOIN ".table_name('titles')." tt ON tt.id=u.current_title_id WHERE p.board_id=? AND p.is_deleted=0 ORDER BY p.created_at DESC LIMIT 100",[$id]);
    foreach($posts as $p){
        echo '<div class="card"><b>'.h($p['username']).'</b>';
        if(!empty($p['title_name'])) echo '<span class="title-badge" style="--title:'.h($p['title_color']).'">'.h($p['title_name']).'</span>';
        echo '<p>'.render_text_with_links($p['content'],'post',(int)$p['id']).'</p>';
        if(!empty($p['media_path']) && $p['media_type']==='image') echo '<div class="media"><img src="../'.h($p['media_path']).'" alt=""></div>';
        if(!empty($p['media_path']) && $p['media_type']==='video') echo '<div class="media"><video src="../'.h($p['media_path']).'" controls playsinline></video></div>';
        echo '</div>';
    }
}else{
    echo '<h1>'.h(t('boards')).'</h1><p class="muted">板块列表已修复为完整显示，不再只露出一点。</p>';
    foreach($boards as $b) echo '<a class="card" href="boards.php?id='.h($b['id']).'"><h2>'.h($b['name']).'</h2><p class="muted">'.h($b['description']).'</p></a>';
}
shell_end();
