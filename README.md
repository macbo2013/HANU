# HANU

HANU V8 第一代公测版，一个 PHP + MySQL 的社交系统安装包,v9现已发布！

> 公测反馈：qm66668888@qq.com

## 项目说明

HANU 是一个可上传到 PHP + MySQL 服务器运行的社交系统。  
它不是纯静态网页，因此不能直接用 GitHub Pages 运行 PHP 和 MySQL。GitHub 这里主要用于展示代码、保存版本、下载源码。

## 功能

- 安装向导
- 自定义站点名称
- 登录 / 注册
- 动态发布
- 图片 / 视频动态
- 好友系统
- 私信
- 群聊
- 板块
- 签到积分
- 等级系统
- 称号系统
- WAF 拦截页
- 外链安全中间页
- 管理后台
- 多语言：简体中文、繁體中文、English

## 目录结构

```text
index.php          # 安装器入口
source/            # 正式源码，安装时复制到根目录
language/          # 语言包
ICO/               # 默认图标目录，默认文件名 favicon.ico
install.sh         # Linux 一键环境安装脚本
README.md
```

## Linux 一键安装

适合 Ubuntu / Debian / CentOS / RHEL 服务器。

```bash
wget https://raw.githubusercontent.com/macbo2013/HANU/main/install.sh
sudo bash install.sh
```

脚本会安装：

- Nginx
- PHP-FPM
- PHP MySQL / GD / mbstring / curl / zip / xml
- MariaDB / MySQL
- Git / unzip / curl

然后打开服务器 IP 或域名进入安装器。

## 手动安装

1. 把项目上传到网站根目录。
2. 打开域名或服务器 IP。
3. 按安装器提示填写数据库信息。
4. 创建管理员账号。
5. 安装完成后删除安装文件夹。

## 数据库要求

- MySQL 5.7+
- MariaDB 也可用
- 字符集建议 `utf8mb4`

## 许可

公测阶段，暂未正式发布稳定版协议。


## 环境检测版 install.sh

`install.sh` 会先检测服务器环境，不会一上来就乱装。

会检测：

- Nginx
- Apache / httpd
- PHP CLI
- PHP-FPM
- PHP 版本是否 >= 7.4
- PHP 扩展：pdo、pdo_mysql、gd、mbstring、curl、zip、xml
- MySQL / MariaDB
- Git
- Unzip
- Curl
- 是否存在常见服务器面板环境

如果检测不通过，脚本会给出选择：

```text
1) 自动安装推荐环境：Nginx + PHP-FPM + MariaDB
2) 不安装环境，只继续部署 HANU 代码
3) 查看手动安装 / 面板安装建议，然后退出
4) 重新检测
```

如果你用宝塔面板 / aaPanel，建议先在面板里装好：

- Nginx 或 Apache
- PHP 7.4+
- MySQL 5.7+ 或 MariaDB
- PHP 扩展：pdo_mysql、gd、mbstring、curl、zip、xml

然后再运行脚本部署 HANU。


## 更新中心

HANU 现在内置更新检测和数据库迁移系统。

后台入口：

```text
/app/update.php
```

更新中心可以：

- 检测 GitHub 是否有新版本
- 显示当前版本和最新版本
- 保存 GitHub 更新源
- 一键运行数据库兼容升级

## 保留数据更新

服务器上进入网站根目录运行：

```bash
sudo bash update.sh
```

更新脚本会：

1. 备份当前网站文件
2. 尝试备份数据库
3. 从 GitHub 拉取最新代码
4. 同步代码
5. 保留：
   - `config/config.php`
   - `data/`
   - `ICO/`
6. 运行数据库迁移：
   - 只创建新表
   - 只补充缺失字段
   - 不删除旧表
   - 不清空旧数据


# HANU V9 第二代公测版更新说明

版本号：`1.0.1-beta.2`  
建议 Release Tag：`v1.0.1-beta.2`

## 这次更新了什么

本次重点修复“更新权限”和“更新提醒”的逻辑。

### 1. 只有管理员可以检测更新

更新检测接口：

```text
/api/update_check.php
```

现在必须是管理员登录状态才可以访问。普通用户访问会被拒绝。

后台更新中心：

```text
/app/update.php
```

同样只有管理员可以进入。

### 2. 更新不是强制更新

系统只会告诉管理员“检测到新版本”，不会自动替管理员更新。

管理员可以自己决定：

```text
现在更新
稍后更新
今天不再提醒
```

### 3. 只有管理员会看到更新弹窗

普通用户不会看到任何更新弹窗，也不会被打扰。

管理员登录后台或访问站点时，如果 GitHub 上有更高版本，系统会显示一个小弹窗：

```text
发现新版本
当前版本
最新版本
查看更新
今天不再提醒
```

### 4. 继续保留数据更新

服务器执行：

```bash
sudo bash update.sh
```

更新时会保留：

```text
config/config.php
data/
ICO/
```

也就是说会保留：

```text
数据库配置
用户头像
用户上传图片/视频
缓存目录
站点图标
```

### 5. 数据库兼容旧版本

数据库升级继续使用迁移系统。

迁移只会做这些事：

```text
创建缺失的新表
添加缺失的新字段
写入必要的新设置
```

不会做这些事：

```text
不会清空用户数据
不会删除旧表
不会重建数据库
不会覆盖 config/config.php
```



