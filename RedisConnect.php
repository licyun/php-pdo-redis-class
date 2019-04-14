<?php
/**
 * Created by IntelliJ IDEA.
 * User: licyun
 * Date: 2019/4/14 0014
 * Time: 16:31
 */

/**
 * 封装REDIS类
 */
class RedisConnect{

    const REDISHOSTNAME = "127.0.0.1";
    const REDISPORT = 6379;
    const REDISTIMEOUT = 0;
    private static $_instance = null; //静态实例

    /**
     * 私有化构造函数,防止类外实例化
     *
     */
    private function __construct()
    {
        self::$_instance = new Redis();
        self::$_instance->connect(self::REDISHOSTNAME, self::REDISPORT, self::REDISTIMEOUT);
    }

    /**
     * 获取类单例
     *
     * @return object
     */
    public static  function getRedisInstance()
    {
        if(! isset(self::$_instance)){
            new self;
        }
        return self::$_instance;
    }

    /*
     * 禁止clone
     */
    private function __clone(){}
}

?>