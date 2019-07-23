<?php
/*****************************************************
 * File name: config.php
 * Create date: 2018/12/14
 * Author: ronzheng
 * Description: lib自定义配置
 *****************************************************/

defined('GAME_OPENID') || define('GAME_OPENID', '__ulink_game_openid');

defined('CURL_ERROR_NO') || define('CURL_ERROR_NO', '-1000000'); //curl 请求是错误码

define('MINIPROGRAM_LOGIN_EXPIRED', 1800); //微信小程序登录态过期时间

define('CORE_LIB_VERSION', '1.8.14');

define('LIB_DEFAULT_INSTANCEID', 155589);

define('REDIS_LIMIT_ACTID', '1138');

define('ULINK_PAAS_L5_SID', [
    'yxzj' => ['64069121', '184877056'],
    'cjm' => ['64069121', '230293504'],
    'default' => ['64069121', '99024896'],
]);

define('IDGENERATOR_IVTCODE_KEY1', 'ulinkframework:idgenerator:ivtcode1');
define('IDGENERATOR_IVTCODE_KEY2', 'ulinkframework:idgenerator:ivtcode2');
