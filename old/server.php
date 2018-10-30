<?php

class webSocket
{
    protected $users;//保存已连接的用户
    protected $sockets = []; //保存socket

    public function __construct($url = '127.0.0.1', $port = '8888')
    {
        if (substr(php_sapi_name(), 0, 3) !== 'cli') {
            die('请通过命令行模式运行!');
        }
        $this->master = $this->webSocket($url, $port);
        $this->sockets[] = $this->master;
    }

    //建立服务终端
    private function webSocket($url, $port)
    {
        //创建服务
        $service = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        //设置
        socket_set_option($service, SOL_SOCKET, SO_REUSEADDR, 1);
        //绑定
        socket_bind($service, $url, $port);
        //开始监听
        socket_listen($service, 5);

        return $service;
    }

    //外部接口
    public function run()
    {
        //死循环直到退出
        while (true) {
            $changes = $this->sockets;
            if (socket_select($changes, $write, $except, 0) === false) {
                exit('error');
            }
            foreach ($changes as $socket) {
                //新的连接请求
                if ($this->master == $socket) {
                    $client = socket_accept($this->master); //接受一次握手
                    $this->sockets[] = $client; //把这次连接存进连接池
                    $uuid = uniqid(); //唯一ID
                    $this->users[$uuid] = [
                        'socket' => $client, //存入客户连接
                        'hand' => false //标识为没握手
                    ];
                    $this->log('来人了!');
                } else {
                    $len = 0;
                    $buffer = '';
                    //读取该socket的信息，注意：第二个参数是引用传参即接收数据，第三个参数是接收数据的长度
                    do {
                        $l = socket_recv($socket, $buf, 1000, 0);
                        $len += $l;
                        $buffer .= $buf;
                    } while ($l == 1000);

                    //根据socket在user池里面查找相应的$k,即健ID
                    $k = $this->search($socket);
                    //如果接收的信息长度小于7，则该client的socket为断开连接
                    if ($len < 7) {
                        //给该client的socket进行断开操作，并在$this->sockets和$this->users里面进行删除
                        $this->close($k);
                        $this->log("$k:关闭连接");
                        continue;
                    }
                    //判断该socket是否已经握手
                    if (!$this->users[$k]['hand']) {
                        //如果没有握手，则进行握手处理
                        $this->accept($k, $buffer);
                    } else {
                        //走到这里就是该client发送信息了，对接受到的信息进行uncode处理
                        $buffer = $this->uncode($buffer);
                        if ($buffer == false) {
                            continue;
                        }
                        $this->log($buffer);
                        //如果不为空，则进行消息推送操作
                        foreach ($this->users as $v) {
                            socket_write($v['socket'], $this->formatMsg($buffer));
                        }

                    }
                }
            }


        }
    }

    public function uncode($str)
    {
        $mask = array();
        $data = '';
        $msg = unpack('H*', $str);
        $head = substr($msg[1], 0, 2);
        if (hexdec($head{1}) === 8) {
            $data = false;
        } else if (hexdec($head{1}) === 1) {
            $mask[] = hexdec(substr($msg[1], 4, 2));
            $mask[] = hexdec(substr($msg[1], 6, 2));
            $mask[] = hexdec(substr($msg[1], 8, 2));
            $mask[] = hexdec(substr($msg[1], 10, 2));
            $s = 12;
            $e = strlen($msg[1]) - 2;
            $n = 0;
            for ($i = $s; $i <= $e; $i += 2) {
                $data .= chr($mask[$n % 4] ^ hexdec(substr($msg[1], $i, 2)));
                $n++;
            }
        }
        return $data;
    }

    //断开连接
    public function close($key)
    {
        //断开相应socket
        socket_close($this->users[$key]['socket']);
        //删除相应的user信息
        unset($this->users[$key]);
        //重新定义sockets连接池
        $this->sockets = array($this->master);
        foreach ($this->users as $v) {
            $this->sockets[] = $v['socket'];
        }
    }

    //查找用户唯一id
    public function search($socket)
    {
        foreach ($this->users as $key => $value) {
            if ($socket == $value['socket']) {
                return $key;
            }
        }
        return false;
    }

    //握手
    public function accept($uuid, $buffer)
    {
        if (preg_match("/Sec-WebSocket-Key: (.*)\r\n/i", $buffer, $matches)) {

            //握手按照协议组装key
            $key = base64_encode(sha1($matches[1] . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11', true));

            $header = "HTTP/1.1 101 Switching Protocols\r\n";
            $header .= "Upgrade: websocket\r\n";
            $header .= "Sec-WebSocket-Version: 13\r\n";
            $header .= "Connection: Upgrade\r\n";
            $header .= "Sec-WebSocket-Accept: " . $key . "\r\n\r\n";
            socket_write($this->users[$uuid]['socket'], $header, strlen($header));
            $this->users[$uuid]['hand'] = true;
        }
    }

    //推送消息处理
    public function formatMsg($msg)
    {
        $frame = [];
        $frame[0] = '81';
        $len = strlen($msg);
        if ($len < 126) {
            $frame[1] = $len < 16 ? '0' . dechex($len) : dechex($len);
        } elseif ($len < 65025) {
            $s = dechex($len);
            $frame[1] = '7e' . str_repeat('0', 4 - strlen($s)) . $s;
        } else {
            $s = dechex($len);
            $frame[1] = '7f' . str_repeat('0', 16 - strlen($s)) . $s;
        }

        $data = '';
        $l = strlen($msg);
        for ($i = 0; $i < $l; $i++) {
            $data .= dechex(ord($msg{$i}));
        }
        $frame[2] = $data;
        $data = implode('', $frame);
        return pack('H*', $data);
    }

    //获取消息处理
    public function getMsg($buffer)
    {
        $res = '';
        $len = ord($buffer) & 127;
        if ($len === 126) {
            $masks = substr($buffer, 4, 4);
            $data = substr($buffer, 8);
        } elseif ($len === 127) {
            $masks = substr($buffer, 10, 4);
            $data = substr($buffer, 14);
        } else {
            $masks = substr($buffer, 2, 4);
            $data = substr($buffer, 6);
        }

        for ($index = 0; $index < strlen($data); $index++) {
            $res .= $data[$index] ^ $masks[$index % 4];
        }

        return $res;
    }

    //写入日志文件
    private function log($message)
    {
        $content = '';
        if (file_exists('log.txt')) {
            $content = file_get_contents('log.txt');
        }
        $content = $content . "\r\n" . $message;
        file_put_contents('log.txt', $content);
    }
}

$ws = new webSocket();
$ws->run();
