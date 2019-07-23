<?php

/*****************************************************
 * File name: DbMysql.php
 * Create date: 2018/09/25
 * Author: smallyang
 * Modify: ronzheng 2018/12/20
 * 1、修改update和where条件同时包含相同字段在绑定变量时被覆盖的问题
 * 2、修改where条件直接传组装完成的SQL语句时单引号被转义问题
 * Description: mysql类库
 *****************************************************/

namespace Lib\Db;

use Lib\Base\Common;

class DbMysql
{
    /**
     * @var \PDO
     */
    private $mysql;
    private $stmt;
    private $band_values;
    private $update_band_values;
    private $table;
    private $realTable;
    private $where_keys;
    private $limit_keys;
    private $group_keys;
    private $having_keys;
    private $order_keys;
    public static $transTimes = 0;

    protected $extracts = array('=', '>', '>=', '<', '<=', '!=', '<>', 'is', 'is not');

    private static $dbObject;

    /**
     * 实例化
     *
     * @param $dbConfig
     * @return $this
     */
    public static function init($dbConfig)
    {
        if (!self::$dbObject) {
            self::$dbObject = new self($dbConfig);
        }
        return self::$dbObject;
    }

    /**
     * 构造方法
     *
     * DbMysql constructor.
     * @param $dbConfig
     */
    private function __construct($dbConfig)
    {
        if (!$this->mysql) {
            $this->mysql = $this->connectDb($dbConfig);
        }
        return $this;
    }

