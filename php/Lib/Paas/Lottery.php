<?php
/**
 * 抽奖发礼包
 * User: ronzheng
 * Date: 2018/11/21
 */
namespace Lib\Paas;

use Lib;
use Lib\Base\Common;
use Lib\Db\DbModel;

class Lottery extends Base
{
    public function __construct()
    {
        parent::__construct('resource.mrms');
    }

    /**
     * 抽奖
     * @param array $lotteryInfo =
     * [
     *      'MRMSID' =>'【必填】礼包单号'
     *      'iPackageGroupId' =>'【选填】（iPackageGroupId和iPackageGroupIdList二选一）礼包组ID',
     *      'iPackageGroupIdList' => '【选填】（iPackageGroupId和iPackageGroupIdList二选一）礼包组连抽信息,格式：礼包组ID1,发送数量:抽奖次数@@礼包组ID2,发送数量:抽奖次数@@...',
     *      'iPackageNum' => '【选填】 礼包组数量，默认1',
     *      'sGameOpenid' => '【选填】游戏openid(默认取当前登录账号的openid)，给非当前登录用户的角色发货时传递该值',
     *      'area' =>'【必填】服务器ID（或者手游渠道：手Q、微信)',
     *      'platId' =>'【如果业务区分平台则必填，否则选填】手机操作系统（iOS：0、安卓：1）',
     *      'partition' =>'【如果业务区分区服则必填，否则选填】小区ID',
     *      'roleId' =>'【必填】角色ID',
     *      'roleName' =>'【选填】角色昵称，编码方式：urlencode，编码：utf8',
     *      'checkparam' =>'【必填】角色信息，可以通过Role\getRoleInfo()函数获取',
     *      'checkstr' =>'【必填】角色签名，可以通过Role\getRoleInfo()函数获取',
     *]
     * @param int $loginType【选填】 1-表示小程序登录态，0-pt|互联|msdk
     * @return array ['ret'=>'0 发货成功。-1 发货失败，回滚资格。-2 发货失败，不回滚资格','msg'=>'错误信息','data'=>'抽奖成功时，返回的中奖信息']
     */
    public function doLottery($lotteryInfo, $loginType = 0)
    {
        $checkRet = $this->checkLotteryParam($lotteryInfo);
        if ($checkRet['ret'] != '0') {
            return $checkRet;
        }

        $this->setApiName('resource.mrms');
        $this->getPaasSign();

        $paasParam = array();
        $paasParam = $this->getSignInfo(); //生成接口调用签名
        if ($loginType == 1) {
            (new MiniProgramLogin())->getPaasAuthInfo($paasParam); //透传登录态信息
        } else {
            (new Login())->getLoginAuthInfo($paasParam); //透传登录态信息
        }

        if (!isset($lotteryInfo['iPackageNum'])) {
            $lotteryInfo['iPackageNum'] = 1;
        }

        $paasParam = array_merge($paasParam, $lotteryInfo);
        //组合URL
        $url = $this->getPaasUrl() . '?' . $this->getQueryString($paasParam);

        $res = (new Lib\Base\HttpRequest)->httpsGet($url);
        recordAMSLog(__FILE__ . "," . __LINE__ . ",lottery paas result：" . json_encode($res));

        //curl请求paas接口异常
        if ($res["ret"] == CURL_ERROR_NO) {
            if ($res["errNo"] == '28') {
                //请求pass接口超时，记录发货失败信息到DB
                $this->saveLotteryLog(-1, 'app curl paas timeout', $lotteryInfo);
                return ['ret' => '-2', 'msg' => '系统繁忙，请稍后再试[' . __LINE__ . ']！']; // 不用回滚资格
            } else {
                //curl超时以外的其他报错，说明paas接口请求失败，回滚用户资格
                return ['ret' => '-1', 'msg' => '系统繁忙，请稍后再试[' . __LINE__ . ']！'];
            }
        }

        //curl请求paas正常，但是paas调mrms超时，记录发货失败信息到DB，不回滚资格
        if ($res["ret"] == '-99999') {
            $this->saveLotteryLog(-2, 'paas curl mrms timeout', $lotteryInfo);
            return ['ret' => '-2', 'msg' => '系统繁忙，请稍后再试[' . __LINE__ . ']！']; // 不用回滚资格
        }

        if ($res["ret"] != 0) {
            //curl请求paas正常，但是paas接口返回异常（paas接口还没有请求mrms接口发货，一些校验信息失败），发货失败，回滚资格
            $res['ret'] = '-1';
        } else if ($res["data"]["iRet"] == '0') {
            // 发货成功
            $res['ret'] = '0';
            $res['msg'] = $res['data']['sMsg'];
        } else if ($res["data"]["iRet"] > -100000 && $res["data"]["iRet"] < 0) {
            //curl请求paas正常，paas请求mrms正常，mrms返回发货失败，根据mrms错误码确认需要回滚资格
            $res['ret'] = '-1';
            $res['msg'] = '系统繁忙，请稍后再试[' . __LINE__ . ']！';
        } else {
            //curl请求paas正常，paas请求mrms正常，mrms返回发货失败，根据mrms错误码确认不需要回滚资格
            $res['ret'] = '-2';
            $res['msg'] = $res['data']['sMsg'];
        }
        return $res;
    }

