<?php
/**
 * openid转换接口
 * User: ronzheng
 * Date: 2018/12/24
 */

namespace Lib\Service;

use Lib\Base\HttpRequest;
use Lib\Base\LB;

class TransOpenId
{
    public function __construct()
    {

    }
    /**
     * 2个不同业务之间openid互转
     * @param string|array $openidList 带转换的openid，可以是单个字符串或者一位数组（数字索引，0开始）
     * @param string $targetAppid 要转换的目标业务的appid
     * @param string  $appName 接口转换申请的应用名称
     * @return array|bool 失败返回false，成功返回转换之后的openid对应关系列表
     * Array
     * (
     *       [0] => Array
     *          (
     *               [source_openid] => o6_bmjrPTlm6_2sgVt7hMZOPfL2M
     *               [target_openid] => o8m9FuCk1a21fL1YUutu_MjhuQtQ
     *           )
     *
     *       [1] => Array
     *          (
     *               [source_openid] => o6_bmjpDo9228S4fSZnJSvldABQI
     *               [target_openid] => o8m9FuAX_NnIC7lhoZeNajP3_GtE
     *           )
     * )
     */
    public function openid2Openid($openidList, $targetAppid, $appName)
    {
        if (isset($_SERVER['PROXYNAME'])) {
            $url = 'http://proxy-yxgw-comm:12361/innerapi/acctapi/transid/openid_to_openid?appname=' . $appName;
        } else {
            $l5 = LB::getHostInfo(64028801, 65536);
            if (!$l5) {
                return false;
            }
            $url = 'http://' . $l5['hostIp'] . ':' . $l5['hostPort'] . '/innerapi/acctapi/transid/openid_to_openid?appname=' . $appName;
        }
        
        $postData = [];
        $postData['target_appid'] = $targetAppid;
        if (is_string($openidList)) {
            $postData['openid_list'][] = $openidList;
        } else {
            $postData['openid_list'] = $openidList;
        }

        recordAMSLog(__FILE__ . "," . __LINE__ . ", openid2Opneid request url=" . $url . ', data=' . json_encode($postData));

        $res = (new HttpRequest())->httpsPost($url, json_encode($postData));
        if (isset($res['openid_list'])) {
            return $res['openid_list'];
        }
        return false;
    }
}
