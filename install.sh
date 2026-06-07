#!/usr/bin/env bash
set -Eeuo pipefail

APP_NAME="HANU"
APP_VERSION="V9 Public Beta 2"
DEFAULT_REPO_URL="https://github.com/macbo2013/HANU.git"
DEFAULT_WEB_ROOT="/var/www/hanu"

RED="\033[31m"
GREEN="\033[32m"
YELLOW="\033[33m"
BLUE="\033[34m"
CYAN="\033[36m"
BOLD="\033[1m"
RESET="\033[0m"

HAS_NGINX=0
HAS_APACHE=0
HAS_PHP=0
HAS_PHP_FPM=0
HAS_MYSQL=0
HAS_GIT=0
HAS_UNZIP=0
HAS_CURL=0
HAS_PANEL=0
PKG_MANAGER=""
WEB_ROOT=""
SERVER_NAME=""
WEB_SERVER_CHOICE="nginx"

say(){ echo -e "${BLUE}==>${RESET} $*"; }
ok(){ echo -e "${GREEN}OK:${RESET} $*"; }
warn(){ echo -e "${YELLOW}WARN:${RESET} $*"; }
bad(){ echo -e "${RED}NO:${RESET} $*"; }
info(){ echo -e "${CYAN}INFO:${RESET} $*"; }
fail(){ echo -e "${RED}ERROR:${RESET} $*"; exit 1; }

pause(){
  read -rp "按 Enter 继续..."
}

require_root(){
  if [[ "${EUID}" -ne 0 ]]; then
    fail "请用 root 运行：sudo bash install.sh"
  fi
}

detect_pkg_manager(){
  if command -v apt-get >/dev/null 2>&1; then
    PKG_MANAGER="apt"
  elif command -v dnf >/dev/null 2>&1; then
    PKG_MANAGER="dnf"
  elif command -v yum >/dev/null 2>&1; then
    PKG_MANAGER="yum"
  else
    PKG_MANAGER="unknown"
  fi
}

detect_panel(){
  HAS_PANEL=0
  if [[ -d /www/server/panel ]] || command -v bt >/dev/null 2>&1; then
    HAS_PANEL=1
  fi
  if [[ -d /www/server/php ]] || [[ -d /www/server/nginx ]] || [[ -d /www/server/apache ]]; then
    HAS_PANEL=1
  fi
}

detect_nginx(){
  if command -v nginx >/dev/null 2>&1 || systemctl list-unit-files 2>/dev/null | grep -Eq '^nginx\.service'; then
    HAS_NGINX=1
  else
    HAS_NGINX=0
  fi
}

detect_apache(){
  if command -v apache2 >/dev/null 2>&1 || command -v httpd >/dev/null 2>&1 || systemctl list-unit-files 2>/dev/null | grep -Eq '^(apache2|httpd)\.service'; then
    HAS_APACHE=1
  else
    HAS_APACHE=0
  fi
}

detect_php(){
  if command -v php >/dev/null 2>&1; then
    HAS_PHP=1
  else
    HAS_PHP=0
  fi

  HAS_PHP_FPM=0
  if systemctl list-unit-files 2>/dev/null | grep -Eq 'php.*fpm'; then
    HAS_PHP_FPM=1
  fi
  if find /run/php /var/run/php -type s -name "php*-fpm.sock" 2>/dev/null | grep -q .; then
    HAS_PHP_FPM=1
  fi
}

detect_mysql(){
  if command -v mysql >/dev/null 2>&1 || command -v mariadb >/dev/null 2>&1; then
    HAS_MYSQL=1
  else
    HAS_MYSQL=0
  fi
}

detect_basic_tools(){
  command -v git >/dev/null 2>&1 && HAS_GIT=1 || HAS_GIT=0
  command -v unzip >/dev/null 2>&1 && HAS_UNZIP=1 || HAS_UNZIP=0
  command -v curl >/dev/null 2>&1 && HAS_CURL=1 || HAS_CURL=0
}

php_version_ok(){
  if [[ "$HAS_PHP" -ne 1 ]]; then
    return 1
  fi
  php -r 'exit(version_compare(PHP_VERSION, "7.4.0", ">=") ? 0 : 1);' >/dev/null 2>&1
}

