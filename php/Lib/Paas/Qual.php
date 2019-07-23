<?php
/**
 * Qual类
 *
 * 功能：查询资格
 *
 * @author ronzheng <ronzheng@tencent.com>
 * @version 1.0.0 2019/03/27
 * @since
 *
 */
namespace Lib\Paas;

use Lib;

class Qual extends Base
{
    public function __construct()
    {
        parent::__construct('qual.query');
    }

    /**
     * 查询资格剩余情况（支持批量查询）
     * @param array $qual【必填】 =
     * [
     *    'qualkey1' => '资格总数',
     *    'qualkey2' => '资格总数',
     *    ...,
     *    'sGameOpenid' => '【选填】，默认不需要传递，自动查询当前登录态中的openid的资格信息。如果要查询非当前登录账号的资格情况，可以通过该参数查询指定openid的账号资格信息'
     *]
     * 注意：qualkey 不需要传递openid，paas接口会自动拼接当前登录态中的openid到key上
     * @return array
     * [
     *      'ret' => '0-成功，其他失败',
     *      'msg' => '接口返回的信息',
     *      'errNo' => '错误码',
     *      'sSerial' => '流水号',
     *      ['qualkey1'] => [
     *          'iRet' => '0-成功，其他失败',
     *          'sMsg' => '查询资格时返回想你想',
     *          'type' => 'DB_VALID_QUAL(有效)|DB_EXPIRE_QUAL(过期)|DB_NO_USERQUAL(无记录)|ERROR_HOLD_QRYRUL(查询失败)'
     *          'data' => [
     *              ['iTotalNum'] => '总资格数',
     *              ['iLeftNum'] => '剩余资格数',
     *              ['iUsedNum'] => '已经使用的资格数',
     *              ['iFrozenNum'] => '被冻结的资格数'
     *          ]
     *      ],
     *      ['qualkey2'] => ['参考qualkey1结果'],
     *      ...
     * ]
     * 注意：1、对应资格查询成功判断条件：$ret['ret'] == 0 && $ret['qualkey1']['iRet'] == 0
     *       2、$ret['ret'] != 0 || $ret['qualkey1']['iRet'] != 0 报系统繁忙
     */
    public function getQualInfo($qual)
    {
        $paasParam = array();
        $paasParam = $this->getSignInfo(); //生成接口调用签名
        (new Login())->getLoginAuthInfo($paasParam); //透传登录态信息

        //sGameOpenid 参数以get方式传递
        if (isset($qual['sGameOpenid'])) {
            $paasParam['sGameOpenid'] = $qual['sGameOpenid'];
            unset($qual['sGameOpenid']);
        }

        //组合URL
        $url = $this->getPaasUrl() . '?' . $this->getQueryString($paasParam);

        $res = (new Lib\Base\HttpRequest)->httpsPost($url, json_encode($qual));
        recordAMSLog(__FILE__ . "," . __LINE__ . ",qual query result：" . json_encode($res));
        if ($res['ret'] == '0') {
            $data = $res['data'];
            unset($res['data']);
            $res = array_merge($res, $data);
        }
        return $res;
    }
}
