<?php

/**
 * 微信好友关系链 和 好友发消息
 * @author jimzyliang
 * @version 1.0.0 2019/07/08
 * @since
 *
 */

namespace Lib\Paas;

use Lib;

class Wxrelation extends Base
{
    public function __construct()
    {

        parent::__construct('user.wxrelation');
    }

    /**
     * 微信好友关系链
     * @param int $taskId 产品提供，一次活动申请一个，限量都控制在taskId里
     * @param str $wxappName 产品提供，需产品申请。
     * 
     * @return array
     * [
     *      'ret' => '0-成功，其他失败',
     *      'msg' => '接口返回的信息',
     *      'errNo' => '错误码',
     *      'sSerial' => '流水号',
     *      ['data'] => [
     *          "error":{
     *               "code":0,  //失败则-90001
     *               "message":"ok" 
     *           },
     *          'result' => {
     *              'model_id' => 3 ,数字
     *              'send_list' => '已发送列表，常为空数组，没啥用',
     *              'friend_list' => [
     *                  {
     *                      "sopenid"=>"ssFm5w-Irxxxxxxxxxxxxxxx",
     *                      "head_img_url"=>"http://wx.qlogo.cn/mmopen/vi_32/sWJxxxxxxxxxSKNys6m3BJSMDUXRJpEnp5lqAiaUw/0",
     *                      "nick_name"=>"青dd裙",
     *                      "score"=>0.0989999994635582,
     *                      "tag":1
     *                  },
     *                  {
     *                  }
     *              ]
     *          }
     *      ],
     * ]
     */
    public function getWxrelation($taskId, $wxappName)
    {
        $paasParam = array();
        $paasParam = $this->getSignInfo(); //生成接口调用签名
        (new Login())->getLoginAuthInfo($paasParam); //透传登录态信息

        $postData = array( //从post data读取
            'taskId' => $taskId,
            'wxappName' => $wxappName,
        );
        $postData = http_build_query($postData);
        //组合URL
        $url = $this->getPaasUrl() . '?' . $this->getQueryString($paasParam);
        $res = (new Lib\Base\HttpRequest)->httpsPost($url, $postData);
        recordAMSLog(__FILE__ . "," . __LINE__ . ",frame_getWxrelation_result：" . json_encode($res));
        if ($res['ret'] == '0') {
            $data = $res['data'];
            unset($res['data']);
            $res = array_merge($res, $data);
        }
        return $res;
    }

    /**
     * 给微信好友发消息
     * @param array $qual【必填】 =
     * [
     *       'taskId' => 产品提供, 频率控制都要针对它
     *       'wxappName' => 产品提供,
     *       'toUser' => 上面查出的加密openid,
     *       'modelId' => 上面查出的modelId,
     *       'msgType' => 消息类型, link分享消息 / openid
     *       'msgTitle' => 标题
     *       'msgDesc' => 描述
     *       'msgUrl' => 分享消息打开后的链接
     *       'msgThumbUrl' => 分享右边的小icone
     *]
     * 注意：qualkey 不需要传递openid，paas接口会自动拼接当前登录态中的openid到key上
     * @return array
     * [
     *      'ret' => '0-成功，其他失败',
     *      'msg' => '接口返回的信息',
     *      'errNo' => '错误码',
     *      'sSerial' => '流水号',
     *      ['data'] => [
     *          "error":{
     *               "code":0,  //失败则-90004
     *               "message":"ok" 
     *           },
     *      ],
     * ]
     */
    public function sendWxmsg($postData)
    {
        $paasParam = array();
        $paasParam = $this->getSignInfo(); //生成接口调用签名
        (new Login())->getLoginAuthInfo($paasParam); //透传登录态信息

        //发送微信信息
        $paasParam['dosendmsg'] = 1;

        $postData = http_build_query($postData);
        //组合URL
        $url = $this->getPaasUrl() . '?' . $this->getQueryString($paasParam);
        recordAMSLog(__FILE__ . "," . __LINE__ . ",frame_sendWxmsg_result:" . $url);

        $res = (new Lib\Base\HttpRequest)->httpsPost($url, $postData);
        recordAMSLog(__FILE__ . "," . __LINE__ . ",frame_sendWxmsg_result:" . json_encode($res));
        if ($res['ret'] == '0') {
            $data = $res['data'];
            unset($res['data']);
            $res = array_merge($res, $data);
        }
        return $res;
    }
}
