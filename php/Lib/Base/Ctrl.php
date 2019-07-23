<?php
/*****************************************************
 * File name: ctrl.php
 * Create date: 2017/11/30
 * Author: smallyang
 * modify: ronzheng 2018/12/7
 * 1、对所有请求默认开启登录态校验
 * 2、增加白名单校验机制
 * 3、判断活动是否在线
 * 4、校验活动业务类型以及sappid
 * Description: ctrl 基类
 *****************************************************/

namespace Lib\Base;

use Lib\Paas\Login;

class Ctrl
{
    /**
     * 输出json
     *
     * @param $iRetcode int 状态码
     * @param $sErrorMsg string 状态说明
     * @param $vmResult array 返回的数组
     * @return string json
     */
    public function outputJSON($iRetcode = 0, $sErrorMsg = 'ok', $vmResult = array())
    {
        $res = array(
            'iRet' => $iRetcode,
            'sMsg' => $sErrorMsg,
            'jData' => $vmResult,
        );

        if (isset($_GET['sAmsSerial'])) {
            $res['sSerial'] = $_GET['sAmsSerial'];
            $res['sSerial'] = str_replace('AMS-', 'ULINK-', $res['sSerial']);
        }
        recordAMSLog(__FILE__ . "," . __LINE__ . ",output data(before filterHtml):" . var_export($res, true));
        $res = Common::filterHtml($res);
        recordAMSLog(__FILE__ . "," . __LINE__ . ",output data(after filterHtml):" . var_export($res, true));

        echo json_encode($res);

        //echo UTF8toGBK($r);

        exit();
    }

