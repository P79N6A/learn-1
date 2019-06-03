<?php
require "vendor/autoload.php";

ini_set("display_errors", "On");

error_reporting(E_ALL | E_STRICT);
/*$a = md5(md5('123456'));
dd($a);
dd(md5($a.'gGz9TUd3'));
$obj = new \App\Pay\Demo(new \App\Pay\Wetch());

$obj->transfer();
echo 'sdfasdfsadf';*/

/*$a['dd'] = 0;
if(isset($a['dd'])){
    dd(1);


}*/
//dd('hello jenkins!!!');


/*$sum = 0;
srand(microtime(true));

$rand = rand(10,13);

echo $rand;

echo '</br>';

$t = microtime(true);

for ($i = 0; $i<40000+$r;$i++){
    for ($j=0;$j<=40000;$j++){
        $sum = $sum+$i+$j;
    }
}

echo $sum;

echo '</br>';

$end = microtime(true);



echo $end-$t;*/


/*function fib($n)
{
    $result = [0, 1];
    if ($n < 2) {
        return $result[$n];
    }
    $result = [0, 1];
    for ($i = 2; $i <= $n; $i++) {
        $result[] = $result[$i - 2] + $result[$i - 1];
    }
    return $result;
}*/

/*$stack = new \App\DataStructure\Queue();

$stack->enqueue('dongjie1');
$stack->enqueue('dongjie2');
$stack->enqueue('dongjie3');
$stack->enqueue('dongjie4');
//dd($stack->size());

$stack->dequeue();

$stack->front();
$stack->enqueue('dongjie5');
dd($stack->getItems());*/

$doublyLinkedList = new \App\DataStructure\DoublyLinkedList();

$doublyLinkedList->append(1);
$doublyLinkedList->append(2);
$doublyLinkedList->append(3);
$doublyLinkedList->append(4);
$doublyLinkedList->append(5);
$doublyLinkedList->append(6);

while ($doublyLinkedList);