    /**
     * 查询中奖记录
     * @param string $mrmsId【必填】 礼包单号，必须传
     * @param int $curPage【选填】 要查询第几页的数据（分页查询时用到，默认查询第一页）
     * @param int $pageSize【选填】 没有查询的记录数（分页查询时用到，默认一次查询10条）
     * @param string $iPackageId【选填】 查询指定的礼包ID的中奖纪录（多个礼包ID逗号分隔）
     * @param string $iExcludePackageId【选填】 查询时排除指定的礼包ID的中奖纪录（多个礼包ID逗号分隔）
     *
     * @return array
     *         [
     *              'ret' => '返回码',
     *              'msg' => '系统消息'
     *              'data' => [
     *                  'iRet' => '返回码',
     *                  'sMsg' => '系统消息',
     *                  'myGiftList' =>'所有礼包信息',
     *                  'pageNow' => '当前页码',
     *                  'pageTotal' => '总页数'
     *              ]
     *         ]
     */
    public function getLotteryInfo($mrmsId, $curPage = 1, $pageSize = 10, $iPackageId = '', $iExcludePackageId = '')
    {
        if (!Common::checkMRMSID($mrmsId)) {
            return $this->getRetData('-1000608', '礼包单号不能为空！');
        }

        if (!Common::checkIsNum($curPage)) {
            return $this->getRetData('-1000609', '当前查询数据页码错误！');
        }

        if (!Common::checkIsNum($pageSize)) {
            return $this->getRetData('-1000610', '每页返回结果记录数参数错误！');
        }

        if ($iPackageId != '') {
            $pkgIds = explode(',', $iPackageId);
            foreach ($pkgIds as $id) {
                if (!Common::checkIsNum($id)) {
                    return $this->getRetData('-1000611', '要查询的礼包ID错误！');
                }
            }
        }

        if ($iExcludePackageId != '') {
            $elPkgIds = explode(',', $iExcludePackageId);
            foreach ($elPkgIds as $id) {
                if (!Common::checkIsNum($id)) {
                    return $this->getRetData('-1000612', '查询时要排除的礼包ID错误！');
                }
            }
        }

        $this->setApiName('lottery.info');
        $this->getPaasSign();

        $paasParam = array();
        $paasParam = $this->getSignInfo(); //生成接口调用签名
        (new Login())->getLoginAuthInfo($paasParam); //透传登录态信息

        $paasParam = array_merge($paasParam, ['MRMSID' => $mrmsId, 'iPageNow' => intval($curPage), 'iPageSize' => intval($pageSize), 'iPackageId' => $iPackageId, 'iExcludePackageId' => $iExcludePackageId]);
        //组合URL
        $url = $this->getPaasUrl() . '?' . $this->getQueryString($paasParam);
        $res = (new Lib\Base\HttpRequest)->httpsGet($url);
        recordAMSLog(__FILE__ . "," . __LINE__ . ",lotteryinfo paas result：" . json_encode($res));
        return $res;
    }

