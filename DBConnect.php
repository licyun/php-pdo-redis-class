<?php
/**
 * Created by IntelliJ IDEA.
 * User: licyun
 * Date: 2019/4/14 0014
 * Time: 16:13
 */

require_once("RedisConnect.php");
define("CACHETIME", 60*60);     #缓存时间
/**
 * 封装PDODB类
 */
class PDODB{
    /**
     * 定义相关属性
     */

    private $host = '';   //主机地址
    private $port = '';     //端口号
    private $user = '';     //用户名
    private $pass = '';     //密码
    private $dbname = ''; //数据库名
    private $charset = '';//字符集
    public $dsn;      //数据源名称
    private $pdo;    //用于存放PDO的一个对象
    // 静态私有属性用于保存单例对象
    private static $instance;

    /**
     * [__construct 构造方法]
     * @param [array] $config [配置数组]
     */
    private function __construct($dbconfig) {
        // 初始化属性
        $this->initParams($dbconfig);
        // 初始化dsn
        $this->initDSN();
        // 实例化PDO对象
        $this->initPDO();
        // 初始化PDO对象的属性
        $this->initAttribute();
    }

    /**
     * [getInstance 获取PDO单例对象的公开方法]
     * @param  [array] $config [description]
     * @return [PDOobject] self::$instance [pdo对象]
     */
    public static function getInstance($dbconfig) {
        if (!self::$instance instanceof self) {
            self::$instance = new self($dbconfig);
        }
        return self::$instance;
    }

    /**
     * [initParams 初始化属性]
     * @param  [array] $config [配置数组]
     */
    private function initParams($dbconfig) {
        $this->host = $dbconfig['host'];
        $this->port = $dbconfig['port'];
        $this->user = $dbconfig['user'];
        $this->pass = $dbconfig['pass'];
        $this->dbname = $dbconfig['dbname'];
        $this->charset = $dbconfig['charset'];
    }

    /**
     * [initDSN 初始化dsn]
     */
    private function initDSN() {
        $this->dsn = "mysql:host=$this->host;port=$this->port;dbname=$this->dbname;charset=$this->charset";
    }

    /**
     * [initPDO 实例化PDO对象]
     * @return [boolean] [false|none]
     */
    private function initPDO() {
        // 在实例化PDO对象的时候自动的走异常模式（也是唯一走异常模式的地方）
        try{
            print($this->dsn);
            $this->pdo = new PDO($this->dsn, $this->user, $this->pass);
        }catch(PDOException $e) {
            $this->my_error($e);
        }
    }

    /**
     * [initAttribute 初始化PDO对象属性]
     * @return [boolean] [false|none]
     */
    private function initAttribute() {
        // 修改错误模式为异常模式
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
    }

    /**
     * [my_error 输出异常信息]
     * @param  [PDOException] $e [异常对象]
     * @return [boolean]    [false|none]
     */
    private function my_error($e) {
        echo "执行sql语句失败!<br/>";
        echo "错误的代码是:",$e->getCode(),"<br/>";
        echo "错误的信息是:",$e->getMessage(),"<br/>";
        echo "错误的脚本是:",$e->getFile(),"<br/>";
        echo "错误的行号是:",$e->getLine(),'<br/>';
        return false;
    }

    /**
     * [queryNoParam 无参数查询返回数组]
     * @param  [string] $sql [sql语句]
     * @param  [boolean] $one [是否返回一条内容] 默认为否
     * @return [array] $result [数组]
     */
    public function queryNoParam($sql, $one = false) {
        //过滤不符合条件格式的数据
        if (empty($sql) || !is_string($sql))
            return false;
        //拼接sql组成redis 的 key
        $temsql = "queryNoParam".$sql;
        $key = md5($temsql);
        // 获取redis单例
        $redis = RedisConnect::getRedisInstance();
        //redis缓存判断
        if( $redis->exists($key)){
            $result = unserialize($redis->get($key));
        }else{
            try{
                //预处理
                $stmt = $this->pdo->prepare($sql);
                //执行绑定参数并取出结果
                $stmt->execute();
                //判断是否返回一条结果
                if ($one){
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                }else{
                    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
                }
                //存储redis
                $redis->set($key, serialize($result));
                $redis->expire($key, CACHETIME);
                // 关闭游标，释放结果集
                $stmt->closeCursor();
            }catch(PDOException $e) {
                $this->my_error($e);
            }
        }
        return $result;
    }

    /**
     * [queryObjectNoParam 无参数查询返回对象]
     * @param  [string] $sql [sql语句]
     * @param  [boolean] $one [是否返回一条内容] 默认为否
     * @return [object] $result [对象]
     */
    public function queryObjectNoParam($sql, $one = false) {
        //过滤不符合条件格式的数据
        if (empty($sql) || !is_string($sql))
            return false;
        //拼接sql组成redis 的 key
        $temsql = "queryObjectNoParam".$sql;
        $key = md5($temsql);
        // 获取redis单例
        $redis = RedisConnect::getRedisInstance();
        //redis缓存判断
        if( $redis->exists($key)){
            $result = unserialize($redis->get($key));
        }else{
            try{
                //预处理
                $stmt = $this->pdo->prepare($sql);
                //执行绑定参数并取出结果
                $stmt->execute();
                //判断是否返回一条结果
                if ($one){
                    $result = $stmt->fetch(PDO::FETCH_OBJ);
                }else{
                    $result = $stmt->fetchAll(PDO::FETCH_OBJ);
                }
                //存储redis
                $redis->set($key, serialize($result));
                $redis->expire($key, CACHETIME);
                // 关闭游标，释放结果集
                $stmt->closeCursor();
            }catch(PDOException $e) {
                $this->my_error($e);
            }
        }
        return $result;
    }

