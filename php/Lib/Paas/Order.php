<?php
/**
 * 订单类
 * User: ronzheng
 * Date: 2018/11/21
 */

namespace Lib\Paas;

use Lib;

class Order
{
    public function __construct()
    {
        parent::__construct('order.placeorder');
    }

    /**
     * 生成订单
     * @param array $data =
     * [
     *      'payActId' => '支付配置ID，请联系开发配置',
     *      'payPropId' => '支付礼包ID，请联系开发配置（含金额与道具配置）',
     *      'needConvert' => '是否需要转换为游戏登录态，默认为1',
     *      'area' => '服务器ID（或者手游渠道：手Q、微信)',
     *      'partition' => '小区ID',
     *      'roleId' => '角色ID',
     *      'roleName' => '角色昵称，编码方式：urlencode，编码：utf8',
     * ]
     * @return array
     * [
     *      'ret' => '返回码',
     *      'msg' => '系统消息',
     *      'serial' => '订单号',
     *      'act_amount' => '物品数量',
     *      'dc' => '支付方式',
     *      'event_id' => '事件ID',
     *      'iPayType' => '支付类型',
     *      'serverTime' => '系统时间',
     * ]
     */
    public function generateOrder($data)
    {
        $this->setApiName('order.placeorder');
        $this->getPaasSign();

        $paasParam = array();
        $paasParam = $this->getSignInfo(); //生成接口调用签名
        (new Login())->getLoginAuthInfo($paasParam); //透传登录态信息
        $paasParam = array_merge($paasParam, $data);

        //url
        $url = $this->getPaasUrl() . '?' . $this->getQueryString($paasParam);

        $res = (new Lib\Base\HttpRequest)->httpsGet($url);
        return $res;

    }

    /**
     * 订单详情
     * @param string $userId 下单账号openid/qq号
     * @param string $orderId 订单号，由下单接口获得
     * @return array ['ret' =>'返回码', 'msg' =>'系统消息', 'data' => ['iStatus' => '订单状态：1待付款，2已付款,发货中，3已发货,交易完成，-2系统取消，-1用户取消', ...]]
     */
    public function orderDetail($userId, $orderId)
    {
        $this->setApiName('order.orderdetail');
        $this->getPaasSign();

        $paasParam = array();
        $paasParam = $this->getSignInfo(); //生成接口调用签名
        (new Login())->getLoginAuthInfo($paasParam); //透传登录态信息
        $paasParam = array_merge($paasParam, ['user' => $userId, 'serial' => $orderId]);

        //url
        $url = $this->getPaasUrl() . '?' . $this->getQueryString($paasParam);

        $res = (new Lib\Base\HttpRequest)->httpsGet($url);
        return $res;
    }

    /**
     * 订单列表
     * @param string $userId   下单账号openid/qq号
     * @param int $page     页号，默认为1
     * @param int $pageSize     每页条目数，默认为10
     * @return array ['ret' =>'返回码', 'msg' =>'系统消息', 'data' => '订单列表']
     */
    public function orderList($userId, $page = 1, $pageSize = 10)
    {
        $this->setApiName('order.orderlist');
        $this->getPaasSign();

        $paasParam = array();
        $paasParam = $this->getSignInfo(); //生成接口调用签名
        (new Login())->getLoginAuthInfo($paasParam); //透传登录态信息
        $paasParam = array_merge($paasParam, ['user' => $userId, '' => $page, 'pageSize' => $pageSize]);

        //url
        $url = $this->getPaasUrl() . '?' . $this->getQueryString($paasParam);

        $res = (new Lib\Base\HttpRequest)->httpsGet($url);
        return $res;
    }
}
