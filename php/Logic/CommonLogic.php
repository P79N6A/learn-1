<?php

/*****************************************************
 * File name: CommonLogic.php
 * Create date: 2019/07/011
 * Author: chuandong
 * Description: 公共逻辑
 *****************************************************/

namespace Logic;

use Lib\Base\Common;
use Lib\Base\RedisAction;
use Lib\Paas\Role;

class CommonLogic extends BaseLogic
{
    /**
     * 获取前端用于角色选择器的登录信息、签名、时间戳
     *
     * @return array
     */
    public function getSecretInfo()
    {
        $role = new Role();
        $secretData = $role->getSecretInfo();
        if (!isset($secretData['sCode']) && !empty($secretData['sCode'])) {
            return [];
        }
        return $secretData;
    }

    /**
     * 访问频率控制
     *
     * @param $openid
     * @return bool
     */
    public function accessLimit($openid, $routeCtrl = null)
    {
        $route = Common::getRequestParam('route');
        if (is_null($routeCtrl)) {
            $routeArr = explode('/', $route);
            $routeCtrl = $routeArr[0] . '_' . $routeArr[1];
        }
        $redis = RedisAction::init();
        if ($redis === false) {
            recordAMSLog('redis 连接失败.route:' . $route);
            return $this->setError('服务器繁忙');
        }
        //接口访问频率控制
        $accessLimit = $redis->accessLimit('accessLimit_' . $routeCtrl . '_' . $openid, 1, 2);
        if ($accessLimit != 0) {
            return $this->setError('操作太频繁了,请稍后再试');
        }
        return true;
    }
}
