<?php

/**
 * +----------------------------------------------------------------------
 * | LCLPHP [ This is a freeware ]
 * +----------------------------------------------------------------------
 * | Copyright (c) 2015 All rights reserved.
 * +----------------------------------------------------------------------
 * | Author: 罗敏贵 <e-mail:minguiluo@163.com> <QQ:271391233>
 * | SVN: $Id: TickyPHP.php 29529 2018/2/6 15:45 luomingui $
 * +----------------------------------------------------------------------
 * | 文件功能：TickyPHP
 * +----------------------------------------------------------------------
 */

namespace ticky;

class App extends Object {

    /**
     * @var bool 是否初始化过
     */
    protected static $init = false;
    public static $namespace = 'application';

    /**
     * @var bool 应用调试模式
     */
    public static $debug = true;

    /**
     * @var array 额外加载文件
     */
    protected static $file = [];

    /**
     * @var array 请求调度分发
     */
    protected static $dispatch;

    public static function run(Request $request = null) {
        try {
            $request = is_null($request) ? Request::instance() : $request;
            self::initEnv(); // 检测开发环境
            self::checkXSS(); // 检测xss

            $config = self::initCommon();

            // 模块/控制器绑定
            if (defined('BIND_MODULE')) {
                BIND_MODULE && Route::bind(BIND_MODULE);
            } elseif ($config['auto_bind_module']) {
                // 入口自动绑定
                $name = pathinfo($request->baseFile(), PATHINFO_FILENAME);
                if ($name && 'index' != $name && is_dir(APP_PATH . $name)) {
                    Route::bind($name);
                }
            }

            self::init(BIND_MODULE);

            // 默认语言
            $langset = trim(Cookie::get('lang')) == '' ? $config['default_lang'] : Cookie::get('lang');
            Lang::range($config['default_lang']);
            // 开启多语言机制 检测当前语言
            //$config['lang_switch_on'] && Lang::detect();
            // 加载系统语言包
            Lang::load([
                TICKY_PATH . 'lang' . DS . strtolower($langset) . EXT,
                APP_PATH . 'lang' . DS . strtolower($langset) . EXT,
                APP_PATH . 'common' . DS . 'lang' . DS . strtolower($langset) . EXT,
            ]);

            // 监听 app_dispatch
            Hook::listen('app_dispatch', self::$dispatch);
            // 获取应用调度信息
            $dispatch = self::$dispatch;

            // 未设置调度信息则进行 URL 路由检测
            if (empty($dispatch)) {
                $dispatch = Route::parseUrl();
            }

            // 监听 app_begin
            Hook::listen('app_begin', $dispatch);

            Route::invokeClass();
        } catch (HttpResponseException $exception) {
            $data = $exception->getResponse();
        }

        // 输出数据到客户端
        if ($data instanceof Response) {
            $response = $data;
        } elseif (!is_null($data)) {
            // 默认自动识别响应输出类型
            $type = $request->isAjax() ?
                    Config::get('default_ajax_return') :
                    Config::get('default_return_type');

            $response = new Response($data);
        } else {
            $response = new Response();
        }
        // 监听 app_end
        Hook::listen('app_end', $response);

        return $response;
    }

    /**
     * 初始化应用，并返回配置信息
     * @access public
     * @return array
     */
    public static function initCommon() {
        if (empty(self::$init)) {
            Loader::addNamespace(self::$namespace, APP_PATH);
            $config = self::init();
            self::$debug = Env::get('app_debug', Config::get('app_debug'));
            if (!self::$debug) {
                error_reporting(0);
                ini_set('display_errors', 'Off');
            } elseif (!IS_CLI) {
                if (ob_get_level() > 0) {
                    $output = ob_get_clean();
                }
                ob_start();
                if (!empty($output)) {
                    echo $output;
                }
            }

            if (!empty($config['root_namespace'])) {
                Loader::addNamespace($config['root_namespace']);
            }

            // 加载额外文件
            if (!empty($config['extra_file_list'])) {
                foreach ($config['extra_file_list'] as $file) {
                    $file = strpos($file, '.') ? $file : APP_PATH . $file . EXT;
                    if (is_file($file) && !isset(self::$file[$file])) {
                        include $file;
                        self::$file[$file] = true;
                    }
                }
            }
            $sonMenu = Config::get('son_menu');
            if (empty($sonMenu)) {
                Config::set('app_host', 'http://' . $_SERVER['HTTP_HOST'] . '/');
            } else {
                Config::set('app_host', 'http://' . $_SERVER['HTTP_HOST'] . '/' . Config::get('son_menu') . '/');
            }
            // 设置系统时区
            date_default_timezone_set($config['default_timezone']);
            // 监听 app_init
            Hook::listen('app_init');
            self::$init = true;
        }
        return Config::get();
    }

