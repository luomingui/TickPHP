<?php

/**
 * +----------------------------------------------------------------------
 * | TickyPHP [ This is a freeware ]
 * +----------------------------------------------------------------------
 * | Copyright (c) 2015 http://tickyphp.cn All rights reserved.
 * +----------------------------------------------------------------------
 * | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
 * +----------------------------------------------------------------------
 * | Author: luomingui <e-mail:minguiluo@163.com> <QQ:271391233>
 * +----------------------------------------------------------------------
 * | SVN: $Id: helper.php 29529 2018-2-28 luomingui $
 * +----------------------------------------------------------------------
 * | Description：helper
 * +----------------------------------------------------------------------
 */
use ticky\Config;
use ticky\Cookie;
use ticky\Error;
use ticky\Db;
use ticky\Cache;
use ticky\Loader;
use ticky\Session;
use ticky\Crypt;
use ticky\Lang;

if (!function_exists('config')) {

    /**
     * 获取和设置配置参数
     * @param string|array  $name 参数名
     * @param mixed         $value 参数值
     * @param string        $range 作用域
     * @return mixed
     */
    function config($name = '', $value = null, $range = '') {
        if (is_null($value) && is_string($name)) {
            return 0 === strpos($name, '?') ? Config::has(substr($name, 1), $range) : Config::get($name, $range);
        } else {
            return Config::set($name, $value, $range);
        }
    }

}
if (!function_exists('db')) {

    /**
     * 实例化数据库类
     * @param string        $name 操作的数据表名称（不含前缀）
     * @param array|string  $config 数据库配置参数
     * @param bool          $force 是否强制重新连接
     * @return \think\db\Query
     */
    function db($name = '', $config = [], $force = false) {
        return Db::getInstance($config, $force)->name($name);
    }

}
if (!function_exists('cookie')) {

    /**
     * Cookie管理
     * @param string|array  $name cookie名称，如果为数组表示进行cookie设置
     * @param mixed         $value cookie值
     * @param mixed         $option 参数
     * @return mixed
     */
    function cookie($name, $value = '', $option = null) {
        if (is_array($name)) {
// 初始化
            Cookie::init($name);
        } elseif (is_null($name)) {
// 清除
            Cookie::clear($value);
        } elseif ('' === $value) {
// 获取
            return 0 === strpos($name, '?') ? Cookie::has(substr($name, 1), $option) : Cookie::get($name, $option);
        } elseif (is_null($value)) {
// 删除
            return Cookie::delete($name);
        } else {
// 设置
            return Cookie::set($name, $value, $option);
        }
    }

}
if (!function_exists('cache')) {

    /**
     * 缓存管理
     * @param mixed     $name 缓存名称，如果为数组表示进行缓存设置
     * @param mixed     $value 缓存值
     * @param mixed     $options 缓存参数
     * @param string    $tag 缓存标签
     * @return mixed
     */
    function cache($name, $value = '', $options = null, $tag = null) {
        if (is_array($options)) {
            // 缓存操作的同时初始化
            $cache = Cache::connect($options);
        } elseif (is_array($name)) {
            // 缓存初始化
            return Cache::connect($name);
        } else {
            $cache = Cache::init();
        }

        if (is_null($name)) {
            return $cache->clear($value);
        } elseif ('' === $value) {
            // 获取缓存
            return 0 === strpos($name, '?') ? $cache->has(substr($name, 1)) : $cache->get($name);
        } elseif (is_null($value)) {
            // 删除缓存
            return $cache->rm($name);
        } elseif (0 === strpos($name, '?') && '' !== $value) {
            $expire = is_numeric($options) ? $options : null;
            return $cache->remember(substr($name, 1), $value, $expire);
        } else {
            // 缓存数据
            if (is_array($options)) {
                $expire = isset($options['expire']) ? $options['expire'] : null; //修复查询缓存无法设置过期时间
            } else {
                $expire = is_numeric($options) ? $options : null; //默认快捷缓存设置过期时间
            }
            if (is_null($tag)) {
                return $cache->set($name, $value, $expire);
            } else {
                return $cache->tag($tag)->set($name, $value, $expire);
            }
        }
    }

}
if (!function_exists('trace')) {

    /**
     * 添加和获取页面Trace记录
     * @param string $value 变量
     * @param string $label 标签
     * @param string $level 日志级别
     * @param boolean $record 是否记录日志
     * @return void|array
     */
    function trace($value = '[ticky]', $label = '', $level = 'DEBUG', $record = false) {
        return Error::trace($value, $label, $level, $record);
    }

}
if (!function_exists('model')) {

    /**
     * 实例化一个没有模型文件的Model
     * @param string $name Model名称 支持指定基础模型 例如 MongoModel:User
     * @param string $tablePrefix 表前缀
     * @param mixed $connection 数据库连接信息
     * @return Think\Model
     */
    function M($name = '', $tablePrefix = '', $connection = '') {
        return Loader::model($name, $tablePrefix, $connection);
    }

}
if (!function_exists('session')) {

    /**
     * Session管理
     * @param string|array  $name session名称，如果为数组表示进行session设置
     * @param mixed         $value session值
     * @param string        $prefix 前缀
     * @return mixed
     */
    function session($name, $value = '', $prefix = null) {
        if (is_array($name)) {
// 初始化
            Session::init($name);
        } elseif (is_null($name)) {
// 清除
            Session::clear('' === $value ? null : $value);
        } elseif ('' === $value) {
// 判断或获取
            return 0 === strpos($name, '?') ? Session::has(substr($name, 1), $prefix) : Session::get($name, $prefix);
        } elseif (is_null($value)) {
// 删除
            return Session::delete($name, $prefix);
        } else {
// 设置
            return Session::set($name, $value, $prefix);
        }
    }

}
if (!function_exists('lang')) {

    /**
     * 获取和设置语言定义(不区分大小写)
     * @param string|array $name 语言变量
     * @param mixed $value 语言值或者变量
     * @return mixed
     */
    function L($name = null, $value = null) {
        if ($value != null) {
            Lang::set($name, $value);
        }
        return Lang::get($name);
    }

}

