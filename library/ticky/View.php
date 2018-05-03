<?php

/**
 * +----------------------------------------------------------------------
 * | LCLPHP [ This is a freeware ]
 * +----------------------------------------------------------------------
 * | Copyright (c) 2015 All rights reserved.
 * +----------------------------------------------------------------------
 * | Author: 罗敏贵 <e-mail:minguiluo@163.com> <QQ:271391233>
 * | SVN: $Id: View.php 29529 2018/2/1 12:24 luomingui $
 * +----------------------------------------------------------------------
 * | 文件功能：视图基类
 * +----------------------------------------------------------------------
 */

namespace ticky;

class View {

    /**
     * 模板输出变量
     * @var tVar
     * @access protected
     */
    protected $data = [];
    protected $module;
    protected $controller;
    protected $action;
    protected $response;
    protected $request;

    function __construct($module, $controller, $action) {
        $this->module = strtolower($module);
        $this->controller = strtolower($controller);
        $this->action = strtolower($action);

        $this->response = new Response();
        $this->request = Request::instance();
    }

    /**
     * 模板变量赋值
     * @access public
     * @param mixed $name
     * @param mixed $value
     */
    public function assign($name, $value) {

        if (is_array($name)) {
            $this->data = array_merge($this->data, $name);
        } else {
            $this->data[$name] = $value;
        }
        return $this;
    }

    /**
     * 检测模板变量是否设置
     * @access public
     * @param string $name 模板变量名
     * @return bool
     */
    public function __isset($name) {
        return isset($this->data[$name]);
    }

    /**
     * 取得模板变量的值
     * @access public
     * @param string $name
     * @return mixed
     */
    public function get($name = '') {
        if ('' === $name) {
            return $this->data;
        }
        return isset($this->data[$name]) ? $this->data[$name] : false;
    }

    /**
     * [render 渲染模板文件]
     * @param  [type] $file           [待编译的文件]
     * @param  array  $templateConfig [编译配置]
     * @return [type]                 [description]
     */
    public function render($file = '', $templateConfig = []) {
        if (empty($file) || is_null($file) || strlen($file) <= 0) {
            $file = $this->controller . '/' . $this->action;
        }
        extract($this->data);

        $tp = new Template();
        $tp->assign($this->data);
        $tp->display($file);
    }

}
