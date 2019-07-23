<?php
/**
 * SMS 短信发送接口
 * User: ronzheng
 * Date: 2019/03/04
 */

namespace Lib\Paas;

use Lib;
use Lib\Base\Common;

class SMS extends Base
{
    public function __construct()
    {
        parent::__construct('resource.sms');
    }

    /**
     * 发送短信
     *
     * @param array = [
     *      'phone' => '【必填】接收短信的手机号码',
     *      'messageKey' => '【必填】短信文案模板ID',
     *      'verifyCode' => '【根据情况确认是否需要传】短信验证码（如果是要发送短信验证码，该值必须传，其他情况不需要传）'
     * ]
     *
     * @return array ['ret' => '0-成功，<0-失败', 'msg'=>'接口调用相关提示信息']
     */
    public function sendMessage($msgData)
    {
        if (!isset($msgData['phone']) || !Common::checkMobile($msgData['phone'])) {
            return ['ret' => '-1', 'msg' => '手机号码错误！', 'data' => ['ret' => '-1']];
        }

        if (!isset($msgData['messageKey']) || !Common::checkIsNum($msgData['messageKey'])) {
            return ['ret' => '-2', 'msg' => 'messageKey错误！', 'data' => ['ret' => '-2']];
        }

        if (isset($msgData['verifyCode']) && $msgData['verifyCode'] != '' && !preg_match('/^[0-9a-zA-Z]{4,10}$/', $msgData['verifyCode'])) {
            return ['ret' => '-3', 'msg' => '验证码错误！', 'data' => ['ret' => '-3']];
        }

        //整合参数
        $paasParam = array();
        $paasParam = $this->getSignInfo(); //生成接口调用签名
        $paasParam = array_merge($paasParam, $msgData);
        (new Login())->getLoginAuthInfo($paasParam); //透传登录态信息

        //组合URL
        $url = $this->getPaasUrl() . '?' . $this->getQueryString($paasParam);

        $res = (new Lib\Base\HttpRequest)->httpsGet($url);

        //success
        if ($res['ret'] == '0' && $res['data']['ret'] == '0') {
            return ['ret' => '0', 'msg' => '短信发送成功！'];
        } else {
            return ['ret' => '-4', 'msg' => $res['msg']];
        }
    }
}