    /**
     * 扣除资格+发货（事务）,应用程序不需要进行资格控制以及资格回滚等，每次发货，直接请求该接口即可
     *
     * @param array $lotteryParam 礼包和角色信息
     * [
     *      'MRMSID' =>'【必填】礼包单号'
     *      'iPackageGroupId' =>'【选填】（iPackageGroupId和iPackageGroupIdList二选一）礼包组ID',
     *      'iPackageGroupIdList' => '【选填】（iPackageGroupId和iPackageGroupIdList二选一）礼包组连抽信息,格式：礼包组ID1,发送数量:抽奖次数@@礼包组ID2,发送数量:抽奖次数@@...',
     *      'iPackageNum' => '【选填】 礼包组数量，默认1',
     *      'sGameOpenid' => '【选填】游戏openid(默认取当前登录账号的openid)，给非当前登录用户的角色发货时传递该值',
     *      'area' =>'【必填】服务器ID（或者手游渠道：手Q、微信)',
     *      'platId' =>'【如果业务区分平台则必填，否则选填】手机操作系统（iOS：0、安卓：1）',
     *      'partition' =>'【如果业务区分区服则必填，否则选填】小区ID',
     *      'roleId' =>'【必填】角色ID',
     *      'roleName' =>'【选填】角色昵称，编码方式：urlencode，编码：utf8',
     *      'checkparam' =>'【必填】角色信息，可以通过Role\getRoleInfo()函数获取',
     *      'checkstr' =>'【必填】角色签名，可以通过Role\getRoleInfo()函数获取',
     *]
     * @param array $qualParam  资格信息
     * [
     *      'qualKey' => '【必填】用于资格控制的key(必须唯一)，不要加openid，paas接口会自动加上openid',
     *      'limitNum' =>'【必填】周期内可以获得的总资格',
     *      'limitType' => '【必填】A|D|W|M  A-整个活动期间，D-以天为周期性资格，W-以自然周为周期性资格，M-以自然月为周期性资格'
     *      'cycleNum' =>'【limitType参数不为A时必填，为A时该值无意义】limitType对应的周期长度'
     *      'usePerTime' => '【选填】每次使用需要扣除的数量，默认扣除1',
     * ]
     *
     * 例如：
     * $qualParam['qualKey'=>'test1', 'limitNum'=>'1', 'limitType'=>'A'] 表示整个活动期间获得1次资格
     * $qualParam['qualKey'=>'test1', 'limitNum'=>'1', 'limitType'=>'D', 'cycleNum'=>'2'] 表示以每2天为一个周期，每个周期（每2天）获得1次资格
     * $qualParam['qualKey'=>'test1', 'limitNum'=>'2', 'limitType'=>'D', 'cycleNum'=>'1'] 表示以每1天为一个周期，每个周期（每1天）获得2次资格
     * $qualParam['qualKey'=>'test1', 'limitNum'=>'2', 'limitType'=>'W', 'cycleNum'=>'1'] 表示以每1自然周为一个周期，每个周期（每1个自然周）获得2次资格
     * $qualParam['qualKey'=>'test1', 'limitNum'=>'1', 'limitType'=>'M', 'cycleNum'=>'3'] 表示以每3自然月为一个周期，每个周期（每3个自然月）获得1次资格
     *
     * @param array $pubQualParam  【选填】公共资格信息，默认空数组
     * [
     *      'qualKey' => '【必填】用于资格控制的key(必须唯一)，不要加openid，paas接口会自动加上openid',
     *      'limitNum' =>'【必填】周期内可以获得的总资格',
     *      'limitType' => '【必填】A|D|W|M  A-整个活动期间，D-以天为周期性资格，W-以自然周为周期性资格，M-以自然月为周期性资格'
     *      'cycleNum' =>'【limitType参数不为A时必填，为A时该值无意义】limitType对应的周期长度'
     *      'usePerTime' => '【选填】每次使用需要扣除的数量，默认扣除1',
     * ]
     *
     * 使用场景：
     * 抽奖限制每天1次，活动期间总限制5次。就可以通过$pubQualParam参数来控制
     * $qualParam = ['qualKey'=>'lottery_login_day', 'limitNum'=>'1', 'limitType'=>'D', 'cycleNum'=>'1']
     * $pubQualParam = ['qualKey'=>'lottery_login_total', 'limitNum'=>'5', 'limitType'=>'A']
     *
     *
     * @return array 抽奖结果信息
     * [
     *      'ret'=>'0发货成功，<0发货失败',
     *      'msg'=>'接口调用返回提示信息',
     *      'data'=>[
     *          'iRet'=>'',
     *          'sMsg'=>'',
     *          'iPackageGroupId'=>'成功发货的礼包组ID',
     *          'iPackageId'=>'成功发货的礼包ID',
     *          'sPackageName'=>'成功发货的礼包名称',
     *          'iPackageNum'=>'成功发货的礼包数量',
     *          'dTimeNow' =>'成功发货的时间'
     *      ],
     *      'sSerial'=>'流水号，用户查看日志'
     * ]
     */
    public function lotteryQualTrans($lotteryParam, $qualParam, $pubQualParam = [])
    {
        $checkRet = $this->checkLotteryParam($lotteryParam);
        if ($checkRet['ret'] != '0') {
            return $checkRet;
        }

        $this->setApiName('lottery.trans');
        $this->getPaasSign();

        $paasParam = array();
        $paasParam = $this->getSignInfo(); //生成接口调用签名
        (new Login())->getLoginAuthInfo($paasParam); //透传登录态信息

        if (!isset($qualParam['qualKey']) || $qualParam['qualKey'] == '') {
            return $this->getRetData('-1000613', '资格key不能为空！');
        }

        if (!isset($qualParam['usePerTime']) || empty($qualParam['usePerTime'])) {
            $qualParam['usePerTime'] = 1;
        }
        if (!in_array($qualParam['limitType'], array('A', 'D', 'W', 'M'))) {
            recordAMSLog(__FILE__ . "," . __LINE__ . ",limitType error：value=" . $qualParam['limitType']);
            return ['ret' => '-1000603', 'msg' => '资格类型错误！'];
        }
        if ($qualParam['usePerTime'] > $qualParam['limitNum']) {
            recordAMSLog(__FILE__ . "," . __LINE__ . ",usePerTime should be smaller than limitNum!");
            return ['ret' => '-1000604', 'msg' => '需要扣除的资格数错误！'];
        }

        $lotteryParam['sQualKey'] = $qualParam['qualKey'];

        if ($qualParam['limitType'] == 'A') {
            $lotteryParam['sLimitType'] = '1';
        } else {
            if (!isset($qualParam['cycleNum']) || !preg_match('/^[1-9]+\d*$/', $qualParam['cycleNum'])) {
                return ['ret' => '-1000614', 'msg' => '周期资格数错误！'];
            }
            $lotteryParam['sLimitType'] = '2_' . $qualParam['limitType'] . '_' . $qualParam['cycleNum'];
        }

        $lotteryParam['iLimitNum'] = $qualParam['limitNum'];
        $lotteryParam['iUsePerTime'] = $qualParam['usePerTime'];

        if (!isset($lotteryParam['iPackageNum'])) {
            $lotteryParam['iPackageNum'] = 1;
        }

        if (isset($pubQualParam['qualKey']) && $pubQualParam['qualKey'] != '') {

            if ($pubQualParam['qualKey'] == $qualParam['qualKey']) {
                recordAMSLog(__FILE__ . "," . __LINE__ . ",public qualkey and private qualkey cannot be the same!");
                return ['ret' => '-1000615', 'msg' => '公有资格key和私有资格key不能相同！'];
            }

            if (!preg_match('/^[1-9]\d*$/', $pubQualParam['limitNum'])) {
                recordAMSLog(__FILE__ . "," . __LINE__ . ",public qual limitNum error：value=" . $pubQualParam['limitNum']);
                return ['ret' => '-1000616', 'msg' => '公有资格错误！'];
            }

            if (!isset($pubQualParam['usePerTime']) || $pubQualParam['usePerTime'] == '') {
                $pubQualParam['usePerTime'] = 1;
            }
            if (!preg_match('/^[1-9]\d*$/', $pubQualParam['usePerTime'])) {
                recordAMSLog(__FILE__ . "," . __LINE__ . ",public qual usePerTime error：value=" . $pubQualParam['usePerTime']);
                return ['ret' => '-1000617', 'msg' => '每次扣除的公有资格数错误！'];
            }

            if (!in_array($pubQualParam['limitType'], array('A', 'D', 'W', 'M'))) {
                recordAMSLog(__FILE__ . "," . __LINE__ . ",public qual limitType error：value=" . $pubQualParam['limitType']);
                return ['ret' => '-1000618', 'msg' => '公有资格类型错误！'];
            }

            if ($pubQualParam['usePerTime'] > $pubQualParam['limitNum']) {
                recordAMSLog(__FILE__ . "," . __LINE__ . ",public qual usePerTime should be smaller than limitNum!");
                return ['ret' => '-1000619', 'msg' => '需要扣除的公有资格数错误！'];
            }

            $lotteryParam['sPubQualKey'] = $pubQualParam['qualKey'];

            if ($pubQualParam['limitType'] == 'A') {
                $lotteryParam['sPubLimitType'] = '1';
            } else {
                if (!isset($pubQualParam['cycleNum']) || !preg_match('/^[1-9]+\d*$/', $pubQualParam['cycleNum'])) {
                    recordAMSLog(__FILE__ . "," . __LINE__ . ",public qual cycleNum error：value=" . $pubQualParam['cycleNum']);
                    return ['ret' => '-1000620', 'msg' => '周期公有资格数错误！'];
                }
                $lotteryParam['sPubLimitType'] = '2_' . $pubQualParam['limitType'] . '_' . $pubQualParam['cycleNum'];
            }

            $lotteryParam['iPubLimitNum'] = $pubQualParam['limitNum'];
            $lotteryParam['iPubUsePerTime'] = $pubQualParam['usePerTime'];
        }

        $paasParam = array_merge($paasParam, $lotteryParam);
        //组合URL
        $url = $this->getPaasUrl() . '?' . $this->getQueryString($paasParam);

        $res = (new Lib\Base\HttpRequest)->httpsGet($url);
        recordAMSLog(__FILE__ . "," . __LINE__ . ",lottery trans paas result：" . json_encode($res));

        if ($res['ret'] == 0 && ($res["data"]["iRet"] < -100000 || ($res["data"]["iRet"] >= 0 && $res["data"]["iRet"] < 100000))) {
            // 发货成功 (-∞,-100000) || [0,100000)
            $res['ret'] = '0';
            $res['msg'] = $res['data']['sMsg'];
        } else {
            //失败 [100000,+∞) || [-100000,0)
            $res['ret'] = '-1';
            if (isset($res["data"]["iRet"]) && $res["data"]["iRet"] == -2014) {
                $res['msg'] = '资格不足';
            } else {
                $res['msg'] = isset($res['data']['sMsg']) ? $res['data']['sMsg'] : '系统繁忙，请稍后再试！';
            }
        }

        return $res;
    }