function authcode($data, $operation = 'decrypt', $key = '', $expire = 0) {
    if (is_null($data)) {
        return null;
    }
    if ($operation == 'decrypt') {//解密 decrypt
        return Crypt::decrypt($data, $key);
    } else {//加密 encrypt
        return Crypt::encrypt($data, $key, $expire);
    }
}

/**
 * 获取输入参数 支持过滤和默认值
 * 使用方法:
 * <code>
 * G('id',0); 获取id参数 自动判断get或者post
 * G('post.name','','htmlspecialchars'); 获取$_POST['name']
 * G('get.'); 获取$_GET
 * </code>
 * @param string $name 变量的名称 支持指定类型
 * @param mixed $default 不存在的时候默认值
 * @param mixed $filter 参数过滤方法
 * @param mixed $datas 要获取的额外数据源
 * @return mixed
 */
function G($name, $default = '', $filter = null, $datas = null) {
    static $_PUT = null;
    if (strpos($name, '/')) { // 指定修饰符
        list($name, $type) = explode('/', $name, 2);
    } elseif (false) { // 默认强制转换为字符串
        $type = 's';
    }
    if (strpos($name, '.')) { // 指定参数来源
        list($method, $name) = explode('.', $name, 2);
    } else { // 默认为自动判断
        $method = 'param';
    }
    switch (strtolower($method)) {
        case 'get' :
            $input = & $_GET;
            break;
        case 'post' :
            $input = & $_POST;
            break;
        case 'put' :
            $input = globals_put();
            break;
        case 'session' :
            $input = & $_SESSION;
            break;
        case 'cookie' :
            $input = & $_COOKIE;
            break;
        case 'server' :
            $input = & $_SERVER;
            break;
        case 'globals' :
            $input = & $GLOBALS;
            break;
        case 'request' :
            $input = & $_REQUEST;
            break;
        case 'path' :
            $input = array();
            if (!empty($_SERVER['PATH_INFO'])) {
                $depr = config('url_pathinfo_depr');
                $input = explode($depr, trim($_SERVER['PATH_INFO'], $depr));
            }
            break;
        case 'param' :
            switch ($_SERVER['REQUEST_METHOD']) {
                case 'POST':
                    $input = $_POST;
                    break;
                case 'PUT':
                    $input = globals_put();
                    break;
                default:
                    $input = $_GET;
            }
            break;
    }

    if ('' == $name) { // 获取全部变量
        $data = $input;
        $filters = isset($filter) ? $filter : "dhtmlspecialchars";
        if ($filters) {
            if (is_string($filters)) {
                $filters = explode(',', $filters);
            }
            foreach ($filters as $filter) {
                $data = array_map_recursive($filter, $data); // 参数过滤
            }
        }
    } elseif (isset($input[$name])) { // 取值操作
        $data = $input[$name];
        $filters = isset($filter) ? $filter : "dhtmlspecialchars";
        if ($filters) {
            if (is_string($filters)) {
                if (0 === strpos($filters, '/')) {
                    if (1 !== preg_match($filters, (string) $data)) {
                        return isset($default) ? $default : null;  // 支持正则验证
                    }
                } else {
                    $filters = explode(',', $filters);
                }
            } elseif (is_int($filters)) {
                $filters = array($filters);
            }
            if (is_array($filters)) {
                foreach ($filters as $filter) {
                    if (function_exists($filter)) {
                        $data = is_array($data) ? array_map_recursive($filter, $data) : $filter($data); // 参数过滤
                    } else {
                        $data = filter_var($data, is_int($filter) ? $filter : filter_id($filter));
                        if (false === $data) {
                            return isset($default) ? $default : null;
                        }
                    }
                }
            }
        }
        if (!empty($type)) {
            switch (strtolower($type)) {
                case 'a': // 数组
                    $data = (array) $data;
                    break;
                case 'd': // 数字
                    $data = (int) $data;
                    break;
                case 'f': // 浮点
                    $data = (float) $data;
                    break;
                case 'b': // 布尔
                    $data = (boolean) $data;
                    break;
                case 's':   // 字符串
                default:
                    $data = (string) $data;
            }
        }
    } else { // 变量默认值
        $data = isset($default) ? $default : null;
    }
    is_array($data) && array_walk_recursive($data, 'ticky_filter');
    return $data;
}

