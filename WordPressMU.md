# WordPress MU 是什么 #

WordPress MU 是 WordPress 的多用户（Multi-User）版本。

如果您不善于编辑 PHP 代码，并且为配置那些复杂的页面服务和数据库系统而倍感头疼，也没想开发什么高端的项目来运行，那么别找了，访问 http://renren.in/ 为自己和朋友注册一个 Blog 空间吧！这将使您可以稳定长期的为自己保存很多计划和想法，并且免去了您的其他顾虑。

# WordPress MU 安装前的准备 #

## Apache ##

WordPress MU 必须启用 Apache 的 mod\_rewrite 模块才能正常服务。这里是一些 Apache 2 的介绍，Apache 1.3 的内容与其类似。

  1. 请确认类似下面这行代码出现在了您的 httpd.conf 文件中：
```
LoadModule rewrite_module /usr/lib/apache2/modules/mod_rewrite.so
```
  1. 您的虚拟主机的 `<Directory>` 中，找到：
```
AllowOverride None
```
> > 将其修改为：
```
AllowOverride FileInfo Options
```
  1. 在配置文件的 `<VirtualHost>` 单元中将有一行定义了主机名称。您需要添加以下代码使主机正常工作：
```
ServerAlias *.domain.tld
```
> > 然后把
```
domain.tld
```
> > 替换成您的域名，并且移除引号。

## DNS ##

如果您想使用 http://blog.domain.tld/ 这种形式的链接作为 Blog 的地址，那么您必须在 DNS 记录中添加一条泛解析记录。

这通常意味着您需要在您的 DNS 配置工具页面中添加一个“`*`”的主机名称。

Matt 撰写了一篇更为详细的文章：
http://ma.tt/2003/10/10/wildcard-dns-and-sub-domains/

## PHP ##

  1. 不要在浏览器中显示错误信息。几乎所有的情况下都是关闭的，但有时当您进行测试的时候可能忘记了关闭；
  1. 如果您想限制 Blog 的注册，请在管理界面限制电子邮箱域名；
  1. 如果您的 PHP 编译为对内存限制进行检查（默认为 8MB，当然，这太小了），您需要增加这个值到 32MB 或 64MB 以避免 PHP 出现内存溢出的错误。请在 php.ini 文件中查找 "memory\_limit"；
  1. 全局变量（GLOBAL variables）必须关闭。这是首先需要做的事情，也是安全检查管理中需要做的。近日，该参数的默认值已经被修改为关闭状态！

最简单的设置方法就是在安装的过程中在 .htaccess 文件中进行声明。如果您还没有安装 WPMU，那么您可以编辑 htaccess.dist 文件，并在其开头部分添加以下两行：
```
php_flag register_globals 0
php_flag display_errors 0
```

默认情况下，该设置并没有包含在该文件中。因为不是所有的服务器都支持该设置。如果您的服务器不支持该设置，当您安装完 WPMU 后会出现 "500 internal error" 错误信息。想要解决这个问题，您只需要编辑位于您 WPMU 跟目录的 ".htaccess" 文件并在开始的部分找到先前添加进去的代码，删除并保存即可。

在这里您可以了解到如何开启该功能：http: //ie.php.net/configuration.changes

如果您不想编辑您的 .htaccess 文件，那么您可以对 php.ini 文件进行修改。涉及到您的服务器，这已经超出了该 Readme 的讲解范畴。但如果您是共享主机用户，或许您无法访问需要修改的文件。那么您需要联系服务器的管理人员对其进行修改。

如果您有服务器管理权限，尝试 "找到 php.ini 文件" 查看以下位置：
```
/etc/php4/apache2/php.ini
/usr/local/lib/php.ini
```

一旦您打开了 php.ini 文件，请查找 register\_globals 和 display\_errors 的相关段落。确认他们都为关闭状态：
```
display_errors = Off
register_globals = Off
```

