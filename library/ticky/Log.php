<?php

/**
 * +----------------------------------------------------------------------
 * | LCLPHP [ This is a freeware ]
 * +----------------------------------------------------------------------
 * | Copyright (c) 2015 All rights reserved.
 * +----------------------------------------------------------------------
 * | Author: 罗敏贵 <e-mail:minguiluo@163.com> <QQ:271391233>
 * | SVN: $Id: log.php 29529 2018/2/6 15:03 luomingui $
 * +----------------------------------------------------------------------
 * | 文件功能：TickyPHP
 * +----------------------------------------------------------------------
 */

namespace ticky;

class Log {

    // 日志级别 从上到下，由低到高

    const EMERG = 'EMERG';  // 严重错误: 导致系统崩溃无法使用
    const ALERT = 'ALERT';  // 警戒性错误: 必须被立即修改的错误
    const CRIT = 'CRIT';  // 临界值错误: 超过临界值的错误，例如一天24小时，而输入的是25小时这样
    const ERR = 'ERR';  // 一般错误: 一般性错误
    const WARN = 'WARN';  // 警告性错误: 需要发出警告的错误
    const NOTICE = 'NOTIC';  // 通知: 程序可以运行但是还不够完美的错误
    const INFO = 'INFO';  // 信息: 程序输出信息
    const DEBUG = 'DEBUG';  // 调试: 调试信息
    const SQL = 'SQL';  // SQL：SQL语句 注意只在调试模式开启时有效

    /**
     * @var array 日志信息
     */

    protected static $log = [];

    /**
     * @var array 配置参数
     */
    protected static $config = [];

    /**
     * @var array 日志类型
     */
    protected static $type = ['log', 'error', 'info', 'sql', 'notice', 'alert', 'debug'];

    /**
     * @var log\driver\File|log\driver\Test|log\driver\Socket 日志写入驱动
     */
    protected static $driver;

    /**
     * @var string 当前日志授权 key
     */
    protected static $key;

    /**
     * 日志初始化
     * @access public
     * @param  array $config 配置参数
     * @return void
     */
    public static function init($config = []) {

        $type = isset($config['type']) ? $config['type'] : 'File';
        $class = false !== strpos($type, '\\') ? $type : '\\ticky\\log\\' . ucwords($type);

        self::$config = $config;
        unset($config['type']);

        if (class_exists($class)) {
            self::$driver = new $class($config);
        } else {
            throw new \Exception('class not exists:' . $class, $class);
        }
    }

    /**
     * 获取日志信息
     * @access public
     * @param  string $type 信息类型
     * @return array|string
     */
    public static function getLog($type = '') {
        return $type ? self::$log[$type] : self::$log;
    }

    /**
     * 记录调试信息
     * @access public
     * @param  mixed  $msg  调试信息
     * @param  string $type 信息类型
     * @return void
     */
    public static function record($msg, $type = 'log') {
        self::$log[$type][] = $msg;
        // 命令行下面日志写入改进
        self::save();
    }

    /**
     * 清空日志信息
     * @access public
     * @return void
     */
    public static function clear() {
        self::$log = [];
    }

    /**
     * 设置当前日志记录的授权 key
     * @access public
     * @param  string $key 授权 key
     * @return void
     */
    public static function key($key) {
        self::$key = $key;
    }

    /**
     * 检查日志写入权限
     * @access public
     * @param  array $config 当前日志配置参数
     * @return bool
     */
    public static function check($config) {
        return !self::$key || empty($config['allow_key']) || in_array(self::$key, $config['allow_key']);
    }

    /**
     * 保存调试信息
     * @access public
     * @return bool
     */
    public static function save() {
        // 没有需要保存的记录则直接返回
        if (empty(self::$log)) {
            return true;
        }

        is_null(self::$driver) && self::init();

        // 检测日志写入权限
        if (!self::check(self::$config)) {
            return false;
        }

        if (empty(self::$config['level'])) {
            // 获取全部日志
            $log = self::$log;
            if (isset($log['debug'])) {
                unset($log['debug']);
            }
        } else {
            // 记录允许级别
            $log = [];
            foreach (self::$config['level'] as $level) {
                if (isset(self::$log[$level])) {
                    $log[$level] = self::$log[$level];
                }
            }
        }

        if ($result = self::$driver->save($log)) {
            self::$log = [];
        }

        return $result;
    }

    /**
     * 实时写入日志信息 并支持行为
     * @access public
     * @param  mixed  $msg   调试信息
     * @param  string $type  信息类型
     * @param  bool   $force 是否强制写入
     * @return bool
     */
    public static function write($msg, $type = 'log', $force = false) {
        $log = self::$log;

        // 如果不是强制写入，而且信息类型不在可记录的类别中则直接返回 false 不做记录
        if (true !== $force && !empty(self::$config['level']) && !in_array($type, self::$config['level'])) {
            return false;
        }

        // 封装日志信息
        $log[$type][] = $msg;

        is_null(self::$driver) && self::init();

        // 写入日志
        if ($result = self::$driver->save($log)) {
            self::$log = [];
        }

        return $result;
    }

    /**
     * 静态方法调用
     * @access public
     * @param  string $method 调用方法
     * @param  mixed  $args   参数
     * @return void
     */
    public static function __callStatic($method, $args) {
        if (in_array($method, self::$type)) {
            array_push($args, $method);

            call_user_func_array('Log::record', $args);
        }
    }

}
