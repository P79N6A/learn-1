<?php
/**
 * Created by PhpStorm.
 * User: billge
 * Date: 2019/6/11
 * Time: 16:20
 */
namespace Lib\Paas;

use Lib;
use Lib\Base\Common;

class Email extends Base
{
    public function __construct()
    {
        parent::__construct('resource.email');
    }

    /**
     * 发送邮件
     *
     * @param array = [
     *      'receiver' => [] 【必填】接收人,
     *      'cc' => []       【必填】抄送人,
     *      'messageKey' => '【必填】文案模板ID',
     *      'verifyCode' => '【根据情况确认是否需要传】验证信息（以下字段都是模板替换）'
     *      'userName' => '【根据情况确认是否需要传】用户名称'
     * ]
     *
     * @return array ['ret' => '0-成功，<0-失败', 'msg'=>'接口调用相关提示信息']
     */
    public function sendEmail($msgData)
    {
        if (!isset($msgData['receiver']) || empty($msgData['receiver'])) {
            return ['ret' => '-1', 'msg' => '必须要有收件人！', 'data' => ['ret' => '-1']];
        }
        if (is_string($msgData['receiver'])) {
            $msgData['receiver'] = array($msgData['receiver']);
        }
        foreach ($msgData['receiver'] as $email) {
            if (!Common::checkEmail($email)) {
                return ['ret' => '-4', 'msg' => "收件人[$email]格式错误", 'data' => ['ret' => '-4']];
            }
        }
        if (isset($msgData['cc'])) {
            if (is_string($msgData['cc'])) {
                $msgData['cc'] = array($msgData['cc']);
            }
            foreach ($msgData['cc'] as $email) {
                if (!Common::checkEmail($email)) {
                    return ['ret' => '-4', 'msg' => "抄送人[$email]格式错误", 'data' => ['ret' => '-4']];
                }
            }
        }
        if (!isset($msgData['messageKey']) || !Common::checkIsNum($msgData['messageKey'])) {
            return ['ret' => '-2', 'msg' => 'messageKey错误！', 'data' => ['ret' => '-2']];
        }
        if (isset($msgData['verifyCode']) && !is_string($msgData['userName'])) {
            return ['ret' => '-3', 'msg' => '验证信息错误！', 'data' => ['ret' => '-3']];
        }
        if (isset($msgData['userName']) && !is_string($msgData['userName'])) {
            return ['ret' => '-5', 'msg' => '用户名信息错误！', 'data' => ['ret' => '-5']];
        }

        $paasParam = $this->getSignInfo(); //生成接口调用签名
        (new Login())->getLoginAuthInfo($paasParam); //透传登录态信息

        //url
        $url = $this->getPaasUrl() . '?' . $this->getQueryString($paasParam);
        $data = http_build_query($msgData);
        $res = (new Lib\Base\HttpRequest)->httpsPost($url, $data);

        //success
        if ($res['ret'] == '0' && $res['data']['ret'] == '0') {
            return ['ret' => '0', 'msg' => '邮件发送成功！'];
        } else {
            return ['ret' => '-9', 'msg' => $res['msg']];
        }

    }
}

