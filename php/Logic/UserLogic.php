<?php

/*****************************************************
 * File name: UserLogic.php
 * Create date: 2019/07/22
 * Author: chuandong
 * Description: 用户相关逻辑
 *****************************************************/

namespace Logic;

use Lib\Paas\Custom;


class UserLogic extends BaseLogic
{
    /**
     * @param $area //大区
     * @param $duplicate //副本id
     * @param $boss //bossid
     */
    public function init($area, $duplicate, $boss)
    {
        //获取昨天天的数据
        $date = date("Ymd", strtotime("-1 day"));
        $param = [
            'dtstatdate' => $date,
            'izoneareaid' => $area,
            'duplicatetableid' => $duplicate,
            'process' => $boss
        ];
        //调用请求自定义接口获取数据
        $custom = new Custom('1030');
        $customData = $custom->getData($param);

        if ($customData["ret"] != 0 || $customData['data']['error_code'] != 0) {
            recordAMSLog('调用数据接口失败:请求参数->' . json_encode($param));
            recordAMSLog('调用数据接口失败:返回->' . json_encode($customData));
            return $this->setError('数据核算中...');
        }
        $customData = $customData['data']['result']['data'];
        //将数据内容解码生成一个新数组
        foreach ($customData as $datum) {
            $temp = json_decode($datum, true);
            $temp['teamnumber'] = explode(',', $temp['teamnumber']);
            $temp['teamnumber'] = array_map('base64_decode', $temp['teamnumber']);
            $temp['guildname'] = base64_decode($temp['guildname']);
            $resData[] = $temp;
        }
        unset($customData);
        array_multisort(array_column($resData, 'cn'), SORT_ASC, $resData);
        return $resData;
    }
}
