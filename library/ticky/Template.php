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
 * | SVN: $Id: Template.php 29529 2018-3-6 luomingui $
 * +----------------------------------------------------------------------
 * | Description：Template 我们的控制器就可以调用template中的assign方法进行赋值，show方法进行模板编译了。
 * +----------------------------------------------------------------------
 */

namespace ticky;

use ticky\Object;

class Template extends Object {

    private $tpl_vars = []; //键值对
    private $file;

    public function __construct() {

    }

    /**
     * [path 获得模板文件路径]
     * @return [type] [description]
     */
    private function path() {
        switch ($this->file) {
            case "SUCCESS":
                $file = Config::get('dispatch_success_tmpl');
                break;
            case "ERROR":
                $file = Config::get('dispatch_error_tmpl');
                break;
            default :
                $file = APP_PATH . DS . BIND_MODULE . DS . 'views' . DS . $this->file . '.php';
                break;
        }
        return $file;
    }

    /**
     * 模板变量赋值
     * @access public
     * @param mixed $name
     * @param mixed $value
     */
    public function assign($name, $value = '') {
        if (is_array($name)) {
            $this->tpl_vars = array_merge($this->tpl_vars, $name);
        } else {
            $this->tpl_vars[$name] = $value;
        }
        return $this;
    }

    /**
     * 输出内容
     * @param string $file 模板文件名
     */
    public function display($file) {
        $this->file = $file;
        $tplFile = $this->path();
        //判断模板是否存在
        if (!is_file($tplFile)) {
            throw new \Exception('模板文件 ' . BIND_MODULE . DS . $file . ' 不存在!');
        }
        //编译后的文件
        $filename = str_replace("/", "_", $file);
        $filename = str_replace("\\", "_", $filename);
        $comFile = CACHE_PATH . 'views/' . BIND_MODULE . '_' . $filename . '.php';
        !is_dir(CACHE_PATH . 'views/') && mkdir(CACHE_PATH . 'views/', 0755, true);

        //判断编译文件是否需要重新生成(编译文件是否存在或者模板文件修改时间大于编译文件的修改时间)
        if (App::$debug || !file_exists($comFile) || filemtime($tplFile) > filemtime($comFile)) {
            $this->fetch($tplFile, $comFile);
        }
        extract($this->tpl_vars, EXTR_OVERWRITE); //从数组中将变量导入到当前的符号表
        include $comFile;
    }

    /**
     * 生成编译文件
     * @param string $tplFile 模板路径
     * @param string $comFile 编译路径
     * @return string
     */
    private function fetch($tplFile, $comFile) {

        if ($fp = fopen($tplFile, 'r')) {
            $template = fread($fp, filesize($tplFile));
            fclose($fp);
        } elseif ($fp = fopen($filename = substr($tplFile, 0, -4) . '.php', 'r')) {
            $template = $this->getphptemplate(fread($fp, filesize($filename)));
            fclose($fp);
        }

        //编译,此处也可以使用ob_start()进行静态化
        $content = $this->tplReplace($template);
        if (empty($content)) {
            throw new \Exception('template error  ' . $tplFile);
        }
        if (!$fp = fopen($comFile, 'w')) {
            throw new \Exception('directory_notfound ' . $comFile);
        }
        flock($fp, 2);
        fwrite($fp, $content);
        fclose($fp);
    }

