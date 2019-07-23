<?php
/**
 * 请求自定义PaaS接口
 * User: ronzheng
 * Date: 2018/12/17
 */

namespace Lib\Paas;

use Lib;

class Custom extends Base
{
    public function __construct($customId)
    {
        $apiName = 'custom.' . $customId;
        parent::__construct($apiName);
    }

    /**
     * 调用paas自定义接口
     * @param array $data 一维数组，接口需要的参数信息，key=>value,post方式提交
     * @return array
     * [
     *      'ret' => '返回码',
     *      'msg' => '系统消息',
     *      'errNo' => '错误码',
     *      'sAmsSerial' => '流水号',
     *      'data' => [],   //接口返回结果集数组
     * ]
     */
    public function getData($data)
    {
        $paasParam = array();
        $paasParam = $this->getSignInfo(); //生成接口调用签名
        (new Login())->getLoginAuthInfo($paasParam); //透传登录态信息

        //url
        $url = $this->getPaasUrl() . '?' . $this->getQueryString($paasParam);
        $dataStr = http_build_query($data);
        $res = (new Lib\Base\HttpRequest)->httpsPost($url, $dataStr);
        return $res;
    }
}