    /**
     * 扣除积分+发货（事务）
     *
     * @param array $lotteryParam 礼包和角色信息
     * [
     *      'MRMSID' =>'【必填】礼包单号'
     *      'iPackageGroupId' =>'【选填】（iPackageGroupId和iPackageGroupIdList二选一）礼包组ID',
     *      'iPackageGroupIdList' => '【选填】（iPackageGroupId和iPackageGroupIdList二选一）礼包组连抽信息,格式：礼包组ID1,发送数量:抽奖次数@@礼包组ID2,发送数量:抽奖次数@@...',
     *      'iPackageNum' => '【选填】 礼包组数量，默认1',
     *      'sGameOpenid' => '【选填】游戏openid(默认取当前登录账号的openid)，给非当前登录用户的角色发货时传递该值',
     *      'area' =>'【必填】服务器ID（或者手游渠道：手Q、微信)',
     *      'platId' =>'【如果业务区分平台则必填，否则选填】手机操作系统（iOS：0、安卓：1）',
     *      'partition' =>'【如果业务区分区服则必填，否则选填】小区ID',
     *      'roleId' =>'【必填】角色ID',
     *      'roleName' =>'【选填】角色昵称，编码方式：urlencode，编码：utf8',
     *      'checkparam' =>'【必填】角色信息，可以通过Role\getRoleInfo()函数获取',
     *      'checkstr' =>'【必填】角色签名，可以通过Role\getRoleInfo()函数获取',
     *]
     * @param array $pointParam  积分信息
     * [
     *      'iPointId' => '积分ID',
     *      'iPointAmount' =>'要扣除的积分数量',
     *      'iPointType' => '积分账号类型：0-openid（默认），1-openid+area，2-openid+area+roleId'
     * ]
     *
     * @return array 抽奖结果信息
     * [
     *      'ret'=>'0成功，<0失败',
     *      'msg'=>'接口调用返回提示信息',
     *      'data'=>[
     *          'iRet'=>'',
     *          'sMsg'=>'',
     *          'iPackageGroupId'=>'成功发货的礼包组ID',
     *          'iPackageId'=>'成功发货的礼包ID',
     *          'sPackageName'=>'成功发货的礼包名称',
     *          'iPackageNum'=>'成功发货的礼包数量',
     *          'dTimeNow' =>'成功发货的时间'
     *      ],
     *      'sSerial'=>'流水号，用户查看日志'
     * ]
     */
    public function lotteryPointTrans($lotteryParam, $pointParam)
    {
        $checkRet = $this->checkLotteryParam($lotteryParam);
        if ($checkRet['ret'] != '0') {
            return $checkRet;
        }

        $this->setApiName('lottery.trans');
        $this->getPaasSign();

        $paasParam = array();
        $paasParam = $this->getSignInfo(); //生成接口调用签名
        (new Login())->getLoginAuthInfo($paasParam); //透传登录态信息

        if (!isset($lotteryParam['iPackageNum'])) {
            $lotteryParam['iPackageNum'] = 1;
        }

        if (empty($pointParam)) {
            return ['ret' => '-1000602', 'msg' => '需要扣除的积分信息错误', 'data' => []];
        }

        if (!isset($pointParam['iPointId']) || !preg_match('/^[1-9]\d*$/', $pointParam['iPointId'])) {
            return ['ret' => '-1000603', 'msg' => '需要扣除的积分ID错误', 'data' => []];
        }

        if (!isset($pointParam['iPointAmount'])) {
            $pointParam['iPointAmount'] = 1;
        }
        if (!preg_match('/^[1-9]\d*$/', $pointParam['iPointAmount'])) {
            return ['ret' => '-1000604', 'msg' => '需要扣除的积分数量错误', 'data' => []];
        }

        if (!isset($pointParam['iPointType'])) {
            $pointParam['iPointType'] = '0';
        }

        if (!in_array($pointParam['iPointType'], ['0', '1', '2'])) {
            return ['ret' => '-1000605', 'msg' => '需要扣除的积分账号类型错误！', 'data' => []];
        }

        if ($pointParam['iPointType'] == '1' && !isset($lotteryParam['area'])) {
            return ['ret' => '-1000606', 'msg' => '积分账号类型iPointType=1时，大区area必须传！', 'data' => []];
        }

        if ($pointParam['iPointType'] == '2' && (!isset($lotteryParam['area']) || !isset($lotteryParam['roleId']))) {
            return ['ret' => '-1000607', 'msg' => '积分账号类型iPointType=2时，大区（area）和角色ID（roleId）必须传！', 'data' => []];
        }

        $paasParam = array_merge($paasParam, $lotteryParam, $pointParam);
        //组合URL
        $url = $this->getPaasUrl() . '?' . $this->getQueryString($paasParam);

        $res = (new Lib\Base\HttpRequest)->httpsGet($url);
        recordAMSLog(__FILE__ . "," . __LINE__ . ",lottery trans paas result：" . json_encode($res));

        // 发货成功 (-∞,-100000) || [0,100000)
        if ($res['ret'] == 0 && ($res["data"]["iRet"] < -100000 || ($res["data"]["iRet"] >= 0 && $res["data"]["iRet"] < 100000))) {
            //积分不足的情况特殊处理（iRet=1203 or 1004 or 1006），返回发货不成功
            if (isset($res["data"]["iRet"]) && in_array($res["data"]["iRet"], [1203, 1004, 1006])) {
                $res['ret'] = '-1';
                $res['msg'] = '积分不足';
            } else {
                $res['ret'] = '0';
                $res['msg'] = $res['data']['sMsg'];
            }
        } else {
            //失败 [100000,+∞) || [-100000,0)
            $res['ret'] = '-1';
            $res['msg'] = isset($res['data']['sMsg']) ? $res['data']['sMsg'] : '系统繁忙，请稍后再试！';
        }

        return $res;
    }

