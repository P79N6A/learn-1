<?php

/*****************************************************
 * File name: DbModel.php
 * Create date: 2018/09/20
 * Author: smallyang
 * Description: model 基类库
 * modify by:ronzheng 线上环境直接通过配置文件加载数据库信息，并且屏蔽具体错误信息
 **/

namespace Lib\Db;

use Lib\Base\Common;

class DbModel
{

    public static $modelObject;

    /**
     * 初始化
     *
     * @return DbProxy
     */
    public static function init()
    {
        if (!self::$modelObject) {
            self::$modelObject = new self();
        }
        return self::$modelObject;
    }

    /**
     * @param $method
     * @param $arguments
     * @return DbMysql|DbProxy
     */
    public function __call($method, $arguments)
    {
        if (ENV == 'test') {

            //配置数据量
            if (!defined('DB_HOST')) {
                outputJSON('-201', '请在 config.php 中 配置 "DB_HOST" 数据库常量');
            }

            //数据库参数缺失
            $dbHost = DB_HOST;
            if (!(isset($dbHost['host']) && isset($dbHost['db_name']) && isset($dbHost['user_name']) && isset($dbHost['password']))) {
                outputJSON('-202', '"DB_HOST" 配置文件缺失参数，请检查');
            }
            return call_user_func_array(array(DbMysql::init($dbHost), $method), $arguments);
        } else {
            //数据库参数缺失
            $actId = Common::getRequestParam('iActId');
            if (!Common::checkIsNum($actId)) {
                recordAMSLog(__FILE__ . "," . $method . "," . json_encode($arguments) . ",活动ID错误，actid=" . $actId);
                outputJSON('-203', '系统繁忙，请稍后再试[' . __LINE__ . ']！');
            }
            $config = Common::getActConfig($actId);
            if ($config === false || empty($config['DB_CFG']['instance']) || empty($config['DB_CFG']['db_name'])) {
                recordAMSLog(__FILE__ . "," . $method . "," . json_encode($arguments) . ",数据库配置文件缺失或者相关配置项不存在，请检查！");
                outputJSON('-202', '系统繁忙，请稍后再试[' . __LINE__ . ']！');
            } else {
                $dbHost = $config['DB_CFG'];
            }

            return call_user_func_array(array(DbProxy::init($dbHost), $method), $arguments);
        }
    }
}