function array_map_recursive($filter, $data) {
    $result = array();
    foreach ($data as $key => $val) {
        $result[$key] = is_array($val) ? array_map_recursive($filter, $val) : call_user_func($filter, $val);
    }
    return $result;
}

function globals_put() {
    if (is_null($_PUT)) {
        parse_str(file_get_contents('php://input'), $_PUT);
    }
    return $_PUT;
}

/**
 * URL重定向
 * @param string $url 重定向的URL地址
 * @param integer $time 重定向的等待时间（秒）
 * @param string $msg 重定向前的提示信息
 * @return void
 */
function redirect($url, $time = 0, $msg = '') {
    //多行URL地址支持
    $url = str_replace(array("\n", "\r"), '', $url);
    if (empty($msg)) {
        $msg = "系统将在{$time}秒之后自动跳转到{$url}！";
    }
    if (!headers_sent()) {
        // redirect
        if (0 === $time) {
            header('Location: ' . $url);
        } else {
            header("refresh:{$time};url={$url}");
            echo($msg);
        }
        exit();
    } else {
        $str = "<meta http-equiv='Refresh' content='{$time};URL={$url}'>";
        if ($time != 0) {
            $str .= $msg;
        }
        exit($str);
    }
}

/**
 * 格式化时间
 * @param $timestamp - 时间戳
 * @param $format - dt=日期时间 d=日期 t=时间 u=个性化 其他=自定义
 * @param $timeoffset - 时区
 * @return string
 */
function dgmdate($timestamp, $format = 'dt', $timeoffset = '9999', $uformat = '') {
    $dateconvert = config('dateconvert') ? config('dateconvert') : '1';
    $format == 'u' && !$dateconvert && $format = 'dt';
    static $dformat, $tformat, $dtformat, $offset;
    if ($dformat === null) {
        $dformat = config('dateformat') ? config('dateformat') : 'Y-m-d  H:i:s';
        $tformat = config('timeformat') ? config('timeformat') : '24';
        $dtformat = $dformat . ' ' . $tformat;
        $offset = config('timeoffset') ? config('timeoffset') : '8';
        $sysoffset = config('timeoffset') ? config('timeoffset') : '8';
        $offset = $offset == 9999 ? ($sysoffset ? $sysoffset : 0) : $offset;
    }
    $timeoffset = $timeoffset == 9999 ? $offset : $timeoffset;
    $timestamp += $timeoffset * 3600;
    $format = empty($format) || $format == 'dt' ? $dtformat : ($format == 'd' ? $dformat : ($format == 't' ? $tformat : $format));
    if ($format == 'u') {
        $todaytimestamp = NOW_TIME - (NOW_TIME + $timeoffset * 3600) % 86400 + $timeoffset * 3600;
        $s = gmdate(!$uformat ? $dtformat : $uformat, $timestamp);
        $time = NOW_TIME + $timeoffset * 3600 - $timestamp;
        if ($timestamp >= $todaytimestamp) {
            if ($time > 3600) {
                $return = intval($time / 3600) . ' ' . L('hour') . L('before');
            } elseif ($time > 1800) {
                $return = L('half') . L('hour') . L('before');
            } elseif ($time > 60) {
                $return = intval($time / 60) . ' ' . L('min') . L('before');
            } elseif ($time > 0) {
                $return = $time . ' ' . L('sec') . L('before');
            } elseif ($time == 0) {
                $return = L('now');
            } else {
                $return = $s;
            }
            if ($time >= 0 && !defined('IN_MOBILE')) {
                $return = '<span title="' . $s . '">' . $return . '</span>';
            }
        } elseif (($days = intval(($todaytimestamp - $timestamp) / 86400)) >= 0 && $days < 7) {
            if ($days == 0) {
                $return = L('yday') . ' ' . gmdate($tformat, $timestamp);
            } elseif ($days == 1) {
                $return = L('byday') . ' ' . gmdate($tformat, $timestamp);
            } else {
                $return = ($days + 1) . ' ' . L('day') . L('before');
            }
        } else {
            $return = ""; //$s;
        }
        return $return;
    } else {
        return gmdate($format, $timestamp);
    }
}