    /**
     * 查询抽奖周期礼包剩余量
     * @param string $mrmsId【必填】 礼包单号
     * @param string $iPackageId【必填】 礼包ID,多个礼包ID用逗号间隔
     * @param int $iQueryQQLeft【选填】 1-表示查询单q限量，0-表示总限量
     * @return array
     *         [
     *              'ret' => '返回码',
     *              'msg' => '系统消息'
     *              'data' => [
     *                  'iRet' => '返回码',
     *                  'sMsg' => '系统消息',
     *                  'jData'=> [
     *                        'iDayLeft' => '日剩余量',
     *                       'iDayTotal' => '日总量',
     *                       'iWeekLeft' => '周剩余量',
     *                       'iWeekTotal' => '周总量',
     *                       'iMonthLeft' => '月剩余量',
     *                       'iMonthTotal' => '月总量',
     *                       'iLeft' => '总剩余量',
     *                       'iTotal' => '总量',
     *                  ]
     *              ]
     *         ]
     */
    public function getLotteryLimit($mrmsId, $iPackageId, $iQueryQQLeft = 0)
    {
        $this->setApiName('resource.lotterylimitsvr');
        $this->getPaasSign();

        $paasParam = array();
        $paasParam = $this->getSignInfo(); //生成接口调用签名
        (new Login())->getLoginAuthInfo($paasParam); //透传登录态信息

        //获取iModuleId
        $arr = explode("-", $mrmsId);
        if (count($arr) == 3 && $arr[0] == "IEGAMS") {
            $iModuleId = $arr[2];
        } else {
            return ['ret' => '-1000605', 'msg' => '礼包单号错误', 'data' => []];
        }
		
		if(!preg_match("/^\d+(,\d+)*$/", $iPackageId)){
            return ["ret" => '-1000629', 'msg' => '礼包ID错误', 'data' => []];
        }
		
        $paasParam = array_merge($paasParam, ['iModuleId' => intval($iModuleId), 'iPackageId' => $iPackageId, 'iQueryQQLeft' => intval($iQueryQQLeft)]);
        //组合URL
        $url = $this->getPaasUrl() . '?' . $this->getQueryString($paasParam);
        $res = (new Lib\Base\HttpRequest)->httpsGet($url);
        recordAMSLog(__FILE__ . "," . __LINE__ . ",lotterylimit paas result：" . json_encode($res));
        return $res;
    }

