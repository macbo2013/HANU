<?php
declare(strict_types=1);
define('HANU_ROOT', dirname(__DIR__));
if (!file_exists(HANU_ROOT . '/config/config.php') || !file_exists(HANU_ROOT . '/data/install.lock')) {
    header('Location: ../index.php');
    exit;
}
$HANU_CONFIG = require HANU_ROOT . '/config/config.php';

class HanuWafException extends RuntimeException {
    public $logId = 0;
    public $blockId = 0;
    public $penalty = '';
}

function cfg(string $key, $default = null) { global $HANU_CONFIG; return $HANU_CONFIG[$key] ?? $default; }
function db(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;
    $dsn = 'mysql:host=' . cfg('db_host') . ';port=' . cfg('db_port') . ';dbname=' . cfg('db_name') . ';charset=' . cfg('db_charset', 'utf8mb4');
    $pdo = new PDO($dsn, cfg('db_user'), cfg('db_pass'), [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]);
    return $pdo;
}
function table_name(string $name): string { return '`' . cfg('table_prefix', 'hanu_') . $name . '`'; }
function now_ts(): int { return time(); }
function q_one(string $sql, array $p = []): ?array { $s=db()->prepare($sql); $s->execute($p); $r=$s->fetch(); return $r ?: null; }
function q_all(string $sql, array $p = []): array { $s=db()->prepare($sql); $s->execute($p); return $s->fetchAll(); }
function q_exec(string $sql, array $p = []): int { $s=db()->prepare($sql); $s->execute($p); return $s->rowCount(); }
function h($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function text_cut($s, int $start = 0, int $length = 255): string { $s=(string)$s; if(function_exists('mb_substr')) return mb_substr($s,$start,$length,'UTF-8'); return substr($s,$start,$length); }
function clean($s, int $max = 255): string { return text_cut(trim((string)$s), 0, $max); }
function redirect_to(string $path): void { header('Location: '.$path); exit; }
function json_out(array $data): void { header('Content-Type: application/json; charset=utf-8'); echo json_encode($data, JSON_UNESCAPED_UNICODE); exit; }

function site_setting(string $name, $default = null) {
    try {
        $r = q_one("SELECT value FROM " . table_name('settings') . " WHERE name=?", [$name]);
        return $r ? $r['value'] : $default;
    } catch (Throwable $e) {
        return $default;
    }
}
function set_site_setting(string $name, string $value): void {
    q_exec("INSERT INTO " . table_name('settings') . "(name,value) VALUES(?,?) ON DUPLICATE KEY UPDATE value=VALUES(value)", [$name, $value]);
}
function app_name(): string {
    return (string)site_setting('site_name', cfg('site_name', 'HANU'));
}
function version_label(): string {
    return (string)site_setting('version_label', cfg('version_label', 'HANU V10 正式版'));
}




if (!function_exists('point_name')) {
function point_name(): string {
    return (string)site_setting('point_name', cfg('point_name', '积分'));
}
}

function support_email(): string {
    return (string)site_setting('support_email', cfg('support_email', 'qm66668888@qq.com'));
}

function lang(): array { static $lang=null; if($lang!==null)return $lang; $file=HANU_ROOT.'/data/lang/current.php'; $lang=file_exists($file)?require $file:[]; return $lang; }
function t(string $key): string { $l=lang(); return $l[$key] ?? $key; }
function client_ip(): string { return $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? ''; }

function log_waf(?int $userId, string $rule, string $content, string $context = 'content'): int {
    q_exec("INSERT INTO ".table_name('waf_logs')."(user_id,ip,rule,content,created_at) VALUES(?,?,?,?,?)",[$userId,client_ip(),$rule.' / '.$context,text_cut($content,0,2000),now_ts()]);
    return (int)db()->lastInsertId();
}
function apply_waf_penalty(?int $userId, int $logId): array {
    if(!$userId) return ['block_id'=>0,'message'=>'游客请求已被拦截。','penalty'=>'none'];
    $u=q_one("SELECT id,waf_level,is_banned,ban_until FROM ".table_name('users')." WHERE id=?",[$userId]);
    if(!$u) return ['block_id'=>0,'message'=>'请求已被拦截。','penalty'=>'none'];
    $today=date('Y-m-d');
    $start=strtotime($today.' 00:00:00');
    $count=(int)(q_one("SELECT COUNT(*) c FROM ".table_name('waf_logs')." WHERE user_id=? AND created_at>=?",[$userId,$start])['c'] ?? 0);
    $level=(int)($u['waf_level'] ?? 0);
    $banSeconds=null; $msg='请求已被拦截，本次已记录。'; $newLevel=$level;

    if($level===0 && $count>=5){ $banSeconds=3600; $newLevel=1; $msg='你今天已触发 5 次安全规则，账号已被临时封禁 1 小时。'; }
    elseif($level===1){ $banSeconds=7*86400; $newLevel=2; $msg='你在 1 小时封禁后再次触发安全规则，账号已被封禁 1 周。'; }
    elseif($level===2 && $count>=5){ $banSeconds=30*86400; $newLevel=3; $msg='你在 1 周封禁后再次多次触发安全规则，账号已被封禁 1 个月。'; }
    elseif($level===3){ $banSeconds=365*86400; $newLevel=4; $msg='你在 1 个月封禁后再次触发安全规则，账号已被封禁 1 年。'; }
    elseif($level>=4){ $banSeconds=null; $newLevel=5; $msg='你在 1 年封禁后再次触发安全规则，账号已被永久封禁。'; }

    if($banSeconds !== null || $newLevel>=5){
        $until = $newLevel>=5 ? null : now_ts()+$banSeconds;
        q_exec("UPDATE ".table_name('users')." SET is_banned=1,ban_reason=?,ban_until=?,waf_level=?,updated_at=? WHERE id=?",[$msg,$until,$newLevel,now_ts(),$userId]);
    } elseif($newLevel!==$level) {
        q_exec("UPDATE ".table_name('users')." SET waf_level=?,updated_at=? WHERE id=?",[$newLevel,now_ts(),$userId]);
    }

    q_exec("INSERT INTO ".table_name('waf_blocks')."(user_id,waf_log_id,penalty_level,ban_seconds,message,created_at) VALUES(?,?,?,?,?,?)",[$userId,$logId,$newLevel,$banSeconds,$msg,now_ts()]);
    return ['block_id'=>(int)db()->lastInsertId(),'message'=>$msg,'penalty'=>$newLevel>=5?'permanent':(string)($banSeconds ?? 'none')];
}
function waf_violation(?int $userId, string $rule, string $content, string $context='content'): void {
    $logId=log_waf($userId,$rule,$content,$context);
    $pen=apply_waf_penalty($userId,$logId);
    $e=new HanuWafException('请求已被安全系统拦截');
    $e->logId=$logId; $e->blockId=(int)$pen['block_id']; $e->penalty=$pen['message'];
    throw $e;
}
function waf_check_text(string $text, string $context='content', ?int $userId=null): void {
    $rules=[
      'script标签'=>'/<\s*script/i',
      'javascript协议'=>'/javascript\s*:/i',
      '事件注入'=>'/onerror\s*=|onload\s*=|onclick\s*=/i',
      'SQL注入'=>'/\bunion\s+select\b|\binformation_schema\b|\bdrop\s+table\b|\bsleep\s*\(/i',
      '路径穿越'=>'/\.\.\//'
    ];
    foreach($rules as $name=>$pattern){
        if(preg_match($pattern,$text)) waf_violation($userId,$name,$text,$context);
    }
}
function render_text_with_links(string $text, string $sourceType='content', int $sourceId=0): string {
    $out=''; $offset=0;
    if(!preg_match_all('~https?://[^\s<>"\']+~i',$text,$matches,PREG_OFFSET_CAPTURE)) return nl2br(h($text));
    foreach($matches[0] as $m){
        $url=$m[0]; $pos=$m[1];
        $out .= h(substr($text,$offset,$pos-$offset));
        $u=rawurlencode(base64_encode($url));
        $out .= '<a class="safe-link" href="outbound.php?u='.$u.'&from='.rawurlencode($sourceType).'&id='.(int)$sourceId.'">'.h($url).'</a>';
        $offset=$pos+strlen($url);
    }
    $out .= h(substr($text,$offset));
    return nl2br($out);
}
function recalc_level(int $userId): void {
    $u=q_one("SELECT points FROM ".table_name('users')." WHERE id=?",[$userId]); if(!$u)return;
    $level=max(1,(int)floor(((int)$u['points'])/100)+1);
    q_exec("UPDATE ".table_name('users')." SET level=?,updated_at=? WHERE id=?",[$level,now_ts(),$userId]);
}
function add_points(int $userId,int $points,string $reason): void {
    q_exec("UPDATE ".table_name('users')." SET points=points+?,updated_at=? WHERE id=?",[$points,now_ts(),$userId]);
    q_exec("INSERT INTO ".table_name('point_logs')."(user_id,points,reason,created_at) VALUES(?,?,?,?)",[$userId,$points,$reason,now_ts()]);
    recalc_level($userId);
}
function upload_file(string $field,array $allowedExt,int $maxBytes,string $subdir='media'): ?string {
    if(!isset($_FILES[$field])||!is_array($_FILES[$field])||($_FILES[$field]['error']??UPLOAD_ERR_NO_FILE)===UPLOAD_ERR_NO_FILE)return null;
    if(($_FILES[$field]['error']??UPLOAD_ERR_OK)!==UPLOAD_ERR_OK)throw new RuntimeException('文件上传失败');
    if(($_FILES[$field]['size']??0)>$maxBytes)throw new RuntimeException('文件太大');
    $name=$_FILES[$field]['name']??''; $ext=strtolower(pathinfo($name,PATHINFO_EXTENSION));
    if(!in_array($ext,$allowedExt,true))throw new RuntimeException('不支持的文件类型');
    $dir=HANU_ROOT.'/data/uploads/'.trim($subdir,'/'); if(!is_dir($dir))mkdir($dir,0777,true);
    $file=date('YmdHis').'_'.bin2hex(random_bytes(6)).'.'.$ext; $target=$dir.'/'.$file;
    if(!move_uploaded_file($_FILES[$field]['tmp_name'],$target))throw new RuntimeException('保存上传文件失败');
    return 'data/uploads/'.trim($subdir,'/').'/'.$file;
}
function gd_load_image(string $path,string $ext){ if(!function_exists('imagecreatetruecolor'))return null; if(in_array($ext,['jpg','jpeg'],true)&&function_exists('imagecreatefromjpeg'))return @imagecreatefromjpeg($path); if($ext==='png'&&function_exists('imagecreatefrompng'))return @imagecreatefrompng($path); if($ext==='webp'&&function_exists('imagecreatefromwebp'))return @imagecreatefromwebp($path); if($ext==='gif'&&function_exists('imagecreatefromgif'))return @imagecreatefromgif($path); return null; }
function gd_save_image($img,string $path,string $ext): bool { if(in_array($ext,['jpg','jpeg'],true)&&function_exists('imagejpeg'))return imagejpeg($img,$path,88); if($ext==='png'&&function_exists('imagepng'))return imagepng($img,$path,8); if($ext==='webp'&&function_exists('imagewebp'))return imagewebp($img,$path,86); if($ext==='gif'&&function_exists('imagegif'))return imagegif($img,$path); return false; }
function resize_image_file(string $relativePath,int $maxW,int $maxH,bool $square=false): string {
    $path=HANU_ROOT.'/'.$relativePath; $ext=strtolower(pathinfo($path,PATHINFO_EXTENSION)); $src=gd_load_image($path,$ext); if(!$src)return $relativePath;
    $w=imagesx($src); $h=imagesy($src); if($w<=0||$h<=0){imagedestroy($src);return $relativePath;}
    if($square){$side=min($w,$h);$sx=(int)(($w-$side)/2);$sy=(int)(($h-$side)/2);$dw=$maxW;$dh=$maxW;$dst=imagecreatetruecolor($dw,$dh);imagealphablending($dst,false);imagesavealpha($dst,true);imagecopyresampled($dst,$src,0,0,$sx,$sy,$dw,$dh,$side,$side);}
    else{$ratio=min($maxW/$w,$maxH/$h,1);$dw=max(1,(int)($w*$ratio));$dh=max(1,(int)($h*$ratio));$dst=imagecreatetruecolor($dw,$dh);imagealphablending($dst,false);imagesavealpha($dst,true);imagecopyresampled($dst,$src,0,0,0,0,$dw,$dh,$w,$h);}
    gd_save_image($dst,$path,$ext); imagedestroy($src); imagedestroy($dst); return $relativePath;
}
