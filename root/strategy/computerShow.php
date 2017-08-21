<?php
namespace root\strategy;

class computerShow implements GoodsShowStrategy
{
    public function show()
    {
        echo '显示电脑';
    }
}