    /**
     * 初始化应用或模块
     * @access public
     * @param string $module 模块名
     * @return array
     */
    private static function init($module = '') {
        // 定位模块目录
        $module = $module ? $module . DS : 'common';
        // 加载初始化文件
        if (is_file(APP_PATH . $module . DS . 'init' . EXT)) {
            include APP_PATH . $module . DS . 'init' . EXT;
        } elseif (is_file(RUNTIME_PATH . $module . DS . 'init' . EXT)) {
            include RUNTIME_PATH . $module . DS . 'init' . EXT;
        } else {
            // 加载模块配置
            $config = Config::load(APP_PATH . $module . DS . 'config' . EXT);
            // 读取数据库配置文件
            $filename = APP_PATH . $module . DS . 'database' . EXT;
            Config::load($filename, 'database');
            // 读取扩展配置文件
            if (is_dir(CONF_PATH . $module . 'extra')) {
                $dir = CONF_PATH . $module . 'extra';
                $files = scandir($dir);
                foreach ($files as $file) {
                    if ('.' . pathinfo($file, PATHINFO_EXTENSION) === CONF_EXT) {
                        $filename = $dir . DS . $file;
                        Config::load($filename, pathinfo($file, PATHINFO_FILENAME));
                    }
                }
            }
            // 加载应用状态配置
            if ($config['app_status']) {
                Config::load(APP_PATH . $module . DS . $config['app_status'] . EXT);
            }

            // 加载公共文件
            $path = APP_PATH . $module;
            if (is_file($path . DS . 'common' . EXT)) {
                include $path . DS . 'common' . EXT;
            }

            // 加载当前模块语言包
            if ($module) {
                $langset = trim(Cookie::get('lang')) == '' ? $config['default_lang'] : Cookie::get('lang');
                Lang::load($path . DS . 'lang' . DS . $langset . EXT);
            }
        }
        return Config::get();
    }

    // 配置环境信息
    private static function initEnv() {
        //防注入
        if (MAGIC_QUOTES_GPC) {
            $_GET = dstripslashes($_GET);
            $_POST = dstripslashes($_POST);
            $_COOKIE = dstripslashes($_COOKIE);
            $_SESSION = dstripslashes($_SESSION);
        }
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST)) {
            $_GET = array_merge($_GET, $_POST);
        }
        $_GET['page'] = isset($_GET['page']) ? max(0, intval($_GET['page'])) : '0';

        define('IS_AJAX', ((isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') || !empty($_POST[Config::get('var_ajax_submit')]) || !empty($_GET[Config::get('var_ajax_submit')])) ? true : false);
    }

    private static function checkXSS() {
        static $check = array('"', '>', '<', '\'', '(', ')', 'CONTENT-TRANSFER-ENCODING');
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            $temp = $_SERVER['REQUEST_URI'];
        } elseif (empty($_GET['formhash'])) {
            $temp = $_SERVER['REQUEST_URI'] . file_get_contents('php://input');
        } else {
            $temp = '';
        }
        if (!empty($temp)) {
            $temp = strtoupper(urldecode(urldecode($temp)));
            foreach ($check as $str) {
                if (strpos($temp, $str) !== false) {
                    return false;
                }
            }
        }
        return true;
    }

}
