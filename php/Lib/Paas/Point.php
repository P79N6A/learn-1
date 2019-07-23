<?php
/**
 * 积分类
 * User: ronzheng
 * Date: 2018/11/21
 */

namespace Lib\Paas;

use Lib;
use Lib\Base\Common;

class Point extends Base
{
    public function __construct()
    {
        parent::__construct('point.query');
    }

    /**
     * 查询积分(支持批量查询)
     * @param string|array $pointId【必填】 积分ID，如果是查询多个积分，积分ID以数组方式传递,例如：[id1, id2, id3, ...]
     * @param array $extendData【根据积分账号类型决定是否填写】 积分赠送账号类型
     * [
     *        'area' => '[可选]大区',
     *        'roleId' => '[可选]角色ID',
     *        'sGameOpenid' => '[可选]游戏openid(默认取当前登录账号的openid)，查询非当前登录用户的积分时传递该值'
     * ]
     *    积分账号分为三种类型(具体是那种类型可以跟活动跟进的开发人员或者产品确认)：
     *    1、openid，$extendData参数不需要传
     *    2、openid+area，$extendData中只需要传area
     *    3、openid+area+roleid，$extendData中area、roleId都必须传
     *
     * @return array
     * [
     *      'ret' => '0 成功 从data中获取积分信息，!=0 失败',
     *      'msg' => '系统消息',
     *      'data' =>
     *      [
     *          'id1' => ['point' => '剩余积分1', 'total' => '总积分1'],
     *          'id2' => ['point' => '剩余积分2', 'total' => '总积分2'],
     *          'id3' => ['point' => '剩余积分3', 'total' => '总积分3'],
     *          ...
     *      ]
     * ]
     */
    public function query($pointId, $extendData = [])
    {
        if (is_array($pointId)) {
            $this->setApiName('point.batchquery');
        } else {
            $this->setApiName('point.query');
        }
        $this->getPaasSign();

        $paasParam = array();
        $paasParam = $this->getSignInfo(); //生成接口调用签名
        (new Login())->getLoginAuthInfo($paasParam); //透传登录态信息
        $pointData = [];
        if (is_array($pointId)) {
            $pointData['pointIds'] = implode(',', $pointId);
        } else {
            $pointData['pointId'] = $pointId;
        }

        if (isset($extendData['area']) && Common::checkArea($extendData['area'])) {
            $pointData['area'] = $extendData['area'];
        }

        if (isset($extendData['roleId']) && Common::checkRoleId($extendData['roleId'], 2)) {
            $pointData['roleId'] = $extendData['roleId'];
        }

        if (isset($extendData['sGameOpenid']) && (Common::checkOpenid($extendData['sGameOpenid'], 'wq') || Common::checkQQ($extendData['sGameOpenid']))) {
            $pointData['sGameOpenid'] = $extendData['sGameOpenid'];
        }

        $paasParam = array_merge($paasParam, $pointData);

        //url
        $url = $this->getPaasUrl() . '?' . $this->getQueryString($paasParam);

        $res = (new Lib\Base\HttpRequest)->httpsGet($url);
        if ($res['ret'] == 0 && is_int($pointId)) {
            $res['data'][$pointId] = ['point' => $res['data']['point'], 'total' => $res['data']['total']];
            unset($res['data']['point'], $res['data']['total']);
        }
        return $res;
    }

    /**
     * 扣除积分
     * @param string $pointId 积分ID，请联系开发配置
     * @param int $num 要扣除的积分数量，默认为1
     * @return array ['ret' => '返回码', 'msg' => '系统消息']
     */
    public function reduce($pointId, $num = 1)
    {
        $this->setApiName('point.reduce');
        $this->getPaasSign();

        $paasParam = array();
        $paasParam = $this->getSignInfo(); //生成接口调用签名
        (new Login())->getLoginAuthInfo($paasParam); //透传登录态信息
        $paasParam = array_merge($paasParam, ['pointId' => $pointId, 'num' => $num]);

        //url
        $url = $this->getPaasUrl() . '?' . $this->getQueryString($paasParam);

        $res = (new Lib\Base\HttpRequest)->httpsGet($url);
        return $res;
    }
}
