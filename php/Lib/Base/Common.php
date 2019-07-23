<?php
/**
 * Common基类
 * User: ronzheng
 * Date: 2018/11/16
 * Time: 17:20
 */

namespace Lib\Base;

class Common
{
    /**
     *
     *  获取线上环境的配置文件(token和secret)
     *
     * @param  string $sAppId Ulink平台应用ID
     * @param  string $key 需要获取的key的值，默认空，获取APPID下所有配置信息
     * @return array|bool
     */
    public static function getOnlineConfig($sAppId, $key = '')
    {
        if (!$sAppId) {
            return false;
        }
        $file = ACT_CONFIG_PATH . "/app/{$sAppId}.cfg";
        $common = parse_ini_file($file, true);
        if (!$common) {
            recordAMSLog('config file read error');
            return false;
        } else if (!$common[$sAppId]) {
            recordAMSLog('sAppId config read error');
            return false;
        } else {
            if ($key != '') {
                if (isset($common[$sAppId][$key])) {
                    return $common[$sAppId][$key];
                } else {
                    recordAMSLog($key . ' not exist!');
                    return false;
                }
            } else {
                return $common[$sAppId];
            }
        }
    }

    /**
     * 获取活动配置文件
     * @param int $actId 要获取配置的活动号
     * @return array|false 配置文件不存在时返回false,存在则返回配置信息数组
     */
    public static function getActConfig($actId)
    {
        $id1 = str_pad($actId, 6, '0', STR_PAD_LEFT);
        $file = ACT_CONFIG_PATH . '/act/' . substr($id1, 0, 2) . '/' . substr($id1, 2, 2) . '/' . $actId . (ENV != 'pro' ? '.test' : '') . '.cfg';
        if (file_exists($file)) {
            $config = parse_ini_file($file, true);
            return $config;
        } else {
            recordAMSLog("配置文件" . $file . "不存在!");
            return false;
        }
    }

    /**
     * 获取全局配置文件
     * @return array|false 配置文件不存在时返回false,存在则返回配置信息数组
     */
    public static function getBaseConfig()
    {
        $file = ACT_CONFIG_PATH . '/base.cfg';
        if (file_exists($file)) {
            $config = parse_ini_file($file, true);
            return $config;
        } else {
            recordAMSLog("配置文件" . $file . "不存在!");
            return false;
        }
    }

    /**
     * 检查邮件信息是否正确
     * @param string $email 待检查的邮件
     * @return bool  true-正确，false-错误
     */
    public static function checkEmail($email)
    {
        if (!preg_match('/^[a-zA-Z0-9_-]+@[a-zA-Z0-9_-]+(\.[a-zA-Z0-9_-]+)+$/', $email)) {
            return false;
        }
        return true;
    }

    /**
     * 检查手机号是否正确
     * @param string $mobile 待检查的手机号码
     * @return bool  true-手机号正确，false-手机号错误
     */
    public static function checkMobile($mobile)
    {
        if (!preg_match('/^1[3456789]\d{9}$/', $mobile)) {
            return false;
        }
        return true;
    }

    /**
     * 检查openid是否正确
     * @param string $openid  需要检查的openid
     * @param string $type openid需要满足的账号体系，$type = qq|wx|wq
     *               qq：表示openid需要满足QQ账号的openid规则
     *               wx：表示openid需要满足微信账号的openid规则要求
     *               wq：表示openid满足微信或者QQ任意一种规则对openid的要求即可
     * @param bool true-满足要求，false-不满足要求
     */
    public static function checkOpenid($openid, $type = 'qq')
    {
        $reg = '';
        if ($type == 'qq') {
            $reg = '/^[0-9A-Z]{32}$/';
        } else if ($type == 'wx' || $type == 'wq') {
            $reg = '/^[\dA-Za-z-_]{28,64}$/';
        } else {
            return false;
        }
        $matchNum = preg_match($reg, $openid);
        return $matchNum === 1 ? true : false;
    }

    /**
     * 检查QQ号是否正确
     * @param int $qq 需要检查的QQ号
     * @param bool true-是QQ号，false-不是QQ号
     */
    public static function checkQQ($qq)
    {
        $matchNum = preg_match('/^[1-9]\d{4,9}$/', $qq);
        return $matchNum === 1 ? true : false;
    }

    /**
     * 检查大区是否符合要求
     * @param string $area 待检查的大区值
     * @param string $game 游戏业务码,根据游戏业务进行精确检测
     * @return bool true-大区正确，false-大区错误
     */
    public static function checkArea($area, $game = '')
    {
        $matchNum = preg_match('/^[1-9]\d{0,4}$/', $area);
        return $matchNum === 1 ? true : false;
    }

