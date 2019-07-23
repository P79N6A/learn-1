<?php
/**
 * 脏字过滤
 * User: ronzheng
 * Date: 2018/12/20
 */

namespace Lib\Paas;

use Lib;

class DirtyFilter extends Base
{
    public function __construct()
    {
        parent::__construct('tool.checkdirty');
    }

    /**
     * 检查脏字
     * @param string $words【必填】 待检查的内容 必须是UTF-8编码
     * @param string $clearWords【必填】 引用传递，返回过滤脏字之后的内容
     * @return bool true - 不包含脏字  false-包含脏字
     */
    public function checkDirtyWord($words, &$clearWords)
    {
        //判断是否为空
        if (!$words) {
            $clearWords = '';
            return true;
        }

        //整合参数
        $paasParam = array();
        $paasParam = $this->getSignInfo(); //生成接口调用签名

        //组合URL
        $url = $this->getPaasUrl() . '?' . $this->getQueryString($paasParam);

        $res = (new Lib\Base\HttpRequest)->httpsPost($url, $words);

        recordAMSLog(__FILE__ . "," . __LINE__ . ",dirty words check return data =" . json_encode($res));

        //接口出现异常，为了确保安全，返回校验不通过
        if ($res['ret'] != 0 || $res['data']['ret'] != 0) {
            $clearWords = '';
            return false;
        }
        //包含脏字
        if ($res['data']['isDirty'] == '1') {
            $clearWords = $res['data']['data']['msg_'];
            return false;
        } else {
            $clearWords = $words;
        }

        return true;
    }
}
