<?php

/**
 * +----------------------------------------------------------------------
 * | TickyPHP [ This is a freeware ]
 * +----------------------------------------------------------------------
 * | Copyright (c) 2015 All rights reserved.
 * +----------------------------------------------------------------------
 * | Author: luomingui <e-mail:minguiluo@163.com> <QQ:271391233>
 * | SVN: $Id: Db.php 29529 2018-2-9 luomingui $
 * +----------------------------------------------------------------------
 * | Description：数据库中间层实现类
 * +----------------------------------------------------------------------
 */

namespace ticky;

class Db extends Object {

    static private $instance = [];     //  数据库连接实例

    /**
     * 数据库初始化，并取得数据库类实例
     * @access public
     * @param  mixed       $config 连接配置
     * @param  bool|string $name   连接标识 true 强制重新连接
     * @return Connection
     * @throws Exception
     */

    static public function getInstance($config = [], $name = false) {
        if (false === $name) {
            $name = md5(serialize($config));
        }
        if (true === $name || !isset(self::$instance[$name])) {
            // 解析连接参数 支持数组和字符串
            $options = self::parseConfig($config);
            if (empty($options['type'])) {
                throw new \InvalidArgumentException('Undefined db type');
            }
            $class = false !== strpos($options['type'], '\\') ? $options['type'] : '\\ticky\\db\\driver\\' . ucwords($options['type']);
            if (true === $name) {
                $name = md5(serialize($config));
            }
            self::$instance[$name] = new $class($options);
        }
        return self::$instance[$name];
    }

    /**
     * 数据库连接参数解析
     * @static
     * @access private
     * @param mixed $config
     * @return array
     */
    static private function parseConfig($config) {
        if (empty($config)) {
            $config = Config::get('database');
        } elseif (is_string($config) && false === strpos($config, '/')) {
            $config = Config::get($config); // 支持读取配置参数
        }
        return is_string($config) ? self::parseDsn($config) : $config;
    }

    /**
     * DSN解析
     * 格式： mysql://username:passwd@localhost:3306/DbName?param1=val1&param2=val2#utf8
     * @static
     * @access private
     * @param string $dsnStr
     * @return array
     */
    static private function parseDsn($dsnStr) {
        if (empty($dsnStr)) {
            return false;
        }
        $info = parse_url($dsnStr);
        if (!$info) {
            return false;
        }
        $dsn = array(
            'type' => $info['scheme'],
            'username' => isset($info['user']) ? $info['user'] : '',
            'password' => isset($info['pass']) ? $info['pass'] : '',
            'hostname' => isset($info['host']) ? $info['host'] : '',
            'hostport' => isset($info['port']) ? $info['port'] : '',
            'database' => isset($info['path']) ? substr($info['path'], 1) : '',
            'charset' => isset($info['fragment']) ? $info['fragment'] : 'utf8',
        );

        if (isset($info['query'])) {
            parse_str($info['query'], $dsn['params']);
        } else {
            $dsn['params'] = array();
        }
        return $dsn;
    }

    // 调用驱动类的方法
    static public function __callStatic($method, $params) {
        return call_user_func_array(array(self::$_instance, $method), $params);
    }

}