    /**
     * 检查平台是否符合要求
     * @param string $plat 待检查的平台值
     * @return bool true-平台正确，false-平台错误
     */
    public static function checkPlat($plat)
    {
        return in_array($plat, array('0', '1'));
    }

    /**
     * 检查游戏区服是否正确
     * @param string $partition 待检查的游戏区服值
     * @return bool true-区服正确，false-区服错误
     */
    public static function checkPartition($partition)
    {
        $matchNum = preg_match('/^[1-9]\d{0,4}$/', $partition);
        return $matchNum === 1 ? true : false;
    }

    /**
     * 检查角色ID是否正确
     * @param string $roleId 待检查的角色ID
     * @param int $type 角色满足的条件，$type = 1|2,1-正整数，2-字母和数字
     * @return bool true-角色ID正确，false-角色ID错误
     */
    public static function checkRoleId($roleId, $type = 2)
    {
        $reg = '/^[1-9]\d{0,19}$/';
        if ($type == 2) {
            $reg = '/^[a-zA-Z0-9-_]{1,64}$/';
        }
        $matchNum = preg_match($reg, $roleId);
        return $matchNum === 1 ? true : false;
    }

    /**
     * 检查是否为正整数
     * @param string $num 带检查的数值
     * @return bool true-是正整数，false-不是正整数
     */
    public static function checkIsNum($num)
    {
        $matchNum = preg_match('/^[1-9]\d{0,19}$/', $num);
        return $matchNum === 1 ? true : false;
    }

    /**
     * 检查是否为合法的腾讯url
     * @param string $url 待检查的URL
     * @return bool true-url正确，false-url错误
     */
    public static function checkQQUrl($url)
    {
        $matchNum = preg_match('/^http(s?):\/\/([a-zA-Z0-9-_\.]+?)\.(qq\.com|qlogo\.cn)\//', $url);
        return $matchNum === 1 ? true : false;
    }

    /**
     * 检查是否为合法的MRMS单号
     * @param string $mrmsId 待检查的礼包单号
     * @return bool true-正确，false-错误
     */
    public static function checkMRMSID($mrmsId)
    {
        $matchNum = preg_match('/^IEGAMS-\d+-\d+$/', $mrmsId);
        return $matchNum === 1 ? true : false;
    }

    /**
     * 检查是否为合法的角色信息参数字符串
     * @param string $roleParam 待检查的角色信息参数字符串
     * @return bool true-正确，false-错误
     */
    public static function checkRoleParam($roleParam)
    {
        $matchNum = preg_match('/^[0-9A-Za-z-_\|\*]{1,500}$/', $roleParam);
        return $matchNum === 1 ? true : false;
    }

    /**
     * 检查是否为合法的角色信息校验字符串
     * @param string $roleStr 待检查的校验字符串
     * @return bool true-正确，false-错误
     */
    public static function checkRoleStr($roleStr)
    {
        $matchNum = preg_match('/^[0-9A-Fe-f]{32}$/', $roleStr);
        return $matchNum === 1 ? true : false;
    }

    /**
     * 生成随机字符串
     * @param int $strLength 字符串长度
     * @param int $type   字符串类型 1-数字，2-字符，3-字符数字混合
     * @param int $repeat 字符是否允许重复  0-不重复，1-重复
     */
    public static function getRandStr($strLength = 6, $type = 1, $repeat = 0)
    {
        $rand = '';
        $start = 0;
        $end = 61;
        $orgString = '0123456789QAZXSWEDCVFRTGBNHYUJMKIOLPijnmkopluhbvgytfcxdreszaqw';

        if ($type == 1) {
            $end = 9;
        } else if ($type == 2) {
            $start = 10;
        }

        if ($repeat == 0) {
            if ($type == 1 && $strLength > 10) {
                $repeat = 1;
            }

            if ($type == 2 && $strLength > 52) {
                $repeat = 1;
            }

            if ($type == 3 && $strLength > 62) {
                $repeat = 1;
            }
        }

        for ($i = 0; $i < $strLength; $i++) {
            $iIndex = rand($start, $end);

            $rand .= $orgString[$iIndex];
            if ($repeat == 0) {
                $sTmp = $orgString[$start];
                $orgString[$start] = $orgString[$iIndex];
                $orgString[$iIndex] = $sTmp;
                $start++;
            }
        }
        return $rand;
    }

