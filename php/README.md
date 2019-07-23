# ulink_frame php框架简明教程

---

`ulink_frame` 是ulink平台上使用的php cgi接口框架，它主打`简单`、`实用`、`高效`。是一个小而简的框架。简单的路由加上简单的数据ORM，帮助快速开发实现功能。请使用php7进行开发。

以下是篇章目录：

[TOC]

## 一、框架目录

```
├── Ctrl                      // 控制器层 
│   ├── Index.php           
│   ├── Demo.php           
|
├── Lib                         // 核心内库
│   ├── Base                    // 基础库文件夹
│   │    ├── Ctrl.php
│   │    └── Function.php 
│   │    └── HttpRequest.php
│   │    └── Redis.php
│   │
│   ├── Db                      // 数据库类库
│   │    ├── DbModel.php
│   │    └── DbProxy.php 
│   │    └── DbMysql.php
│   │
│   ├── Paas                    // 功能服务接口SDK
│   │    ├── Base.php
│   │    └── DataMore.php 
│   │    └── IDIP.php
│   │    └── Lottery.php
│   │    └── Role.php
│   │    └── XXXX.php
│   │
│   ├── App.php                 //Lib库主入口
|
│
├── Model                       // 数据库层
│   ├── ExchangeModel.php
│   └── XModel.php           
|
├── Logic                        // 逻辑层
│   ├── ConvertLogic.php
│   └── XLogic.php            
|
├── Service                      // 服务层
│   ├── IDIPServer.php
│   └── L5Server.php 
│
├── Logs                      // 本地日志目录
│   ├── 20180928.log          //（要打开写入权限）
│   └── 20180929.log     
|
├── config.php                   //自定义配置文件
├── index.php                    // 主入口
└── README.md
```

按照MVC划分思想，框架目录的结构为：

- index.php cgi请求主的入口，所有的请求都是由这里路由转发。
- config.php  自定义相关的常量配置。
- Ctrl/ 目录存放控制器文件，是我们对外提供的cgi文件入口。
- Lib/  目录存放一些框架的基类，比如redis，mysql，paas基类等。
- Model/ 目录存放mysql交互相关的文件。
- Service/ 目录存放api请求相关的服务文件或者第三方的sdk接口文件等，比如：IDIP,L5等。
- Logic/  目录存放一些逻辑相关的文件，比如逻辑交互,包括数据处理、复杂的数据格式化交互等逻辑操作。
- Logs/ 目录本地写入的一些日志，mysql,redis,api请求等的日志都会记录，方便调试。

## 二、如何使用

先把源码下载到本地。有2种方式可以运行。

### 1. 用php自带server启动服务，不用Nginx或者Apache

```
# cd /data/www/ulink_frame

# php -S 127.0.0.1:8200

PHP 7.2.0 Development Server started at Fri Sep 28 15:23:54 2018
Listening on http://127.0.0.1:8200
Document root is /data/www/ulink_frame
Press Ctrl-C to quit.
```

### 2. 使用Nginx + php-fpm

请先运行php-fpm。

Nginx配置如下：

```
server {
    listen 8200;
    server_name 127.0.0.1;

    root /data/www/ulink_frame;
    index index.php index.html index.htm;

    access_log /usr/local/var/log/nginx/ulink.access.log main;
    error_log /usr/local/var/log/nginx/ulink.error.log error;

    location ~ \.php$ {
        fastcgi_pass   127.0.0.1:9000;
        fastcgi_index  index.php;
        include        fastcgi.conf;
    }
}
```
然后启动或者重启Nginx。

打开浏览器访问：

    http://127.0.0.1:8200/index.php?route=Index/welcome

返回json类型的字符串：

```
{
    "iRet": 0,
    "sMsg": "welcome",
    "jData": [
        "hello, world"
    ]
}
```

## 三、路由介绍

我们访问cgi接口的地址如下：

    http://127.0.0.1:8200/index.php?route=Index/welcome

其中`route`参数是必须的，它代表着路由规则参数，没做其他很复杂的正规匹配路由或者pathinfo模式的路由，只希望简单高效。

```
route=Index/welcome 

表示访问的是Ctrl目录下Index.php文件下的welcome方法。
```

    http://127.0.0.1:8200/index.php?route=User/add

```
route=User/add 

表示访问的是Ctrl目录下User.php文件下的add方法。
```
很简单，无需其他定义。

