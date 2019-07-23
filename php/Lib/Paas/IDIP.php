<?php
/**
 * IDIP 查询接口
 * User: ronzheng
 * Date: 2018/11/21
 */

namespace Lib\Paas;

use Lib;
use Lib\Base\Common;

class IDIP extends Base
{
    public function __construct()
    {
        parent::__construct('gameattr.sidip');
    }

    /**
     * 获取IDIP数据
     *
     * @param $cmdStr【必填】 要查询的IDIP命令串
     * @param $decode【选填】 是否对返回结果进行urldecode操作，true-是，false-否
     * @return mixed
     */
    public function getsData($cmdStr, $decode = true)
    {
        //整合参数
        $paasParam = array();
        $paasParam = $this->getSignInfo(); //生成接口调用签名
        (new Login())->getLoginAuthInfo($paasParam); //透传登录态信息

        //组合URL
        $url = $this->getPaasUrl() . '?' . $this->getQueryString($paasParam);

        $res = (new Lib\Base\HttpRequest)->httpsPost($url, $cmdStr);

        //error
        if ($res['ret'] != 0) {
            return $res;
        }

        //解析返回结果，如果结果中包含有|间隔的值，会按照|解析到数组，如果结果中包含按照空格间隔的值，会按照空格解析到数组
        //result=0&role_list=1|1234 123 1 123 123 123 1 123 123 123 123 123 1 1 1 12345612 3213 121345664 1 1 0 100 0 0 0 0 |
        //解析结果：$result['result'] = 0;
        //          $result[role_list][0] = 1;
        //          $result[role_list][1] = ['1234','123','1','123','123','123','1','123','123','123','123','123','1','1','1','12345612','3213','121345664','1','1','0','100','0','0','0','0');
        $fields = explode('&', $res['data']);
        $loop = count($fields);
        for ($i = 0; $i < $loop; $i++) {
            $kv = explode('=', $fields[$i]);
            if (count($kv) > 1) {
                if (strpos($kv[1], '|') !== false) {
                    $lists = explode('|', $kv[1]);
                    foreach ($lists as $index => $iteamList) {
                        if (!empty($iteamList)) {
                            if (strpos($iteamList, ' ') !== false) {
                                $iteams = explode(' ', $iteamList);
                                $result[$kv[0]][$index] = $iteams;
                            } else {
                                $result[$kv[0]][$index] = $iteamList;
                            }
                        }
                    }
                } else {
                    $result[$kv[0]] = $kv[1];
                }
            }
        }
        if ($decode) {
            $result = Common::urlDecodeDeep($result);
        }
        return $result;
    }

    /**
     * 获取IDIP数据（已经废弃，请勿调用）
     *
     * @param $cmdStr 要查询的IDIP命令串
     * @return mixed
     */
    public function getData($cmdStr)
    {
        //整合参数
        $paasParam = array();
        $paasParam = $this->getSignInfo(); //生成接口调用签名
        (new Login())->getLoginAuthInfo($paasParam); //透传登录态信息

        //组合URL
        $url = $this->getPaasUrl() . '?' . $this->getQueryString($paasParam);

        $res = (new Lib\Base\HttpRequest)->httpsPost($url, $cmdStr);

        //error
        if ($res['ret'] != 0) {
            return $res;
        }

        //解析返回结果，如果结果中包含有|间隔的值，会按照|解析到数组，如果结果中包含按照空格间隔的值，会按照空格解析到数组
        $fields = explode('&', $res['data']);
        $loop = count($fields);
        for ($i = 0; $i < $loop; $i++) {
            $kv = explode('=', $fields[$i]);
            if (count($kv) > 1) {
                if (strpos('|', $kv[1]) !== false) {
                    $lists = explode('|', $kv[1]);
                    foreach ($lists as $index => $iteamList) {
                        if (strpos(' ', $iteamList) !== false) {
                            $iteams = explode('', $iteamList);
                            $result[$kv[0]][$index] = $iteams;
                        } else {
                            $result[$kv[0]][$index] = $iteamList;
                        }
                    }
                } else {
                    $result[$kv[0]] = $kv[1];
                }
            }
        }

        return $result;
    }

}
