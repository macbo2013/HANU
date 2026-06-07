# HANU V10 正式版

版本号：`1.0.2`  
Release Tag：`v1.0.2`

## 更新内容

- 发布 HANU V10 正式版
- 合并 V9 已知热修复
- 修复 update/about/banned 页面缺少依赖导致的 Fatal error
- 新增积分名称自定义
- 新增积分红包系统
- 保留更新中心“更新源设置”
- 数据库迁移继续兼容旧版本

## 更新方式

```bash
sudo bash update.sh
php cli/migrate.php
```

更新会保留：

```text
config/config.php
data/
ICO/
```