## 四、代码规范

请必须要完全遵循 `PSR` 的代码书写规范。

规范地址：https://www.php-fig.org/psr/

中文翻译地址：https://psr.phphub.org/

### 1. 大小写问题的代码规范简述：

> 
1. 文件夹的名字要首字母大写，大驼峰的方式。
2. 文件名首字母大写。大驼峰的方式。
3. 必须使用 Namespace 命名空间的方式来组织目录和代码结构。
4. Namespace 首字母大写。大驼峰的方式。
5. Class 类名，首字母大写。大驼峰的方式。
6. function 方法名，首字母小写，小驼峰方式。
7. 常量名(CONST)声明必须要全部大小,如果有多个单词，就用_分开。
8. 变量名首字母小写，采用小驼峰方式。

### 2. 换行、空格以及注释问题的规范简述：

> 
1. Class 类名的后面的左花括号 { `要`换行。
2. Function 函数名后面的左花括号{ `要`换行。
3. 控制语句（if、else、 while、 switch、try、catch）后面的左花括号 { `不要`换行。
4. `所有的`单词的左右都得加1个空格。
5. `所有的`类和函数都得加注释。

### 3. MySQL数据库命名规范简述：

> 
1. 数据库DB命名规范：db名字_业务缩写_活动名称
   例如：dbSmallyang_hyrz_a20171120ticket
2. 数据表Table命名规范：tb固定前缀 + 首字母大写单词 (大驼峰)
   例如：tbBind、tbUserInfo、tbAccept、tbLog等。

### 4. redis命名书写规范简述：

> 
1. redis key的命名规范：业务缩写_活动任务名_自定义（以“_”作为分隔）
   比如：hyrz_a20171120ticket_mobile_%s
2. 必须要给每一个KEY设定过期时间： `setex()`, `expire()`,`expireAt()`

### 5. 例子说明

```
http://127.0.0.1:8200/index.php?route=User/add

对应：Ctrl文件夹下的User.php文件

\Ctrl\User.php  //首字母大写

User.php 首字母大写。

add 首字母小写。

<?php

/*****************************************************
 * File name: User.php
 * Create date: 2018/09/07
 * Author: smallyang
 * Description: 用户模块cgi
 *****************************************************/

namespace Ctrl; //换行

use Lib; //换行

class User extends Lib\Base\Ctrl
{ 
    /**
     * 用户信息
     */
    CONST USER = 'user_info';
    
    /**
     * 用户年龄段
     */
    CONST USER_AGE_LIST = [18,20,30,40];
    
    /**
     * redis key
    */
    CONST REDIS_USER_INFO = 'hyrz_a20171120ticket_mobile_%s';
    
    /**
     * 添加新用户
     */
    public function add()
    {
        //if else
        if ($expr1) { //左右空格
            // if body
        } elseif ($expr2) { //elesif 连着写
            // elseif body
        } else {
            // else body;
        }
        
        //switch
        switch ($expr) { //左右空格
            case 0:
                echo 'First case, with a break'; //对其
                break;  //换行写break ,也对其。
            case 1:
                echo 'Second case, which falls through';
                // no break
            case 2:
            case 3:
            case 4:
                echo 'Third case';
                return;
            default:
                echo 'Default case';
                break;
        }
        
        //while
        while ($expr) { //左右空格
            // structure body
        }
        
        //do while
        do {
            // structure body;  //左右空格
        } while ($expr);
        
        //for
        for ($i = 0; $i < 10; $i++) {
        //注意几个参数之间的空格
            // for body
        }
        
        //foreach
        foreach ($iterable as $key => $value) {      
        //还是空格问题
            // foreach body
        }
        
        //try catch
        try {
            // try body
        } catch (FirstExceptionType $e) {
            //同样也是注意空格。
            // catch body
        } catch (OtherExceptionType $e) {
            // catch body
        }

        $this->outputJSON(0, 'ok');
    }
}

```

### 4. 自动化格式工具

#### phpstrom

目前很多编辑器自带PSR规范的自动格式化功能，比如phpstrom。我们每次写完代码，用快捷键 cmd+opt+L ,就能快速格式化代码。

![](https://ws2.sinaimg.cn/large/006tNc79gy1fvqggyqhb1j30go07eaak.jpg)

#### PHP-CS-Fixer 

PHP Coding Standards Fixer 根据PSR 1~2封装的自动化脚本，可以自动格式化我们的项目代码。

官方地址：https://github.com/FriendsOfPHP/PHP-CS-Fixer

简单安装和使用如下：

```
$ curl -L https://cs.sensiolabs.org/download/php-cs-fixer-v2.phar -o php-cs-fixer #安装

$ sudo chmod a+x php-cs-fixer
$ sudo mv php-cs-fixer /usr/local/bin/php-cs-fixer

$ php php-cs-fixer fix /path/to/dir  //格式化文件夹

$ php php-cs-fixer fix test.php  //格式化单个文件
```
## 四、类库调用

由于框架采用 `Namespace` 命名空间的方法来定义文件夹和类的，所以，是非常方便的来调用任何文件夹下的任何文件的。

命名空间的名字是和目录名一致的，所以，我们只需要知道那个类库文件在哪个目录下，就能轻松调用。

```
比如我们new redis类。

文件路径是： Lib/Base/Redis.php

那么我只需 use 一下即可：

use Lib\Base\Redis;

$redis = Redis::init();

或者：

use Lib;

$redis = Lib\Base\Redis::init();

```

这就意味着，我们可以自定义很多文件和文件夹，只要按照这样的命名方式，就可以很随意的调用。

```

Model/UserModel.php 文件namespace 申明为：Model
    ||
use Model\UserModel;
    ||
$userModel = new Model();

-------------------------------------

Extent/Api/UserApi.php 文件namespace 申明为：Extent\Api
    ||
use Extent\Api\UserApi
    ||
$UserApi = new UserApi();

```

## 五、DB ORM 介绍

框架自带了一个mysql的ORM。和其他框架一样，可以链式串联调用，减少写sql以及减少sql注入的可能。

可以在任何地方调用，只需要申明命名空间调用即可。

```php
<?php

use Lib; //使用

$db = Lib\Db\DbModel::init();  // 父类方法，自动切换

//$db = Lib\db\DbMysql::init(DB_TEST_HOST);  // 本地mysql
//$db = Lib\db\DbProxy::init(DB_ONLINE_HOST); // 线上proxy

//查询多条
$list = $db->table('uss')->limit(10)->select();

//查询多条，单列
$list = $db->table('uss')->limit(10)->select('num');

//查询1条
$list0 = $db->table('uss')->where(['id' => 10])->find();
$list0 = $db->table('uss')->where(['id' => 10])->find('num');

//总数
$list1 = $db->table('uss')->where(['id' => ['!=', 1]])->count();

$list2 = $db->table('uss')->where(['num' => ['<=', 100], 'id' => ['>=', 10]])->count();

//新增
$list4 = $db->table('uss')->insert(['num' => 9, 'ddate' => '2019/09/09']);

//update
$list5 = $db->table('uss')->where(['id' => 3050])->update(['num' => 1234]);

//删除
$db->table('uss')->where(['id' => 3051])->delete();

//直接写SQL修改（update、delete、add）
$db->exec('delete from uss where id = 12;');

//直接写SQL 查询（select）
$db->query('select * from uss where id = 12;');


//分表
$list5 = $db->table('uss')->sub('12345', 10)->where(['id' => ['!=', 1]])->order('num', 'ASC')->limit(10)->select();

//事务
try {
    $db->begin();
    $db->table('uss')->insert(['num' => 99, 'ddate' => '2019/09/09']);
    $db->table('uss')->where(['id' => 3050])->update(['num' => 5678]);
    $db->table('uss')->where(['id' => 3051])->delete();
    $db->commit();
} catch (\Exception $e) {
    $db->rollBack();
    echo "Failed: " . $e->getMessage();
}

```
***注意***：由于线上环境隔离，本地无法调用线上的MySQL。但是本地MySQL和线上DBProxy调用方式一样，所以在本地申明`DB_TEST_HOST`数据库配置常量，调用自己的本地的MySQL。线上配置`DB_ONLINE_HOST`数据库常量配置。上线之后，无须改动代码，无缝切换。

## 六、Paas类库调用

Paas类库主要封装了活动日常中使用到的，诸如：

>角色查询
发送礼包
填写收货地址
调用IDIP接口
调用Datamore接口

在`Lib/Paas`文件夹中，已经封装了这些类库的封装sdk, 只需要配置参数，就能简单调用。

在`Ctrl/Demo.php`中已经有了测试的数据，还会源源不断的增加中。