/**
 * 根据中文裁减字符串
 * @param $string - 字符串
 * @param $length - 长度
 * @param $dot - 缩略后缀
 * @return 返回带省略号被裁减好的字符串
 */
function cutstr($string, $length, $dot = '...') {
    if (strlen($string) <= $length) {
        return $string;
    }
    $pre = chr(1);
    $end = chr(1);
    $string = str_replace(array('&amp;', '&quot;', '&lt;', '&gt;'), array($pre . '&' . $end, $pre . '"' . $end, $pre . '<' . $end, $pre . '>' . $end), $string);
    $strcut = '';
    if (strtolower(CHARSET) == 'utf-8') {
        $n = $tn = $noc = 0;
        while ($n < strlen($string)) {
            $t = ord($string[$n]);
            if ($t == 9 || $t == 10 || (32 <= $t && $t <= 126)) {
                $tn = 1;
                $n++;
                $noc++;
            } elseif (194 <= $t && $t <= 223) {
                $tn = 2;
                $n += 2;
                $noc += 2;
            } elseif (224 <= $t && $t <= 239) {
                $tn = 3;
                $n += 3;
                $noc += 2;
            } elseif (240 <= $t && $t <= 247) {
                $tn = 4;
                $n += 4;
                $noc += 2;
            } elseif (248 <= $t && $t <= 251) {
                $tn = 5;
                $n += 5;
                $noc += 2;
            } elseif ($t == 252 || $t == 253) {
                $tn = 6;
                $n += 6;
                $noc += 2;
            } else {
                $n++;
            }

            if ($noc >= $length) {
                break;
            }
        }
        if ($noc > $length) {
            $n -= $tn;
        }

        $strcut = substr($string, 0, $n);
    } else {
        $_length = $length - 1;
        for ($i = 0; $i < $length; $i++) {
            if (ord($string[$i]) <= 127) {
                $strcut .= $string[$i];
            } else if ($i < $_length) {
                $strcut .= $string[$i] . $string[++$i];
            }
        }
    }

    $strcut = str_replace(array($pre . '&' . $end, $pre . '"' . $end, $pre . '<' . $end, $pre . '>' . $end), array('&amp;', '&quot;', '&lt;', '&gt;'), $strcut);

    $pos = strrpos($strcut, chr(1));
    if ($pos !== false) {
        $strcut = substr($strcut, 0, $pos);
    }
    return $strcut . $dot;
}

/**
 * 获取客户端IP地址
 * @param integer $type 返回类型 0 返回IP地址 1 返回IPV4地址数字
 * @param boolean $adv 是否进行高级模式获取（有可能被伪装）
 * @return mixed
 */
function get_client_ip($type = 0, $adv = false) {
    $type = $type ? 1 : 0;
    static $ip = NULL;
    if ($ip !== NULL) {
        return $ip[$type];
    }
    if ($adv) {
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $pos = array_search('unknown', $arr);
            if (false !== $pos) {
                unset($arr[$pos]);
            }
            $ip = trim($arr[0]);
        } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
    } elseif (isset($_SERVER['REMOTE_ADDR'])) {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    // IP地址合法验证
    $long = sprintf("%u", ip2long($ip));
    $ip = $long ? array($ip, $long) : array('0.0.0.0', 0);
    return $ip[$type];
}

/**
 * XML编码
 * @param mixed $data 数据
 * @param string $root 根节点名
 * @param string $item 数字索引的子节点名
 * @param string $attr 根节点属性
 * @param string $id   数字索引子节点key转换的属性名
 * @param string $encoding 数据编码
 * @return string
 */
