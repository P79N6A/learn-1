<?php
/**
 * 登录
 * User: ronzheng
 * Date: 2018/11/21
 */

namespace Lib\Paas;

use Lib;
use Lib\Base\Common;
use Lib\Base\Redis;
use Lib\Base\RedisAction;

class Login extends Base
{
    private $loginInfo; //QQ互联或者微信授权登录态相关信息
    private $msdkData; //msdk方式登录
    private $ptData; //pt方式登录

    public function __construct()
    {
        $this->loginInfo = []; //access_token登录态
        $this->msdkData = []; //msdk登录态
        $this->ptData = []; //pt登录态
        $this->scData = []; //scode登录态

        parent::__construct('user.checklogin');
        $this->initLogin();
    }

    /**
     * 初始化登录态相关的参数信息
     * @return null
     */
    private function initLogin()
    {
        $skey = Common::cookieParam('skey');
        $pskey = Common::cookieParam('p_skey');
        $accessToken = Common::getRequestParam('access_token');

        //登录态优先去sCode
        if (!empty(Common::getRequestParam('sCode'))) {
            $this->scData['sCode'] = Common::getRequestParam('sCode');
            if (!preg_match('/^[0-9A-Za-z-_%=\/]+$/', $this->scData['sCode'])) {
                $this->scData['sCode'] = '';
            }
        } else if (!empty(Common::getRequestParam('msdkEncodeParam'))) {
            //msdk登录态，直接将登录态参数通过url传递
            $this->msdkData['sig'] = Common::getRequestParam('sig');
            $this->msdkData['msdkTime'] = Common::getRequestParam('timestamp');
            $this->msdkData['appid'] = Common::getRequestParam('appid');
            $this->msdkData['version'] = Common::getRequestParam('version');
            $this->msdkData['algorithm'] = Common::getRequestParam('algorithm');
            $this->msdkData['encode'] = Common::getRequestParam('encode');
            $this->msdkData['msdkEncodeParam'] = Common::getRequestParam('msdkEncodeParam');
            recordAMSLog(__FILE__ . "," . __LINE__ . ",msdk_Data=" . json_encode($this->msdkData));
        } else if (!empty($accessToken) && $accessToken != $skey && $accessToken != $pskey && Common::checkOpenid($this->getRequestOpenid(), 'wq')) {
            //QQ互联或者微信授权登录,登录态信息加密之后传递
            $this->loginInfo["access_token"] = $accessToken;
            $this->loginInfo["appid"] = Common::getRequestParam('appid');
            $this->loginInfo["openid"] = $this->getRequestOpenid();
            recordAMSLog(__FILE__ . "," . __LINE__ . ",access_token_Data=" . json_encode($this->loginInfo));
        } else if (!empty(Common::cookieParam('p_uin')) && !empty(Common::cookieParam('p_skey'))) {
            //PT登录，登录态信息加密之后传递，另外传递cookie信息
            $this->ptData["appid"] = Common::getRequestParam('appid');
            $this->ptData['skey'] = Common::cookieParam('p_skey');
            $this->ptData['uin'] = Common::cookieParam('p_uin');
            recordAMSLog(__FILE__ . "," . __LINE__ . ",pt_Data=" . json_encode($this->ptData));
        } else if (!empty(Common::cookieParam('uin')) && !empty(Common::cookieParam('skey'))) {
            //PT登录，登录态信息加密之后传递，另外传递cookie信息
            $this->ptData["appid"] = Common::getRequestParam('appid');
            $this->ptData['skey'] = Common::cookieParam('skey');
            $this->ptData['uin'] = Common::cookieParam('uin');
            recordAMSLog(__FILE__ . "," . __LINE__ . ",pt_Data=" . json_encode($this->ptData));
        }
    }

