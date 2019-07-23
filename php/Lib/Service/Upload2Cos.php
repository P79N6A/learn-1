<?php
namespace Lib\Service;

use Lib\Base\Common;
use Lib\Base\LB;

/**
 * 上传资源到内部cos（腾讯云）
 * http://km.oa.com/articles/show/326041
 *
 * @author julianshu
 * @since  2017-05-24
 */
class Upload2Cos {
    private $app_id = '40095';
    private $secret_id = 'kEZTfd3smzbrhy2vNTL1q0LL';
    private $secret_key = '42jMBgWheNSexLo9GkI7SV5MGUD//mB3oU';
    private $host = 'sh.gfp.tencent-cloud.com';
    private $bucket = 'ulink'; // 存储桶

    // 支持l5/cl5寻址
    private $modid = '64265537'; // modid
    private $cmdid = '65536'; // cmdid

    public $err_code = null; // 错误码
    public $err_msg = null; // 错误信息

    public $fileSize = null; // 文件字节
    public $fileSha1 = null; // 文件sha1值
    public $fileType = null; // 文件类型：normal multipart appendable
    public $limit = 500; // 单次下载或者上传最大容量，单位为M
    public $bucketAcl = 2; // 存储桶访问权限，1=private，2=public-read

    public $marker = array(); // 时间标志，用于统计各种操作耗时

    public $debug = false; // 是否开启调试模式

    public function __construct($bucket='') {
//        if (empty ($bucket))
//            throw new \Exception ('bucket name cannot be empty');
//        $this->bucket = $bucket;
        if(!empty($bucket)) {
            $this->bucket = $bucket;
        }
        if (defined('ENV') && ENV != 'test') {
            // 正式环境 读取配置
            $actId = Common::getRequestParam('iActId');
            $actConfig = Common::getActConfig($actId);
            $config = $actConfig['COS_CFG'];
            $this->secret_id = $config['secret_id'];
            $this->secret_key = $config['secret_key'];
            $this->app_id = '40088';
        }
    }

    /**
     * 检测文件是否存在
     *
     * @param string $uri
     *            cos资源定位符
     * @return boolean true if exists; false otherwise.
     */
    public function checkExists($uri) {
        return $this->request($uri, 'head');
    }

    /**
     * 下载文件
     *
     * @param string $uri
     *            cos资源定位符
     * @param string $localFile
     *            本地文件路径
     * @return boolean true if success; false otherwise.
     */
    public function download($uri, $localFile = '') {
        if (!$this->checkExists($uri))
            return false;
        $fileSize = $this->fileSize;
        $fileSha1 = $this->fileSha1;
        if (empty ($localFile)) {
            $uri_tmp = ltrim($uri, '/');
            $localFile = str_replace('/', '_', $uri_tmp);
        }
        if (file_exists($localFile)) {
            if ($fileSha1 === sha1_file($localFile)) {
                return true;
            } else {
                unlink($localFile);
            }
        }

        $this->limit *= 1024 * 1024;
        $pieces = ceil($fileSize / $this->limit);
        $from_range = 0;
        $to_range = $from_range + $this->limit;
        for ($i = 0; $i < $pieces; $i++) {
            $exHeaderList = array(
                'Range' => 'bytes=' . $from_range . '-' . $to_range
            );
            $ret = $this->request($uri, 'get', $localFile, '', $exHeaderList);
            if (!$ret)
                return false;
            $from_range = $to_range + 1;
            $to_range = $from_range + $this->limit;
        }
        return true;
    }

    /**
     * 上传文件（文件类型：normal）
     *
     * @param string $localFile
     *            本地文件路径
     * @param string $uri
     *            cos资源定位符
     * @return boolean true if success; false otherwise.
     */
    public function upload($localFile, $uri) {
        if (!file_exists($localFile)) {
            return $this->_err_handle(-1001, $localFile . ' not exists');
        }
        $data = file_get_contents($localFile);
        $data_sha1 = sha1_file($localFile);
        $exHeaderList = array(
            'x-cos-content-sha1' => $data_sha1
        );
        return $this->request($uri, 'put', '', $data, $exHeaderList);
    }

