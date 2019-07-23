<?php
/* @var $this Redis */

/*****************************************************
 * File name: redisClass.php
 * Create date: 2017/10/13
 * Author: smallyang
 * Modify: ronzheng 1、线上环境redis连接信息直接读配置文件 2、key自动加上前缀，防止重复，简化调用
 * Description: redis连接类
 *****************************************************/

namespace Lib\Base;

class Redis
{
    /**
     * @var \Redis
     */
    static $redisObject;

    private $redis;

    /**
     * 初始化
     *
     * @return \Redis|$this
     */
    public static function init()
    {
        if (!self::$redisObject) {
            try {
                self::$redisObject = new self();
            } catch (Exception $e) {
                outputJSON('-300', '系统繁忙，请稍后再试[' . __LINE__ . ']！');
            }
        }
        return self::$redisObject;
    }

    /**
     * redisClass constructor
     *
     */
    private function __construct()
    {
        //测试环境
        if (ENV == 'test') {
            //配置数据量
            if (!defined('REDIS_HOST')) {
                outputJSON('-301', '请在 config.php 中 配置 "REDIS_HOST" 数据库常量');
            }

            //参数缺失
            $config = REDIS_HOST;
            if (!(isset($config['host']) && isset($config['port']) && isset($config['password']) && isset($config['timeout']))) {
                outputJSON('-302', '"REDIS_HOST" 配置文件缺失参数，请检查');
            }
        } else {
            //online
            //数据库参数缺失
            $actId = Common::getRequestParam('iActId');
            if (!Common::checkIsNum($actId)) {
                recordAMSLog(__FILE__ . "," . __LINE__ . ",活动ID错误，actid=" . $actId);
                outputJSON('-303', '系统繁忙，请稍后再试[' . __LINE__ . ']！');
            }
            $actConfig = Common::getActConfig($actId);
            if ($actConfig === false || empty($actConfig['REDIS_CFG']['host']) || empty($actConfig['REDIS_CFG']['port'])
                || empty($actConfig['REDIS_CFG']['password']) || empty($actConfig['REDIS_CFG']['timeout'])) {
                recordAMSLog(__FILE__ . "," . $method . "," . json_encode($arguments) . ",Redis配置文件缺失或者相关配置项不存在，请检查！");
                outputJSON('-304', '系统繁忙，请稍后再试[' . __LINE__ . ']！');
            } else {
                $config = $actConfig['REDIS_CFG'];
            }
        }

        //redis
        $this->redis = new \Redis();

        //connect
        $ret = $this->redis->connect($config["host"], $config["port"], $config["timeout"]);
        if (!$ret) {
            recordAMSLog('连接Redis服务器失败！host:' . $config["host"] . '，port:' . $config["port"]);
            throw new \Exception('连接Redis服务器失败!');
        }
        //passport
        if ($config["password"]) {
            $ret = $this->redis->auth($config["password"]);
            if (!$ret) {
                recordAMSLog('Redis auth认证失败，password=' . $config["password"]);
                throw new \Exception('Redis auth认证失败!');
            }
        }

    }

    /**
     * 调用redis原生方法
     *
     * @param $method
     * @param $argv
     * @return mixed|null
     * @throws \Exception
     */
    public function __call($method, $argv)
    {
        $result = null;

        $mt = strtolower($method);
        if (in_array($mt, ['keys', 'getkeys', 'flushdb', 'flushall', 'echo', 'swapdb'])) {
            outputJSON('-305', '禁止调用函数：' . $method);
        }
        if (!in_array($argv[0], ['close'])) {
            $actId = Common::getRequestParam('iActId');
            if (!Common::checkIsNum($actId)) {
                outputJSON('-306', '活动ID错误：' . $actId);
            }

            if ($actId > REDIS_LIMIT_ACTID) {
                outputJSON('-307', 'Redis相关调用请切换到Lib\Base\RedisAction类!');
            }

            $argv[0] = 'ulink_act_' . $actId . '_' . $argv[0]; //key自动加上前缀，防止重复
        }

        $result = call_user_func_array(array($this->redis, $method), $argv);
        recordAMSLog("Redis debug: func={$method}, argv: " . json_encode($argv) . " result: " . json_encode($result));

        return $result;
    }

    /**
     * 获取lock
     * @param string 要获取锁定的key
     * @param int    需要加锁最长时间
     * @return bool true:成功获取锁，可以进行后续操作， false-获取锁失败，终止后续操作
     */
    public function getLock($sKey, $time = 3)
    {
        $iExpireTime = time() + $time;
        $bRet = $this->setnx($sKey, $iExpireTime);
        recordAMSLog('getLock setnx: ' . $bRet);

        if ($bRet) {
            $time = $this->expireAt($sKey, $iExpireTime);
            recordAMSLog('getLock expireAt: ' . $time);
            return true;
        } else {
            return false;
        }
    }

    /**
     * 释放lock
     * @param string $sKey 要释放的lock在redis中对应的key
     * @return bool
     */
    public function delLock($sKey)
    {
        $iLoop = 3;
        do {
            $iKeys = $this->del($sKey);
        } while ((--$iLoop) && !$iKeys);

        return true;
    }

    /**
     * 频率限制
     *
     * @param string $key 唯一的key值，可以是ip或者openID
     * @param int $cycle 多少秒
     * @param int $limit 多少次
     * @return int 0 允许访问，-1|-2 不允许访问
     */
    public function accessLimit($key, $cycle = 2, $limit = 1)
    {
        //为空,则直接通过
        if (!$key) {
            return 0;
        }

        $used = $this->get($key); // 1、查

        if (false === $used) {
            $used = 0;
        } else if ($used >= $limit) {
            // 防止设置expire失败，补设一次
            $ttl = $this->ttl($key);
            if (-1 == $ttl) {
                $this->expire($key, $cycle);
            }
            return -1;
        }

        $ret = $this->incr($key); //2、自增

        // 首次写入，设置过期时间
        if ($ret == 1) {
            $this->expire($key, $cycle);
        } else if ($ret > $limit) {
            return -2;
        }
        return 0;
    }
}
