<?php
namespace root\strategy;;

class mobileShow implements GoodsShowStrategy
{
    public function show()
    {
        echo '显示手机';
    }
}