php_ext_ok(){
  local ext="$1"
  if [[ "$HAS_PHP" -ne 1 ]]; then
    return 1
  fi
  php -m 2>/dev/null | tr '[:upper:]' '[:lower:]' | grep -qx "${ext,,}"
}

print_header(){
  clear || true
  echo -e "${BOLD}${CYAN}"
  echo "  ██╗  ██╗ █████╗ ███╗   ██╗██╗   ██╗"
  echo "  ██║  ██║██╔══██╗████╗  ██║██║   ██║"
  echo "  ███████║███████║██╔██╗ ██║██║   ██║"
  echo "  ██╔══██║██╔══██║██║╚██╗██║██║   ██║"
  echo "  ██║  ██║██║  ██║██║ ╚████║╚██████╔╝"
  echo "  ╚═╝  ╚═╝╚═╝  ╚═╝╚═╝  ╚═══╝ ╚═════╝ "
  echo -e "${RESET}"
  echo -e "${BOLD}${APP_NAME} ${APP_VERSION} Linux Installer${RESET}"
  echo
}

run_detection(){
  detect_pkg_manager
  detect_panel
  detect_nginx
  detect_apache
  detect_php
  detect_mysql
  detect_basic_tools
}

status_line(){
  local name="$1"
  local value="$2"
  local detail="${3:-}"
  if [[ "$value" -eq 1 ]]; then
    ok "${name} ${detail}"
  else
    bad "${name} ${detail}"
  fi
}

print_env_report(){
  print_header
  say "正在检测服务器环境"
  echo

  status_line "Nginx" "$HAS_NGINX" "$(command -v nginx >/dev/null 2>&1 && nginx -v 2>&1 || true)"
  status_line "Apache / httpd" "$HAS_APACHE"
  status_line "PHP CLI" "$HAS_PHP" "$(command -v php >/dev/null 2>&1 && php -v | head -n 1 || true)"
  status_line "PHP-FPM" "$HAS_PHP_FPM"
  status_line "MySQL / MariaDB 客户端" "$HAS_MYSQL"
  status_line "Git" "$HAS_GIT"
  status_line "Unzip" "$HAS_UNZIP"
  status_line "Curl" "$HAS_CURL"

  if [[ "$HAS_PANEL" -eq 1 ]]; then
    ok "检测到服务器面板环境，可能是宝塔/aaPanel 或同类面板"
  else
    warn "未检测到常见服务器面板"
  fi

  echo
  say "PHP 扩展检测"
  local exts=("pdo" "pdo_mysql" "gd" "mbstring" "curl" "zip" "xml")
  for e in "${exts[@]}"; do
    if php_ext_ok "$e"; then
      ok "php-${e}"
    else
      bad "php-${e}"
    fi
  done

  if php_version_ok; then
    ok "PHP 版本 >= 7.4"
  else
    bad "PHP 版本不足或未安装，HANU 推荐 PHP 7.4+"
  fi
}

environment_ready(){
  local ok_all=1

  if [[ "$HAS_NGINX" -ne 1 && "$HAS_APACHE" -ne 1 ]]; then
    ok_all=0
  fi

  if [[ "$HAS_PHP" -ne 1 ]]; then
    ok_all=0
  fi

  if ! php_version_ok; then
    ok_all=0
  fi

  if [[ "$HAS_MYSQL" -ne 1 ]]; then
    ok_all=0
  fi

  if [[ "$HAS_GIT" -ne 1 || "$HAS_UNZIP" -ne 1 || "$HAS_CURL" -ne 1 ]]; then
    ok_all=0
  fi

  local exts=("pdo" "pdo_mysql" "gd" "mbstring" "curl" "zip" "xml")
  for e in "${exts[@]}"; do
    if ! php_ext_ok "$e"; then
      ok_all=0
    fi
  done

  [[ "$ok_all" -eq 1 ]]
}

print_failed_options(){
  echo
  warn "环境检测未完全通过。你可以选择下面一种方式继续："
  echo
  echo "  1) 自动安装推荐环境：Nginx + PHP-FPM + MariaDB"
  echo "  2) 不安装环境，只继续部署 HANU 代码"
  echo "  3) 查看手动安装 / 面板安装建议，然后退出"
  echo "  4) 重新检测"
  echo
}