当您修改完 php.ini 文件后，您需要重新启动 Apache 服务，以使设置生效。

# WordPress MU 的安装 #

  1. 下载并解压缩 WordPress MU 的压缩包。解压过程中会创建一个名为 "wordpressmu" 后面带版本号的目录。
  1. 在您的服务器上为 WordPress MU 创建一个数据库，最好是让该 MySQL 数据库的用户有全部权限来对数据库进行访问和修改。
  1. 请把 WordPress MU 的文件上传到服务器上合适的位置：
    * 如果您想实现域名直接访问到 WordPress MU 站点（例如：http://example.com/），移动或上传所有的文件和目录到服务器站点的跟目录中。
    * 如果您想让 WordPress MU 使用自己的子目录（例如：http://example.com/blogs/），重命名 wordpressmu 目录为您喜欢的目录名称并移动或上传该目录到您的服务器站点目录中。例如，您想将 WordPress MU 安装在一个名叫 "blogs" 目录中，您需要重命名 "wordpressmu" 目录为 "blogs"，并且将其上传到您服务器站点根目录中。
  1. 请确认您的安装目录和 wp-contents 目录为页面服务可写入。
  1. 在您最喜欢的浏览器中运行 WordPress MU 的安装脚本。这将访问 index.php 文件。
    * 如果您的 WordPress MU 安装在根目录中，您需要访问：http://example.com/index.php
    * 如果您的 WordPress MU 安装在您定义的子目录中，例如 blogs，您需要访问：http://example.com/blogs/index.php

相关文档：http://codex.wordpress.org/Installing_WordPress

如果您是升级安装，请查看 http://codex.wordpress.org/Upgrading_WPMU

# WordPress MU 其他资料 #
## 错误日志 ##

如果您正在进行基于 WordPress MU 的站点开发工作，推荐您开启 PHP 错误日志功能。在您的 php.ini 文件中查找 "Error handling and logging" 段落，并进行相应配置。

如果开启，Mysql 数据库错误将记录到 PHP 错误日志中，或者根据您的选择同时发送到某个文件中。安装结束后，编辑 wp-config.php 文件并定义常量 "ERRORLOGFILE" 来指定 MySQL 错误日志。该文件必须为页面服务可写入。请不要记录到一个页面服务可见的文件中或人们可以直接下载的文件中。

定义举例：
```
define( "ERRORLOGFILE", "/tmp/mysql.log" );
```

## 性能 ##

WordPress MU 拥有一个缓存的框架允许第三方开发人员创建缓存引擎来提升站点性能。

这里有两种类型的缓存插件已可供 WordPress 使用。

  1. 对象缓存。
> > 该类型插件将经常访问的那内容存储在高速存储设备中，例如内存或磁盘静态文件系统中。
> > 安装这些插件，请复制到 wp-content 目录中。
```
Memcached：http://dev.wp-plugins.org/browser/memcached/trunk/
Filesystem：http://neosmart.net/dl.php?id=14
Xcache：http://neosmart.net/dl.php?id=12
eAccelerator：http://neosmart.net/dl.php?id=13
```
  1. 全页面缓存。
> > 该类型插件将所有的页面进行了缓存，降低了灵活性，但系统开销要比对象缓存类插件低。在一个繁忙的 WordPress MU 站点中，频繁的生成缓存文件并进行存储，将有可能拖慢服务器的速度，定期的清理缓存文件将有效解决这个问题。您可以因地制宜。


> 安装这些插件，请复制到 wp-content 目录中。

> WP Super Cache：http://ocaoimh.ie/wp-super-cache/

## 支持论坛和问题反馈 ##

  * http://mu.wordpress.org/forums/ （英文官方）
  * http://wfans.org/forums/forum-15-1.html （中文用户）

请在提出问题之前详细阅读：http://codex.wordpress.org/Debugging_WPMU

如果确认是一个漏洞，请提交到这里：http://trac.mu.wordpress.org/report/1