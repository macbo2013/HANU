#!/usr/bin/env bash
set -Eeuo pipefail

DEFAULT_REPO_URL="https://github.com/macbo2013/HANU.git"
DEFAULT_BRANCH="main"

RED="\033[31m"; GREEN="\033[32m"; YELLOW="\033[33m"; BLUE="\033[34m"; RESET="\033[0m"
say(){ echo -e "${BLUE}==>${RESET} $*"; }
ok(){ echo -e "${GREEN}OK:${RESET} $*"; }
warn(){ echo -e "${YELLOW}WARN:${RESET} $*"; }
fail(){ echo -e "${RED}ERROR:${RESET} $*"; exit 1; }

if [[ "${EUID}" -ne 0 ]]; then
  fail "请用 root 运行：sudo bash update.sh"
fi

WEB_ROOT="$(cd "$(dirname "$0")" && pwd)"
BACKUP_ROOT="${WEB_ROOT}/../hanu_backups"
DATE_TAG="$(date +%Y%m%d_%H%M%S)"
TMP_DIR="/tmp/hanu_update_${DATE_TAG}"

echo
say "HANU 保留数据更新工具"
echo "网站目录：${WEB_ROOT}"
echo

read -rp "GitHub 仓库地址 [${DEFAULT_REPO_URL}]: " REPO_URL
REPO_URL="${REPO_URL:-$DEFAULT_REPO_URL}"
read -rp "分支 [${DEFAULT_BRANCH}]: " BRANCH
BRANCH="${BRANCH:-$DEFAULT_BRANCH}"

say "创建备份"
mkdir -p "$BACKUP_ROOT"
tar --exclude="${WEB_ROOT}/data/cache" -czf "${BACKUP_ROOT}/hanu_files_${DATE_TAG}.tar.gz" -C "$WEB_ROOT" .
ok "文件备份：${BACKUP_ROOT}/hanu_files_${DATE_TAG}.tar.gz"

if [[ -f "${WEB_ROOT}/config/config.php" ]] && command -v php >/dev/null 2>&1 && command -v mysqldump >/dev/null 2>&1; then
  say "尝试备份数据库"
  DB_JSON="$(php -r '$c=require "'${WEB_ROOT}'/config/config.php"; echo json_encode($c, JSON_UNESCAPED_UNICODE);')"
  DB_HOST="$(php -r '$c=json_decode($argv[1],true); echo $c["db_host"] ?? "localhost";' "$DB_JSON")"
  DB_PORT="$(php -r '$c=json_decode($argv[1],true); echo $c["db_port"] ?? "3306";' "$DB_JSON")"
  DB_NAME="$(php -r '$c=json_decode($argv[1],true); echo $c["db_name"] ?? "";' "$DB_JSON")"
  DB_USER="$(php -r '$c=json_decode($argv[1],true); echo $c["db_user"] ?? "";' "$DB_JSON")"
  DB_PASS="$(php -r '$c=json_decode($argv[1],true); echo $c["db_pass"] ?? "";' "$DB_JSON")"
  if [[ -n "$DB_NAME" && -n "$DB_USER" ]]; then
    MYSQL_PWD="$DB_PASS" mysqldump -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" "$DB_NAME" > "${BACKUP_ROOT}/hanu_db_${DATE_TAG}.sql" || warn "数据库备份失败，继续更新前请确认你有其他备份。"
    [[ -f "${BACKUP_ROOT}/hanu_db_${DATE_TAG}.sql" ]] && ok "数据库备份：${BACKUP_ROOT}/hanu_db_${DATE_TAG}.sql"
  fi
else
  warn "未能自动备份数据库。请确认你已经在面板/phpMyAdmin 备份。"
fi

read -rp "确认已经备份，继续更新？[y/N]: " CONFIRM
CONFIRM="${CONFIRM:-N}"
[[ "$CONFIRM" =~ ^[Yy]$ ]] || fail "已取消更新"

say "下载最新代码"
rm -rf "$TMP_DIR"
git clone --depth=1 --branch "$BRANCH" "$REPO_URL" "$TMP_DIR"

if [[ -d "${TMP_DIR}/source" ]]; then
  SRC_DIR="${TMP_DIR}/source"
else
  SRC_DIR="$TMP_DIR"
fi

say "同步代码，保留 config / data / ICO"
if command -v rsync >/dev/null 2>&1; then
  rsync -a \
    --exclude "config/config.php" \
    --exclude "data/" \
    --exclude "ICO/" \
    --exclude ".git/" \
    "$SRC_DIR"/ "$WEB_ROOT"/
else
  warn "未安装 rsync，使用 cp 兼容模式"
  cp -a "$SRC_DIR"/. "$WEB_ROOT"/
fi

mkdir -p "$WEB_ROOT/config" "$WEB_ROOT/data/cache" "$WEB_ROOT/data/uploads" "$WEB_ROOT/data/lang" "$WEB_ROOT/ICO"
chmod -R 775 "$WEB_ROOT/config" "$WEB_ROOT/data" "$WEB_ROOT/ICO" || true
chown -R www-data:www-data "$WEB_ROOT" 2>/dev/null || chown -R nginx:nginx "$WEB_ROOT" 2>/dev/null || chown -R apache:apache "$WEB_ROOT" 2>/dev/null || true

say "运行数据库兼容升级"
if [[ -f "${WEB_ROOT}/cli/migrate.php" ]]; then
  php "${WEB_ROOT}/cli/migrate.php"
else
  warn "未找到 cli/migrate.php，跳过数据库迁移"
fi

if [[ -f "${WEB_ROOT}/VERSION" ]]; then
  say "当前版本：$(cat "${WEB_ROOT}/VERSION")"
fi

ok "更新完成"
echo
echo "保留的数据："
echo "  config/config.php"
echo "  data/"
echo "  ICO/"
echo
echo "备份目录：${BACKUP_ROOT}"
