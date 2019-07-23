<?php
/**
 * 微信小程序后端接口
 * User: ronzheng
 * Date: 2018/11/21
 */

namespace Lib\Service;

use Lib\Base\Common;
use Lib\Base\HttpRequest;

class MiniProgram
{
    public function __construct()
    {

    }
    /**
     * 微信小程序code转seesion_key
     * @param string $code 小程序通过wx.login()获取到的code
     * @return array|bool 成功则返回包含openid和seesion_key的数组，失败返回false
     */
    public function code2Session($code)
    {
        $appid = Common::getCustomParam('appId');
        $appSecret = Common::getCustomParam('appSecret');
        if ($appid != '' && $appSecret != '') {
            $url = 'https://api.weixin.qq.com/sns/jscode2session?appid=' . $appid . '&secret=' . $appSecret . '&js_code=' . $code . '&grant_type=authorization_code';

            recordAMSLog(__FILE__ . "," . __LINE__ . ", code2Session request:" . $url);

            $res = (new HttpRequest())->httpsGet($url);

            recordAMSLog(__FILE__ . "," . __LINE__ . ", code2Session result:" . json_encode($res));

            if (isset($res['openid']) && !empty($res['openid'])) {
                return $res;
            } else {
                return false;
            }
        }
        recordAMSLog(__FILE__ . "," . __LINE__ . ", code2Session get appid appsecret error, appid:" . var_export($appid, true) . ", appSecret:" . var_export($appSecret, true));
        return false;
    }
}