function xml_encode($data, $root = 'think', $item = 'item', $attr = '', $id = 'id', $encoding = 'utf-8') {
    if (is_array($attr)) {
        $_attr = array();
        foreach ($attr as $key => $value) {
            $_attr[] = "{$key}=\"{$value}\"";
        }
        $attr = implode(' ', $_attr);
    }
    $attr = trim($attr);
    $attr = empty($attr) ? '' : " {$attr}";
    $xml = "<?
xml version = \"1.0\" encoding=\"{$encoding}\"?>";
    $xml .= "<{$root}{$attr}>";
    $xml .= data_to_xml($data, $item, $id);
    $xml .= "</{$root}>";
    return $xml;
}

/**
 * 数据XML编码
 * @param mixed  $data 数据
 * @param string $item 数字索引时的节点名称
 * @param string $id   数字索引key转换为的属性名
 * @return string
 */
function data_to_xml($data, $item = 'item', $id = 'id') {
    $xml = $attr = '';
    foreach ($data as $key => $val) {
        if (is_numeric($key)) {
            $id && $attr = " {$id}=\"{$key}\"";
            $key = $item;
        }
        $xml .= "<{$key}{$attr}>";
        $xml .= (is_array($val) || is_object($val)) ? data_to_xml($val, $item, $id) : $val;
        $xml .= "</{$key}>";
    }
    return $xml;
}

function dhtmlspecialchars($string, $flags = null) {
    if (is_array($string)) {
        foreach ($string as $key => $val) {
            $string[$key] = dhtmlspecialchars($val, $flags);
        }
    } else {
        if ($flags === null) {
            $string = str_replace(array('&', '"', '<', '>'), array('&amp;', '&quot;', '&lt;', '&gt;'), $string);
            if (strpos($string, '&amp;#') !== false) {
                $string = preg_replace('/&amp;((#(\d{3,5}|x[a-fA-F0-9]{4}));)/', '&\\1', $string);
            }
        } else {
            if (PHP_VERSION < '5.4.0') {
                $string = htmlspecialchars($string, $flags);
            } else {
                if (strtolower(CHARSET) == 'utf-8') {
                    $charset = 'UTF-8';
                } else {
                    $charset = 'ISO-8859-1';
                }
                $string = htmlspecialchars($string, $flags, $charset);
            }
        }
    }
    return $string;
}

function delhtml($str) {  //清除html标签
    $st = -1; //开始
    $et = -1; //结束
    $stmp = array();
    $stmp[] = " ";
    $len = strlen($str);
    for ($i = 0; $i < $len; $i++) {
        $ss = substr($str, $i, 1);
        if (ord($ss) == 60) { //ord("<")==60
            $st = $i;
        }
        if (ord($ss) == 62) { //ord(">")==62
            $et = $i;
            if ($st != -1) {
                $stmp[] = substr($str, $st, $et - $st + 1);
            }
        }
    }
    $str = str_replace($stmp, "", $str);
    return $str;
}

/**
 * HTML标签自动补全代码
 */
function closetags($html) {
// 不需要补全的标签
    $arr_single_tags = array('meta', 'img', 'br', 'link', 'area');
// 匹配开始标签
    preg_match_all('#<([a-z]+)(?: .*)?(?<![/|/ ])>#iU', $html, $result);
    $openedtags = $result[1];
// 匹配关闭标签
    preg_match_all('#</([a-z]+)>#iU', $html, $result);
    $closedtags = $result[1];
// 计算关闭开启标签数量，如果相同就返回html数据
    $len_opened = count($openedtags);
    if (count($closedtags) == $len_opened) {
        return $html;
    }
// 把排序数组，将最后一个开启的标签放在最前面
    $openedtags = array_reverse($openedtags);
// 遍历开启标签数组
    for ($i = 0; $i < $len_opened; $i++) {
// 如果需要补全的标签
        if (!in_array($openedtags[$i], $arr_single_tags)) {
// 如果这个标签不在关闭的标签中
            if (!in_array($openedtags[$i], $closedtags)) {
// 直接补全闭合标签
                $html .= '</' . $openedtags[$i] . '>';
            } else {
                unset($closedtags[array_search($openedtags[$i], $closedtags)]);
            }
        }
    }
    return $html;
}

function strexists($string, $find) {
    return !(strpos($string, $find) === FALSE);
}

function debug($var = null, $vardump = false) {
    echo '<pre>';
    $vardump = empty($var) ? true : $vardump;
    if ($vardump) {
        var_dump($var);
    } else {
        print_r($var);
    }
    echo '</pre>';
    exit();
}
