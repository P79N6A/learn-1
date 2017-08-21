<?php
namespace root\helper;

class Runtime
{
    private $startTime = 0;
    private $stopTime = 0;
    private static $instance = null;

    private function __construct()
    {

    }

    static function getInstance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self;
        }
        return self::$instance;
    }

    private function getMicrotime()
    {
        list($msec, $sec) = explode(' ', microtime());
        return ((float)$msec + (float)$sec);
    }

    public function start()
    {
        $this->startTime = $this->getMicrotime();
        return $this->startTime;
    }

    public function stop()
    {
        $this->stopTime = $this->getMicrotime();
        return $this->stopTime;
    }

    /**
     * 返回运行时间毫秒
     *
     * @return float
     */
    public function spent()
    {
        return round(($this->stopTime - $this->startTime) * 1000, 3);
    }
}