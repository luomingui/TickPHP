<?php

define('TICKY_VERSION', '1.1');
define('TICKY_START_TIME', microtime(true));
define('TICKY_START_MEM', memory_get_usage());
define('DS', DIRECTORY_SEPARATOR);
define('EXT', '.php');
// 系统常量定义
defined('TICKY_PATH') or define('TICKY_PATH', __DIR__ . DS);
define('LIB_PATH', TICKY_PATH . 'library' . DS);
define('CORE_PATH', LIB_PATH . 'ticky' . DS);
defined('APP_PATH') or define('APP_PATH', dirname($_SERVER['SCRIPT_FILENAME']) . DS);
defined('ROOT_PATH') or define('ROOT_PATH', dirname(realpath(APP_PATH)) . DS);

defined('EXTEND_PATH') or define('EXTEND_PATH', ROOT_PATH . 'extend' . DS);
defined('VENDOR_PATH') or define('VENDOR_PATH', ROOT_PATH . 'vendor' . DS);

defined('RUNTIME_PATH') or define('RUNTIME_PATH', ROOT_PATH . 'runtime' . DS);
defined('LOG_PATH') or define('LOG_PATH', RUNTIME_PATH . 'log' . DS);
defined('CACHE_PATH') or define('CACHE_PATH', RUNTIME_PATH . 'cache' . DS);
defined('TEMP_PATH') or define('TEMP_PATH', RUNTIME_PATH . 'temp' . DS);

defined('CONF_PATH') or define('CONF_PATH', APP_PATH); // 配置文件目录
defined('CONF_EXT') or define('CONF_EXT', EXT); // 配置文件后缀
defined('ENV_PREFIX') or define('ENV_PREFIX', 'PHP_'); // 环境变量的配置前缀
//定义当前请求的系统常量
define('NOW_TIME', $_SERVER['REQUEST_TIME']);
// 环境常量
define('IS_CGI', (0 === strpos(PHP_SAPI, 'cgi') || false !== strpos(PHP_SAPI, 'fcgi')) ? 1 : 0 );
define('IS_CLI', PHP_SAPI == 'cli' ? true : false);
define('IS_WIN', strpos(PHP_OS, 'WIN') !== false);

define('REQUEST_METHOD', $_SERVER['REQUEST_METHOD']);
define('IS_GET', REQUEST_METHOD == 'GET' ? true : false);
define('IS_POST', REQUEST_METHOD == 'POST' ? true : false);
define('IS_PUT', REQUEST_METHOD == 'PUT' ? true : false);
define('IS_DELETE', REQUEST_METHOD == 'DELETE' ? true : false);

if (version_compare(PHP_VERSION, '5.4.0', '<')) {
    ini_set('magic_quotes_runtime', 0);
    define('MAGIC_QUOTES_GPC', get_magic_quotes_gpc() ? true : false);
} else {
    define('MAGIC_QUOTES_GPC', false);
}
define('ICONV_ENABLE', function_exists('iconv'));
define('MB_ENABLE', function_exists('mb_convert_encoding'));
define('EXT_OBGZIP', function_exists('ob_gzhandler'));

if (!IS_CLI) {
    if (!defined('_PHP_FILE_')) {
        if (IS_CGI) {
            $_temp = explode('.php', $_SERVER['PHP_SELF']);
            define('_PHP_FILE_', rtrim(str_replace($_SERVER['HTTP_HOST'], '', $_temp[0] . '.php'), '/'));
        } else {
            define('_PHP_FILE_', rtrim($_SERVER['SCRIPT_NAME'], '/'));
        }
    }
    if (!defined('__ROOT__')) {
        $_root = rtrim(dirname(_PHP_FILE_), '/');
        define('__ROOT__', (($_root == '/' || $_root == '\\') ? '' : $_root));
    }
}


// 载入Loader类
require CORE_PATH . 'Loader.php';

// 注册自动加载
\ticky\Loader::register();

// 注册错误和异常处理机制
\ticky\Error::register();

if (!file_exists(TICKY_PATH . 'Convention' . EXT)) {
    exit(TICKY_PATH . 'Convention' . EXT);
}
// 加载惯例配置文件
\ticky\Config::set(include TICKY_PATH . 'convention' . EXT);

// 加载核心Ticky类
\ticky\App::run();
