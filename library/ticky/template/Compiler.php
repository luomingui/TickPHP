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
 * | SVN: $Id: Compiler.php 29529 2018-3-6 luomingui $
 * +----------------------------------------------------------------------
 * | Description：Compiler 翻译模板文件
 * +----------------------------------------------------------------------
 */

namespace ticky\template;

use ticky\Object;

class Compiler extends Object {

    private $tpl_vars = [];

    /**
     * [compile 编译模板文件]
     * @param  [type] $source   [模板文件]
     * @param  [type] $destFile [编译后文件]
     * @param  [type] $values   [键值对]
     * @param  [type] $config   [配置]
     * @return [type]           [description]
     */
    public function compile($source, $destFile, $values, $config = []) {


        file_put_contents($destFile, $template);
    }

    /**
     * 模板赋值操作
     * @param mixed $tpl_var 如果是字符串,就作为数组索引,如果是数组，就循环赋值
     * @param mixed $tpl_value 当$tpl_var为string时的值,默认为 null
     */
    public function assign($tpl_var, $tpl_value = null) {
        if (is_array($tpl_var) && count($tpl_var) > 0) {
            foreach ($tpl_var as $k => $v) {
                $this->tpl_vars[$k] = $v;
            }
        } elseif ($tpl_var) {
            $this->tpl_vars[$tpl_var] = $tpl_value;
        }
    }

    /**
     * 生成编译文件
     * @param string $tplFile 模板路径
     * @param string $comFile 编译路径
     * @return string
     */
    private function fetch($tplFile, $comFile) {

        //判断编译文件是否需要重新生成(编译文件是否存在或者模板文件修改时间大于编译文件的修改时间)
        if (!file_exists($comFile) || filemtime($tplFile) > filemtime($comFile)) {
            //编译,此处也可以使用ob_start()进行静态化
            $content = $this->tplReplace(file_get_contents($tplFile));
            file_put_contents($comFile, $content);
        }
    }

    /**
     * 编译文件
     * @param string $content 待编译的内容
     * @return string
     */
    private function tplReplace($content) {
        //转义左右定界符 正则表达式字符
        $left = preg_quote($this->left_delimiter, '/');
        $right = preg_quote($this->right_delimiter, '/');

        //简单模拟编译 变量
        $pattern = array(
            //例如{$test}
            '/' . $left . '\$([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)' . $right . '/i'
        );

        $replace = array(
            '<?php echo $this->tpl_vars[\'${1}\']; ?>'
        );

        //正则处理
        return preg_replace($pattern, $replace, $content);
    }

}
