<?php
/*****************************************************
 * File name: User.php
 * Create date: 2019/07/22
 * Author: chuandongz
 * Description: 用户模块cgi
 *****************************************************/

namespace Ctrl;

use Lib;
use Lib\Base\Common;
use Logic\BaseLogic;
use Logic\CommonLogic;
use Logic\UserLogic;

class User extends Lib\Base\Ctrl
{
    /**
     * 拉取页面数据(不用登陆)
     *
     * @return string
     */
    public function initNoLogin()
    {
        //访问频率控制
        $commonLogic = new CommonLogic();
        if ($commonLogic->accessLimit(getRealIp()) === false) {
            $this->outputJSON(-5000, $commonLogic->getError());
        }
        //大区
        $area = Common::getRequestParam('area') ?: '20951';
        if (Common::checkArea($area) === false) {
            return $this->outputJSON(-2, '大区 不合法');
        }
        //副本id
        $duplicate = Common::getRequestParam('duplicate') ?: '60130';
        if (key_exists($duplicate, BaseLogic::DUPLICATE_LISTS) === false) {
            return $this->outputJSON(-3, '副本id 不合法');
        }
        //bossid
        $boss = Common::getRequestParam('boss') ?: '1';
        /*if (in_array($boss, BaseLogic::DUPLICATE_LISTS[$duplicate]) === false) {
            return $this->outputJSON(-4, 'bossid 不合法');
        }*/
        $userLogic = new UserLogic();
        $resData = $userLogic->init($area, $duplicate, $boss);
        if ($resData === false) {
            return $this->outputJSON(-4003, $userLogic->getError());
        }
        return $this->outputJSON(0, '拉取成功', $resData);
    }

    /**
     * 拉取页面数据(需要登陆)
     *
     * @return string
     */
    public function initNeedLogin()
    {
        $openid = Common::getRequestParam('openid');
        if (Common::checkOpenid($openid, 'wq') === false) {
            return $this->outputJSON(-1, 'openid 不合法');
        }
        //访问频率控制
        $commonLogic = new CommonLogic();
        if ($commonLogic->accessLimit($openid) === false) {
            $this->outputJSON(-5000, $commonLogic->getError());
        }

        //大区
        $area = Common::getRequestParam('area') ?: '20951';
        if (Common::checkArea($area) === false) {
            return $this->outputJSON(-2, '大区 不合法');
        }
        //副本id
        $duplicate = Common::getRequestParam('duplicate') ?: '60130';
        if (key_exists($duplicate, BaseLogic::DUPLICATE_LISTS) === false) {
            return $this->outputJSON(-3, '副本id 不合法');
        }
        //bossid
        $boss = Common::getRequestParam('boss') ?: '1';
        if (in_array($boss, BaseLogic::DUPLICATE_LISTS[$duplicate]) === false) {
            return $this->outputJSON(-4, 'bossid 不合法');
        }
        $userLogic = new UserLogic();
        $resData = $userLogic->init($area, $duplicate, $boss);
        if ($resData === false) {
            return $this->outputJSON(-4003, $userLogic->getError());
        }
        return $this->outputJSON(0, '拉取成功', $resData);
    }
}