    /**
     * 检查登陆状态
     * @return bool true 登录成功，false 登录失败
     */
    public function checkLogin()
    {
        $paasParam = array();
        $paasParam = $this->getSignInfo(); //接口签名信息
        $this->getLoginAuthInfo($paasParam); //透传登录态信息

        $openid = $this->getRequestOpenid();

        if (empty($paasParam['sCode']) && empty($this->msdkData)) {
            recordAMSLog(__FILE__ . "," . __LINE__ . ",pt,msdk,access_token logininfo empty." . json_encode($paasParam));
            return false;
        }

        recordAMSLog(__FILE__ . "," . __LINE__ . ",paas login request data：" . json_encode($paasParam));
        //组合URL
        $url = $this->getPaasUrl() . '?' . $this->getQueryString($paasParam);

        $res = (new Lib\Base\HttpRequest)->httpsGet($url);
        recordAMSLog(__FILE__ . "," . __LINE__ . ",paas login return data：" . json_encode($res));

        //['ret'=>'0-接口调用成功，非0-接口调用失败','is_login'=>'1-登录，0-未登录']
        if ($res['ret'] != '0') {
            return false;
        }
        if ($res['data']['ret'] == '0' && $res['data']['is_login'] == '1') {
            if (empty($res['data']['gameOpenid'])) {
                recordAMSLog(__FILE__ . "," . __LINE__ . ", login success, gameOpenid empty!");
                return false;
            }
            $_GET[GAME_OPENID] = $res['data']['gameOpenid'];
            $_POST[GAME_OPENID] = $res['data']['gameOpenid'];
            $_COOKIE[GAME_OPENID] = $res['data']['gameOpenid'];

            $_GET['iBaseUin'] = $res['data']['iBaseUin'];
            $_POST['iBaseUin'] = $res['data']['iBaseUin'];
            $_COOKIE['iBaseUin'] = $res['data']['iBaseUin'];

            return true;
        } else {
            return false;
        }
    }

    /**
     * openid 转换，将Ulink授权获得openid转换成业务对应的openid
     */
    public function translateOpenid($sTargetAppid = '')
    {
        $this->setApiName('user.getopenid');
        $this->getPaasSign();

        $paasParam = array();
        $paasParam = $this->getSignInfo();
        (new Login())->getLoginAuthInfo($paasParam); //透传登录态信息

        //自定义目标appid
        $paasParam['sTargetAppid'] = $sTargetAppid;

        //组合URL
        $url = $this->getPaasUrl() . '?' . $this->getQueryString($paasParam);

        $res = (new Lib\Base\HttpRequest)->httpsGet($url);
        if (is_array($res) && $res['ret'] == '0' && isset($res['data']['openid'])) {
            return $res['data']['openid'];
        } else {
            return false;
        }
    }

    /**
     * 检查指定openid是否在白名单
     * @param string $openid【选填】 带检查的openid，如果不传，则判断当前登录态中的openid转换为游戏业务的openid是否在白名单
     * @return bool true-在白名单， false-不在白名单
     */
    public function checkInWhite($openid = '')
    {
        if ($openid == '') {

            $openid = !empty(Common::getRequestParam('openid')) ? Common::getRequestParam('openid') : $this->translateOpenid();
            if ($openid === false) {
                return false;
            } else {
                //从redis集合中判断是否在白名单
                $actId = Common::getRequestParam('iActId');
                if ($actId > REDIS_LIMIT_ACTID) {
                    $redis = RedisAction::init('ulink');
                } else {
                    $redis = Redis::init();
                }
                if (!Common::checkIsNum($actId)) {
                    recordAMSLog(__FILE__ . "," . __LINE__ . ",actid error，actid=" . $actId);
                    return false;
                }
                $key = 'whitelist';
                return $redis->sIsMember($key, $openid);
            }
        }
    }

    /**
     * 获取登陆认证信息,不用传递参数，自动获取相关参数信息
     * @param array $param【必填】 引用传递数组，将登录态相关信息直接合并到原有数组
     * @return bool true-成功，false-失败
     */
    public function getLoginAuthInfo(&$param)
    {
        $loginCode = "";
        $strEncryption = "";
        $secretKey = Common::getSecretKey();
        if (!$secretKey) {
            return false;
        }

        //QQ互联或者微信开放平台授权登录,PT登录需要加密登录态信息
        if (!empty($this->msdkData)) {
            $param = array_merge($param, $this->msdkData);
        } else if (!empty($this->loginInfo)) {
            $param['sCode'] = Common::encryptData($this->loginInfo);
        } else if (!empty($this->ptData)) {
            $param['sCode'] = Common::encryptData($this->ptData);
        } else if (!empty($this->scData)) {
            $param['sCode'] = $this->scData['sCode'];
        }
        return true;
    }
}
