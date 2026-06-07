# HANU V9 第二代公测版

版本号：`1.0.1-beta.1`  
建议 Release Tag：`v1.0.1-beta.1`

## 更新内容

- 新增首页公测公告
- 新增关于页面 `/app/about.php`
- 后台站点设置支持修改首页公告
- 更新中心版本号升级
- 数据库迁移系统新增 V9 兼容迁移
- 继续保留旧数据更新机制

## 更新方式

服务器进入网站目录后执行：

```bash
sudo bash update.sh
```

更新会保留：

```text
config/config.php
data/
ICO/
```

数据库迁移只会补充新表和新字段，不会清空旧数据。
