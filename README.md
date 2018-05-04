![](https://files.cnblogs.com/files/luomingui/ticky-logo.gif)
===============
[![Total Downloads](https://poser.pugx.org/topticky/framework/downloads)](https://packagist.org/packages/topticky/framework)
[![Latest Stable Version](https://poser.pugx.org/topticky/framework/v/stable)](https://packagist.org/packages/topticky/framework)
[![License](https://poser.pugx.org/topticky/framework/license)](https://packagist.org/packages/topticky/framework)
[![composer.lock](https://poser.pugx.org/topticky/framework/composerlock)](https://packagist.org/packages/topticky/framework)

## 全面的WEB开发特性支持

最新的TickyPHP为WEB应用开发提供了强有力的支持，这些支持包括：

*  MVC支持-基于多层模型（M）、视图（V）、控制器（C）的设计模式
*  ORM支持-提供了全功能和高性能的ORM支持，支持大部分数据库
*  模板引擎PHP原生来做模板引擎
*  缓存支持-提供了包括文件、数据库、Memcache、Xcache、Redis等多种类型的缓存支持

## 命名规范
+   MySQL的表名需小写或小写加下划线，如：item，car_orders。
+   模块名（Models）需用大驼峰命名法，即首字母大写，并在名称后添加Model，如：ItemModel，CarModel。
+   控制器（Controllers）需用大驼峰命名法，即首字母大写，并在名称后添加Controller，如：ItemController，CarController。
+   （Action）需用小驼峰命名法，即首字母小写，如：index，indexPost。
+   视图（Views）部署结构为控制器名/行为名，如：item/view.php，car/buy.php。
+   项目文件夹需用小驼峰命名法

### 模块化设计 模块/控制器/方法/操作 如：http://localhost/index.php?m=admin&c=news&a=Index
+   后台
+   前台
+   手机
+   API
+   ....

## 安全性 https://www.cnblogs.com/luyucheng/p/6234524.html

框架在系统层面提供了众多的安全特性，确保你的网站和产品安全无忧。这些特性包括：

* 防跨网站脚本攻击XSS
* 防跨网站请求伪造攻击CSRF
* 防SQL注入
* 防Session固定攻击
* 防Session劫持攻击
* 防文件上传漏洞攻击
* 表单令牌验证
* 输入数据过滤
