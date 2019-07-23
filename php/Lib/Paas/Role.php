<?php
/**
 * 角色校验类
 * User: ronzheng
 * Date: 2018/11/21
 */

namespace Lib\Paas;

use Lib;

class Role extends Base
{
    public function __construct()
    {
        parent::__construct('gameattr.roleinfo');
    }

    /**
     * 获取角色基本信息
     * @param array $data =
     * [
     *      'area' => '【必填】服务器ID（或者手游渠道：手Q、微信)',
     *      'platId' => '【业务区分平台时必填，否则选填】手机操作系统（iOS：0、安卓：1）',
     *      'partition' => '【业务区分区服时必填，否则选填】小区ID',
     *      'roleId' => '【如果需要校验openid和roleId的匹配关系时必填，否则选填】角色ID',
     *      'roleName' => '【选填】角色昵称(urlencode)',
     *      'sGameOpenid' => '【选填】游戏openid，查询非当前登录用户的角色信息时传递该值'
     * ]
     * @param int $loginType【选填】 1-表示小程序登录态，0-pt|互联|msdk
     * @return array ['ret' => '0.成功; 其他.失败', 'msg' => '提示语，ret失败时描述信息', 'data' => ['result' => '0.成功; 1.游戏内未创建角色; 其他.失败;']]
     */
    public function getRoleInfo($data, $loginType = 0)
    {
        $paasParam = array();
        $paasParam = $this->getSignInfo(); //生成接口调用签名
        if ($loginType == 1) {
            (new MiniProgramLogin())->getPaasAuthInfo($paasParam); //透传登录态信息
        } else {
            (new Login())->getLoginAuthInfo($paasParam); //透传登录态信息
        }

        $paasParam = array_merge($paasParam, $data);

        //url
        $url = $this->getPaasUrl() . '?' . $this->getQueryString($paasParam);

        $res = (new Lib\Base\HttpRequest)->httpsGet($url);
        recordAMSLog(__FILE__ . "," . __LINE__ . ",paas return roleinfo=" . var_export($res, true));
        return $res;
    }

    /**
     * 获取前端用于角色选择器的登录信息、签名、时间戳
     * @return array ['sSign' => '接口签名', 'sCode' => '加密登录态', 'timestamp' => '时间戳']
     */
    public function getSecretInfo()
    {
        $this->setApiName('gameattr.nologinrole');
        $this->getPaasSign();

        $retData = array();
        $signInfo = $this->getSignInfo();
        $retData['sSign'] = $signInfo['sSign'];
        $retData['timestamp'] = $signInfo['timestamp'];
        (new Login())->getLoginAuthInfo($retData); //透传登录态信息

        return $retData;
    }
}