print_manual_advice(){
  print_header
  echo -e "${BOLD}手动安装建议${RESET}"
  echo
  echo "HANU 需要这些组件："
  echo
  echo "  - Nginx 或 Apache"
  echo "  - PHP 7.4+"
  echo "  - PHP 扩展：pdo、pdo_mysql、gd、mbstring、curl、zip、xml"
  echo "  - MySQL 5.7+ 或 MariaDB"
  echo "  - Git、Unzip、Curl"
  echo
  echo "如果你是新手，可以使用服务器面板，例如宝塔面板 / aaPanel。"
  echo "建议到对应面板的官方网站复制最新版安装命令，不要随便复制陌生网站的命令。"
  echo
  echo "面板安装后，推荐在面板里安装："
  echo
  echo "  - Nginx"
  echo "  - PHP 7.4 / 8.0 / 8.1"
  echo "  - MySQL 5.7 或 MariaDB"
  echo "  - PHP 扩展：fileinfo、mbstring、mysqli、pdo_mysql、gd、curl、zip"
  echo
  echo "安装好环境后，再运行："
  echo
  echo "  sudo bash install.sh"
  echo
  pause
}

ask_environment_action(){
  while true; do
    print_failed_options
    read -rp "请选择 [1/2/3/4]: " CHOICE
    case "${CHOICE}" in
      1)
        install_packages
        start_services
        run_detection
        print_env_report
        if environment_ready; then
          ok "环境已准备完成"
          break
        else
          warn "自动安装后仍有项目未通过，请查看上方检测结果。"
          print_manual_advice
          exit 1
        fi
        ;;
      2)
        warn "你选择跳过环境安装。若服务器环境不完整，HANU 可能无法运行。"
        break
        ;;
      3)
        print_manual_advice
        exit 0
        ;;
      4)
        run_detection
        print_env_report
        if environment_ready; then
          ok "环境检测通过"
          break
        fi
        ;;
      *)
        warn "请输入 1、2、3 或 4"
        ;;
    esac
  done
}

install_packages(){
  say "准备安装推荐环境"
  if [[ "$PKG_MANAGER" == "apt" ]]; then
    apt-get update
    DEBIAN_FRONTEND=noninteractive apt-get install -y \
      nginx mariadb-server git unzip curl ca-certificates rsync \
      php-fpm php-cli php-mysql php-gd php-mbstring php-curl php-zip php-xml
  elif [[ "$PKG_MANAGER" == "dnf" ]]; then
    dnf install -y nginx mariadb-server git unzip curl rsync \
      php php-fpm php-cli php-mysqlnd php-gd php-mbstring php-curl php-zip php-xml
  elif [[ "$PKG_MANAGER" == "yum" ]]; then
    yum install -y epel-release || true
    yum install -y nginx mariadb-server git unzip curl rsync \
      php php-fpm php-cli php-mysqlnd php-gd php-mbstring php-curl php-zip php-xml
  else
    fail "无法自动安装。未识别包管理器，请手动安装环境。"
  fi
}

start_one_service(){
  local svc="$1"
  if systemctl list-unit-files 2>/dev/null | grep -q "^${svc}\.service"; then
    systemctl enable "$svc" || true
    systemctl restart "$svc" || true
  fi
}

start_services(){
  say "启动 Web / PHP / MySQL 服务"

  start_one_service nginx
  start_one_service apache2
  start_one_service httpd
  start_one_service mariadb
  start_one_service mysql

  local fpm_service=""
  fpm_service="$(systemctl list-unit-files 2>/dev/null | awk '/php.*fpm/ {print $1; exit}' || true)"
  if [[ -n "$fpm_service" ]]; then
    systemctl enable "$fpm_service" || true
    systemctl restart "$fpm_service" || true
  fi
}

