<?php
/**
 * 根据sid获取ip和端口信息
 * User: ronzheng
 * Date: 2018/11/29
 */

namespace Lib\Base;

class LB
{
    public function __construct()
    {
    }

    public static function getHostInfo($modId, $cmdId)
    {
        $retInfo = [];
        $l5Info = [
            'modId' => $modId,
            'cmdId' => $cmdId,
        ];

        $ret = L5ApiGetRoute($l5Info, 0.2);
        recordAMSLog("l5 return data: ret=" . var_export($ret, true) . ", l5info=" . json_encode($l5Info));
        //成功返回0，失败返回-1
        if ($ret == 0) {
            $retInfo['hostIp'] = $l5Info['hostIp'];
            $retInfo['hostPort'] = $l5Info['hostPort'];
            $retInfo['url'] = 'http://' . $l5Info['hostIp'] . ':' . $l5Info['hostPort'];
            $ret1 = L5ApiRouteResultUpdate($l5Info, 0, 3000000);
            recordAMSLog("l5 update return data: ret=" . var_export($ret1, true));
            return $retInfo;
        }
        return false;
    }

    public function __destruct()
    {
    }
}