    /**
     * 上传文件（文件类型：appendable）
     *
     * @param string $localFile
     *            本地文件路径
     * @param string $uri
     *            cos资源定位符
     * @return boolean true if success; false otherwise.
     */
    public function uploadByAppend($localFile, $uri = '') {
        if (!file_exists($localFile)) {
            return $this->_err_handle(-1001, $localFile . ' not exists');
        }
        $fileSize = filesize($localFile);
        $this->limit *= 1024 * 1024;
        $fp = fopen($localFile, 'r');
        $paramList ['append'] = '';
        $paramList ['position'] = 0;
        while (!feof($fp)) {
            $part_data = fread($fp, $this->limit);
            $exHeaderList = array(
                'x-cos-content-sha1' => sha1($part_data)
            );
            $ret = $this->request($uri, 'post', '', $part_data, $exHeaderList, $paramList);
            if (!$ret)
                return false;
            $paramList ['position'] += $this->limit;
        }
        return true;
    }

    /**
     * 上传文件（文件类型：multipart）
     *
     * @param string $localFile
     *            本地文件路径
     * @param string $uri
     *            cos资源定位符
     * @return boolean true if success; false otherwise.
     */
    public function uploadByPart($localFile, $uri) {
        if (!file_exists($localFile)) {
            return $this->_err_handle(-1001, $localFile . ' not exists');
        }
        $upload_id = $this->_init_multipart_upload($uri);
        $fp = fopen($localFile, 'r');
        $part_list = array();
        $part_num = 1;
        $this->limit *= 1024 * 1024;
        while (!feof($fp)) {
            $part_data = fread($fp, $this->limit);
            $part_sha1 = sha1($part_data);
            $ret = $this->_upload_part($uri, $part_data, $part_sha1, $part_num, $upload_id);
            if (!$ret)
                return false;
            $part_list [$part_num] = $part_sha1;
            $part_num++;
        }
        fclose($fp);
        return $this->_complete_multipart_upload($uri, $part_list, $upload_id);
    }

    /**
     * 初始化分片上传
     *
     * @param string $uri
     *            cos资源定位符
     * @return string upload id if success; false otherwise.
     */
    private function _init_multipart_upload($uri) {
        $paramList = array(
            'uploads' => ''
        );
        $ret = $this->request($uri, 'post', '', '', array(), $paramList);
        if ($ret) {
            $xml = simplexml_load_string($ret);
            if (!empty ($xml->UploadId)) {
                return ( string )$xml->UploadId;
            }
        }
        return false;
    }

    /**
     * 上传分块
     *
     * @param string $uri
     *            cos资源定位符
     * @param string $part_data
     *            分块数据
     * @param string $part_sha1
     *            分块数据sha1值
     * @param number $part_num
     *            分块数据所属块编号
     * @param string $upload_id
     *            初始化获取的upload id
     * @return boolean true if success; false otherwise.
     */
    private function _upload_part($uri, $part_data, $part_sha1, $part_num, $upload_id) {
        $exHeaderList = array(
            'x-cos-content-sha1' => $part_sha1
        );
        $paramList = array(
            'partnumber' => $part_num,
            'uploadid' => $upload_id
        );
        return $this->request($uri, 'put', '', $part_data, $exHeaderList, $paramList);
    }

    /**
     * 完成分块上传
     *
     * @param string $uri
     *            cos资源定位符
     * @param array $part_list
     *            分块信息
     * @param string $upload_id
     *            初始化获取的upload id
     * @return boolean true if success; false otherwise.
     */
    private function _complete_multipart_upload($uri, $part_list, $upload_id) {
        $data = '<CompleteMultipartUpload>';
        foreach ($part_list as $part_num => $part_sha1) {
            $data .= '<Part><PartNumber>' . $part_num . '</PartNumber><ETag>' . $part_sha1 . '</ETag></Part>';
        }
        $data .= '</CompleteMultipartUpload>';
        $paramList = array(
            'uploadid' => $upload_id
        );
        return $this->request($uri, 'post', '', $data, array(), $paramList);
    }

    /**
     * 删除文件
     *
     * @param string $uri
     *            cos资源定位符
     * @return boolean true if success; false otherwise.
     */
    public function delete($uri) {
        return $this->request($uri, 'delete');
    }

    /**
     * 删除多个文件
     *
     * @param array $list
     *            删除文件名称数组
     * @return string xml格式的返回信息; false otherwise.
     */
    public function multiDelete($list) {
        $data = '<Delete><Quiet>False</Quiet>';
        foreach ($list as $name) {
            $data .= '<Object><Key>' . $name . '</Key></Object>';
        }
        $data .= '</Delete>';
        $exHeaderList = array(
            'Content-MD5' => base64_encode(md5($data, true))
        );
        $paramList = array(
            'delete' => ''
        );
        return $this->request('', 'post', '', $data, $exHeaderList, $paramList);
    }

    /**
     * 检测存储桶是否存在
     *
     * @return boolean true if exists; false otherwise
     */
    public function checkBucketExists() {
        return $this->request('', 'head');
    }

