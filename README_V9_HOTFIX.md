# HANU V9 修复版 beta.3

版本号：`1.0.1-beta.3`  
建议 Release Tag：`v1.0.1-beta.3`

## 修复问题

### 1. 修复更新中心 Fatal error

修复：

```text
Cannot redeclare hanu_current_version()
```

原因是 `includes/update.php` 和 `includes/bootstrap.php` 同时声明了同名函数。

本版本已移除重复声明，并给更新函数加入防重复保护。

### 2. 修复群聊发不了消息

群聊发送接口和群聊页面都做了加强：

- JS 发送失败时会提示错误
- 群聊页面增加普通表单发送兜底
- API 返回更明确的错误信息
- 支持 WAF 拦截后跳转拦截页

### 3. 新增群聊密码保护

创建群聊时可以设置密码。

加入群聊时：

- 没密码的群可以直接加入
- 有密码的群必须输入正确密码

### 4. 板块页面调整

- 板块列表改成按钮点击进入
- 删除“板块列表已修复为完整显示，不再只露出一点。”这句提示
- 板块详情页增加返回按钮和发布按钮

## 升级方式

```bash
sudo bash update.sh
```

然后运行数据库迁移：

```bash
php cli/migrate.php
```

迁移只会新增缺失字段，不会清空旧数据。
