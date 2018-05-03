<?php

/**
 * +----------------------------------------------------------------------
 * | LCLPHP [ This is a freeware ]
 * +----------------------------------------------------------------------
 * | Copyright (c) 2015 All rights reserved.
 * +----------------------------------------------------------------------
 * | Author: 罗敏贵 <e-mail:minguiluo@163.com> <QQ:271391233>
 * | SVN: $Id: File.php 29529 2018/2/6 15:00 luomingui $
 * +----------------------------------------------------------------------
 * | 文件功能：TickyPHP
 * +----------------------------------------------------------------------
 */

namespace ticky\log;

class File {

    protected $config = [
        'time_format' => ' c ',
        'single' => false,
        'file_size' => 2097152,
        'apart_level' => [],
    ];
    protected $writed = [];

    // 实例化并传入参数
    public function __construct($config = []) {
        if (is_array($config)) {
            $this->config = array_merge($this->config, $config);
        }
    }

    /**
     * 日志写入接口
     * @access public
     * @param array $log 日志信息
     * @return bool
     */
    public function save(array $log = []) {
        if ($this->config['single']) {
            $destination = LOG_PATH . 'single.log';
        } else {
            $cli = IS_CLI ? '_cli' : '';
            $destination = LOG_PATH . date('Ym') . DS . date('d') . $cli . '.log';
        }

        $path = dirname($destination);

        !is_dir($path) && mkdir($path, 0755, true);

        $info = '';
        foreach ($log as $type => $val) {
            $level = '';
            foreach ($val as $msg) {
                if (!is_string($msg)) {
                    $msg = var_export($msg, true);
                }
                $level .= '[ ' . $type . ' ] ' . $msg . "\r\n";
            }
            if (in_array($type, $this->config['apart_level'])) {
                // 独立记录的日志级别
                if ($this->config['single']) {
                    $filename = $path . DS . $type . '.log';
                } else {
                    $filename = $path . DS . date('d') . '_' . $type . $cli . '.log';
                }
                $this->write($level, $filename, true);
            } else {
                $info .= $level;
            }
        }
        if ($info) {
            return $this->write($info, $destination);
        }
        return true;
    }

    protected function write($message, $destination, $apart = false) {
        //检测日志文件大小，超过配置大小则备份日志文件重新生成
        if (is_file($destination) && floor($this->config['file_size']) <= filesize($destination)) {
            rename($destination, dirname($destination) . DS . time() . '-' . basename($destination));
            $this->writed[$destination] = false;
        }

        if (empty($this->writed[$destination]) && !IS_CLI) {
            if (!$apart) {
                if (isset($_SERVER['HTTP_HOST'])) {
                    $current_uri = $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
                } else {
                    $current_uri = "cmd:" . implode(' ', $_SERVER['argv']);
                }
                $message = '[ info ] ' . $current_uri . "\r\n" . $message;
            }
            $now = date("Y-m-d H:i", time()); //http://blog.csdn.net/namelessml/article/details/52387274
            $method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'CLI';
            $uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
            $message = "----------------------------------------------------------------\r\n[{$now}] " . $message;

            $this->writed[$destination] = true;
        }

        if (IS_CLI) {
            $now = date("Y-m-d H:i", time());
            $message = "[{$now}] " . $message;
        }
        return error_log($message, 3, $destination);
    }

}