    /**
     * 连接数据库
     *
     * @param $dbConfig
     * @return mixed|\PDO
     */
    private function connectDb($dbConfig)
    {
        //配置
        if (!isset($dbConfig['host']) || !isset($dbConfig['db_name']) || !isset($dbConfig['user_name']) || !isset($dbConfig['password'])) {
            die('mysql配置文件缺失参数,请检查');
        }

        $dsn = 'mysql:host=' . $dbConfig['host'] . ';dbname=' . $dbConfig['db_name'];
        $options = array(\PDO::ATTR_PERSISTENT => true, \PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES UTF8");
        try {
            $this->mysql = new \PDO($dsn, $dbConfig['user_name'], $dbConfig['password'], $options);
        } catch (\PDOException $e) {
            recordAMSLog('数据库连接失败: ' . $e->getMessage());
            return false;
        }

        return $this->mysql;
    }

    /**
     * 单纯的执行sql|一般的增删改
     *
     * @param  string $sql
     * @return mixed
     */
    public function exec($sql)
    {
        $row_count = $this->prepare($sql)->execute();
        if (is_array($row_count) && $row_count[2]) {
            recordAMSLog('MySQL Error：' . $row_count[2]);
            return false;
        } else {
            recordAMSLog('sql Debug：' . $this->getLastSql() . ', sql result: ' . json_encode($row_count));
            return $row_count;
        }
    }

    /**
     * 单纯的执行sql|一般的查询
     *
     * @param  string $sql
     * @param int $row
     * @return mixed
     */
    public function query($sql, $row = 0)
    {
        if (empty($sql)) {
            return false;
        }
        $row_count = $this->prepare($sql)->execute();

        //错误
        if (is_array($row_count) && $row_count[2]) {
            recordAMSLog('MySQL Error：' . $row_count[2]);
            return false;
        }

        $this->stmt->setFetchMode(\PDO::FETCH_ASSOC);
        if ($row) {
            $result = $this->stmt->fetch();
        } else {
            $result = $this->stmt->fetchAll();
        }
        recordAMSLog('sql Debug：' . $this->getLastSql() . ', sql result: ' . json_encode($result));
        return $result;
    }

    /**
     * 指定表名
     *
     * @param string $tableName
     * @return $this
     */
    public function table($tableName)
    {
        $this->table = $tableName;
        $this->realTable = $tableName;
        $this->where_keys = '';
        $this->band_values = [];
        $this->update_band_values = [];
        $this->limit_keys = '';
        $this->group_keys = '';
        $this->having_keys = '';
        $this->order_keys = '';
        return $this;
    }

    /**
     * 分开字符转义
     * @param string|array $value
     * @return string
     */
    private function addEscape($value)
    {
        $fieldStr = '`';
        if (is_array($value)) {
            $fieldStr .= implode('`,`', $value);
        } else {
            $fieldStr .= $value;
        }
        $fieldStr .= '`';
        return $fieldStr;
    }

    /**
     * prepare准备
     *
     * @param $sql
     * @return $this
     */
    private function prepare($sql)
    {
        $this->stmt = $this->mysql->prepare($sql);
        return $this;
    }

    /**
     * 产生占位符
     *
     * @param  array $data
     * @return string
     */
    private function createPlaceHolder(array $data)
    {
        $holder = '';
        foreach ($data as $value) {
            $holder .= '?, ';
        }
        return rtrim(trim($holder), ',');
    }

    /**
     * 产生绑定值
     *
     * @param  array $data
     * @return $this
     */
    private function createBandValue(array $data)
    {
        $index = 1;
        foreach ($data as $key => $value) {
            $value = addslashes($value);
            $value = str_replace('`', '\`', $value);
            if (is_int($value)) {
                $this->band_values[$index] = array($value, \PDO::PARAM_INT);
            } else {
                $this->band_values[$index] = array($value, \PDO::PARAM_STR);
            }
            $index++;
        }
        return $this;
    }

    /**
     * 数据绑定
     *
     * @return $this
     */
    private function bind()
    {
        //循环绑定
        if (!empty($this->band_values)) {
            if (!empty($this->update_band_values)) {
                $this->band_values = array_merge($this->update_band_values, $this->band_values);
                unset($this->band_values[0]);
            }
            foreach ($this->band_values as $key => $value) {
                $this->stmt->bindValue($key, $value[0], $value[1]);
            }
        }
        return $this;
    }

    /**
     * 执行stmt
     *
     * @return int 受影响的行数
     */
    private function execute()
    {
        $this->stmt->execute();
        //打出错误信息
        if ($this->stmt->errorCode() != '00000') {
            return $this->stmt->errorInfo();
        } else {
            return $this->stmt->rowCount();
        }
    }

    /**
     * 插入数据/增加
     *
     * @param $data
     * @param $mode string insert, ignore, replace
     * @return int|bool SQL执行失败则返回false，SQL执行成功，如果有自增ID返回自增ID，没有自增ID返回0、
     * 注意：$ret === false 则表示SQL执行异常
     */
    public function insert($data, $mode = 'insert')
    {
        if (empty($data)) {
            return false;
        }

        //插入模式
        $insertMode = 'INSERT INTO ';
        if ($mode == 'ignore') {
            $insertMode = 'INSERT IGNORE INTO ';
        } elseif ($mode == 'replace') {
            $insertMode = 'REPLACE INTO ';
        }

        $data_keys = array_keys($data);
        $sql = $insertMode . $this->table . '(' . $this->addEscape($data_keys) . ') VALUES (' . $this->createPlaceHolder($data_keys) . ')';
        $state = $this->prepare($sql)->createBandValue($data)->bind()->execute();
        if (is_array($state) && $state[2]) {
            recordAMSLog('MySQL Error：' . $state[2]);
            return false;
        } else {
            $last_insert_id = $this->mysql->lastInsertId();
            recordAMSLog('sql Debug：' . $this->getLastSql() . ', sql result: ' . json_encode($last_insert_id));
            if ($last_insert_id) {
                return $last_insert_id;
            } else {
                //return $state;
                return 0; //跟dbproxy结果返回一致
            }
        }
    }

    /**
     * 删除
     *
     * @return int
     */
    public function delete()
    {
        $sql = "DELETE FROM " . $this->table . $this->where_keys;
        $row_count = $this->prepare($sql)->bind()->execute();
        if (is_array($row_count) && $row_count[2]) {
            recordAMSLog('MySQL Error：' . $row_count[2]);
            return false;
        } else {
            recordAMSLog('sql Debug：' . $this->getLastSql() . ', sql result: ' . json_encode($row_count));
            return $row_count;
        }
    }

    /**
     * update 修改
     *
     * @param array $data
     * @return int
     */
    public function update($data)
    {
        $update_keys = [];
        if (is_array($data)) {
            $index = 1;
            $this->update_band_values[0] = '';
            foreach ($data as $key => $value) {
                $update_keys[] = $this->addEscape($key) . " = ?";
                if (is_int($value)) {
                    $this->update_band_values[$index] = array($value, \PDO::PARAM_INT);
                } else {
                    $this->update_band_values[$index] = array($value, \PDO::PARAM_STR);
                }
                $index++;
            }
            $update_keys = implode(", ", $update_keys);
        } else {
            $update_keys = $data;
        }

        $sql = "UPDATE " . $this->table . " SET " . $update_keys . $this->where_keys . $this->limit_keys;
        $row_count = $this->prepare($sql)->bind()->execute();
        if (is_array($row_count) && $row_count[2]) {
            recordAMSLog('MySQL Error：' . $row_count[2]);
            return false;
        } else {
            recordAMSLog('sql Debug：' . $this->getLastSql() . ', sql result: ' . json_encode($row_count));
            return $row_count;
        }
    }

    /**
     * where 条件
     *
     * @param  array|string $data
     * @param string $relation
     * @return $this
     */
    public function where($data = '', $relation = 'AND')
    {
        if (empty($data)) {
            return $this;
        }
        if (is_array($data)) {
            $index = 1;
            foreach ($data as $key => $value) {
                if (is_array($value)) {
                    if (!in_array($value[0], $this->extracts)) {
                        recordAMSLog('不支持这个运算符: ' . $value[0]);
                        return false;
                    }
                    //去除多表查询中的. 不知道是不是PDO的BUG
                    //$key_new = str_replace('.', '', $key);
                    $where_keys[] = $key . " " . $value[0] . " ?";
                    if (is_int($value[1])) {
                        $this->band_values[$index] = array($value[1], \PDO::PARAM_INT);
                    } else {
                        $this->band_values[$index] = array($value[1], \PDO::PARAM_STR);
                    }
                } else {
                    //去除多表查询中的. 不知道是不是PDO的BUG
                    //$key_new = str_replace('.', '', $key);
                    $where_keys[] = $key . " = ?";
                    if (is_int($value)) {
                        $this->band_values[$index] = array($value, \PDO::PARAM_INT);
                    } else {
                        $this->band_values[$index] = array($value, \PDO::PARAM_STR);
                    }
                }
                $index++;
            }
            $this->where_keys = ' WHERE ' . implode(' ' . strtoupper($relation) . ' ', $where_keys);
        } else {
            $this->where_keys = ' WHERE ' . $data;
        }
        return $this;
    }

    /**
     * 查询/多条
     * @param  string $field
     * @return mixed
     */
    public function select($field = '*')
    {
        $sql = "SELECT " . $field . " FROM " . $this->table . $this->where_keys . $this->group_keys . $this->having_keys . $this->order_keys . $this->limit_keys;
        $row_count = $this->prepare($sql)->bind()->execute();
        if ($row_count) {
            if (is_array($row_count) && $row_count[2]) {
                recordAMSLog('MySQL Error：' . $row_count[2]);
                return false;
            } else {
                $result = $this->stmt->fetchAll(\PDO::FETCH_ASSOC);
                recordAMSLog('sql Debug：' . $this->getLastSql() . ', sql result: ' . json_encode($result));
                return $result;
            }
        } else {
            return array();
        }
    }

    /**
     * 查询/一条
     * @param  string $field
     * @return array
     */
    public function find($field = '*')
    {
        $sql = "SELECT " . $field . " FROM " . $this->table . $this->where_keys . $this->group_keys . $this->having_keys . $this->order_keys . $this->limit_keys;
        $row_count = $this->prepare($sql)->bind()->execute();
        if ($row_count) {
            //出错
            if (is_array($row_count) && $row_count[2]) {
                recordAMSLog('MySQL Error：' . $row_count[2]);
				return false;
            } else {
                $result = $this->stmt->fetch(\PDO::FETCH_ASSOC);
                recordAMSLog('sql Debug：' . $this->getLastSql() . ', sql result: ' . json_encode($result));
                return $result;
            }
        } else {
            return array();
        }
    }

    /**
     * 总数
     */
    public function count()
    {
        $sql = "SELECT COUNT(*) as count FROM " . $this->table . $this->where_keys . $this->group_keys . $this->having_keys . $this->order_keys . $this->limit_keys;
        $row_count = $this->prepare($sql)->bind()->execute();
        if (is_array($row_count) && $row_count[2]) {
            recordAMSLog('MySQL Error：' . $row_count[2]);
        } else {
            $this->stmt->setFetchMode(\PDO::FETCH_ASSOC);
            $result = $this->stmt->fetch();
            recordAMSLog('sql Debug：' . $this->getLastSql() . ', sql result: ' . json_encode($result));
            return $result['count'];
        }
    }

    /**
     * 分表
     *
     * @param $str
     * @param int $count
     * @return $this
     */
    public function sub($str, $count = 10)
    {
        $num = Common::time33($str);
        $this->table = $this->realTable . '_' . fmod($num, $count);
        return $this;
    }

    /**
     * group 分组
     *
     * @param  string $field
     * @return $this
     */
    public function group($field = '')
    {
        if (empty($field)) {
            return $this;
        }
        $this->group_keys = ' GROUP BY ' . $field;
        return $this;
    }

    /**
     * having 字句，分组后筛选
     *
     * @param  string $field
     * @return $this
     */
    public function having($field = '')
    {
        if (empty($field)) {
            return $this;
        }
        $this->having_keys = ' HAVING ' . $field;
        return $this;
    }

    /**
     * 排序order by
     *
     * @param  string $field
     * @param  string $order
     * @return $this
     */
    public function order($field = '', $order = 'DESC')
    {
        if (empty($field)) {
            return $this;
        }
        $order = strtoupper($order);
        if (!in_array($order, ['ASC', 'DESC'])) {
            $order = 'DESC';
        }
        $this->order_keys = ' ORDER BY ' . $field . " " . $order;
        return $this;
    }

    /**
     * limit分页
     *
     * @param  $start
     * @param  $end
     * @return $this
     */
    public function limit($start = '', $end = '')
    {
        if (empty($start)) {
            return $this;
        }
        if ($end) {
            $limit = " LIMIT {$start}, {$end}";
        } else {
            $limit = " LIMIT {$start}";
        }
        $this->limit_keys = $limit;
        return $this;
    }

    /**
     * 获得最后执行的sql
     *
     * @return string
     */
    public function getLastSql()
    {
        if ($this->band_values) {
            $sql_string = $this->stmt->queryString;
            foreach ($this->band_values as $key => $value) {
                if ($value[1] == \PDO::PARAM_STR) {
                    $sql_value = "'" . $value[0] . "'";
                } else {
                    $sql_value = $value[0];
                }
                $sql_string = preg_replace('/\?/', $sql_value, $sql_string, 1);
            }
            return $sql_string;
        } else {
            return $this->stmt->queryString;
        }
    }

    /**
     * 获取表table名
     *
     * @return string
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * 启动事务
     *
     * @return void
     */
    public function begin()
    {
        //数据rollback 支持
        if (!$this->mysql) {
            return false;
        }
        if (self::$transTimes == 0) {
            $this->mysql->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $this->mysql->beginTransaction();
            recordAMSLog("start trans");
        }
        self::$transTimes++;
        return;
    }

    /**
     * 用于非自动提交状态下面的查询提交
     *
     * @return bool
     */
    public function commit()
    {
        //数据rollback 支持
        if (!$this->mysql) {
            return false;
        }
        if (self::$transTimes > 0) {
            $result = $this->mysql->commit();
            self::$transTimes = 0;
            if (!$result) {
                recordAMSLog('commit error');
                return false;
            } else {
                recordAMSLog("commit success");
            }
        }
        return true;
    }

    /**
     * 事务回滚
     * @access function
     * @return bool
     */
    public function rollback()
    {
        if (!$this->mysql) {
            return false;
        }
        if (self::$transTimes > 0) {
            $result = $this->mysql->rollback();
            self::$transTimes = 0;
            if (!$result) {
                recordAMSLog("rollback error");
                return false;
            } else {
                recordAMSLog("rollback success");
            }
        }
        return true;
    }

//    /**
    //     * 调用pdo里面的原始方法
    //     *
    //     * @param  string $method
    //     * @param  sring $arguments
    //     * @return minu
    //     */
    //    public function __call($method, $arguments)
    //    {
    //        return call_user_func_array(array($this->mysql, $method), $arguments);
    //    }
}
