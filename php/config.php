<?php
/*****************************************************
 * File name: config.php
 * Create date: 2017/11/14
 * Author: smallyang
 * Description: 自定义配置
 *****************************************************/

// 环境变量 test、pre、pro (测试环境必须定义)
// 审核提交后，会自动修改成 pre
// 正式发布，会自动修改成 pro
define('ENV', 'test');
//Lib库路径，正式环境会自动替
define('LIB_PATH', __DIR__ . DIRECTORY_SEPARATOR . 'Lib');
//活动配置文件路径，正式环境会自动替
define('ACT_CONFIG_PATH', __DIR__);
//公用配置文件路径，正式环境会自动替
define('COMMON_CONFIG_PATH', __DIR__);
//OSS库路径，正式环境会自动替
define('OSS_LIB_PATH', __DIR__);
//APP路径常量
define('APP', __DIR__ . DIRECTORY_SEPARATOR);

//DB (测试环境必须定义)
define('DB_HOST', [
    'host' => '192.168.1.163',
    'db_name' => 'dbGhgame_mt4_a1359',
    'user_name' => 'root',
    'password' => '123456',
]);

//redis (测试环境必须定义)
define('REDIS_HOST', [
    'host' => '192.168.1.163',
    'port' => 6379,
    'password' => '',
    'timeout' => '3',
]);

// 白名单模块本地配置
define('WHITE_OPENID', [
    '00' => [] // whiteID => openid列表，支持多个
]);

//不需要校验登录态模块, route参数的值加入数组，则对应的模块不强制校验登录态。默认情况下，所有模块强制校验登录态
define('NO_CHECK_LOGIN', [
    'User/initNoLogin'
    //    'index/user',
]);

define('ULINK_SAPPID', 'ULINK-GHGAME-828541'); // 应用ID，活动开发时需要更新
define('ULINK_SVERSION', '1.0'); // PAAS接口版本号，活动开发时需要更新
define('ULINK_STOKEN', '0e6ebb40b2c54a97448844cafba92cc4'); // 用来生成paas接口调用签名的token。测试环境必须设置该值，正式环境直接读取配置文件，该值不生效
define('ULINK_SECRET', 'a63e5af86eef5ff5'); // 用来生成登录信息加密的密钥。测试环境必须设置该值，正式环境直接读取配置文件，该值不生效

define('REQUESTS_PER_SECOND', 0); //每秒允许的请求次数，0表示不限制。例如：设置3000时表示每秒钟请求量不超过3000

//1-测试环境 ，必须用测试号参加活动，所有数据均为测试环境（包括IDIP，发货）
define('PASS_TEST', 0);

//1-预发布环境，必须用测试号参加活动（设置为1时，PASS_TEST必须为0，否则不生效）。礼包、资格等为测试环境数据，发货，idip等为正式环境数据
//一般在测试时将该值设为1，方便测试人员可以自己在paas平台修改礼包概率、清除资格等操作
define('PASS_PRE', 0);