    /**
     * [queryArrayParam 传递数组查询返回数组]
     * @param  [string] $sql [sql语句]
     * @param   $[data] array(':username'=>'licyun', ':id'=>'1')
     * @param  [boolean] $one [是否返回一条内容] 默认为否
     * @return [array] $result [数组]
     */
    public function queryArrayParam($sql, $data, $one = false) {
        //过滤不符合条件格式的数据
        if (!is_array($data) || empty($sql) || !is_string($sql))
            return false;
        //拼接sql组成redis 的 key
        $temsql = "queryArrayParam".$sql;
        foreach ($data as $key => $value)
        {
            $temsql = $temsql.$key.$value;
        }
        $key = md5($temsql);
        // 获取redis单例
        $redis = RedisConnect::getRedisInstance();
        //redis缓存判断
        if( $redis->exists($key)){
            $result = unserialize($redis->get($key));
        }else{
            try{
                //预处理
                $stmt = $this->pdo->prepare($sql);
                //遍历绑定参数
                foreach ($data as $key => $value)
                {
                    $stmt->bindParam($key, $value);   //绑定参数，$stmt是预处理对象
                }
                //执行绑定参数并取出结果
                $stmt->execute();
                //判断是否返回一条结果
                if ($one){
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                }else{
                    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
                }
                //存储redis
                $redis->set($key, serialize($result));
                $redis->expire($key, CACHETIME);
                // 关闭游标，释放结果集
                $stmt->closeCursor();
            }catch(PDOException $e) {
                $this->my_error($e);
            }
        }
        return $result;
    }

    /**
     * [queryObjectArrayParam 传递数组查询对象]
     * @param  [string] $sql [sql语句]
     * @param   $[data] array(':username'=>'licyun', ':id'=>'1')
     * @param  [boolean] $one [是否返回一条内容] 默认为否
     * @return [Object] $result [对象]
     */
    public function queryObjectArrayParam($sql, $data, $one = false) {
        //过滤不符合条件格式的数据
        if (!is_array($data) || empty($sql) || !is_string($sql))
            return false;
        //拼接sql组成redis 的 key
        $temsql = "queryObjectArrayParam".$sql;
        foreach ($data as $key => $value)
        {
            $temsql = $temsql.$key.$value;
        }
        $key = md5($temsql);
        // 获取redis单例
        $redis = RedisConnect::getRedisInstance();
        //redis缓存判断
        if( $redis->exists($key)){
            $result = unserialize($redis->get($key));
        }else{
            try{
                //预处理
                $stmt = $this->pdo->prepare($sql);
                //遍历绑定参数
                foreach ($data as $key => $value)
                {
                    $stmt->bindParam($key, $value);   //绑定参数，$stmt是预处理对象
                }
                //执行绑定参数并取出结果
                $stmt->execute();
                //判断是否返回一条结果
                if ($one){
                    $result = $stmt->fetch(PDO::FETCH_OBJ);
                }else{
                    $result = $stmt->fetchAll(PDO::FETCH_OBJ);
                }
                //存储redis
                $redis->set($key, serialize($result));
                $redis->expire($key, CACHETIME);
                // 关闭游标，释放结果集
                // $stmt->closeCursor();
            }catch(PDOException $e) {
                $this->my_error($e);
            }
        }
        return $result;
    }

    /**
     * [updateArrayParam 传递数组执行更新操作]
     * @param  [string] $sql [sql语句]
     * @param   $[data] array(':username'=>'licyun', ':id'=>'1')
     * @return [int] $result [影响结果条数]
     */
    public function updateArrayParam($sql, $data) {
        //过滤不符合条件格式的数据
        if (!is_array($data) || empty($sql) || !is_string($sql))
            return false;
        $result = -1;
        try{
            //预处理
            $stmt = $this->pdo->prepare($sql);
            //遍历绑定参数
            foreach ($data as $key => $value)
            {
                $stmt->bindParam($key, $value);   //绑定参数，$stmt是预处理对象
            }
            //执行绑定参数并取出结果
            $result = $stmt->execute();
        }catch(PDOException $e) {
            $this->my_error($e);
        }
        return $result;
    }

    /**
     * [__clone 私有化克隆方法，保护单例模式]
     */
    private function __clone() {}


    /**
     * [__set 为一个不可访问的属性赋值的时候自动触发]
     * @param [string] $name  [属性名]
     * @param [mixed] $value [属性值]
     */
    public function __set($name,$value) {
        $allow_set = array('host','port','user','pass','dbname','charset');
        if(in_array($name,$allow_set)) {
            //当前属性可以被赋值
            $this->$name = $value;
        }
    }


    /**
     * [__get *获得一个不可访问的属性的值的时候自动触发]
     * @param  [string] $name [属性名]
     * @return [string] $name的value [该属性名的值]
     */
    public function __get($name) {
        $allow_get = array('host','port','user','pass','dbname','charset');
        if (in_array($name,$allow_get)) {
            return $this->$name;
        }
    }


    /**
     * [__call 访问一个不可访问的对象方法的时候触发]
     * @param  [string] $name     [属性名]
     * @param  [array] $argument [参数列表]
     */
    public function __call($name, $argument) {
        echo "对不起,您访问的".$name."()方法不存在!<br/>";
    }

    /**
     * [__callstatic 访问一个不可访问的类方法(静态方法)的时候触发]
     * @param  [string] $name     [属性名]
     * @param  [array] $argument [参数列表]
     */
    public static function __callStatic($name, $argument) {
        echo "对不起,您访问的".$name."()静态方法不存在!<br/>";
    }
}