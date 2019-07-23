<?php
/**
 * IdGenerator类
 * 功能：唯一ID生成器
 *
 * @author ronzheng <ronzheng@tencent.com>
 * @version 1.0.0 2019/03/12
 * @since
 *
 */
namespace Lib\Service;

use Lib\Base\Common;
use Lib\Base\RedisAction;

class IdGenerator
{
    /**
     * 根据用户openid生成一个邀请码，同一个账号每次请求，生成邀请码不同
     * @return string 邀请码，由0-9a-zA-Z组成，长度不固定，调用方需要自己保存邀请码
     */

    public function getUid1($openId)
    {
        if (empty($openId)) {
            return false;
        }

        $hashNum = Common::time33($openId);
        $redis = RedisAction::init(['prefix' => 0]);
        $incrNum = $redis->incr(IDGENERATOR_IVTCODE_KEY1, rand(1, 5));
        return Common::number2Base62($hashNum . $incrNum);
    }

    /**
     * 生成长度11位的邀请码
     * 第1位：A-Z随机一个大写字符
     * 第2-11位：固定长度的数字
     * @return string 邀请码，每次调用结果都不同，全局唯一，调用方需要自己保存邀请码
     */
    public function getUid2()
    {
        $redis = RedisAction::init(['prefix' => 0]);

        $incrNum = $redis->incr(IDGENERATOR_IVTCODE_KEY2, rand(1, 5));
        $len = strlen($incrNum);
        $num = substr('1000000000', 0, -$len) . $incrNum;
        $code = chr(rand(65, 90)) . (string) $num;
        return $code;
    }
}
