<?php
declare(strict_types=1);

session_start();
error_reporting(E_ALL);
ini_set('display_errors', '0');

define('HANU_PACKAGE_ROOT', __DIR__);
define('HANU_SOURCE_DIR', __DIR__ . '/source');
define('HANU_LANGUAGE_DIR', __DIR__ . '/language');

$step = $_GET['step'] ?? 'welcome';
$steps = [
  'welcome' => '欢迎',
  'check' => '检测',
  'language' => '语言',
  'database' => '数据库',
  'site' => '站点',
  'admin' => '管理员',
  'groups' => '成员组',
  'boards' => '板块',
  'install' => '安装',
  'finish' => '完成'
];

function h($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function scheme_host(): string {
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['SERVER_PORT'] ?? '') === '443');
    return ($https ? 'https://' : 'http://') . ($_SERVER['HTTP_HOST'] ?? 'localhost');
}
function current_base_path(): string {
    $script = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
    return rtrim(str_replace('\\', '/', dirname($script)), '/');
}
function text_cut($s, int $start = 0, int $length = 255): string {
    $s = (string)$s;
    if (function_exists('mb_substr')) return mb_substr($s, $start, $length, 'UTF-8');
    return substr($s, $start, $length);
}

function page_start(string $step, array $steps): void {
    $keys = array_keys($steps);
    $idx = array_search($step, $keys, true);
    if ($idx === false) $idx = 0;
    $pct = round((($idx + 1) / count($steps)) * 100);
    echo '<!doctype html><html lang="zh-CN"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
    echo '<title>HANU V8 第一代公测版 安装器</title>';
    echo '<link rel="icon" href="ICO/favicon.ico">';
    echo '<style>
    :root{
      color-scheme:light;
      --bg0:#f8fbff;--bg1:#edf4ff;--bg2:#ffffff;
      --text:#101828;--muted:#667085;--soft:#f3f6fb;
      --card:rgba(255,255,255,.82);--card2:rgba(255,255,255,.58);
      --line:rgba(15,23,42,.12);--input:#ffffff;--inputText:#101828;
      --p:#007aff;--p2:#5856d6;--ok:#16a34a;--bad:#ef4444;--warn:#f59e0b;
      --shadow:0 28px 90px rgba(15,23,42,.16);
    }
    [data-mode="dark"]{
      color-scheme:dark;
      --bg0:#050816;--bg1:#0f172a;--bg2:#111827;
      --text:#f8fafc;--muted:#94a3b8;--soft:#111827;
      --card:rgba(15,23,42,.88);--card2:rgba(15,23,42,.66);
      --line:rgba(255,255,255,.13);--input:#0b1220;--inputText:#f8fafc;
      --shadow:0 28px 90px rgba(0,0,0,.34);
    }
    *{box-sizing:border-box}
    html,body{margin:0;min-height:100vh}
    body{
      font-family:-apple-system,BlinkMacSystemFont,"Segoe UI","Microsoft YaHei UI",sans-serif;
      color:var(--text);
      background:
        radial-gradient(circle at 10% 0%,rgba(0,122,255,.18),transparent 26%),
        radial-gradient(circle at 86% 16%,rgba(88,86,214,.16),transparent 30%),
        linear-gradient(135deg,var(--bg0),var(--bg1));
      padding:26px;
      overflow-x:hidden;
    }
    a{text-decoration:none;color:inherit}
    button,input,textarea,select{font:inherit}
    .next-mask{
      position:fixed;inset:0;z-index:100;display:none;place-items:center;
      background:
        radial-gradient(circle at 30% 20%,rgba(0,122,255,.22),transparent 34%),
        radial-gradient(circle at 70% 70%,rgba(88,86,214,.22),transparent 35%),
        linear-gradient(135deg,var(--bg0),var(--bg1));
      opacity:0;
    }
    .next-mask.show{display:grid;animation:fadeIn .22s ease forwards}
    .next-card{
      width:min(380px,calc(100vw - 36px));padding:30px;border-radius:34px;text-align:center;
      background:var(--card);border:1px solid var(--line);box-shadow:var(--shadow);backdrop-filter:blur(24px) saturate(160%);
      animation:floatIn .26s cubic-bezier(.2,.8,.2,1) both;
    }
    .next-logo,.logo{
      width:82px;height:82px;border-radius:28px;margin:0 auto 16px;display:grid;place-items:center;
      color:white;font-size:30px;font-weight:950;background:linear-gradient(135deg,var(--p),var(--p2));
      box-shadow:0 18px 46px rgba(0,122,255,.28);
    }
    .flow-line{height:10px;border-radius:999px;background:rgba(148,163,184,.22);overflow:hidden;margin-top:18px}
    .flow-line span{display:block;height:100%;width:0;background:linear-gradient(90deg,var(--p),var(--p2),#34c759);animation:flow .55s ease forwards}
    @keyframes fadeIn{to{opacity:1}}
    @keyframes floatIn{from{opacity:0;transform:translateY(14px) scale(.98)}to{opacity:1;transform:none}}
    @keyframes flow{to{width:100%}}
    .shell{width:min(1180px,100%);margin:0 auto;display:grid;grid-template-columns:320px minmax(0,1fr);gap:18px;animation:enter .42s cubic-bezier(.2,.8,.2,1) both}
    @keyframes enter{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:none}}
    .side,.main{background:var(--card);border:1px solid var(--line);border-radius:36px;box-shadow:var(--shadow);backdrop-filter:blur(26px) saturate(160%)}
    .side{padding:20px;position:sticky;top:20px;height:calc(100vh - 52px);overflow:auto}
    .main{padding:26px;min-height:calc(100vh - 52px)}
    .brand{display:flex;gap:13px;align-items:center}
    .brand .logo{width:64px;height:64px;border-radius:23px;margin:0;font-size:24px}
    .brand b{font-size:24px;letter-spacing:-.05em}.brand span,.muted{color:var(--muted);line-height:1.75}
    .progress-wrap{margin:22px 0 18px}.progress-top{display:flex;justify-content:space-between;color:var(--muted);font-size:12px;margin-bottom:8px}
    .progress{height:10px;border-radius:999px;background:rgba(148,163,184,.20);overflow:hidden;border:1px solid var(--line)}
    .progress span{display:block;height:100%;width:var(--pct);background:linear-gradient(90deg,var(--p),var(--p2),#34c759)}
    .steps{display:grid;gap:8px}.step{display:flex;align-items:center;gap:10px;padding:11px 12px;border-radius:17px;color:var(--muted);background:var(--card2);border:1px solid transparent}
    .step .dot{width:24px;height:24px;border-radius:10px;display:grid;place-items:center;background:rgba(148,163,184,.20);font-size:12px}
    .step.on{color:white;background:linear-gradient(135deg,var(--p),var(--p2));box-shadow:0 16px 40px rgba(0,122,255,.22)}
    .step.on .dot{background:rgba(255,255,255,.24)}
    .top-actions{display:flex;justify-content:space-between;gap:10px;align-items:center;margin-bottom:18px}
    .theme-toggle{border:1px solid var(--line);background:var(--card2);color:var(--text);border-radius:999px;padding:9px 12px;cursor:pointer}
    h1{font-size:clamp(30px,4vw,48px);letter-spacing:-.06em;margin:0 0 10px}h2{letter-spacing:-.035em}
    .hero{min-height:240px;border-radius:30px;padding:28px;color:white;background:radial-gradient(circle at 82% 20%,rgba(255,255,255,.32),transparent 28%),linear-gradient(135deg,var(--p),var(--p2));display:flex;flex-direction:column;justify-content:space-between;margin-bottom:18px}
    .hero h1{font-size:clamp(48px,7vw,82px);line-height:.9;margin:0}
    .notice{padding:15px 16px;border-radius:20px;background:rgba(0,122,255,.10);border:1px solid rgba(0,122,255,.18);margin:13px 0;color:var(--text)}
    .notice.bad{background:rgba(239,68,68,.10);border-color:rgba(239,68,68,.22)}.notice.warn{background:rgba(245,158,11,.12);border-color:rgba(245,158,11,.22)}
    .grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}.grid3{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px}
    .module{padding:16px;border-radius:24px;background:var(--card2);border:1px solid var(--line)}.module h3{margin:0 0 6px}
    .field{margin:13px 0}.field label{display:flex;justify-content:space-between;gap:8px;color:var(--muted);font-size:13px;margin-bottom:7px}
    .field input,.field textarea,.field select{width:100%;border:1px solid var(--line);border-radius:18px;padding:13px 15px;background:var(--input);color:var(--inputText);outline:none;box-shadow:inset 0 1px 0 rgba(255,255,255,.04)}
    .field textarea{min-height:112px;resize:vertical}.field input::placeholder,.field textarea::placeholder{color:color-mix(in srgb,var(--muted) 78%,transparent)}select option{background:var(--input);color:var(--inputText)}
    .btn{border:0;border-radius:18px;padding:13px 18px;background:linear-gradient(135deg,var(--p),var(--p2));color:white;font-weight:850;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;gap:8px;box-shadow:0 16px 36px rgba(0,122,255,.22)}
    .btn.ghost{background:var(--card2);color:var(--text);border:1px solid var(--line);box-shadow:none}.btn.small{padding:9px 12px;border-radius:14px;font-size:13px}
    .actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:18px}
    .check{display:flex;justify-content:space-between;gap:12px;align-items:center;padding:13px 14px;border-radius:18px;background:var(--card2);border:1px solid var(--line);margin:8px 0}
    .ok{color:var(--ok);font-weight:900}.badtext{color:var(--bad);font-weight:900}.warntext{color:var(--warn);font-weight:900}
    .seg{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin:12px 0}.seg label,.theme-card{display:block;padding:14px;border-radius:18px;background:var(--card2);border:1px solid var(--line);cursor:pointer}
    .theme-card input{margin-right:8px}.swatch{height:46px;border-radius:16px;margin-bottom:10px;background:linear-gradient(135deg,var(--a),var(--b))}
    .mini{font-size:12px;color:var(--muted)}.collapse{border:1px solid var(--line);border-radius:22px;background:var(--card2);margin:12px 0;overflow:hidden}.collapse summary{cursor:pointer;padding:15px 16px;font-weight:850}.collapse .inner{padding:0 16px 16px}
    .row-editor{display:grid;gap:10px}.edit-row{display:grid;grid-template-columns:1.1fr .6fr .7fr auto;gap:10px;align-items:end;padding:12px;border-radius:22px;background:var(--card2);border:1px solid var(--line)}
    .board-row{grid-template-columns:1fr 1.5fr auto}
    .ico-preview{display:flex;align-items:center;gap:14px}.ico-preview img{width:54px;height:54px;border-radius:16px;background:var(--input);border:1px solid var(--line);object-fit:contain}
    @media(max-width:900px){body{padding:12px}.shell{grid-template-columns:1fr}.side{position:relative;height:auto;top:0}.main{min-height:auto;padding:18px}.grid,.grid3,.seg{grid-template-columns:1fr}.steps{grid-template-columns:repeat(2,1fr)}.step{font-size:13px}.brand b{font-size:20px}.edit-row,.board-row{grid-template-columns:1fr}}
    </style>';
    echo '<script>
    (function(){try{var m=localStorage.getItem("hanuInstallerTheme")||"light";document.documentElement.setAttribute("data-mode",m)}catch(e){}})();
    function toggleInstallerTheme(){var r=document.documentElement;var m=r.getAttribute("data-mode")==="dark"?"light":"dark";r.setAttribute("data-mode",m);try{localStorage.setItem("hanuInstallerTheme",m)}catch(e){}}
    function showNextMask(){var m=document.getElementById("nextMask"); if(m){m.classList.add("show");} document.querySelectorAll("button[type=submit],.btn").forEach(function(b){ if(b.tagName==="BUTTON") b.textContent="正在执行下一步"; });}
    document.addEventListener("DOMContentLoaded",function(){
      document.querySelectorAll("form").forEach(function(f){f.addEventListener("submit",function(){showNextMask();});});
      document.querySelectorAll("a.btn[href]").forEach(function(a){a.addEventListener("click",function(e){var href=a.getAttribute("href")||""; if(href.indexOf("index.php?step=")===0){e.preventDefault();showNextMask();setTimeout(function(){location.href=href;},280);}});});
    });
    function addGroupRow(name,level,manage){
      var box=document.getElementById("groupsBox"); if(!box)return;
      var div=document.createElement("div"); div.className="edit-row";
      div.innerHTML=\'<div class="field"><label>成员组名称</label><input name="group_name[]" value="\'+(name||"新成员组")+\'"></div><div class="field"><label>权限等级</label><input name="group_level[]" type="number" value="\'+(level||1)+\'"></div><div class="field"><label>板块权限</label><select name="group_manage[]"><option value="0">普通成员</option><option value="1" \'+(manage?"selected":"")+\'>可管理板块</option></select></div><button type="button" class="btn ghost small" onclick="this.closest(\\\'.edit-row\\\').remove()">删除</button>\';
      box.appendChild(div);
    }
    function addBoardRow(name,desc){
      var box=document.getElementById("boardsBox"); if(!box)return;
      var div=document.createElement("div"); div.className="edit-row board-row";
      div.innerHTML=\'<div class="field"><label>板块名称</label><input name="board_name[]" value="\'+(name||"新板块")+\'"></div><div class="field"><label>板块介绍</label><input name="board_desc[]" value="\'+(desc||"板块介绍")+\'"></div><button type="button" class="btn ghost small" onclick="this.closest(\\\'.edit-row\\\').remove()">删除</button>\';
      box.appendChild(div);
    }
    </script>';
    echo '</head><body>';
    echo '<div id="nextMask" class="next-mask"><div class="next-card"><div class="next-logo">HU</div><h2>正在执行下一步</h2><p class="muted">HANU 正在保存当前步骤并进入下一页...</p><div class="flow-line"><span></span></div></div></div>';
    echo '<div class="shell"><aside class="side"><div class="brand"><div class="logo">HU</div><div><b>HANU</b><span class="muted">V8 第一代公测版安装器</span></div></div>';
    echo '<div class="progress-wrap"><div class="progress-top"><span>安装进度</span><span>'.$pct.'%</span></div><div class="progress" style="--pct:'.$pct.'%"><span></span></div></div><div class="steps">';
    $n=1; foreach($steps as $key=>$label) echo '<div class="step '.($key===$step?'on':'').'"><span class="dot">'.$n++.'</span><span>'.h($label).'</span></div>';
    echo '</div></aside><main class="main"><div class="top-actions"><span class="mini">当前地址：'.h(scheme_host().current_base_path()).'</span><button class="theme-toggle" onclick="toggleInstallerTheme()">切换明暗主题</button></div>';
}

function page_end(): void { echo '</main></div></body></html>'; }
function can_write_dir(string $dir): bool { return is_dir($dir) && is_writable($dir); }
function recursive_copy(string $src, string $dst): void {
    if(!is_dir($src)) throw new RuntimeException("源码目录不存在：" . $src);
    if(!is_dir($dst)) mkdir($dst,0777,true);
    foreach(scandir($src) as $item){
        if($item==='.'||$item==='..') continue;
        $from=$src.'/'.$item; $to=$dst.'/'.$item;
        if(is_dir($from)) recursive_copy($from,$to); else copy($from,$to);
    }
}
function split_sql(string $sql): array {
    $sql=str_replace("\r","\n",$sql); $parts=[]; $buffer=''; $in=false; $quote=''; $len=strlen($sql);
    for($i=0;$i<$len;$i++){ $c=$sql[$i]; if(($c==="'"||$c==='"')&&($i===0||$sql[$i-1]!=='\\')){ if(!$in){$in=true;$quote=$c;} elseif($quote===$c){$in=false;$quote='';} } if($c===';'&&!$in){$part=trim($buffer); if($part!=='')$parts[]=$part; $buffer='';} else $buffer.=$c; }
    $part=trim($buffer); if($part!=='')$parts[]=$part; return $parts;
}
function package_path(string $path): string { return HANU_PACKAGE_ROOT.'/'.ltrim($path,'/'); }
function ensure_runtime_dirs(): void {
    foreach(['config','data','data/cache','data/uploads','data/lang','ICO'] as $rel){
        $dir=package_path($rel); if(!is_dir($dir)) mkdir($dir,0777,true); if(!is_writable($dir)) @chmod($dir,0777);
        if(!is_writable($dir)) throw new RuntimeException('目录不可写：'.$dir.'。请在服务器文件管理器中把权限改为 755、775 或 777。');
    }
}
function selected_lang(): string { return $_SESSION['hanu_install']['language'] ?? 'zh_cn'; }
function validate_prefix(string $prefix): string { $prefix=preg_replace('/[^a-zA-Z0-9_]/','',$prefix); return $prefix ?: 'hanu_'; }
function db_test_or_create(array $cfg): void {
    if(($cfg['db_engine']??'mysql')!=='mysql') throw new RuntimeException('当前版本内置 MySQL / MariaDB 连接。');
    $host=$cfg['db_host']; $port=$cfg['db_port']; $dbname=$cfg['db_name'];
    if(($cfg['create_mode']??'existing')==='root_create'){
        $pdo=new PDO('mysql:host='.$host.';port='.$port.';charset=utf8mb4',$cfg['root_user']??'root',$cfg['root_pass']??'',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
        $safeDb=str_replace('`','``',$dbname);
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$safeDb}` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        if(($cfg['db_user']??'')!==($cfg['root_user']??'root')){
            $safeUser=str_replace("'","\\'",$cfg['db_user']); $safePass=str_replace("'","\\'",$cfg['db_pass']);
            $pdo->exec("CREATE USER IF NOT EXISTS '{$safeUser}'@'localhost' IDENTIFIED BY '{$safePass}'");
            $pdo->exec("GRANT ALL PRIVILEGES ON `{$safeDb}`.* TO '{$safeUser}'@'localhost'");
            $pdo->exec("FLUSH PRIVILEGES");
        }
    }
    new PDO('mysql:host='.$host.';port='.$port.';dbname='.$dbname.';charset=utf8mb4',$cfg['db_user'],$cfg['db_pass'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
}


if($step !== 'welcome' && $step !== 'finish' && empty($_SESSION['hanu_install']['agreed'])){
    header('Location: index.php?step=welcome&need_agree=1');
    exit;
}

if(file_exists(package_path('data/install.lock')) && $step!=='finish'){
    page_start('finish',$steps); echo '<h1>HANU 已安装</h1><p class="muted">检测到 data/install.lock。</p><a class="btn" href="app/home.php">进入 HANU</a>'; page_end(); exit;
}

if($step==='welcome'){
    $agreeError='';
    if($_SERVER['REQUEST_METHOD']==='POST'){
        if(($_POST['agree']??'')==='1'){
            $_SESSION['hanu_install']['agreed']=true;
            header('Location: index.php?step=check');
            exit;
        }
        $agreeError='请先阅读并同意用户协议。';
    }
    if(isset($_GET['need_agree'])) $agreeError='请先阅读并同意用户协议。';
    page_start($step,$steps);
    echo '<section class="hero"><div><div class="logo" style="margin:0 0 18px">HU</div><h1>HANU V8 第一代公测版</h1></div><p>第一代公测版社交系统。支持自定义站点名称、多语言、成员等级、签到积分、称号、群聊、媒体动态、外链安全页、WAF 拦截页和反馈渠道。</p></section>';
    if($agreeError) echo '<div class="notice bad">'.h($agreeError).'</div>';
    echo '<div class="grid"><div class="module"><h3>安全策略</h3><p class="muted">用户触发 WAF 后会进入专门拦截页。每日 5 次违规会被封禁 1 小时；再次违规逐级升级为 1 周、1 月、1 年，最终永久封禁。</p></div><div class="module"><h3>完整系统</h3><p class="muted">包含动态、消息、好友、群聊、板块、签到、积分、等级、称号和管理后台。</p></div><div class="module"><h3>多语言</h3><p class="muted">内置简体中文、繁體中文和 English 语言包，核心功能全部覆盖。</p></div><div class="module"><h3>反馈渠道</h3><p class="muted">公测反馈邮箱：qm66668888@qq.com</p></div></div>';
    echo '<form method="post" action="index.php?step=welcome"><details class="collapse" open><summary>用户协议与安全规则</summary><div class="inner"><p class="muted">1. 本系统用于合法社交交流，禁止发布恶意脚本、攻击代码、违法内容、诈骗链接和破坏性内容。<br>2. 系统包含基础 WAF 与外链中间页，会记录安全触发日志和外链访问日志，供管理员审计。<br>3. 用户每天有 5 次安全触发机会；达到阈值将封禁 1 小时。之后再次违规会升级为 1 周、1 月、1 年，最终永久封禁。<br>4. 安装者应自行遵守当地法律法规并对站点运营负责。</p></div></details><label class="module" style="display:flex;gap:10px;align-items:center"><input type="checkbox" name="agree" value="1"> 我已阅读并同意用户协议与安全规则</label><div class="actions"><button class="btn" type="submit">同意并开始安装</button></div></form>';
    page_end(); exit;
}

if($step==='check'){
    $checks=[
        'PHP 版本 >= 7.4'=>version_compare(PHP_VERSION,'7.4.0','>='),
        'PDO 扩展'=>extension_loaded('pdo'),
        'PDO MySQL 扩展'=>extension_loaded('pdo_mysql'),
        '文件上传功能'=>ini_get('file_uploads')==='1',
        'source 源码文件夹存在'=>is_dir(HANU_SOURCE_DIR),
        'language 语言包文件夹存在'=>is_dir(HANU_LANGUAGE_DIR),
        'ICO 文件夹存在'=>is_dir(package_path('ICO')),
        '当前根目录可写'=>is_writable(HANU_PACKAGE_ROOT),
        'config 目录可创建或可写'=>is_dir(package_path('config'))?is_writable(package_path('config')):is_writable(HANU_PACKAGE_ROOT),
        'data 目录可创建或可写'=>is_dir(package_path('data'))?is_writable(package_path('data')):is_writable(HANU_PACKAGE_ROOT),
    ];
    $ok=!in_array(false,$checks,true);
    page_start($step,$steps);
    echo '<h1>环境检测</h1><p class="muted">自动检测服务器环境、上传能力、目录权限和图标目录。</p>';
    echo '<div class="grid"><div class="module"><h3>服务器</h3><p class="muted">PHP：'.h(PHP_VERSION).'<br>系统：'.h(PHP_OS).'<br>上传限制：'.h(ini_get('upload_max_filesize')).'</p></div><div class="module"><h3>访问路径</h3><p class="muted">'.h(scheme_host().current_base_path()).'</p></div></div>';
    foreach($checks as $name=>$pass) echo '<div class="check"><span>'.h($name).'</span><b class="'.($pass?'ok':'badtext').'">'.($pass?'通过':'失败').'</b></div>';
    echo '<div class="actions">'.($ok?'<a class="btn" href="index.php?step=language">下一步</a>':'<a class="btn ghost" href="index.php?step=check">重新检测</a>').'</div>';
    page_end(); exit;
}

if($step==='language'){
    if($_SERVER['REQUEST_METHOD']==='POST'){ $lang=$_POST['language']??'zh_cn'; if(!in_array($lang,['zh_cn','zh_tw','en'],true))$lang='zh_cn'; $_SESSION['hanu_install']['language']=$lang; header('Location: index.php?step=database'); exit; }
    page_start($step,$steps);
    echo '<h1>语言选择</h1><form method="post" action="index.php?step=language"><div class="seg"><label><input type="radio" name="language" value="zh_cn" checked> 简体中文</label><label><input type="radio" name="language" value="zh_tw"> 繁體中文</label><label><input type="radio" name="language" value="en"> English</label></div><div class="actions"><button class="btn" type="submit">下一步</button></div></form>';
    page_end(); exit;
}

if($step==='database'){
    $err='';
    if($_SERVER['REQUEST_METHOD']==='POST'){
        $cfg=[
            'db_engine'=>$_POST['db_engine']??'mysql','db_version'=>$_POST['db_version']??'mysql57','create_mode'=>$_POST['create_mode']??'existing',
            'db_host'=>trim($_POST['db_host']??'127.0.0.1'),'db_port'=>trim($_POST['db_port']??'3306'),'db_name'=>trim($_POST['db_name']??''),
            'db_user'=>trim($_POST['db_user']??''),'db_pass'=>(string)($_POST['db_pass']??''),'root_user'=>trim($_POST['root_user']??'root'),'root_pass'=>(string)($_POST['root_pass']??''),
            'db_charset'=>'utf8mb4','table_prefix'=>validate_prefix($_POST['table_prefix']??'hanu_')
        ];
        try{ db_test_or_create($cfg); unset($cfg['root_pass']); $_SESSION['hanu_install']['database']=$cfg; header('Location: index.php?step=site'); exit; }
        catch(Throwable $e){ $err='数据库处理失败：'.$e->getMessage(); }
    }
    page_start($step,$steps);
    echo '<h1>数据库配置</h1><p class="muted">支持已有数据库，也支持 root 权限自动创建数据库。</p>'.($err?'<div class="notice bad">'.h($err).'</div>':'');
    echo '<form method="post" action="index.php?step=database"><div class="grid"><div class="field"><label>数据库类型</label><select name="db_engine"><option value="mysql">MySQL / MariaDB</option></select></div><div class="field"><label>数据库版本</label><select name="db_version"><option value="mysql57">MySQL 5.7</option><option value="mysql80">MySQL 8.x</option><option value="mariadb">MariaDB</option></select></div></div><div class="field"><label>数据库模式</label><select name="create_mode"><option value="existing">使用已有数据库 / 已有账号</option><option value="root_create">使用 root 权限自动创建数据库</option></select></div><div class="grid"><div class="field"><label>数据库地址</label><input name="db_host" value="127.0.0.1"></div><div class="field"><label>端口</label><input name="db_port" value="3306"></div></div><div class="grid"><div class="field"><label>数据库名称</label><input name="db_name" placeholder="hanu"></div><div class="field"><label>数据表前缀</label><input name="table_prefix" value="hanu_"></div></div><div class="grid"><div class="field"><label>应用数据库用户名</label><input name="db_user"></div><div class="field"><label>应用数据库密码</label><input name="db_pass" type="password"></div></div><details class="collapse"><summary>可选：root 创建数据库模块</summary><div class="inner"><p class="muted">root 密码只用于本次创建数据库，不会写入配置。</p><div class="grid"><div class="field"><label>root 用户名</label><input name="root_user" value="root"></div><div class="field"><label>root 密码</label><input name="root_pass" type="password"></div></div></div></details><div class="actions"><button class="btn" type="submit">测试数据库并继续</button></div></form>';
    page_end(); exit;
}

if($step==='site'){
    if($_SERVER['REQUEST_METHOD']==='POST'){
        $_SESSION['hanu_install']['site']=['site_name'=>trim($_POST['site_name']??'HANU'),'site_desc'=>trim($_POST['site_desc']??'HANU 社交软件'),'theme'=>$_POST['theme']??'blue'];
        header('Location: index.php?step=admin'); exit;
    }
    page_start($step,$steps);
    $themes=[
        ['blue','蓝色','#007aff','#5856d6'],['green','绿色','#16a34a','#06b6d4'],['pink','粉色','#ec4899','#8b5cf6'],['dark','深色','#0f172a','#475569'],
        ['sunset','日落','#f97316','#ec4899'],['cyber','赛博','#06b6d4','#7c3aed'],['mint','薄荷','#10b981','#14b8a6'],['grape','葡萄','#8b5cf6','#d946ef'],['gold','金色','#f59e0b','#ef4444'],['graphite','石墨','#334155','#0f172a']
    ];
    echo '<h1>站点设置</h1><p class="muted">这里设置的是用户看到的网站名字。安装后站主也可以在后台继续修改，不会固定成 HANU。</p><form method="post" action="index.php?step=site"><div class="field"><label>网站名称</label><input name="site_name" value="HANU"></div><div class="field"><label>网站简介</label><input name="site_desc" value="HANU 社交软件"></div><h2>选择默认主题</h2><div class="grid3">';
    foreach($themes as $t){ echo '<label class="theme-card"><div class="swatch" style="--a:'.$t[2].';--b:'.$t[3].'"></div><input type="radio" name="theme" value="'.$t[0].'" '.($t[0]==='blue'?'checked':'').'> '.h($t[1]).'</label>'; }
    echo '</div><div class="notice"><div class="ico-preview"><img src="ICO/favicon.ico" onerror="this.style.display=\'none\'"><div><b>默认图标</b><p class="muted">系统会自动读取 ICO/favicon.ico 作为默认图标。</p></div></div></div><div class="actions"><button class="btn" type="submit">下一步</button></div></form>';
    page_end(); exit;
}

if($step==='admin'){
    $err='';
    if($_SERVER['REQUEST_METHOD']==='POST'){ $admin=trim($_POST['admin_user']??'admin'); $pass=(string)($_POST['admin_pass']??''); $pass2=(string)($_POST['admin_pass2']??''); if($admin===''||$pass==='')$err='请填写管理员用户名和密码。'; elseif($pass!==$pass2)$err='两次密码不一致。'; else{$_SESSION['hanu_install']['admin']=['username'=>$admin,'password'=>$pass]; header('Location: index.php?step=groups'); exit;} }
    page_start($step,$steps);
    echo '<h1>创建管理员</h1><p class="muted">HANU 不分超级管理员，管理员就是最高权限。</p>'.($err?'<div class="notice bad">'.h($err).'</div>':'').'<form method="post" action="index.php?step=admin"><div class="grid"><div class="field"><label>管理员用户名</label><input name="admin_user" value="admin"></div><div class="field"><label>默认头像文字</label><input value="HU" disabled></div></div><div class="grid"><div class="field"><label>管理员密码</label><input name="admin_pass" type="password"></div><div class="field"><label>确认密码</label><input name="admin_pass2" type="password"></div></div><div class="actions"><button class="btn" type="submit">下一步</button></div></form>';
    page_end(); exit;
}

if($step==='groups'){
    if($_SERVER['REQUEST_METHOD']==='POST'){
        $names=$_POST['group_name']??[]; $levels=$_POST['group_level']??[]; $manages=$_POST['group_manage']??[]; $groups=[];
        foreach($names as $i=>$name){ $name=trim((string)$name); if($name==='')continue; $groups[]=['name'=>$name,'level'=>(int)($levels[$i]??1),'manage'=>(int)($manages[$i]??0)]; }
        if(!$groups)$groups=[['name'=>'注册会员','level'=>1,'manage'=>0]];
        $_SESSION['hanu_install']['groups']=$groups; header('Location: index.php?step=boards'); exit;
    }
    page_start($step,$steps);
    echo '<h1>成员组和权限等级</h1><p class="muted">图形化添加成员组，不再手写配置。</p><form method="post" action="index.php?step=groups"><div id="groupsBox" class="row-editor"></div><div class="actions"><button type="button" class="btn ghost" onclick="addGroupRow()">添加成员组</button><button class="btn" type="submit">下一步</button></div></form><script>addGroupRow("新人",1,0);addGroupRow("普通会员",3,0);addGroupRow("VIP会员",8,0);addGroupRow("高级会员",15,0);addGroupRow("版主",30,1);</script>';
    page_end(); exit;
}

if($step==='boards'){
    if($_SERVER['REQUEST_METHOD']==='POST'){
        $names=$_POST['board_name']??[]; $descs=$_POST['board_desc']??[]; $boards=[];
        foreach($names as $i=>$name){ $name=trim((string)$name); if($name==='')continue; $boards[]=['name'=>$name,'desc'=>trim((string)($descs[$i]??''))]; }
        if(!$boards)$boards=[['name'=>'综合交流','desc'=>'欢迎来到 HANU']];
        $_SESSION['hanu_install']['boards']=$boards; header('Location: index.php?step=install'); exit;
    }
    page_start($step,$steps);
    echo '<h1>默认板块</h1><p class="muted">图形化创建社交板块，安装后管理员也能继续添加。</p><form method="post" action="index.php?step=boards"><div id="boardsBox" class="row-editor"></div><div class="actions"><button type="button" class="btn ghost" onclick="addBoardRow()">添加板块</button><button class="btn" type="submit">开始安装</button></div></form><script>addBoardRow("综合交流","欢迎来到 HANU");addBoardRow("生活日常","分享生活、图片和视频");addBoardRow("技术讨论","讨论技术与作品");addBoardRow("资源分享","分享有价值的内容");</script>';
    page_end(); exit;
}

if($step==='install'){
    page_start($step,$steps); echo '<h1>正在安装</h1><div class="notice">正在复制源码、写配置、导入数据库、创建管理员、成员等级、板块和语言包。</div>';
    try{
        $data=$_SESSION['hanu_install']??[]; foreach(['database','site','admin','groups','boards'] as $r){ if(!isset($data[$r]))throw new RuntimeException('安装信息不完整，请返回重新填写。'); }
        recursive_copy(HANU_SOURCE_DIR,HANU_PACKAGE_ROOT); ensure_runtime_dirs();
        $cfg=['app_name'=>'HANU','app_version'=>'1.0.0-beta.1','update_repo'=>'macbo2013/HANU','update_branch'=>'main','version_label'=>'V8 第一代公测版','support_email'=>'qm66668888@qq.com','language'=>selected_lang(),'site_name'=>$data['site']['site_name'],'site_desc'=>$data['site']['site_desc'],'default_theme'=>$data['site']['theme'],'db_engine'=>$data['database']['db_engine']??'mysql','db_version'=>$data['database']['db_version']??'mysql57','db_host'=>$data['database']['db_host'],'db_port'=>$data['database']['db_port'],'db_name'=>$data['database']['db_name'],'db_user'=>$data['database']['db_user'],'db_pass'=>$data['database']['db_pass'],'db_charset'=>'utf8mb4','table_prefix'=>$data['database']['table_prefix'],'cookie_name'=>'hanu_token'];
        file_put_contents(package_path('config/config.php'),"<?php\nreturn ".var_export($cfg,true).";\n");
        copy(HANU_LANGUAGE_DIR.'/'.selected_lang().'.php',package_path('data/lang/current.php')); foreach(['zh_cn','zh_tw','en'] as $lf)copy(HANU_LANGUAGE_DIR.'/'.$lf.'.php',package_path('data/lang/'.$lf.'.php'));
        $pdo=new PDO('mysql:host='.$cfg['db_host'].';port='.$cfg['db_port'].';dbname='.$cfg['db_name'].';charset=utf8mb4',$cfg['db_user'],$cfg['db_pass'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
        $schema=str_replace('{prefix}',$cfg['table_prefix'],file_get_contents(package_path('install_schema.sql'))); foreach(split_sql($schema) as $sql)if(trim($sql)!=='')$pdo->exec($sql);
        $prefix=$cfg['table_prefix']; $now=time();
        $groupStmt=$pdo->prepare("INSERT INTO `{$prefix}user_groups`(name,level,can_manage_board,created_at) VALUES(?,?,?,?)"); $firstGroupId=null;
        foreach($data['groups'] as $g){ $groupStmt->execute([$g['name'],(int)$g['level'],(int)$g['manage'],$now]); if($firstGroupId===null)$firstGroupId=(int)$pdo->lastInsertId(); }
        $boardStmt=$pdo->prepare("INSERT INTO `{$prefix}boards`(name,description,sort_order,created_at) VALUES(?,?,?,?)"); $sort=1;
        foreach($data['boards'] as $b){ $boardStmt->execute([$b['name'],$b['desc'],$sort++,$now]); }
        $admin=$data['admin']; $userStmt=$pdo->prepare("INSERT INTO `{$prefix}users`(username,password_hash,avatar_text,avatar_path,signature,theme,role,group_id,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?,?,?)");
        $userStmt->execute([$admin['username'],password_hash($admin['password'],PASSWORD_DEFAULT),'HU',null,'HANU 管理员',$cfg['default_theme'],'admin',$firstGroupId,$now,$now]);
        $settingStmt=$pdo->prepare("INSERT INTO `{$prefix}settings`(name,value) VALUES(?,?) ON DUPLICATE KEY UPDATE value=VALUES(value)");
        $settingStmt->execute(['site_name',$cfg['site_name']]);$settingStmt->execute(['site_desc',$cfg['site_desc']]);$settingStmt->execute(['language',$cfg['language']]);$settingStmt->execute(['installed_at',date('c')]);$settingStmt->execute(['app_version','1.0.0-beta.1']);$settingStmt->execute(['update_repo','macbo2013/HANU']);$settingStmt->execute(['update_branch','main']);$settingStmt->execute(['support_email','qm66668888@qq.com']);$settingStmt->execute(['version_label','V8 第一代公测版']);
        $titleStmt=$pdo->prepare("INSERT INTO `{$prefix}titles`(name,color,min_points,created_at) VALUES(?,?,?,?)");
        foreach([['新人上路','#3b82f6',0],['活跃会员','#10b981',100],['人气达人','#f59e0b',500],['社区明星','#ec4899',1000],['传奇用户','#8b5cf6',3000]] as $titleRow){
            $titleStmt->execute([$titleRow[0],$titleRow[1],$titleRow[2],$now]);
        }

        file_put_contents(package_path('data/install.lock'),"installed=".date('c')."\n");
        echo '<div class="notice"><b>安装成功。</b>HANU V8 第一代公测版 已部署到当前根目录。</div><div class="actions"><a class="btn" href="index.php?step=finish">完成安装</a></div>';
    }catch(Throwable $e){ echo '<div class="notice bad">安装失败：'.h($e->getMessage()).'</div><div class="actions"><a class="btn ghost" href="index.php?step=database">返回数据库设置</a></div>'; }
    page_end(); exit;
}

if($step==='finish'){
    page_start($step,$steps);
    echo '<h1>HANU V8 第一代公测版 安装完成</h1><p class="muted">现在可以进入 HANU 社交软件。</p><div class="notice warn">请删除安装文件：source/、language/、README.md。根目录 index.php 已被正式程序覆盖，不要删除正式 index.php。ICO/ 可以保留。</div><div class="actions"><a class="btn" href="app/home.php">进入 HANU</a></div>';
    page_end(); exit;
}