choose_web_server(){
  echo
  say "选择 Web 服务器"
  if [[ "$HAS_NGINX" -eq 1 && "$HAS_APACHE" -eq 1 ]]; then
    echo "检测到 Nginx 和 Apache。推荐使用 Nginx。"
    echo "  1) Nginx"
    echo "  2) Apache"
    read -rp "请选择 [1/2，默认 1]: " WS
    WS="${WS:-1}"
    [[ "$WS" == "2" ]] && WEB_SERVER_CHOICE="apache" || WEB_SERVER_CHOICE="nginx"
  elif [[ "$HAS_NGINX" -eq 1 ]]; then
    WEB_SERVER_CHOICE="nginx"
  elif [[ "$HAS_APACHE" -eq 1 ]]; then
    WEB_SERVER_CHOICE="apache"
  else
    warn "未检测到 Web 服务器，默认按 Nginx 配置。"
    WEB_SERVER_CHOICE="nginx"
  fi
  ok "将使用：${WEB_SERVER_CHOICE}"
}

get_php_fpm_sock(){
  local sock
  sock="$(find /run/php /var/run/php -type s -name "php*-fpm.sock" 2>/dev/null | head -n 1 || true)"
  if [[ -n "$sock" ]]; then
    echo "unix:$sock"
    return
  fi
  echo "127.0.0.1:9000"
}

