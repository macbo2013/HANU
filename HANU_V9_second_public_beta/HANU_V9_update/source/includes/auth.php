<?php
require_once __DIR__ . '/bootstrap.php';

function make_token(): string { return bin2hex(random_bytes(32)); }
function set_login(int $userId): void {
    $token = make_token();
    $exp = now_ts() + 86400 * 30;
    q_exec("INSERT INTO " . table_name('sessions') . "(user_id,token,ip,user_agent,expires_at,created_at) VALUES(?,?,?,?,?,?)",
        [$userId, $token, $_SERVER['REMOTE_ADDR'] ?? '', substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255), $exp, now_ts()]);
    setcookie(cfg('cookie_name', 'hanu_token'), $token, ['expires'=>$exp,'path'=>'/','httponly'=>true,'samesite'=>'Lax']);
}
function current_user(): ?array {
    $token = $_COOKIE[cfg('cookie_name', 'hanu_token')] ?? '';
    if (!$token) return null;
    $sql = "SELECT u.*, g.name AS group_name, g.level AS group_level, g.can_manage_board,
                   tt.name AS title_name, tt.color AS title_color
            FROM " . table_name('sessions') . " s
            JOIN " . table_name('users') . " u ON u.id=s.user_id
            LEFT JOIN " . table_name('user_groups') . " g ON g.id=u.group_id
            LEFT JOIN " . table_name('titles') . " tt ON tt.id=u.current_title_id
            WHERE s.token=? AND s.expires_at>?";
    $u = q_one($sql, [$token, now_ts()]);
    if (!$u) return null;
    if ((int)$u['is_banned'] === 1 && $u['ban_until'] !== null && (int)$u['ban_until'] <= now_ts()) { q_exec("UPDATE " . table_name('users') . " SET is_banned=0,ban_reason=NULL,ban_until=NULL WHERE id=?", [$u['id']]); $u['is_banned']=0; }
    if ((int)$u['is_banned'] === 1 && ($u['ban_until'] === null || (int)$u['ban_until'] > now_ts())) return $u;
    if ((int)$u['is_banned'] === 1) {
        q_exec("UPDATE " . table_name('users') . " SET is_banned=0,ban_reason=NULL,ban_until=NULL WHERE id=?", [$u['id']]);
        $u['is_banned'] = 0;
    }
    q_exec("UPDATE " . table_name('users') . " SET last_active=?,updated_at=? WHERE id=?", [now_ts(), now_ts(), $u['id']]);
    return $u;
}
function require_user(): array {
    $u = current_user();
    if (!$u) redirect_to('login.php');
    if ((int)$u['is_banned'] === 1) { redirect_to('banned.php'); }
    return $u;
}
function is_admin(array $u): bool { return ($u['role'] ?? 'user') === 'admin'; }
function require_admin(): array { $u = require_user(); if (!is_admin($u)) { http_response_code(403); echo '403 Forbidden'; exit; } return $u; }
function logout_user(): void {
    $token = $_COOKIE[cfg('cookie_name', 'hanu_token')] ?? '';
    if ($token) q_exec("DELETE FROM " . table_name('sessions') . " WHERE token=?", [$token]);
    setcookie(cfg('cookie_name', 'hanu_token'), '', time() - 3600, '/');
}
function are_friends(int $a, int $b): bool {
    if ($a === $b) return false;
    $x = min($a, $b); $y = max($a, $b);
    return (bool)q_one("SELECT id FROM " . table_name('friendships') . " WHERE user1_id=? AND user2_id=?", [$x, $y]);
}
function notice_user(int $uid, string $title, string $content, string $type = 'system'): void {
    q_exec("INSERT INTO " . table_name('notifications') . "(user_id,title,content,type,created_at) VALUES(?,?,?,?,?)", [$uid, $title, $content, $type, now_ts()]);
}
function group_member(int $groupId, int $userId): ?array {
    return q_one("SELECT * FROM " . table_name('group_members') . " WHERE group_id=? AND user_id=?", [$groupId, $userId]);
}