    public static function getRandStr2($activityId, $flowId, $iUin)
    {
        $randomNum = mt_rand(1, 99999999);
        $userIp = getRealIp();
        $userIpNum = bindec(decbin(ip2long($userIp)));
        list($usec, $sec) = explode(' ', microtime());
        $seed = (float) $sec + ((float) $usec * 1000000) + (float) $iUin + (float) $activityId + (float) $flowId + (float) $userIpNum + (float) $randomNum;
        mt_srand($seed);
        $str = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890abcdefghijklmnopqrstuvwxyz';
        $randStr = $str[mt_rand(0, 61)] . $str[mt_rand(0, 61)] . $str[mt_rand(0, 61)] . $str[mt_rand(0, 61)] . $str[mt_rand(0, 61)] . $str[mt_rand(0, 61)];
        return $randStr;
    }

    public static function time33($str)
    {
        $hash = 5381;
        for ($i = 0; $i < strlen($str); $i++) {
            $hash += (($hash << 5) & 0x7FFFFFF) + ord($str[$i]); //& 0x7FFFFFFF后保证其值为unsigned int范围
        }

        return ($hash & 0x7FFFFFF);
    }

    /**
     * 获取当前账号类型
     * @param void
     * @return string qq|wx
     */
    public static function getAcctype()
    {
        $acctype = self::getRequestParam('acctype');
        if ($acctype != '') {
            if (in_array($acctype, array('qq', 'pt', 'qc'))) {
                $acctype = 'qq';
            } else if (in_array($acctype, array('wx', 'weixin'))) {
                $acctype = 'wx';
            }
        } else {
            $openid = self::getRequestParam('openid');
            if (self::checkOpenid($openid, 'qq')) {
                $acctype = 'qq';
            } else if (self::checkOpenid($openid, 'wx')) {
                $acctype = 'wx';
            }
        }
        return $acctype;
    }

    /**
     * 获取请求参数
     * @param string $name 要获取参数的名称，如果不传，则获取所有请求参数，包括 $_GET,$_POST,$_COOKIE
     * @return string|array 参数值
     */
    public static function getRequestParam($name = '')
    {
        $requestData = array_merge($_COOKIE, $_POST, $_GET);
        unset($requestData['openid']);
        if ($name == '') {
            return $requestData;
        }
        $name = $name == 'openid' ? GAME_OPENID : $name;
        return isset($requestData[$name]) ? $requestData[$name] : '';
    }

    /**
     * 获取GET请求参数
     * @param string $name 要获取参数的名称，如果不传，则获取所有请求$_GET
     * @return string|array 参数值
     */
    public static function getParam($name = '')
    {
        $getData = $_GET;
        unset($getData['openid']);
        if ($name == '') {
            return $getData;
        }
        $name = $name == 'openid' ? GAME_OPENID : $name;
        return isset($getData[$name]) ? $getData[$name] : '';
    }

    /**
     * 获取POST请求参数
     * @param string $name 要获取参数的名称，如果不传，则获取所有请求$_POST
     * @return string|array 参数值
     */
    public static function postParam($name = '')
    {
        $postData = $_POST;
        unset($postData['openid']);
        if ($name == '') {
            return $postData;
        }
        $name = $name == 'openid' ? GAME_OPENID : $name;
        return isset($postData[$name]) ? $postData[$name] : '';
    }

    /**
     * 获取COOKIE中的数据
     * @param string $name 要获取参数的名称，如果不传，则获取所有$_COOKIE数据
     * @return string|array 参数值
     */
    public static function cookieParam($name = '')
    {
        $cookieData = $_COOKIE;
        unset($cookieData['openid']);
        if ($name == '') {
            return $cookieData;
        }
        $name = $name == 'openid' ? GAME_OPENID : $name;
        return isset($cookieData[$name]) ? $cookieData[$name] : '';
    }

    /**
     *  将cookie数据转成字符串
     * @param array $arrData 需要转字符串的一维数组（key=>value形式），如果不传或者传空数据，默认取$_COOKIE的值
     */
    public static function cookie2Str($arrData = array())
    {
        if (empty($arrData)) {
            $arrData = self::cookieParam();
        }
        $cookieStr = '';
        if (!empty($arrData)) {
            foreach ($arrData as $key => $value) {
                $cookieStr .= $key . '=' . $value . '; '; //分号和空格不能缺
            }
        }
        if ($cookieStr != '') {
            $cookieStr = substr($cookieStr, 0, -2);
        }
        return $cookieStr;
    }