    /**
     * 创建存储桶
     *
     * @param
     *            int 文件权限，有效值：1=private，2=public-read，默认值：private
     * @return boolean true if success; false otherwise
     */
    public function createBucket($acl = 1) {
        if ($acl == 2)
            $exHeaderList = array(
                'x-cos-acl' => 'public-read'
            );
        return $this->request('', 'put', '', '', $exHeaderList);
    }

    /**
     * 删除存储桶
     *
     * @return boolean true if success; false otherwise
     */
    public function deleteBucket() {
        return $this->request('', 'delete');
    }

    /**
     * 获取存储桶跨域访问配置
     *
     * @return string boolean if success; false otherwise
     */
    public function getBucketCors() {
        $paramList = array(
            'cors' => ''
        );
        return $this->request('', 'get', '', '', array(), $paramList);
    }

    /**
     * 设置存储桶跨域访问配置
     *
     * @param array $rules
     *            配置列表
     * @return boolean true if success; false otherwise
     */
    public function putBucketCors($rules = array()) {
        $valid_rules = array(
            'ID',
            'AllowedMethod',
            'AllowedOrigin',
            'AllowedHeader',
            'MaxAgeSeconds',
            'ExposeHeader',
            'AllowCredentials'
        );
        if (!empty ($rules)) {
            $data = '<CORSConfiguration>';
            foreach ($rules as $rule) {
                $data .= '<CORSRule>';
                foreach ($rule as $key => $value) {
                    if (in_array($key, $valid_rules)) {
                        $data .= '<' . $key . '>' . $value . '</' . $key . '>';
                    }
                }
                $data .= '</CORSRule>';
            }
            $data .= '</CORSConfiguration>';
            $exHeaderList = array(
                'Content-MD5' => base64_encode(md5($data, true))
            );
            $paramList = array(
                'cors' => ''
            );
            // var_dump($data);
            return $this->request('', 'put', '', $data, $exHeaderList, $paramList);
        }
    }

    /**
     * 获取特定存储桶内对象
     *
     * @return string bool if true; false otherwise
     */
    public function getObjectList() {
        return $this->request('', 'get');
    }

    /**
     * 获取下载地址
     *
     * @param string $uri
     *            cos资源定位符
     * @return string 下载地址
     */
    public function getUrl($uri) {
        $exists = $this->checkExists($uri);
        if (!$exists)
            return $this->_err_handle(-1001, $uri . ' not exists');
        $this->checkBucketExists(); // 获取bucket文件权限
        $host = $this->bucket . '-' . $this->app_id . '.' . $this->host;
        $uri = $this->formatUri($uri);
        $url = 'http://' . $host . $uri;
        if ($this->bucketAcl == 1) { // public-read,no need auth
            $now = time();
            $times = time() . ';' . strtotime('+24 hours');
            $headers = array(
                'Host' => $host
            );
            $sign = $this->genSign($uri, $times, $times, 'get', '', $this->formatHeaders($headers));
            $auth = "q-sign-algorithm=sha1&q-ak=" . $this->secret_id . '&q-sign-time=' . $times . '&q-key-time=' . $times . '&q-header-list=host&q-url-param-list=&q-signature=' . $sign;
            $url .= '?sign=' . $this->urlencode($auth);
            return $url;
        }
        return $url;
    }

