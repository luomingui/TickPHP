<?php

/**
 * +----------------------------------------------------------------------
 * | LCLPHP [ This is a freeware ]
 * +----------------------------------------------------------------------
 * | Copyright (c) 2015 All rights reserved.
 * +----------------------------------------------------------------------
 * | Author: 罗敏贵 <e-mail:minguiluo@163.com> <QQ:271391233>
 * | SVN: $Id: config.php 29529 2018/2/6 13:14 luomingui $
 * +----------------------------------------------------------------------
 * | 文件功能：TickyPHP
 * +----------------------------------------------------------------------
 */

namespace ticky;

class Controller {

    protected $module;
    protected $controller;
    protected $action;
    protected $view;
    protected $response;
    protected $request;

    // 构造函数，初始化属性，并实例化对应模型
    public function __construct($module, $controller, $action, Request $request = null) {
        $this->module = $module;
        $this->controller = $controller;
        $this->action = $action;

        $this->response = new Response();
        $this->request = Request::instance();

        Hook::listen('action_begin');
        $this->view = new View($module, $controller, $action);

        //控制器初始化
        if (method_exists($this, '_initialize')) {
            $this->_initialize();
        }
    }

    /**
     * 模板变量赋值
     * @access protected
     * @param mixed $name 要显示的模板变量
     * @param mixed $value 变量的值
     * @return Action
     */
    protected function assign($name, $value) {
        $this->view->assign($name, $value);
    }

    public function __set($name, $value) {
        $this->assign($name, $value);
    }

    /**
     * 取得模板显示变量的值
     * @access protected
     * @param string $name 模板显示变量
     * @return mixed
     */
    public function get($name = '') {
        return $this->view->get($name);
    }

    public function __get($name) {
        return $this->get($name);
    }

    /**
     * 检测模板变量的值
     * @access public
     * @param string $name 名称
     * @return boolean
     */
    public function __isset($name) {
        return $this->get($name);
    }

    // 渲染视图
    public function render($action = '') {
        $this->view->render($action);
    }

    /**
     * 操作错误跳转的快捷方法
     * @access protected
     * @param string $message 错误信息
     * @param string $jumpUrl 页面跳转地址
     * @param mixed $ajax 是否为Ajax方式 当数字时指定跳转时间
     * @return void
     */
    protected function error($message = '', $jumpUrl = '', $ajax = false) {
        $this->dispatchJump($message, 0, $jumpUrl, $ajax);
    }

    /**
     * 操作成功跳转的快捷方法
     * @access protected
     * @param string $message 提示信息
     * @param string $jumpUrl 页面跳转地址
     * @param mixed $ajax 是否为Ajax方式 当数字时指定跳转时间
     * @return void
     */
    protected function success($message = '', $jumpUrl = '', $ajax = false) {
        $this->dispatchJump($message, 1, $jumpUrl, $ajax);
    }

    /**
     * Ajax方式返回数据到客户端
     * @access protected
     * @param mixed $data 要返回的数据
     * @param String $type AJAX返回数据格式
     * @param int $json_option 传递给json_encode的option参数
     * @return void
     */
    protected function ajaxReturn($data, $type = '', $json_option = 0) {
        if (empty($type)) {
            $type = config('default_ajax_return');
        }
        switch (strtoupper($type)) {
            case 'JSON' :
                // 返回JSON数据格式到客户端 包含状态信息
                header('Content-Type:application/json; charset=utf-8');
                exit(json_encode($data, $json_option));
            case 'XML' :
                // 返回xml格式数据
                header('Content-Type:text/xml; charset=utf-8');
                exit(xml_encode($data));
            case 'JSONP':
                // 返回JSON数据格式到客户端 包含状态信息
                header('Content-Type:application/json; charset=utf-8');
                $handler = isset($_GET[config('var_jsonp_handler')]) ? $_GET[config('var_jsonp_handler')] : config('DEFAULT_JSONP_HANDLER');
                exit($handler . '(' . json_encode($data, $json_option) . ');');
            case 'EVAL' :
                // 返回可执行的js脚本
                header('Content-Type:text/html; charset=utf-8');
                exit($data);
            default :
                // 用于扩展其他返回格式数据
                Hook::listen('ajax_return', $data);
        }
    }

    /**
     * 默认跳转操作 支持错误导向和正确跳转
     * 调用模板显示 默认为public目录下面的success页面
     * 提示页面为可配置 支持模板标签
     * @param string $message 提示信息
     * @param Boolean $status 状态
     * @param string $jumpUrl 页面跳转地址
     * @param mixed $ajax 是否为Ajax方式 当数字时指定跳转时间
     * @access private
     * @return void
     */
    private function dispatchJump($message, $status = 1, $jumpUrl = '', $ajax = false) {
        if (true === $ajax) {// AJAX提交
            $data = is_array($ajax) ? $ajax : array();
            $data['info'] = $message;
            $data['status'] = $status;
            $data['url'] = $jumpUrl;
            $this->ajaxReturn($data);
        }
        if (is_int($ajax)) {
            $this->assign('waitSecond', $ajax);
        }
        if (!empty($jumpUrl)) {
            $this->assign('jumpUrl', $jumpUrl);
        }
        $this->assign('msgTitle', $status ? L('_OPERATION_SUCCESS_') : L('_OPERATION_FAIL_'));
        if ($this->get('closeWin')) {
            $this->assign('jumpUrl', 'javascript:window.close();');
        }
        $this->assign('status', $status);
        if ($status) {
            $this->assign('message', $message);
            if (!isset($this->waitSecond)) {
                $this->assign('waitSecond', '1');
            }
            if (!isset($this->jumpUrl)) {
                $this->assign("jumpUrl", $_SERVER["HTTP_REFERER"]);
            }
            $this->render('SUCCESS');
        } else {
            $this->assign('error', $message);
            if (!isset($this->waitSecond)) {
                $this->assign('waitSecond', '3');
            }
            if (!isset($this->jumpUrl)) {
                $this->assign('jumpUrl', "javascript:history.back(-1);");
            }
            $this->render('ERROR');
            exit;
        }
    }

    public function changeTableVal() {
        $table = $_GET['table'];
        $id_name = $_GET['id_name'];
        $id_value = $_GET['id_value'];
        $field = $_GET['field'];
        $value = $_GET['value'];
        //changeTableVal/?table=tky_newsclass&id_name=classid&id_value=2&field=closed&value=1
        $data = array();
        $data[$field] = $value;
        $rules = M()->table($table)->where(array($id_name => $id_value))->update($data);
    }

    /**
     * 析构方法
     * @access public
     */
    public function __destruct() {
        Hook::listen('action_end');
    }

}