    /**
     * 编译文件
     * @param string $content 待编译的内容
     * @return string
     */
    private function tplReplace($content) {

        $var_regexp = "((\\\$[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*(\-\>)?[a-zA-Z0-9_\x7f-\xff]*)(\[[a-zA-Z0-9_\-\.\"\'\[\]\$\x7f-\xff]+\])*)";
        $const_regexp = "([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)";

        //转义左右定界符 正则表达式字符 <!--{template header}-->
        $headerexists = preg_match("/{(sub)?template\s+[\w:\/]+?header\}/", $content);
        $headeradd = $headerexists ? "" : '';
        $content = "<?php if(!defined('TICKY_PATH')) exit('Access Denied'); {$headeradd}?>\n$content";
        $pattern = array(
            "/([\n\r]+)\t+/s",
            "/\<\!\-\-\{(.+?)\}\-\-\>/s",
            "/\{lang\s+(.+?)\}/ies", // {lang xxx}
            "/[\n\r\t]*\{date\((.+?)\)\}[\n\r\t]*/ie", // {date xxx}
            "/[\n\r\t]*\{template\s+([a-z0-9_:\/]+)\}[\n\r\t]*/ies", // <!--{template header}-->
            "/[\n\r\t]*\{template\s+(.+?)\}[\n\r\t]*/ies",
            "/\{(\\\$[a-zA-Z0-9_\-\>\[\]\'\"\$\.\x7f-\xff]+)\}/s",
            "/[\n\r\t]*\{echo\s+(.+?)\}[\n\r\t]*/ies",
            //if
            "/([\n\r\t]*)\{if\s+(.+?)\}([\n\r\t]*)/ies",
            "/([\n\r\t]*)\{elseif\s+(.+?)\}([\n\r\t]*)/ies",
            "/\{else\}/i",
            "/\{\/if\}/i",
            //loop
            "/[\n\r\t]*\{loop\s+(\S+)\s+(\S+)\}[\n\r\t]*/ies",
            "/[\n\r\t]*\{loop\s+(\S+)\s+(\S+)\s+(\S+)\}[\n\r\t]*/ies",
            "/\{\/loop\}/i",
            //end loop
            "/ \?\>[\n\r]*\<\? /s",
            "/\"(http)?[\w\.\/:]+\?[^\"]+?&[^\"]+?\"/e",
            "/\<script[^\>]*?src=\"(.+?)\"(.*?)\>\s*\<\/script\>/ies",
            "/\<\?(\s{1})/is",
            "/\<\?\=(.+?)\?\>/is",
        );

        $translation = array(
            "\\1",
            "{\\1}",
            "\$this->languagevar('\\1')",
            "\$this->datetags('\\1')",
            "\$this->loadtemplate('\\1')",
            "\$this->loadtemplate('\\1')",
            "<?=\\1?>",
            "\$this->stripvtags('<? echo \\1; ?>')",
            "\$this->stripvtags('\\1<? if(\\2) { ?>\\3')",
            "\$this->stripvtags('\\1<? } elseif(\\2) { ?>\\3')",
            "<? } else { ?>",
            "<? } ?>",
            //loop
            "\$this->stripvtags('<? if(is_array(\\1)) foreach(\\1 as \\2) { ?>')",
            "\$this->stripvtags('<? if(is_array(\\1)) foreach(\\1 as \\2 => \\3) { ?>')",
            "<? } ?>",
            //end loop
            " ",
            "\$this->transamp('\\0')",
            "\$this->stripscriptamp('\\1', '\\2')",
            "<?php\\1",
            "<?php echo \\1;?>",
        );

        //正则处理
        $template = @preg_replace($pattern, $translation, $content);

        if (!empty($this->replacecode)) {
            $template = str_replace($this->replacecode['search'], $this->replacecode['replace'], $template);
        }
        return $template;
    }

    //====================================================================================
    var $subtemplates = array();
    var $replacecode = array('search' => array(), 'replace' => array());

    private function getphptemplate($content) {
        $pos = strpos($content, "\n");
        return $pos !== false ? substr($content, $pos + 1) : $content;
    }

    private function loadtemplate($file) {
        $filename = APP_PATH . BIND_MODULE . DS . 'views' . DS . $file . '.php';
        if (($content = implode('', file($filename))) || ($content = $this->getphptemplate(implode('', file(substr($filename, 0, -4) . '.php'))))) {
            $this->subtemplates[] = $filename;
            return $content;
        } else {
            return '<?php include "' . $filename . '" ?>';
        }
    }

    private function languagevar($var) {
        return Lang::get($var);
    }

    private function datetags($parameter) {
        $parameter = stripslashes($parameter);
        $i = count($this->replacecode['search']);
        $this->replacecode['search'][$i] = $search = "<!--DATE_TAG_$i-->";
        $this->replacecode['replace'][$i] = "<?php echo dgmdate($parameter);?>";
        return $search;
    }

    private function evaltags($php) {
        $php = str_replace('\"', '"', $php);
        $i = count($this->replacecode['search']);
        $this->replacecode['search'][$i] = $search = "<!--EVAL_TAG_$i-->";
        $this->replacecode['replace'][$i] = "<? $php?>";
        return $search;
    }

    private function stripvtags($expr, $statement = '') {
        $expr = str_replace("\\\"", "\"", preg_replace("/\<\?\=(\\\$.+?)\?\>/s", "\\1", $expr));
        $statement = str_replace("\\\"", "\"", $statement);
        return $expr . $statement;
    }

    private function stripscriptamp($s, $extra) {
        $extra = str_replace('\\"', '"', $extra);
        $s = str_replace('&amp;', '&', $s);
        return "<script src=\"$s\" type=\"text/javascript\"$extra></script>";
    }

    private function transamp($str) {
        $str = str_replace('&', '&amp;', $str);
        $str = str_replace('&amp;amp;', '&amp;', $str);
        $str = str_replace('\"', '"', $str);
        return $str;
    }

}
