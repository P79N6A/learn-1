<?php/** * Created by PhpStorm. * User: donggege * Date: 2019-05-18 * Time: 17:19 */namespace App\DataStructure;class Node{    public $pre = null; //前一个节点    public $next = null; //下一个节点    public $val = null; //值    public function __construct($val)    {        $this->val = $val;    }}class DoublyLinkedList{    protected $head = null; //头节点    protected $end = null; //尾节点    protected $length = 0; //链表长度    //append(element)：向列表尾部添加一个新的项    //    //insert(position, element)：向列表的特定位置插入一个新的项。    //    //remove(element)：从列表中移除一项。    //    //indexOf(element)：返回元素在列表中的索引。如果列表中没有该元素则返回-1。    //    //removeAt(position)：从列表的特定位置移除一项。    //    //isEmpty()：如果链表中不包含任何元素，返回true，如果链表长度大于0则返回false。    //    //size()：返回链表包含的元素个数。与数组的length属性类似。    //    //toString()：由于列表项使用了Node类，就需要重写继承自JavaScript对象默认的toString方法，让其只输出元素的值。    //    /**     * @param $val     */    public function append($val)    {        $node = new Node($val);        if ($this->head == null) {            $this->head = $node;            $this->end = $node;        } else {            $this->end->next = $node;            $node->pre = $this->end;            $this->end = $node;        }    }    public function insert()    {    }    public function remove()    {    }    public function indexOf()    {    }    public function removeAt()    {    }    public function isEmpty()    {    }    public function size()    {    }    public function toString()    {    }}