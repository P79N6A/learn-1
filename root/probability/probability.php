<?php
namespace root\probability;

class Probability
{
    protected $resource;
    protected $initArray = array();

    public function __construct(array $resource)
    {
        $this->resource = $resource;
    }

    public function init()
    {
        foreach ($this->resource as $key => $value) {
            if (empty($value['p'])) {
                throw new \Exception('P KEY NOT EXIST.');
            }
            $this->initArray[$key] = $value['p'];
        }
        return $this;
    }

    public function get_rand()
    {

        $result = '';
        //概率数组的总概率精度
        $proSum = array_sum($this->initArray);
        //概率数组循环
        foreach ($this->initArray as $key => $proCur) {
            $randNum = mt_rand(1, $proSum);
            if ($randNum <= $proCur) {
                $result = $key;
                break;
            } else {
                $proSum -= $proCur;
            }
        }
        unset ($this->resource);
        return $result;
    }

}