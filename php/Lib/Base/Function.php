<?php
/*****************************************************
 * File name: BaseFunction.php
 * Create date: 2017/10/12
 * Author: smallyang
 * Description: 常用的函数
 *****************************************************/

if (!function_exists('checkOpenid')) {
    /**
     * 检测openid
     *
     * @param $openid string openId
     * @param $type string openId类型
     * @return bool 成功匹配状态
     */
    function checkOpenid($openid, $type = '')
    {
        if (empty($openid)) {
            return false;
        }

        if ($type == 'qq') {
            $reg = '/^[0-9A-Z]{32}$/';
        } else if ($type == 'wx') {
            $reg = '/^[\dA-Za-z-_]{28,64}$/';
        } else {
            $reg = '/^[\dA-Za-z-_]{28,64}$/';
        }

        return preg_match($reg, $openid);
    }
}

if (!function_exists('UTF8toGBK')) {

    /**
     * UTF8toGB2312
     *
     * @param $str string 需要转换的utf8字符串或数组
     * @return string 转成gbk后的字符串或数组
     */
    function UTF8toGBK($str)
    {
        if (is_array($str)) {
            foreach ($str as &$value) {
                $value = UTF8toGBK($value);
            }
            return $str;
        } elseif (is_string($str)) {
            $str = iconv("UTF-8", "GBK//IGNORE", $str);
            return $str;
        } else {
            return $str;
        }
    }
}

if (!function_exists('GBKtoUTF8')) {

    /**
     * GB2312toUTF8
     *
     * @param $str string 需要转换的gbk字符串或数组
     * @return string 转成utf8后的字符串或数组
     */
    function GBKtoUTF8($str)
    {
        if (is_array($str)) {
            foreach ($str as &$value) {
                $value = GBKtoUTF8($value);
            }
            return $str;
        } elseif (is_string($str)) {
            $str = iconv("GBK", "UTF-8//IGNORE", $str);
            return $str;
        } else {
            return $str;
        }
    }
}

if (!function_exists('getRealIp')) {

    /**
     * 获取真实的IP
     *
     * @return string
     */
    function getRealIp()
    {
        recordAMSLog(__FILE__ . "," . __LINE__ . ",HTTP_X_FORWARDED_FOR：" . var_export($_SERVER['HTTP_X_FORWARDED_FOR'], true));
        recordAMSLog(__FILE__ . "," . __LINE__ . ",HTTP_CLIENT_IP：" . var_export($_SERVER['HTTP_CLIENT_IP'], true));
        recordAMSLog(__FILE__ . "," . __LINE__ . ",REMOTE_ADDR：" . var_export($_SERVER['REMOTE_ADDR'], true));

        if (isset($_SERVER)) {
            if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);

                foreach ($arr as $ip) {
                    $ip = trim($ip);

                    if ($ip != 'unknown') {
                        $realip = $ip;

                        break;
                    }
                }
            } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
                $realip = $_SERVER['HTTP_CLIENT_IP'];
            } else {
                if (isset($_SERVER['REMOTE_ADDR'])) {
                    $realip = $_SERVER['REMOTE_ADDR'];
                } else {
                    $realip = '0.0.0.0';
                }
            }
        } else {
            if (getenv('HTTP_X_FORWARDED_FOR')) {
                $realip = getenv('HTTP_X_FORWARDED_FOR');
            } elseif (getenv('HTTP_CLIENT_IP')) {
                $realip = getenv('HTTP_CLIENT_IP');
            } else {
                $realip = getenv('REMOTE_ADDR');
            }
        }

        preg_match("/[\d\.]{7,15}/", $realip, $onlineip);
        $realip = !empty($onlineip[0]) ? $onlineip[0] : '0.0.0.0';

        return $realip;
    }
}

if (!function_exists('injectCheck')) {

    /**
     * 检测sql关键字
     *
     * @param $str string sql
     * @return int
     */
    function injectCheck($str)
    {
        return preg_replace("/select|insert|update|delete|drop|\'|\/\*|\*|\+|\-|\"|\.\.\/|\.\/|union|into|load_file|outfile|dump/is", '', $str);
    }
}

