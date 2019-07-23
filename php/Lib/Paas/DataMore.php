<?php
/**
 * DataMore数据接口
 * User: ronzheng
 * Date: 2018/11/21
 * Time: 17:01
 */

namespace Lib\Paas;

use Lib;
use Lib\Base\Common;

class DataMore extends Base
{
    public function __construct()
    {
        parent::__construct('gameattr.sdatamore');
    }

    /**
     * 查询经分接口数据
     *
     * @param int $dmId【必填】 活动数据ID
     * @param string $dmCmd【必填】 经分接口命令串 如："/dmfeature/598/getplayerlastlogin/openid=<sGameOpenId>"
     * @return array
     */
    public function getData($dmId, $dmCmd)
    {
        //公有参数
        $paasParam = array();
        $paasParam = $this->getSignInfo(); //生成接口调用签名

        (new Login())->getLoginAuthInfo($paasParam); //透传登录态信息

        //私有参数
        $paasParam['acctype'] = Common::getAcctype();
        $paasParam['dmId'] = $dmId;

        //组合URL
        $url = $this->getPaasUrl() . '?' . $this->getQueryString($paasParam);

        $res = (new Lib\Base\HttpRequest)->httpsPost($url, $dmCmd);
        return $res;
    }
}