    /**
     * 检查抽奖接口必须传递参数有效性
     */
    private function checkLotteryParam($lotteryParam)
    {
        if (!Common::checkMRMSID($lotteryParam['MRMSID'])) {
            return $this->getRetData('-1000600', '礼包单号不能为空！');
        }

        //$lotteryParam['iPackageGroupId'] 和 $lotteryParam['iPackageGroupIdList'] 二者只能传一个
        if (isset($lotteryParam['iPackageGroupId']) && isset($lotteryParam['iPackageGroupIdList'])) {
            return $this->getRetData('-1000601', '礼包组ID和礼包组连抽信息不能同时传！');
        }

        //$lotteryParam['iPackageGroupId'] 和 $lotteryParam['iPackageGroupIdList'] 不能都为空
        if (!isset($lotteryParam['iPackageGroupId']) && !isset($lotteryParam['iPackageGroupIdList'])) {
            return $this->getRetData('-1000602', '礼包组ID和礼包组连抽信息必选有一个参数传递值！');
        }

        if (isset($lotteryParam['iPackageGroupIdList']) && !preg_match("/^((\d+)(,\d+)?:(\d+)(@@)?)*(\d+)(,\d+)?:(\d+)$/", $lotteryParam['iPackageGroupIdList'])) {
            return $this->getRetData('-1000603', '连抽礼包组格式错误！');
        }

        if (isset($lotteryParam['iPackageGroupId']) && !preg_match('/^\d+$/', $lotteryParam['iPackageGroupId'])) {
            return $this->getRetData('-1000604', '礼包组ID错误！');
        }
        return $this->getRetData('0', 'success');
    }