    /**
     * 运行主程序
     */
    public function run()
    {
        $instanceId = '';

        $actId = Common::getRequestParam('iActId');
        $game = Common::getRequestParam('game');

        if (!Common::checkIsNum($actId)) {
            recordAMSLog(__FILE__ . "," . __LINE__ . ",活动号错误，活动号：" . $actId);
            $this->outputJSON(-1, '活动ID错误！', array('code' => -102));
        }

        $actConfig = array();
        if (ENV != 'test') {
            $actConfig = Common::getActConfig($actId);
            if ($actConfig === false) {
                $this->outputJSON(-1, '系统繁忙，请稍后再试[' . __LINE__ . ']！', array('code' => -104));
            }
            $instanceId = $actConfig['INSTANCE_CFG'][0];
        } else {
            $instanceId = defined('ACT_DEFAULT_INSTANCEID') ? ACT_DEFAULT_INSTANCEID : '';
        }

        $instanceId = empty($instanceId) ? LIB_DEFAULT_INSTANCEID : $instanceId;

        //生成AmsSerial流水
        if (!isset($_GET['sAmsSerial'])) {
            $_GET['sAmsSerial'] = createAmsSerial($instanceId, strtoupper($game));
        }
        recordAMSLog('core_lib_version:' . CORE_LIB_VERSION);

        //get log
        recordAMSLog('HTTP GET Data:' . json_encode($_GET));

        //post log
        recordAMSLog('HTTP POST Data:' . json_encode($_POST));

        //cookie log
        recordAMSLog('HTTP COOKIE Data:' . json_encode($_COOKIE));

        //server log
        recordAMSLog('HTTP SERVER Data:' . json_encode($_SERVER));

        //开启活动级别的限流 add by rongzheng 2018/12/4
        if (defined('REQUESTS_PER_SECOND') && REQUESTS_PER_SECOND > 0) {
            $limitKey = 'ulink_request_limiter_' . $actId;
            try {

                if ($actId > REDIS_LIMIT_ACTID) {
                    $redis = RedisAction::init();
                } else {
                    $redis = Redis::init();
                }

                $ret = $redis->accessLimit($limitKey, 1, REQUESTS_PER_SECOND);
                if ($ret != 0) {
                    $this->outputJSON(-888888, '活动太火爆，请稍后再来参加！', array('code' => -888888));
                }
            } catch (\Exception $e) {
                recordAMSLog("在执行限流时初始化redis对象出现异常，错误信息：" . $e->getMessage());
            }
        }

        //路由获取
        $route = trim(strip_tags($_GET['route']));
        if (!$route) {
            recordAMSLog(__FILE__ . "," . __LINE__ . ",route参数错误：" . var_export($route, true));
            $this->outputJSON(-1, '系统繁忙，请稍后再试[' . __LINE__ . ']！', array('code' => -100));
        }
        $routeArr = explode('/', $route);
        if (!$routeArr[0] || !$routeArr[1]) {
            recordAMSLog(__FILE__ . "," . __LINE__ . ",route参数错误，ctrl:" . var_export($routeArr[0], true) . ",action:" . var_export($routeArr[1], true));
            $this->outputJSON(-1, '系统繁忙，请稍后再试[' . __LINE__ . ']！', array('code' => -101));
        }

        //获取ctrl 和 action
        $routeCtrl = $routeArr[0];
        $routeAction = $routeArr[1];

        if (ENV != 'test') {
            $actConfig = empty($actConfig) ? Common::getActConfig($actId) : $actConfig;

            recordAMSLog(__FILE__ . "," . __LINE__ . ",活动配置信息：" . var_export($actConfig, true));

            if ($actConfig === false) {
                $this->outputJSON(-1, '系统繁忙，请稍后再试[' . __LINE__ . ']！', array('code' => -104));
            }

            //校验请求参数中的业务类型、SAPPID等信息
            if (!isset($actConfig['BASE_INFO']['game']) || \strcmp($actConfig['BASE_INFO']['game'], $game) !== 0) {
                $this->outputJSON(-1, '系统繁忙，请稍后再试[' . __LINE__ . ']！', array('code' => -121));
            }
            //校验SAPPID
            if (!isset($actConfig['BASE_INFO']['sAppId']) || \strcmp($actConfig['BASE_INFO']['sAppId'], ULINK_SAPPID) !== 0) {
                $this->outputJSON(-1, '系统繁忙，请稍后再试[' . __LINE__ . ']！', array('code' => -122));
            }

            //活动如果被强制下线，则给出对应提示
            if (!isset($actConfig['BASE_INFO']['status']) || !in_array($actConfig['BASE_INFO']['status'], ['0', '1'])) {
                $this->outputJSON(-1, '活动太火爆，请稍后再试！', array('code' => -106));
            } else {
                if ($actConfig['BASE_INFO']['status'] == 1) {
                    //判断活动是否在有效期内
                    $nowTime = date('Y-m-d H:i:s');
                    if (!isset($actConfig['BASE_INFO']['sday']) || !isset($actConfig['BASE_INFO']['eday'])) {
                        recordAMSLog(__FILE__ . "," . __LINE__ . ",活动配置文件中缺少sday或者eday,请检查配置文件！");
                        $this->outputJSON(-1, '系统繁忙，请稍后再试[' . __LINE__ . ']！', array('code' => -107));
                    }
                    //校验活动时间
                    if ($nowTime < $actConfig['BASE_INFO']['sday']) {
                        $this->outputJSON(-1, '活动还未开始，敬请期待！', array('code' => -108));
                    }
                    if ($actConfig['BASE_INFO']['eday'] < $nowTime) {
                        $this->outputJSON(-1, '活动已经结束！', array('code' => -109));
                    }
                }
            }
        }

        $login = null;
        //强制开启登录校验
        if (!defined('NO_CHECK_LOGIN') || !in_array($route, NO_CHECK_LOGIN)) {
            $login = new Login();
            if (!$login->checkLogin()) {
                $this->outputJSON(-1, '登录态失效，请重新登录！', array('code' => -103));
            }
        }

        //白名单校验
        if (ENV == 'pre' || (ENV == 'pro' && $actConfig['BASE_INFO']['status'] == '0')) {
            //不需要登录校验的模块不进行白名单校验
            if (!defined('NO_CHECK_LOGIN') || !in_array($route, NO_CHECK_LOGIN)) {
                $login = is_null($login) ? new Login() : $login;
                if (!$login->checkInWhite()) {

                    if (isset($actConfig['NOTWHITEMSG_CFG'][0]) && $actConfig['NOTWHITEMSG_CFG'][0] != '') {
                        $whiteMsg = $actConfig['NOTWHITEMSG_CFG'][0];
                    } else {
                        $whiteMsg = '活动暂未开放，敬请期待！';
                    }

                    $this->outputJSON(-1, $whiteMsg, array('code' => -105));
                }
            }
        }

        try {
            $CtrlClass = "Ctrl\\" . $routeCtrl;
            if (method_exists($CtrlClass, $routeAction)) {
                (new $CtrlClass())->$routeAction();
            } else {
                outputJSON(-101, $routeAction . '方法不存在');
            }
        } catch (\Exception $e) {
            recordAMSLog(__FUNCTION__ . "_Exception: " . $CtrlClass . ' exception: ' . $e->getMessage());
            throw new \Exception(__FUNCTION__ . "_Exception: " . $CtrlClass . ' exception: ' . $e->getMessage());
        }
    }

}