    /**
     * 公共请求方法
     *
     * @param string $uri
     *            cos资源定位符
     * @param string $type
     *            请求方式(head,get,post,put,delete等)
     * @param string $localFile
     *            本地文件存储路径
     * @param string $data
     *            上传数据
     * @param array $exHeaderList
     *            额外头部（除host）
     * @param array $paramList
     *            参数列表
     * @param number $timeout
     *            超时时间
     * @return mixed
     */
    public function request($uri, $type = 'get', $localFile = '', $data = '', $exHeaderList = array(), $paramList = array(), $timeout = 600) {
        $host = $this->bucket . '-' . $this->app_id . '.' . $this->host;
        if (ENV != 'test') {
            // l5/cl5寻址,注意此处视自己安装l5/cl5的php组件提供的调用方法
            $l5Info = LB::getHostInfo($this->modid, $this->cmdid);
            if ($l5Info === false) {
                return $this->_err_handle(-2000, 'L5 get error '.$this->modid . ':' . $this->cmdid);
            }
            $ip_port = $l5Info['hostIp'].":".$l5Info['hostPort'];
        } else {
            $ip_port = $host;
        }
        $uri = $this->formatUri($uri);
        $url = 'http://' . $ip_port . $uri;
        $params1 = $this->formatParams($paramList, 1);
        if (!empty ($params1)) {
            $url .= '?' . $params1;
        }
        // var_dump($url);
        $ch = curl_init(); // 初始化CURL句柄
        curl_setopt($ch, CURLOPT_URL, $url); // 设置请求的URL
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // 设为TRUE把curl_exec()结果转化为字串，而不是直接输出
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($type)); // 设置请求方式
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout); // 设置超时时间

        $now = time();
        $times = time() . ';' . strtotime('+2 hours');
        $headerList = array(
            'Host' => $host
        );
        $headerList = array_merge($headerList, $exHeaderList);
        $headers = $this->formatHeaders($headerList);
        $params = $this->formatParams($paramList);
        $sign = $this->genSign($uri, $times, $times, $type, $params, $headers);
        $q_param_list = empty ($paramList) ? '' : implode(';', array_keys($paramList));
        $q_header_list = implode(';', array_keys($headerList));
        $auth = "q-sign-algorithm=sha1&q-ak=" . $this->secret_id . '&q-sign-time=' . $times . '&q-key-time=' . $times . '&q-header-list=' . strtolower($q_header_list) . '&q-url-param-list=' . strtolower($q_param_list) . '&q-signature=' . $sign;
        $headerList ['Authorization'] = $auth;
        $curl_headers = array();
        foreach ($headerList as $key => $value) {
            $curl_headers [] = $key . ': ' . $value;
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $curl_headers); // 设置HTTP头信息
        curl_setopt($ch, CURLINFO_HEADER_OUT, 1);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        if (!empty ($data)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data); // 设置提交的字符串
        }
        $this->mark('start_req');
        $ret = curl_exec($ch); // 执行预定义的CURL
        $this->mark('end_req');
        $curl_errno = curl_errno($ch);
        if ($curl_errno) { // 非0为失败
            $err_code = $curl_errno;
            $err_msg = 'Curl error: ' . curl_error($ch);
            return $this->_err_handle($err_code, $err_msg);
        }
        $rsp_headers = curl_getinfo($ch);
        $req_headers = $rsp_headers ['request_header'];
        curl_close($ch);
        $httpCode = $rsp_headers ['http_code'];
        $httpHeaderSize = $rsp_headers ['header_size']; // header字符串体积
        $httpHeader = substr($ret, 0, $httpHeaderSize); // 获得header字符串
        if ($this->debug) {
            var_dump($req_headers, $httpHeader);
        }
        if ($httpCode !== 200 && $httpCode !== 206) { // 请求失败 206 Partial Content
            preg_match('/Err-Code: ([-\d]+)\r\n/', $httpHeader, $matches);
            $err_code = $matches [1];
            preg_match('/Err-msg: (.*)\r\n/', $httpHeader, $matches);
            $err_msg = $matches [1];
            return $this->_err_handle($err_code, $err_msg);
        }
        // 获取文件大小
        preg_match('/Size: ([\d]+)\r\n/', $httpHeader, $matches);
        if (isset ($matches [1]))
            $this->fileSize = $matches [1];
        preg_match('/x-cos-object-type: ([\w]+)\r\n/', $httpHeader, $matches);
        if (isset ($matches [1]))
            $this->fileType = $matches [1];

        if ($this->fileType === 'normal') { // normal文件的sha1才是正确的
            preg_match('/x-cos-content-sha1: ([0-9a-f]+)\r\n/', $httpHeader, $matches);
            if (isset ($matches [1]))
                $this->fileSha1 = $matches [1];
        }

        // 获取存储桶访问权限
        if (preg_match('/x-cos-acl: public-read\r\n/', $httpHeader))
            $this->bucketAcl = 2;

        $httpData = substr($ret, $httpHeaderSize);
        if (!empty ($localFile)) { // body写入文件中
            if ($httpCode === 206) { // 追加写入
                $mode = 'ab';
            } else {
                $mode = 'wb';
            }
            $fp = fopen($localFile, $mode);
            if ($fp) {
                $this->mark('start_write');
                fwrite($fp, $httpData);
                $this->mark('end_write');
                fclose($fp);
            } else {
                return $this->_err_handle(-1002, 'open file ' . $localFile . ' failed');
            }
        } elseif (!empty ($httpData)) { // 返回body
            return $httpData;
        }
        return true;
    }

    /**
     * 签名算法
     *
     * @param string $uri
     *            cos资源定位符
     * @param string $sign_time
     *            签名有效时间
     * @param string $key_time
     *            密钥有效时间
     * @param string $type
     *            请求方式
     * @param string $params
     *            参与签名的参数
     * @param string $headers
     *            参与签名的headers
     * @return string 签名
     */
    public function genSign($uri, $sign_time, $key_time, $type = 'get', $params = '', $headers = '') {
        $sign_key = hash_hmac('sha1', $key_time, $this->secret_key);

        $format_string = $type . "\n";
        $format_string .= $uri . "\n";
        $format_string .= $params . "\n";
        $format_string .= $headers . "\n";
        // var_dump($format_string);
        $format_string_tmp = sha1($format_string);
        $string_to_sign = "sha1\n";
        $string_to_sign .= $sign_time . "\n";
        $string_to_sign .= $format_string_tmp . "\n";
        $sign = hash_hmac('sha1', $string_to_sign, $sign_key);
        return $sign;
    }

    /**
     * 格式化uri
     *
     * @param string $uri
     *            cos资源定位符
     * @return string 格式化的uri
     */
    private function formatUri($uri) {
        if (empty ($uri) || $uri === '/')
            return '/';
        $uri_tmp = array_filter(explode('/', $uri));
        $ret = '';
        foreach ($uri_tmp as $segment) {
            $ret .= '/' . $this->urlencode($segment);
        }
        return $ret;
    }

    /**
     * 格式化头部
     *
     * @param array $headerList
     *            http头部列表
     * @return 格式化的头部
     */
    private function formatHeaders($headerList) {
        if (empty ($headerList))
            return '';
        $ret = '';
        foreach ($headerList as $key => $value) {
            $ret .= strtolower($key) . '=' . $this->urlencode($value) . '&';
        }
        $ret = trim($ret, '&');
        return $ret;
    }

    /**
     * 格式化参数
     *
     * @param array $paramList
     *            参数列表
     * @param number $type
     *            格式化方式
     * @return string 格式化的参数
     */
    private function formatParams($paramList, $type = 0) {
        if (empty ($paramList))
            return '';
        $ret = '';
        foreach ($paramList as $key => $value) {
            if ($type == 1 && $value === '') {
                $value = '';
            } else {
                $value = '=' . $this->urlencode($value);
            }
            $ret .= $this->urlencode(strtolower($key)) . $value . '&';
        }
        $ret = trim($ret, '&');
        return $ret;
    }

    //兼容 PHP 5
   //  /**
   //   * 小写urlencode
   //   *
   //   * @param string $str
   //   * @return string 小写的urlencode
   //   */
   //  private function urlencode($str) {
   //      $str = urlencode($str);
   //      $str = preg_replace_callback('/%[0-9A-Z]{2}/', function ($matches) {
   //          return strtolower($matches [0]);
   //      }, $str);
   //      return $str;
   //  }

   //  /**
   //   * 标记当前时间戳
   //   *
   //   * @param string $name
   //   *            当前时间戳名称
   //   */
   //  public function mark($name) {
   //      $this->marker [$name] = microtime(TRUE);
   //  }

   //    /**
   // * 小写urlencode
   // *
   // * @param string $str
   // * @return string 小写的urlencode
   // */
  private function urlencode($str) {
    $str = urlencode ( $str );
    $str = preg_replace_callback ( '/%[0-9A-Z]{2}/', array($this, 'str'), $str );

    return $str;
  }

  private function str($matches) {
    return strtolower ( $matches [0] );
  }


  /**
   * 标记当前时间戳
   *
   * @param string $name
   *          当前时间戳名称
   */
  public function mark($name) {
    $this->marker [$name] = microtime ( TRUE );
  }

    /**
     * 计算两个标记间时间的流逝
     *
     * @param string $point1
     *            标记1
     * @param string $point2
     *            标记2
     * @param number $decimals
     *            保留小时
     * @return number 时间流逝，单位秒
     */
    public function elapsed_time($point1 = '', $point2 = '', $decimals = 4) {
        if ($point1 === '') {
            return '{elapsed_time}';
        }

        if (!isset ($this->marker [$point1])) {
            return '';
        }

        if (!isset ($this->marker [$point2])) {
            $this->marker [$point2] = microtime(TRUE);
        }

        return number_format($this->marker [$point2] - $this->marker [$point1], $decimals);
    }

    /**
     * 错误处理
     *
     * @param number $err_code
     *            错误码
     * @param string $err_msg
     *            错误信息
     * @return bool false always
     */
    private function _err_handle($err_code, $err_msg) {
        $this->err_code = $err_code;
        $this->err_msg = $err_msg;
        return false;
    }
}

?>