    /**
     *  发货失败，将相关信息记录到db
     */
    private function saveLotteryLog($status, $log, $lotteryInfo)
    {
        if (ENV == 'pro') {
            $insData = [];
            $insData['iActId'] = Common::getRequestParam('iActId');
            $insData['openid'] = Common::getRequestParam('openid');
            $insData['area'] = isset($lotteryInfo['area']) ? $lotteryInfo['area'] : '-1';
            $insData['platid'] = isset($lotteryInfo['platId']) ? $lotteryInfo['platId'] : '-1';
            $insData['partition'] = isset($lotteryInfo['partition']) ? $lotteryInfo['partition'] : '-1';
            $insData['roleid'] = isset($lotteryInfo['roleId']) ? $lotteryInfo['roleId'] : '';
            $insData['rolename'] = isset($lotteryInfo['roleName']) ? urlencode($lotteryInfo['roleName']) : '';
            $insData['addTime'] = date('Y-m-d H:i:s');
            $insData['status'] = $status;
            $insData['num'] = '0';
            $insData['log'] = $log;
            $insData['sSerial'] = isset($_GET['sAmsSerial']) ? $_GET['sAmsSerial'] : '';

            $db = DbModel::init();
            $db->table('tbMRMSFail')->insert($insData);
        }
    }
}