    /**
     * 获取活动自定义数据
     * @param string $name 要获取参数的名称，如果不传，则获取所有自定义数据
     * @return string|array 参数值
     */
    public static function getCustomParam($name = '')
    {
        $actId = self::getRequestParam('iActId');
        if (ENV != 'test') {
            $actConfig = self::getActConfig($actId);
            if ($actConfig === false) {
                return false;
            }
            if ($name == '') {
                return $actConfig['CUSTOM_CFG'];
            }
            return isset($actConfig['CUSTOM_CFG'][$name]) ? $actConfig['CUSTOM_CFG'][$name] : '';
        } else {
            $file = ACT_CONFIG_PATH . DIRECTORY_SEPARATOR . 'custom.cfg';
            if (file_exists($file)) {
                $config = parse_ini_file($file, true);
                if ($config['CUSTOM_CFG']) {
                    if ($name == '') {
                        return $config['CUSTOM_CFG'];
                    }
                    return isset($config['CUSTOM_CFG'][$name]) ? $config['CUSTOM_CFG'][$name] : '';
                } else {
                    recordAMSLog("配置文件" . $file . "格式错误!");
                    return false;
                }
            } else {
                recordAMSLog("配置文件" . $file . "不存在!");
                return false;
            }
        }
    }

    /**
     * 获取应用加密用的secretKey
     * @return string|bool 成功返回key,失败返回false
     */
    public static function getSecretKey()
    {
        if (ENV != 'test') {
            $config = explode('-', ULINK_SAPPID)[1];
            $secretKey = self::getOnlineConfig($config, 'secret');
            if (!$secretKey) {
                recordAMSLog('pro secretKey read error');
                return false;
            }
        } else {
            $secretKey = ULINK_SECRET; //测试环境通过配置文件中定义的常量来获取
        }
        return $secretKey;
    }

    /**
     * 获取应用的stoken
     * @return string|bool 成功返回token,失败返回false
     */
    public static function getsToken()
    {
        $sToken = '';
        if (ENV != 'test') {
            $config = explode('-', ULINK_SAPPID)[1];
            $sToken = Common::getOnlineConfig($config, 'sToken');
            if (!$sToken) {
                recordAMSLog('pro sToken read error');
                $sToken = false;
            }
        } else {
            $sToken = ULINK_STOKEN; //测试环境通过配置文件中定义的常量来获取
        }
        return $sToken;
    }

    /**
     * 对称加密算法加密数据
     * @param array $data 需要加密的数据,key=>value组成的一维数组
     * @return string|bool 加密成功返回加密字符串，加密失败返回false
     */
    public static function encryptData($data)
    {
        if (is_array($data)) {
            $secretKey = self::getSecretKey();
            if (!$secretKey) {
                return false;
            }
            $dataStr = json_encode($data);
            $encryptStr = rawurlencode(base64_encode(openssl_encrypt($dataStr, 'aes-128-ecb', $secretKey, OPENSSL_RAW_DATA)));
            return $encryptStr;
        }
    }

    /**
     * 解密数据
     * @param string $encryptStr 待解密数据
     * @return array|bool 成功返回解密之后数据，key=>value组成的一维数组，失败返回false
     */
    public static function decryptData($encryptStr)
    {
        $secretKey = self::getSecretKey();
        if (!$secretKey) {
            return false;
        }

        $encryptStr = rawurldecode($encryptStr);
        $encryptStr = base64_decode($encryptStr);
        $encryptStr = openssl_decrypt($encryptStr, 'aes-128-ecb', $secretKey, OPENSSL_RAW_DATA);
        return json_decode($encryptStr, true);
    }

    /**
     * 编码转换
     * @param array|string $inputData 可以是数组和字符串，需要进行编码转换的输入字符串或者数组
     * @param string $inCharset 输入编码
     * @param string $outCharset 需要转换成的编码
     * @return array|string 经过编码转换之后数组或者字符串
     */
    public static function charsetConvert($inputData, $inCharset = 'GBK', $outCharset = 'UTF-8')
    {
        if (is_array($inputData)) {
            $outputData = array();
            if (!empty($inputData)) {
                foreach ($inputData as $key => $value) {
                    $outputData[$key] = is_array($value) ? self::charsetConvert($value, $inCharset, $outCharset) : iconv($inCharset, $outCharset . "//IGNORE", $value);
                }
            }
        } else {
            $outputData = iconv($inCharset, $outCharset . "//IGNORE", $inputData);
        }
        return $outputData;
    }

