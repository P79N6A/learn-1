<?php
/**
 * 角色校验类
 * User: ronzheng
 * Date: 2018/11/21
 */

namespace Lib\Paas;

use Lib;

class User extends Base
{
    public function __construct()
    {
        parent::__construct('gamesafe.credit');
    }

    /**
     *
     * 查询用户的信用等级
     *
     * @param int $creditScore【选填，默认300，满分1000】 能够通过检查的最低信用度。数值越大，信用度越高
     * @param int $maliciousValue【选填，默认850，满分1000】 能够通过检测的账号恶意度上限值。数值越大，恶意度越高
     *
     * 信用度等级参考值：
     *
     * 信用度             信用程度
     * 小于300分           较差
     * 300分-400分         一般
     * 500分-749分         良好
     * 750分-949分         优秀
     * 950分以上           卓越
     *
     * @return bool true-通过检查（可以进行后续抽奖等流程），false-没有通过检查（不能参与抽奖等）
     *
     */
    public function checkUserCredit($creditScore = 300, $maliciousValue = 850)
    {
        $this->setApiName('gamesafe.credit');
        $this->getPaasSign();

        $paasParam = array();
        $paasParam = $this->getSignInfo(); //生成接口调用签名
        (new Login())->getLoginAuthInfo($paasParam); //透传登录态信息

        //url
        $url = $this->getPaasUrl() . '?' . $this->getQueryString($paasParam);

        $resData = (new Lib\Base\HttpRequest)->httpsGet($url);
        if ($resData['ret'] != '0') {
            return false;
        }
        if ($resData['data']['result'] != '0') {
            return true;
        }
        $creditData = $resData['data']['data'];
        if ($creditData['credit_score'] >= $creditScore && $creditData['malicious_value'] < $maliciousValue) {
            return true;
        }
        return false;
    }
}
