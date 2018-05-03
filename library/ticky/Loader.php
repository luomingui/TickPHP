<?php

/**
 * +----------------------------------------------------------------------
 * | LCLPHP [ This is a freeware ]
 * +----------------------------------------------------------------------
 * | Copyright (c) 2015 All rights reserved.
 * +----------------------------------------------------------------------
 * | Author: 罗敏贵 <e-mail:minguiluo@163.com> <QQ:271391233>
 * | SVN: $Id: Loader.php 29529 2018/2/2 13:18 luomingui $
 * +----------------------------------------------------------------------
 * | 文件功能：自动加载机制
 * +----------------------------------------------------------------------
 */

namespace ticky;

class Loader {

    // 类映射
    private static $map = [];

    /**
     * @var array PSR-4 命名空间前缀长度映射
     */
    private static $prefixLengthsPsr4 = [];

    /**
     * @var array PSR-4 的加载目录
     */
    private static $prefixDirsPsr4 = [];

    /**
     * @var array PSR-4 加载失败的回退目录
     */
    private static $fallbackDirsPsr4 = [];

    /**
     * @var array PSR-0 命名空间前缀映射
     */
    private static $prefixesPsr0 = [];

    /**
     * @var array PSR-0 加载失败的回退目录
     */
    private static $fallbackDirsPsr0 = [];

    /**
     * @var array 命名空间别名
     */
    protected static $namespaceAlias = [];

    /**
     * 注册自动加载机制
     * @access public
     * @param  callable $autoload 自动加载处理方法
     * @return void
     */
    static public function register($autoload = null) {
        // 注册系统自动加载
        if (function_exists('spl_autoload_register')) {
            spl_autoload_register($autoload ?: 'ticky\\Loader::autoload', true, true);
        } else {

            function __autoload($class) {
                return Loader::autoload($class);
            }

        }
        // 注册命名空间定义
        self::addNamespace([
            'ticky' => LIB_PATH . 'ticky' . DS,
            'behavior' => LIB_PATH . 'behavior' . DS,
            'traits' => LIB_PATH . 'traits' . DS,
        ]);

        // 加载类库映射文件
        if (is_file(RUNTIME_PATH . 'classmap' . EXT)) {
            self::addClassMap(__include_file(RUNTIME_PATH . 'classmap' . EXT));
        }
    }

    /**
     * 自动加载
     * @access public
     * @param  string $class 类名
     * @return bool
     */
    static public function autoload($class) {

        // 检测命名空间别名
        if (!empty(self::$namespaceAlias)) {
            $namespace = dirname($class);
            if (isset(self::$namespaceAlias[$namespace])) {
                $original = self::$namespaceAlias[$namespace] . '\\' . basename($class);
                if (class_exists($original)) {
                    return class_alias($original, $class, false);
                }
            }
        }
        if ($file = self::findFile($class)) {
            // 非 Win 环境不严格区分大小写
            //if (!IS_WIN || pathinfo($file, PATHINFO_FILENAME) == pathinfo(realpath($file), PATHINFO_FILENAME))   {
            __include_file($file);
            return true;
            // }
        }
        return false;
    }

    /**
     * 查找文件
     * @access private
     * @param  string $class 类名
     * @return bool|string
     */
    static private function findFile($class) {
        // 类库映射
        if (!empty(self::$map[$class])) {
            return self::$map[$class];
        }

        /*
          // 查找 ticky
          if (strpos($class, '\\') !== false) {
          $name = strstr($class, '\\', true);
          if (in_array($name, array('ticky')) || is_dir(CORE_PATH . $name)) {
          $path = CORE_PATH;
          $filename = $path . str_replace($name, '', $class) . '.php';
          if (is_file($filename)) {
          return $filename;
          }
          } else {
          $filename = APP_PATH . str_replace('\\', '/', $class) . '.php';
          if (is_file($filename)) {
          return $filename;
          }
          }
          }
         */
        // 查找 PSR-4
        $logicalPathPsr4 = strtr($class, '\\', DS) . EXT;
        $first = $class[0];
        // PSR-4 命名空间前缀长度映射
        if (isset(self::$prefixLengthsPsr4[$first])) {
            foreach (self::$prefixLengthsPsr4[$first] as $prefix => $length) {
                if (0 === strpos($class, $prefix)) {
                    foreach (self::$prefixDirsPsr4[$prefix] as $dir) {
                        $psr4 = $dir . DS . $logicalPathPsr4; // substr($logicalPathPsr4, $length);
                        if (is_file($file = $psr4)) {
                            return $file;
                        }
                        if (is_file($file = $dir . DS . substr($logicalPathPsr4, $length))) {
                            return $file;
                        }
                    }
                }
            }
        }
        // 查找 PSR-4 fallback dirs
        foreach (self::$fallbackDirsPsr4 as $dir) {
            $fallback = $dir . DS . $logicalPathPsr4;
            if (is_file($file = $fallback)) {
                return $file;
            }
        }
        // 查找 PSR-0
        if (false !== $pos = strrpos($class, '\\')) {
            // namespace class name
            $logicalPathPsr0 = substr($logicalPathPsr4, 0, $pos + 1)
                    . strtr(substr($logicalPathPsr4, $pos + 1), '_', DS);
        } else {
            // PEAR-like class name
            $logicalPathPsr0 = strtr($class, '_', DS) . EXT;
        }
        if (isset(self::$prefixesPsr0[$first])) {
            foreach (self::$prefixesPsr0[$first] as $prefix => $dirs) {
                if (0 === strpos($class, $prefix)) {
                    foreach ($dirs as $dir) {
                        if (is_file($file = $dir . DS . $logicalPathPsr0)) {
                            return $file;
                        }
                    }
                }
            }
        }
        // 查找 PSR-0 fallback dirs
        foreach (self::$fallbackDirsPsr0 as $dir) {
            if (is_file($file = $dir . DS . $logicalPathPsr0)) {
                return $file;
            }
        }


        // 找不到则设置映射为 false 并返回
        return self::$map[$class] = false;
    }

