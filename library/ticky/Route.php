<?php

namespace ticky;

class Route extends Object {

    private static $bind = [];

    /**
     * 解析URL
     */
    public static function parseUrl($url = '') {

        $sonmenu = Config::get('son_menu');
        $module = Config::get('default_module');
        $controller = Config::get('default_controller');
        $action = Config::get('default_action');
        $param = array();
        $url = $url == '' ? $_SERVER['REQUEST_URI'] : $url;
        $position = strpos($url, '?');   // 清除?之后的内容
        $url = $position === false ? $url : substr($url, 0, $position);
        $url = trim($url, '/');  // 删除前后的“/”
        if ($url) {
            $urlArray = explode('/', $url);
            $urlArray = array_filter($urlArray);
            $ucount = count($urlArray);
            if (empty($sonmenu)) {
                // 获取模块名
                $module = $urlArray[0] ? $urlArray[0] : $module;
                // 获取控制器名
                $controller = isset($urlArray[1]) ? ucfirst($urlArray[1]) : $controller;
                // 获取动作名
                $action = isset($urlArray[2]) ? $urlArray[2] : $action;
                // 获取URL参数
                if (isset($urlArray[2])) {
                    array_splice($urlArray, 0, 3); //移除 模块，控制器，动作
                } else if (isset($urlArray[1])) {
                    array_splice($urlArray, 0, 2); //移除 模块，控制器
                } else {
                    array_shift($urlArray);
                }
                $param = $urlArray ? $urlArray : array();
            } else {
                //子目录
                $sonmenu = $urlArray[0] ? $urlArray[0] : $module;
                // 获取模块名
                $module = $urlArray[1] ? $urlArray[1] : $module;
                // 获取控制器名
                $controller = isset($urlArray[2]) ? ucfirst($urlArray[2]) : $controller;
                // 获取动作名
                $action = isset($urlArray[3]) ? $urlArray[3] : $action;
                // 获取URL参数
                if (isset($urlArray[2])) {
                    array_splice($urlArray, 0, 3); //移除 模块，控制器，动作
                } else if (isset($urlArray[1])) {
                    array_splice($urlArray, 0, 2); //移除 模块，控制器
                } else {
                    array_shift($urlArray);
                }
                $param = $urlArray ? $urlArray : array();
            }
        }
        if (strtolower($module) == "index.php") {
            $module = "home";
        }
        // 当前页面地址
        Config::set('__SELF__', $_SERVER['REQUEST_URI']);
        // 站点公共目录
        if (!empty($sonmenu) && $url) {
            Config::set('__PUBLIC__', __ROOT__ . '/public');
        } else {
            Config::set('__PUBLIC__', '');
        }

        define('BIND_MODULE', strtolower($module));
        define('BIND_CONTROLLER', strtolower($controller));
        define('BIND_ACTION', strtolower($action));

        // 封装路由
        $route = [
            'route_sonmenu' => $sonmenu,
            'route_module' => $module,
            'route_controller' => $controller,
            'route_action' => $action,
            'route_param' => $param,
        ];

        Config::set('route', $route);
        $langset = trim(Cookie::get('lang')) == '' ? Config::get('default_lang') : Cookie::get('lang');

        // 加载模块语言包
        Lang::load([
            APP_PATH . BIND_MODULE . DS . 'lang' . DS . strtolower($langset) . EXT,
        ]);
        self::bind($route);
        return ['type' => 'module', 'module' => $route];
    }

    public static function invokeClass() {

        $fix = self::parseUrl();
        $route = $fix['module'];

        $controller = App::$namespace . DS . $route['route_module'] . DS . 'controllers' . DS . ucwords($route['route_controller']) . 'Controller';
        $dispatch = new $controller($route['route_module'], $route['route_controller'], $route['route_action']);
        if ($dispatch) {
            call_user_func_array(array($dispatch, $route['route_action']), $route['route_param']);
        }
    }

    /**
     * 设置路由绑定
     * @access public
     * @param mixed     $bind 绑定信息
     * @param string    $type 绑定类型 默认为module 支持 namespace class controller
     * @return mixed
     */
    public static function bind($bind, $type = 'module') {
        self::$bind = ['type' => $type, $type => $bind];
    }

    /**
     * 读取路由绑定
     * @access public
     * @param string    $type 绑定类型
     * @return mixed
     */
    public static function getBind($type) {
        return isset(self::$bind[$type]) ? self::$bind[$type] : null;
    }

}
