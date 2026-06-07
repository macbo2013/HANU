# HANU V9 第二代公测版

版本号：`1.0.1-beta.2`  
建议 Release Tag：`v1.0.1-beta.2`

## 更新内容

- 修复更新检测权限
- 只有管理员可以检测更新
- 普通用户不会看到更新提醒
- 更新提醒不是强制更新
- 管理员可以关闭当天更新提醒
- 新增管理员更新弹窗
- 新增 `README_V9_UPDATE.md`
- 数据库迁移继续兼容旧版本

## 更新方式

```bash
sudo bash update.sh
```

更新会保留：

```text
config/config.php
data/
ICO/
```