    /**
     * 注册命名空间
     * @access public
     * @param  string|array $namespace 命名空间
     * @param  string       $path      路径
     * @return void
     */
    public static function addNamespace($namespace, $path = '') {
        if (is_array($namespace)) {
            foreach ($namespace as $prefix => $paths) {
                self::addPsr4($prefix . '\\', rtrim($paths, DS), true);
            }
        } else {
            self::addPsr4($namespace . '\\', rtrim($path, DS), true);
        }
    }

    /**
     * 添加 PSR-4 空间
     * @access private
     * @param  array|string $prefix  空间前缀
     * @param  string       $paths   路径
     * @param  bool         $prepend 预先设置的优先级更高
     * @return void
     */
    private static function addPsr4($prefix, $paths, $prepend = false) {
        if (!$prefix) {
            // Register directories for the root namespace.
            self::$fallbackDirsPsr4 = $prepend ?
                    array_merge((array) $paths, self::$fallbackDirsPsr4) :
                    array_merge(self::$fallbackDirsPsr4, (array) $paths);
        } elseif (!isset(self::$prefixDirsPsr4[$prefix])) {
            // Register directories for a new namespace.
            $length = strlen($prefix);
            if ('\\' !== $prefix[$length - 1]) {
                throw new \InvalidArgumentException(
                "A non-empty PSR-4 prefix must end with a namespace separator."
                );
            }

            self::$prefixLengthsPsr4[$prefix[0]][$prefix] = $length;
            self::$prefixDirsPsr4[$prefix] = (array) $paths;
        } else {
            self::$prefixDirsPsr4[$prefix] = $prepend ?
                    // Prepend directories for an already registered namespace.
                    array_merge((array) $paths, self::$prefixDirsPsr4[$prefix]) :
                    // Append directories for an already registered namespace.
                    array_merge(self::$prefixDirsPsr4[$prefix], (array) $paths);
        }
    }

    /**
     * 注册 classmap
     * @access public
     * @param  string|array $class 类名
     * @param  string       $map   映射
     * @return void
     */
    static public function addClassMap($class, $map = '') {
        if (is_array($class)) {
            self::$map = array_merge(self::$map, $class);
        } else {
            self::$map[$class] = $map;
        }
    }

    // 获取classmap
    static public function getClassMap($class = '') {
        if ('' === $class) {
            return self::$map;
        } elseif (isset(self::$map[$class])) {
            return self::$map[$class];
        } else {
            return null;
        }
    }

    /**
     * 加载制定文件
     * @param string $path 需要查找的目录 如：E:\TickyPHP\application
     * @param string $filename 需要查找的文件名 如：config
     * @param string $type 加载方式 如：c|l
     */
    static public function loadfile($path, $filename, $type = '') {
        $current_dir = opendir($path); //opendir()返回一个目录句柄,失败返回false
        while (($file = readdir($current_dir)) !== false) {//readdir()返回打开目录句柄中的一个条目
            $sub_dir = $path . DIRECTORY_SEPARATOR . $file; //构建子目录路径
            if ($file == '.' || $file == '..') {
                continue;
            } else if (is_dir($sub_dir)) {  //如果是目录,进行递归
                self::loadfile($sub_dir, $filename, $type);
            } else {//如果是文件,直接输出
                if (strpos($file, $filename) !== false) {
                    switch (strtolower($type)) {
                        case 'c':
                            C(include $sub_dir);
                            break;
                        case 'l':
                            break;
                        default :
                            require_once($sub_dir);
                    }
                }
            }
        }
    }

    /**
     * 实例化一个没有模型文件的Model
     * @param string $name Model名称 支持指定基础模型 例如 MongoModel:User
     * @param string $tablePrefix 表前缀
     * @param mixed $connection 数据库连接信息
     * @return Think\Model
     */
    static public function model($name = '', $tablePrefix = '', $connection = '') {
        static $_model = array();
        if (strpos($name, ':')) {
            list($class, $name) = explode(':', $name);
        } else {
            $class = 'ticky\\Model';
        }
        $guid = (is_array($connection) ? implode('', $connection) : $connection) . $tablePrefix . $name . '_' . $class;
        if (!isset($_model[$guid])) {
            $_model[$guid] = new $class($name, $tablePrefix, $connection);
        }
        return $_model[$guid];
    }

    /**
     * 字符串命名风格转换
     * type 0 将 Java 风格转换为 C 的风格 1 将 C 风格转换为 Java 的风格
     * @access public
     * @param  string  $name    字符串
     * @param  integer $type    转换类型
     * @param  bool    $ucfirst 首字母是否大写（驼峰规则）
     * @return string
     */
    static public function parseName($name, $type = 0, $ucfirst = true) {
        if ($type) {
            $name = preg_replace_callback('/_([a-zA-Z])/', function ($match) {
                return strtoupper($match[1]);
            }, $name);

            return $ucfirst ? ucfirst($name) : lcfirst($name);
        }

        return strtolower(trim(preg_replace("/[A-Z]/", "_\\0", $name), "_"));
    }

}

// 作用范围隔离

/**
 * include
 * @param  string $file 文件路径
 * @return mixed
 */
function __include_file($file) {
    return include $file;
}

/**
 * require
 * @param  string $file 文件路径
 * @return mixed
 */
function __require_file($file) {
    return require $file;
}