if (!function_exists('recordAMSLog')) {

    /**
     * ams日志记录
     *
     * @param $logInfo string 需要记录的日志
     * @return null
     */
    function recordAMSLog($logInfo)
    {
        //测试
        if (ENV == 'test') {
            $time = date('Ymd');
            file_put_contents(APP . 'logs/' . $time . '.log', date('Y-m-d H:i:s') . ' ---- ' . $logInfo . PHP_EOL, FILE_APPEND);
        } else {
            //流水号
            if (!isset($_GET["iAmsActivityId"])) {
                $_GET["iAmsActivityId"] = LIB_DEFAULT_INSTANCEID;
            }

            $sAmsSerial = $_GET["sAmsSerial"];
            if (!isset($sAmsSerial)) {
                $sAmsSerial = $_GET["sAmsSerial"] = createAmsSerial();
            }

            if ($_GET["iAmsActivityId"] != "") {
                $obj = new \Logger(null);
                $logInfo = UTF8toGBK($logInfo);
                $obj->writePlatLog(__FILE__, __LINE__, LP_INFO, $_GET["iAmsActivityId"], "AMS", "[$sAmsSerial][Condition] " . $logInfo . "\n");
            }
        }
    }
}

if (!function_exists('recordKLog')) {

    /**
     * klog日志记录
     *
     * @param $logInfo string 需要记录的日志
     * @return null
     */
    function recordKLog($code, $scode, $server, $log)
    {
        //测试
        if (ENV == 'test') {
            $time = date('Ymd');
            file_put_contents(APP . 'logs/' . $time . '.log', date('Y-m-d H:i:s') . ' ---- ' . 'code=' . $code . '&scode=' . $scode . '&server=' . $server . '&log=' . $log . PHP_EOL, FILE_APPEND);
        } else {
            $obj = new \Logger(null);
            $obj->sys_send_log($code, $scode, $server, $log);
        }
    }
}

/**
 * tnm2特性上报
 * @param int $attrId 特性ID
 * @param int $incrNum[可选，默认1] 每次请求上报数量
 *
 */
function tnm2Report($attrId, $incrNum = 1)
{
    if (ENV != 'test' && function_exists('Attr_API') && !empty($attrId)) {
        Attr_API($attrId, $incrNum);
    }
}

/**
 * 输出json
 *
 * @param $iRetcode int 状态码
 * @param $sErrorMsg string 状态说明
 * @param $vmResult array 返回的数组
 * @return string json
 */
function outputJSON($iRetcode = 0, $sErrorMsg = 'ok', $vmResult = array())
{
    $res = array(
        'iRet' => $iRetcode,
        'sMsg' => $sErrorMsg,
        'jData' => $vmResult,
        'sSerial' => $_GET["sAmsSerial"],
    );
    recordAMSLog(__FILE__ . "," . __LINE__ . ",output data(before filterHtml):" . var_export($res, true));
    $res = Lib\Base\Common::filterHtml($res);
    recordAMSLog(__FILE__ . "," . __LINE__ . ",output data(after filterHtml):" . var_export($res, true));
    echo json_encode($res);

    //echo UTF8toGBK($r);

    exit();
}

/**
 * 生成随机数
 *
 * @param int $len
 * @return string
 */
function getRandStr($len = 6)
{
    $chars = 'ABDEFGHJKLMNPQRSTVWXYabdefghijkmnpqrstvwxy23456789';
    $password = '';
    while (strlen($password) < $len) {
        $password .= substr($chars, (mt_rand() % strlen($chars)), 1);
    }
    return $password;
}

/**
 * 生成AmsSerial流水
 *
 * @param $sServiceType
 * @param $iAmsActivityId
 * @return string
 */
function createAmsSerial($iAmsActivityId = 155589, $sServiceType = 'ULINK')
{
    $str = 'AMS-' . $sServiceType . '-' . strftime('%m%d%H%M%S') . '-' . getRandStr();
    $str .= '-' . $iAmsActivityId . '-' . mt_rand(100000, 999999);
    return $str;
}

/**
 * @param $className
 */
function registerNamespace($className)
{
    if (strpos($className, 'Lib') !== false && ENV != 'test') {
        $fileName = dirname(LIB_PATH) . '/' . str_replace('\\', DIRECTORY_SEPARATOR, ucwords($className, '\\')) . '.php';
    } else {
        $fileName = APP . str_replace('\\', DIRECTORY_SEPARATOR, ucwords($className, '\\')) . '.php';
    }

    if (is_file($fileName)) {
        require_once $fileName;
    } else {
        outputJSON(-101, $fileName . '文件不存在');
    }
}

//注册namespace加载类
spl_autoload_register('registerNamespace');
