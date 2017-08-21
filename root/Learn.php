<?php
namespace root;

class Learn
{
    public function test()
    {
        echo 'hello';
    }

    /**
     * 调用不存在的属性
     * @param $key
     */
    public function __get($key)
    {
        if (method_exists($this, $key)) {
            return static::$key();
        }
        throw new \Exception('unknown attribute!');
    }
}