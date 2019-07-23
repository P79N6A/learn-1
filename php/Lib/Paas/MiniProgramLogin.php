<?php
/**
 * 微信小程序登录态
 * User: ronzheng
 * Date: 2018/12/19
 */

namespace Lib\Paas;

use Lib\Base\Common;
use Lib\Service\MiniProgram;

class MiniProgramLogin extends Base
{
    public function __construct()
    {
        parent::__construct('miniprogram.login');
    }

    /**
     * 微信小程序登录态校验
     * @return bool true-登录态校验通过，false-登录态校验失败
     */
    public function checkLogin()
    {
        $openid = $this->getRequestOpenid();
        $accessToken = Common::getRequestParam('access_token');
        $loginInfo = Common::decryptData($accessToken);
        if (!$loginInfo) {
            return false;
        }
        //openid 不匹配
        if (\strcmp($openid, $loginInfo['openid']) !== 0) {
            recordAMSLog(__FILE__ . "," . __LINE__ . ", login fail, Openid does not match, get openid:" . $openid . ", decrypt openid:" . $loginInfo['openid']);
            return false;
        }
        //登录态过期
        if ((time() - $loginInfo['loginTime']) > MINIPROGRAM_LOGIN_EXPIRED) {
            recordAMSLog(__FILE__ . "," . __LINE__ . ", login fail, login expired, login time:" . date('Y-m-d H:i:s', $loginInfo['loginTime']) . ", now time:" . date('Y-m-d H:i:s'));
            return false;
        }
        $_GET[GAME_OPENID] = $loginInfo['openid'];
        $_POST[GAME_OPENID] = $loginInfo['openid'];
        $_COOKIE[GAME_OPENID] = $loginInfo['openid'];
        return true;
    }

    /**
     * 生成微信小程序登录态信息
     * @return bool|array 失败返回false,成功返回一位数组，包含openid和access_token
     */
    public function getLoginAuthInfo()
    {
        $code = Common::getRequestParam('code');
        if (empty($code)) {
            return false;
        }
        $miniProgram = new MiniProgram();
        $res = $miniProgram->code2Session($code);
        if (!$res) {
            return false;
        }
        $data['openid'] = $res['openid'];
        $data['loginTime'] = time();
        $data['seesionKey'] = $res['session_key'];

        $secret = Common::encryptData($data);
        recordAMSLog(__FILE__ . "," . __LINE__ . ", login auth info:" . json_encode($data) . ", access_token:" . var_export($secret, true));
        if (!$secret) {
            return false;
        }
        return ['openid' => $res['openid'], 'access_token' => $secret];
    }

    /**
     *  生成paas接口需要的scode
     */
    public function getPaasAuthInfo(&$param)
    {
        $loginInfo = array();
        $loginInfo["access_token"] = Common::getRequestParam('access_token');
        $loginInfo["appid"] = Common::getRequestParam('appid'); //游戏业务的appid
        $loginInfo["openid"] = Common::getRequestParam('openId'); //游戏openid
        $param['sCode'] = Common::encryptData($loginInfo);
    }
}
