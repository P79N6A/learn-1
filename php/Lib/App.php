<?php
/*****************************************************
 * File name: App.php
 * Create date: 2018/09/14
 * Author: smallyang
 * Description: App
 *****************************************************/
//设置请求头跨域
if (defined('ENV') && ENV == 'test' && isset($_SERVER['HTTP_ORIGIN'])) {
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
    header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
    header('Access-Control-Allow-Credentials: true');
}
//时区
date_default_timezone_set("PRC");

require_once LIB_PATH . DIRECTORY_SEPARATOR . 'Config' . DIRECTORY_SEPARATOR . 'config.php';

//线上环境。
if (!defined('ENV') || ENV != 'test') {
    //加载类库文件
    require_once OSS_LIB_PATH . DIRECTORY_SEPARATOR . 'osslib.inc.php';
}

require_once LIB_PATH . DIRECTORY_SEPARATOR . 'Base' . DIRECTORY_SEPARATOR . 'Function.php';
