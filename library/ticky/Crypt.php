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
 * | SVN: $Id: Crypt.php 29529 2018-3-1 luomingui $
 * +----------------------------------------------------------------------
 * | Description：加密解密类
 * +----------------------------------------------------------------------
 */

namespace ticky;

class Crypt {

    private static $handler = '';

    public static function init($type = '') {
        $type = $type ?: 'ticky';
        $class = strpos($type, '\\') ? $type : 'ticky\\crypt\\driver\\' . ucwords(strtolower($type));
        self::$handler = $class;
    }

    /**
     * 加密字符串
     * @param string $data 字符串
     * @param string $key 加密key
     * @param integer $expire 有效期（秒） 0 为永久有效
     * @return string
     */
    public static function encrypt($data, $key = 'ticky', $expire = 0) {
        if (empty(self::$handler)) {
            self::init();
        }
        $class = self::$handler;
        return $class::encrypt($data, $key, $expire);
    }

    /**
     * 解密字符串
     * @param string $data 字符串
     * @param string $key 加密key
     * @return string
     */
    public static function decrypt($data, $key = 'ticky') {
        if (empty(self::$handler)) {
            self::init();
        }
        $class = self::$handler;
        return $class::decrypt($data, $key);
    }

}
