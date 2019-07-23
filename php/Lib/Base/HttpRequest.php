<?php
/*****************************************************
 * File name: httpRequest
 * Create date: 2017/11/30
 * Author: smallyang
 * modify: ronzheng 2018/1207/
 * 1、post 和get请求，默认增加cookie信息
 * Description: http请求基类
 *****************************************************/

namespace Lib\Base;

use Lib\Base\Common;

class HttpRequest
{
    /**
     * http|https get请求
     *
     * @param $url
     * @param $jsonDecode
     * @param bool $sendCookie 是否发送cookie信息，默认需要发送
     * @return mixed
     */
    public function httpsGet($url, $jsonDecode = true, $sendCookie = true)
    {
        recordAMSLog("httpGet url: " . $url);

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        //添加cookie数据
        $strCookie = Common::cookie2Str();
        if ($strCookie != '' && $sendCookie) {
            curl_setopt($curl, CURLOPT_COOKIESESSION, true);
            curl_setopt($curl, CURLOPT_COOKIE, $strCookie);
        }
        curl_setopt($curl, CURLOPT_TIMEOUT, 6);

        $result = curl_exec($curl);

        if ($result === false) {
            //curl 请求异常
            $errno = curl_errno($curl);
            $errmsg = curl_error($curl);
            curl_close($curl);
            recordAMSLog(__FILE__ . "," . __LINE__ . ",curl get request exception：errno:" . $errno . ",errmsg:" . $errmsg);
            return ['ret' => CURL_ERROR_NO, 'msg' => $errmsg, 'errNo' => $errno, 'data' => []];
        } else {
            if ($jsonDecode) {
                $output = json_decode($result, true);
            } else {
                $output = $result;
            }
            curl_close($curl);
            recordAMSLog("httpGet result: " . $result);
            return $output;
        }
    }

    /**
     * http|https post请求
     *
     * @param $url
     * @param $postData
     * @param $jsonDecode
     * @param bool $sendCookie 是否发送cookie信息，默认需要发送
     * @return mixed
     */
    public function httpsPost($url, $postData, $jsonDecode = true, $sendCookie = true)
    {
        recordAMSLog("httpPost url: " . $url);
        recordAMSLog("httpPost postData: " . json_encode($postData));
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Expect:')); //swoole 100 continue
        // post数据
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        // post的变量
        curl_setopt($curl, CURLOPT_POSTFIELDS, $postData);

        $strCookie = Common::cookie2Str();
        if ($strCookie != '' && $sendCookie) {
            curl_setopt($curl, CURLOPT_COOKIESESSION, true);
            curl_setopt($curl, CURLOPT_COOKIE, $strCookie);
        }
        curl_setopt($curl, CURLOPT_TIMEOUT, 6);
        $result = curl_exec($curl);
        if ($result === false) {
            //curl 请求异常
            $errno = curl_errno($curl);
            $errmsg = curl_error($curl);
            curl_close($curl);
            recordAMSLog(__FILE__ . "," . __LINE__ . ",curl post request exception：errno:" . $errno . ",errmsg:" . $errmsg);
            return ['ret' => CURL_ERROR_NO, 'msg' => $errmsg, 'errNo' => $errno, 'data' => []];
        } else {
            curl_close($curl);
            if ($jsonDecode) {
                $output = json_decode($result, true);
            } else {
                $output = $result;
            }
            recordAMSLog("httpPost result: " . $result);
            return $output;
        }
    }

}
