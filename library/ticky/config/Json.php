<?php

/**
 * +----------------------------------------------------------------------
 * | TickyPHP [ This is a freeware ]
 * +----------------------------------------------------------------------
 * | Copyright (c) 2015 All rights reserved.
 * +----------------------------------------------------------------------
 * | Author: luomingui <e-mail:minguiluo@163.com> <QQ:271391233>
 * | SVN: $Id: Cache.php 29529 2018-2-27 luomingui $
 * +----------------------------------------------------------------------
 * | Description：
 * +----------------------------------------------------------------------
 */

namespace ticky\config;

class Json {

    public function parse($config) {
        if (is_file($config)) {
            $config = file_get_contents($config);
        }
        $result = json_decode($config, true);
        return $result;
    }

}
