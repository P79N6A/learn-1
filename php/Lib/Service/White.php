<?php
/**
 * Created by PhpStorm.
 * User: billge
 * Date: 2019/5/23
 * Time: 15:53
 */
namespace Lib\Service;

use Lib\Base\RedisAction;
use Lib\Base\Common;

class White
{
    private $whiteId;
    private $actId;
    private $num = 0;

    /**
     * White constructor.
     * @param $whiteId
     */
    public function __construct($whiteId)
    {
        $this->whiteId = $whiteId;
        if (ENV !== 'test') {
            $this->actId = Common::getRequestParam('iActId');
            $actConfig = Common::getActConfig($this->actId);
            if ($actConfig && isset($actConfig['WHITE_CFG']) && isset($actConfig['WHITE_CFG'][$whiteId])) {
                recordAMSLog("WHITE_CFG: " . json_encode($actConfig['WHITE_CFG']));
                $this->num = $actConfig['WHITE_CFG'][$whiteId];
            } else {
                recordAMSLog(__FILE__ . ", 白名单配置缺失或该id不在白名单配置中。", LP_ERROR);
            }
        }
    }

    /**
     * 判断该用户是否在openid
     * @param string $openid
     * @return bool
     */
    public function hasOpenId($openid='')
    {
        $openid = $openid ?: Common::getRequestParam('openid');
        if (!$openid) {
            return false;
        }
        if (ENV === 'test') {
            $lists = defined('WHITE_OPENID') ? WHITE_OPENID[$this->whiteId] : [];
            return in_array($openid, $lists);
        } else if ($this->num <= 0){
            // 配置异常
            return false;
        } else {
            $key = sprintf('ulink_sys_%s_white%s', $this->actId, $this->whiteId);
            $key = $this->num == 1 ? $key : ($key."_".fmod(Common::time33($openid), $this->num));
            $ret = RedisAction::init(['prefix'=>'0'])->sIsMember($key, $openid);
            recordAMSLog("WHITE_KEY: ". $key . " ret: ". ($ret ? 'true' : 'false'));
            return $ret;
        }
    }
}