deploy_code(){
  read -rp "网站目录 [${DEFAULT_WEB_ROOT}]: " WEB_ROOT
  WEB_ROOT="${WEB_ROOT:-$DEFAULT_WEB_ROOT}"

  read -rp "GitHub 仓库地址 [${DEFAULT_REPO_URL}]: " REPO_URL
  REPO_URL="${REPO_URL:-$DEFAULT_REPO_URL}"

  say "部署代码到 ${WEB_ROOT}"
  mkdir -p "$WEB_ROOT"

  if [[ -f "./index.php" && -d "./source" ]]; then
    say "检测到当前目录就是 HANU 安装包，直接复制当前目录文件"
    if command -v rsync >/dev/null 2>&1; then
      rsync -a --delete --exclude ".git" ./ "$WEB_ROOT"/
    else
      cp -a . "$WEB_ROOT"/
    fi
  else
    if [[ -d "$WEB_ROOT/.git" ]]; then
      git -C "$WEB_ROOT" pull
    else
      rm -rf "$WEB_ROOT"/*
      git clone "$REPO_URL" "$WEB_ROOT"
    fi
  fi

  mkdir -p "$WEB_ROOT/config" "$WEB_ROOT/data/cache" "$WEB_ROOT/data/uploads" "$WEB_ROOT/data/lang" "$WEB_ROOT/ICO"

  chown -R www-data:www-data "$WEB_ROOT" 2>/dev/null || chown -R nginx:nginx "$WEB_ROOT" 2>/dev/null || chown -R apache:apache "$WEB_ROOT" 2>/dev/null || true
  find "$WEB_ROOT" -type d -exec chmod 755 {} \;
  find "$WEB_ROOT" -type f -exec chmod 644 {} \;
  chmod -R 775 "$WEB_ROOT/config" "$WEB_ROOT/data" "$WEB_ROOT/ICO" || true

  ok "代码部署完成"
}

create_database_optional(){
  echo
  read -rp "是否现在创建 MySQL 数据库？安装器里也能填数据库，所以不懂可以输入 n [y/N]: " CREATE_DB
  CREATE_DB="${CREATE_DB:-N}"
  if [[ ! "$CREATE_DB" =~ ^[Yy]$ ]]; then
    warn "跳过数据库创建。稍后打开网页安装器时填写已有数据库即可。"
    return
  fi

  read -rp "数据库名 [hanu]: " DB_NAME
  DB_NAME="${DB_NAME:-hanu}"
  read -rp "数据库用户 [hanu_user]: " DB_USER
  DB_USER="${DB_USER:-hanu_user}"
  read -rsp "数据库用户密码: " DB_PASS
  echo
  [[ -n "$DB_PASS" ]] || fail "数据库用户密码不能为空"

  read -rp "MySQL root 用户 [root]: " MYSQL_ROOT_USER
  MYSQL_ROOT_USER="${MYSQL_ROOT_USER:-root}"
  read -rsp "MySQL root 密码，若是 Ubuntu socket 登录可直接回车: " MYSQL_ROOT_PASS
  echo

  SQL="
CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';
GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'localhost';
FLUSH PRIVILEGES;
"
  if [[ -z "$MYSQL_ROOT_PASS" ]]; then
    echo "$SQL" | mysql -u "$MYSQL_ROOT_USER" 2>/dev/null || echo "$SQL" | sudo mysql
  else
    echo "$SQL" | mysql -u "$MYSQL_ROOT_USER" -p"$MYSQL_ROOT_PASS"
  fi

  ok "数据库已创建"
  echo
  echo "安装器数据库信息："
  echo "数据库地址：localhost"
  echo "数据库名称：$DB_NAME"
  echo "数据库用户：$DB_USER"
  echo "数据库密码：你刚才输入的密码"
  echo "数据表前缀：hanu_"
}

configure_nginx(){
  read -rp "绑定域名或服务器 IP，留空使用 _ 默认站点: " SERVER_NAME
  SERVER_NAME="${SERVER_NAME:-_}"
  PHP_FPM="$(get_php_fpm_sock)"

  say "写入 Nginx 配置"
  mkdir -p /etc/nginx/sites-available /etc/nginx/sites-enabled

  cat > /etc/nginx/sites-available/hanu.conf <<EOF
server {
    listen 80;
    server_name ${SERVER_NAME};

    root ${WEB_ROOT};
    index index.php index.html;

    client_max_body_size 120M;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass ${PHP_FPM};
    }

    location ~* \.(env|ini|log|sql|sh|md)$ {
        deny all;
    }

    location ~ /\. {
        deny all;
    }
}
EOF

  ln -sf /etc/nginx/sites-available/hanu.conf /etc/nginx/sites-enabled/hanu.conf
  rm -f /etc/nginx/sites-enabled/default || true

  nginx -t
  systemctl reload nginx
  ok "Nginx 配置完成"
}

configure_apache(){
  read -rp "绑定域名或服务器 IP，留空使用 *:80 默认站点: " SERVER_NAME
  SERVER_NAME="${SERVER_NAME:-*}"

  say "写入 Apache 配置"

  local conf_dir="/etc/apache2/sites-available"
  local conf_file="/etc/apache2/sites-available/hanu.conf"
  local reload_cmd="systemctl reload apache2"

  if [[ -d /etc/httpd/conf.d ]]; then
    conf_dir="/etc/httpd/conf.d"
    conf_file="/etc/httpd/conf.d/hanu.conf"
    reload_cmd="systemctl reload httpd"
  fi

  mkdir -p "$conf_dir"

  cat > "$conf_file" <<EOF
<VirtualHost *:80>
    ServerName ${SERVER_NAME}
    DocumentRoot ${WEB_ROOT}

    <Directory ${WEB_ROOT}>
        AllowOverride All
        Require all granted
    </Directory>

    LimitRequestBody 125829120

    <FilesMatch "\.(env|ini|log|sql|sh|md)$">
        Require all denied
    </FilesMatch>
</VirtualHost>
EOF

  if command -v a2enmod >/dev/null 2>&1; then
    a2enmod rewrite || true
    a2ensite hanu.conf || true
    a2dissite 000-default.conf || true
  fi

  $reload_cmd || true
  ok "Apache 配置完成"
}

configure_web_server(){
  if [[ "$WEB_SERVER_CHOICE" == "apache" ]]; then
    configure_apache
  else
    configure_nginx
  fi
}

print_finish(){
  IP="$(hostname -I 2>/dev/null | awk '{print $1}')"
  echo
  ok "HANU Linux 环境安装完成"
  echo
  echo "现在打开："
  if [[ -n "${SERVER_NAME:-}" && "$SERVER_NAME" != "_" && "$SERVER_NAME" != "*" ]]; then
    echo "  http://${SERVER_NAME}/"
  fi
  if [[ -n "${IP:-}" ]]; then
    echo "  http://${IP}/"
  fi
  echo
  echo "第一次打开会进入 HANU 安装器。"
  echo "安装完成后按提示删除 source/、language/ 等安装文件。"
}

main(){
  require_root
  detect_pkg_manager
  run_detection
  print_env_report

  if environment_ready; then
    ok "环境检测通过，可以继续部署。"
  else
    ask_environment_action
  fi

  choose_web_server
  start_services
  deploy_code
  create_database_optional
  configure_web_server
  print_finish
}

main "$@"
