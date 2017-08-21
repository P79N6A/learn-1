<?php
namespace root\strategy;

class show
{
    private $show;

    public function __construct(GoodsShowStrategy $show)
    {
        $this->show = $show;
    }

    public function showGoods()
    {
        $this->show->show();
    }
}