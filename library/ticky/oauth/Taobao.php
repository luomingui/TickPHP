<?php

/**
 * +----------------------------------------------------------------------
 * | TickyPHP [ This is a freeware ]
 * +----------------------------------------------------------------------
 * | Copyright (c) 2015 http://tickyphp.cn All rights reserved.
 * +----------------------------------------------------------------------
 * | Licensed (https://github.com/Aoiujz/ThinkSDK)
 * +----------------------------------------------------------------------
 * | Author: luomingui <e-mail:minguiluo@163.com> <QQ:271391233>
 * +----------------------------------------------------------------------
 * | SVN: $Id: Taobao.php 29529 2018-3-13 luomingui $
 * +----------------------------------------------------------------------
 * | Description：Taobao
 * +----------------------------------------------------------------------
 */

namespace ticky\oauth;

class Taobao extends \Oauth {

    /**
     * 获取requestCode的api接口
     * @var string
     */
    protected $GetRequestCodeURL = 'https://oauth.taobao.com/authorize';

    /**
     * 获取access_token的api接口
     * @var string
     */
    protected $GetAccessTokenURL = 'https://oauth.taobao.com/token';

    /**
     * API根路径
     * @var string
     */
    protected $ApiBase = 'https://eco.taobao.com/router/rest';

    /**
     * 组装接口调用参数 并调用接口
     * @param string $api 微博API
     * @param string $param 调用API的额外参数
     * @param string $method HTTP请求方法 默认为GET
     * @return json
     */
    public function call($api, $param = '', $method = 'GET', $multi = false) {
        /* 淘宝网调用公共参数 */
        $params = array(
            'method' => $api,
            'access_token' => $this->Token['access_token'],
            'format' => 'json',
            'v' => '2.0',
        );
        $data = $this->http($this->url(''), $this->param($params, $param), $method);
        return json_decode($data, true);
    }

    /**
     * 解析access_token方法请求后的返回值
     * @param string $result 获取access_token的方法的返回值
     */
    protected function parseToken($result, $extend) {
        $data = json_decode($result, true);
        if ($data['access_token'] && $data['expires_in'] && $data['taobao_user_id']) {
            $data['openid'] = $data['taobao_user_id'];
            unset($data['taobao_user_id']);
            return $data;
        } else
            throw new Exception("获取淘宝网ACCESS_TOKEN出错：{$data['error']}");
    }

    /**
     * 获取当前授权应用的openid
     * @return string
     */
    public function openid() {
        $data = $this->Token;
        if (isset($data['openid']))
            return $data['openid'];
        else
            throw new Exception('没有获取到淘宝网用户ID！');
    }

}
