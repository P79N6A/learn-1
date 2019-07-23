<?php
/**
 * 上传类
 * User: millyli
 * Date: 2019/03/29
 */

namespace Lib\Service;

use Lib\Base\Common;

class Upload
{
    private static $cos = null;
    private $picLimit;
    private $fileLimit;
    private $error;

    public function __construct()
    {
        if (self::$cos === null){
            self::$cos = new Upload2Cos();
        }
        $this->picLimit = 2*1024*1024;  // 最大2M
        $this->fileLimit = 5*1024*1024;  // 最大2M
    }

    /**
     * 上传图片
     * @param $file $_FILES['file'] ['tmp_name'=>$path, 'type'=>'image/png', 'name'=>'xxx.png']
     * @return bool
     */
    public function uploadPic($file)
    {
        if (!$file['tmp_name'] || !file_exists($file['tmp_name'])){
            $this->error = '上传文件不存在';
            return false;
        }
        if (filesize($file['tmp_name']) > $this->picLimit){
            $this->error = '图片大小不可超过2M';
            return false;
        }
        $type = $file['type'];
        if (substr($type, 0, 6) != 'image/') {
            $this->error = '只支持图片格式上传';
            return false;
        }
        $ext = explode("." , $file['name']);
        $va = count($ext)-1;
        $suffix=strtolower($ext[$va]);
        if (!in_array($suffix, ['jpg', 'png', 'jpeg', 'gif', 'bmp'])){
            $this->error = '不支持的后缀名文件('.$suffix.')';
            return false;
        }
        $iActId = Common::getRequestParam('iActId');
        if (!$iActId) {
            $this->error = '需要匹配活动号';
            return false;
        }
        $filename = md5_file($file['tmp_name']).".".$suffix;
        $ret = self::$cos->upload($file['tmp_name'], "/$iActId/$filename");
        if ($ret) {
            $appId = ENV == 'test' ? '40095' : '40088';
            return "http://ulink-{$appId}.picsh.qpic.cn/$iActId/$filename";
        } else {
            $this->error = "[".self::$cos->err_code."]".self::$cos->err_msg;
            return false;
        }
    }

    /**
     * 上传文件
     * @param $file $_FILES['file'] ['tmp_name'=>$path, 'type'=>'image/png', 'name'=>'xxx.png']
     * @return bool
     */
    public function uploadFile($file)
    {
        if (!$file['tmp_name'] || !file_exists($file['tmp_name'])){
            $this->error = '上传文件不存在';
            return false;
        }
        if (filesize($file['tmp_name']) > $this->fileLimit){
            $this->error = '文件大小不可超过5M';
            return false;
        }
        $type = $file['type'];
        if (substr($type, 0, 12) != 'application/') {
            $this->error = '只支持文件格式上传';
            return false;
        }
        $ext = explode("." , $file['name']);
        $va = count($ext)-1;
        $suffix=strtolower($ext[$va]);
        if (!in_array($suffix, ['doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'pdf', 'zip', 'rar'])){
            $this->error = '不支持的后缀名文件('.$suffix.')';
            return false;
        }
        $iActId = Common::getRequestParam('iActId');
        if (!$iActId) {
            $this->error = '需要匹配活动号';
            return false;
        }
        $filename = md5_file($file['tmp_name']).".".$suffix;
        $ret = self::$cos->upload($file['tmp_name'], "/$iActId/$filename");
        if ($ret) {
            $appId = ENV == 'test' ? '40095' : '40088';
            return "http://ulink-{$appId}.sh.gfp.tencent-cloud.com/$iActId/$filename";
        } else {
            $this->error = "[".self::$cos->err_code."]".self::$cos->err_msg;
            return false;
        }
    }

    public function getError()
    {
        return $this->error;
    }
}
