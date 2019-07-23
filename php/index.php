<?php
/*****************************************************
 * File name: index.php
 * Create date: 2018/01/04
 * Author: smallyang
 * Description: 主入口
 *****************************************************/
//1. 自定义ini配置 （测试环境打开，线上删掉）
ini_set('display_errors', 'On');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

//2. 加载自己的配置 (必须)
require_once 'config.php';

//3. 加载框架核心入口文件 （必须）
require_once LIB_PATH . '/App.php';

//4. run (必须)
(new Lib\Base\Ctrl())->run();
