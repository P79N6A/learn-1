<?php
/**
 * 批量调用paas接口
 * User: ronzheng
 * Date: 2019/03/29
 */

namespace Lib\Paas;

use Lib;

class BatchQuery extends Base
{
    public function __construct()
    {
        parent::__construct('');
    }

    /**
     * 批量请求
     *
     * @param array = [
     *          [
     *              'apiName' => '接口名称',
     *              'getData' => [
     *                  'name1' => 'value1',
     *                  'name2' => 'value2',
     *                  ...
     *              ],
     *              'postData' => array : [
     *                  'name1' => 'value1',
     *                  'name2' => 'value2',
     *                  ...
     *              ] | string: 'post传递的数据的字符串形式'
     *              'postType' => '0:key=value数组，1:json, 2:查询字符串'
     *          ]
     *          ...
     *      ]
     * 数字下标的多维数组，每个数组包含对应的接口请求信息
     * getData 对应的接口请求的get参数，name1，name2，...为参数名称，value1，value2，...为对应的参数值
     * postData array|string
     * array:对应的接口请求的post参数，name1，name2，...为参数名称，value1，value2，...为对应的参数值
     * string:要post传递的数据的字符串形式。
     * postType: post数据的传递方式，0:key=value数组，1:json, 2:查询字符串，即key1=value1&key2=value2&...。postData为array类型时有效。
     *
     * @return array [
     *                  'ret' => '0-成功，<0-失败',
     *                  'msg'=>'接口调用相关提示信息',
     *                  'errNo'=>'',
     *                  'resultMerge' => [
     *                      '0' => [
     *                          'ret' => '',
     *                          ['msg'] => '',
     *                          ['errNo'] => '',
     *                          ['data'] => [
     *                          ]
     *                      ]
     *                      '1' => [参考0],
     *                      ...
     *                  ]
     *         ]
     * 每个key的返回结果跟单独调用返回结果一直，具体反馈结果参考各个接口对应的文档
     */
    public function query($request)
    {
        if (!is_array($request)) {
            return ['ret' => '-100', 'msg' => '接口请求参数不正确！', 'errNo' => '-100'];
        }
        foreach ($request as $key => $value) {
            $getData = array();
            $this->setApiName($value['apiName']);
            $this->getPaasSign();
            $getData = $this->getSignInfo(); //生成接口调用签名
            (new Login())->getLoginAuthInfo($getData); //透传登录态信息
            if (isset($value['getData']) && !empty($value['getData'])) {
                $getData = array_merge($getData, $value['getData']);
            }
            $getStr = $this->getQueryString($getData);

            unset($request[$key]['getData']);
            unset($request[$key]['apiName']);
            $request[$key]['getData'] = $getStr;

            if (isset($value['postData'])) {
                if (!is_array($value['postData']) && !is_string($value['postData'])) {
                    return ['ret' => '-101', 'msg' => '接口请求参数不正确！', 'errNo' => '-101'];
                }
                $postStr = '';
                if (is_array($value['postData']) && isset($value['postType'])) {
                    if ($value['postType'] == '1') {
                        $postStr = json_encode($value['postData']);
                    } else if ($value['postType'] == '2') {
                        $postStr = http_build_query($value['postData']);
                    }
                    if ($postStr != '') {
                        unset($request[$key]['postData']);
                        $request[$key]['postData'] = $postStr;
                    }
                    unset($request[$key]['postType']);
                }
            }
        }

        //组合URL
        $url = $this->getPaasUrl() . '?c=Merge';
        $postData = json_encode($request);
        $res = (new Lib\Base\HttpRequest)->httpsPost($url, $postData);
        return $res;
    }
}
