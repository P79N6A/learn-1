<?php
/**
 * paas接口基类
 * User: ronzheng
 * Date: 2018/11/21
 */

namespace Lib\Paas;

use Lib\Base\Common;
use Lib\Base\LB;

class Base
{
    protected $apiName;
    protected $signInfo;

    public function __construct($apiName)
    {
        $this->setApiName($apiName);
        $this->getPaasSign();
    }

    public function setApiName($apiName)
    {
        $this->apiName = $apiName;
    }

    /**
     * 获取paas接口调用签名
     * @return array
     */
    public function getSignInfo()
    {
        return $this->signInfo;
    }

    public function getApiName()
    {
        return $this->apiName;
    }

    /**
     * 生成接口调用签名和对应的参数
     * @return
     */
    public function getPaasSign()
    {
        $this->signInfo = array();
        $this->signInfo['game'] = Common::getRequestParam('game');
        $this->signInfo['iActId'] = Common::getRequestParam('iActId');
        $this->signInfo['sApiName'] = $this->apiName;
        $this->signInfo['sAppId'] = ULINK_SAPPID;
        $this->signInfo['sVersion'] = ULINK_SVERSION;
        $this->signInfo['timestamp'] = Common::getRequestParam('timestamp');
        if (!preg_match('/^1(\d{9}|\d{12})$/', $this->signInfo['timestamp'])) {
            $this->signInfo['timestamp'] = time();
        }
        $sToken = Common::getsToken();
        if (!$sToken) {
            return false;
        }

        $dataStr = '';
        foreach ($this->signInfo as $key => $value) {
            $dataStr .= $key . '=' . $value . '&';
        }
        $dataStr = \substr($dataStr, 0, -1) . "&sToken=" . $sToken;
        $this->signInfo['sSign'] = strtoupper(md5($dataStr));
    }

    /**
     * 获取paas接口请求url
     */
    public function getPaasUrl()
    {
        if (defined('PASS_TEST') && PASS_TEST == '1') {
            $host = 'https://ulink.game.qq.com/tpaas/';
        } else {
            $host = 'https://ulink.game.qq.com/paas/';
        }

        if (ENV == 'test') {
            return $host;
        } else {
            if (defined('PASS_TEST') && PASS_TEST == '1') {
                $l5Info = LB::getHostInfo(64069121, 102301696);
            } elseif (defined('PASS_PRE') && PASS_PRE == '1') {
                $l5Info = LB::getHostInfo(64069121, 108199936);
            } else {
                $game = Common::getRequestParam('game');
                if (isset(ULINK_PAAS_L5_SID[$game])) {
                    $l5Info = LB::getHostInfo(ULINK_PAAS_L5_SID[$game][0], ULINK_PAAS_L5_SID[$game][1]);
                } else {
                    $l5Info = LB::getHostInfo(ULINK_PAAS_L5_SID['default'][0], ULINK_PAAS_L5_SID['default'][1]);
                }
            }

            if ($l5Info !== false) {
                return $l5Info['url'];
            } else {
                return $host;
            }
        }
    }

    /**
     * 生成url请求参数
     * @param $queryData array 参数名和参数值组成的一维数组
     * @return string url查询参数字符串
     */
    public function getQueryString($queryData)
    {
        if (!is_array($queryData) || empty($queryData)) {
            return '';
        }
        //sAmsSerial
        if ($_GET['sAmsSerial']) {
            $queryData['sAmsSerial'] = $_GET['sAmsSerial'];
        }

        //带上环境env
        if (ENV) {
            $queryData['ulenv'] = ENV;
        }

        $ip = getRealIp();
        $queryData['sUserIP'] = $ip;

        return http_build_query($queryData);
    }

    /**
     * 获取用户前端传入的openid
     */
    protected function getRequestOpenid()
    {
        $openid = isset($_GET['openid']) ? $_GET['openid'] : (isset($_POST['openid']) ? $_POST['openid'] : '');
        $openid = $openid == '' ? (isset($_COOKIE['openid']) ? $_COOKIE['openid'] : '') : $openid;
        if (empty($openid)) {
            $openid = isset($_GET['wxOpenId']) ? $_GET['wxOpenId'] : (isset($_POST['wxOpenId']) ? $_POST['wxOpenId'] : '');
            $openid = $openid == '' ? (isset($_COOKIE['wxOpenId']) ? $_COOKIE['wxOpenId'] : '') : $openid;
        }
        return $openid;
    }

    protected function getRetData($ret, $msg, $data = array())
    {
        return ['ret' => $ret, 'msg' => $msg, 'data' => $data];
    }
}