    /**
     * url编码解码
     * @param array|string $inputData 可以是数组和字符串，需要进行urldecode的数据
     * @return array|string 经过urldecode转换之后数组或者字符串
     */
    public static function urlDecodeDeep($inputData)
    {
        if (is_array($inputData)) {
            $outputData = array();
            if (!empty($inputData)) {
                foreach ($inputData as $key => $value) {
                    $outputData[$key] = is_array($value) ? self::urlDecodeDeep($value) : rawurldecode($value);
                }
            }
        } else {
            $outputData = rawurldecode($inputData);
        }
        return $outputData;
    }

    /**
     * url编码
     * @author ronzheng
     * @param mixed $inputData 可以是数组和字符串，需要进行urlencode的数据
     * @return smixed 经过urlencode转换之后数组或者字符串
     */
    public static function urlEncodeDeep($inputData)
    {
        if (is_array($inputData)) {
            $outputData = array();
            if (!empty($inputData)) {
                foreach ($inputData as $key => $value) {
                    $outputData[$key] = is_array($value) ? self::urlEncodeDeep($value) : rawurlencode($value);
                }
            }
        } else {
            $outputData = rawurlencode($inputData);
        }
        return $outputData;
    }

    /**
     * 反转义字符串
     * @param array|string $inputData 需要反转义的字符串或者数组
     * @return array|string 反转义之后的字符串或者数组
     */
    public static function stripslashesDeep($inputData)
    {
        if ($inputData == '') {
            return '';
        }

        if (get_magic_quotes_gpc()) {
            $inputData = is_array($inputData) ? array_map('Common::stripslashesDeep', $inputData) : stripslashes($inputData);
        }
        return $inputData;
    }

    /**
     * 对字符串进行转义，单引号（'）、双引号（"）、反斜线（\）与 NUL（NULL 字符）
     * @param array|string $inputData 需要转义的字符串或者数组
     * @return array|string 转义之后的字符串或者数组
     */
    public static function addSlashesDeep($inputData)
    {
        if (!get_magic_quotes_gpc()) {
            $inputData = is_array($inputData) ? array_map('Common::addSlashesDeep', $inputData) : addslashes($inputData);
        }
        return $inputData;
    }

    /**
     * HTML标签转到HTML实体
     * @param array|string $inputData 可以是数组和字符串，需要进行编码转换的输入字符串或者数组
     * @return array|string 经过编码转换之后数组或者字符串
     */
    public static function filterHtml($inputData)
    {
        if (is_array($inputData)) {
            $outputData = array();
            if (!empty($inputData)) {
                foreach ($inputData as $key => $value) {
                    if (is_string($value) || is_array($value)) {
                        $outputData[$key] = is_array($value) ? self::filterHtml($value) : htmlspecialchars($value, ENT_QUOTES);
                    } else {
                        $outputData[$key] = $value;
                    }
                }
            }
        } else {
            if (is_string($inputData)) {
                $outputData = htmlspecialchars($inputData, ENT_QUOTES);
            } else {
                $outputData = $inputData;
            }
        }
        return $outputData;
    }

    /**
     * 生成校验和
     * @param array $data key=>value 组成的一维数组，待生成校验和的数据
     * @return string|bool 成功返回MD5之后的字符串（大写），失败返回false
     */
    public static function genToken($data)
    {
        recordAMSLog(__FILE__ . ',' . __LINE__ . 'gentoken user param=' . var_export($data, true));

        $saltKey = Common::getCustomParam('saltKey');

        recordAMSLog(__FILE__ . ',' . __LINE__ . 'gentoken saltKey=' . var_export($saltKey, true));
        if (!$saltKey) {
            return false;
        }
        if (!is_array($data)) {
            return false;
        }

        ksort($data);
        $str = '';
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                return false;
            }
            $str .= $key . '=' . $value . '&';
        }
        $str = substr($str, 0, -1);
        $str .= $saltKey;
        $token = strtoupper(md5($str));
        recordAMSLog(__FILE__ . ',' . __LINE__ . 'gentoken string=' . $str . ', token=' . $token);
        return $token;
    }
	
	/**
     * 数字转成由0-9,a-z,A-Z组成的字符串
     * @param string $number 要转换的数字
     * @param string $string 字符列表
     * @return string 转换之后的字符串
     */
    public static function number2Base62($number, $string = '')
    {
        if (empty($string)) {
            $string = 'vPh7zZwA2LyU4bGq5tcVfIMxJi6XaSoK9CNp0OWljYTHQ8REnmu31BrdgeDkFs+/';
        }

        $out = '';
        for ($t = floor(log10($number) / log10(62)); $t >= 0; $t--) {
            $index = floor($number / pow(62, $t));
            $index = intval($index);
            $out = $out . $string[$index];
            $number = $number - ($index * pow(62, $t));
        }
        return $out;
    }
}
