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

class Compiler2 extends Object {

    private $_config = [
        'suffix' => '.php', //文件后缀名
        'templateDir' => '/views/', //模板所在文件夹
        'compileDir' => '/runtime/cache/views/', //编译后存放的目录
        'suffixCompile' => '.php', //编译后文件后缀
        'isReCacheHtml' => false, //是否需要重新编译成静态html文件
        'isSupportPhp' => true, //是否支持php的语法
        'cacheTime' => 0, //缓存时间,单位秒
    ];
    private $_valueMap = [];
    var $subtemplates = array();
    var $file = '';
    var $replacecode = array('search' => array(), 'replace' => array());

    /**
     * [compile 编译模板文件]
     * @param  [type] $source   [模板文件]
     * @param  [type] $destFile [编译后文件]
     * @param  [type] $values   [键值对]
     * @param  [type] $config   [配置]
     * @return [type]           [description]
     */
    public function compile($source, $destFile, $values, $config = []) {
        $this->_config = array_merge($this->_config, $config);
        if ($fp = @fopen($source, 'r')) {
            $template = @fread($fp, filesize($source));
            fclose($fp);
        } elseif ($fp = @fopen($filename = $source, 'r')) {
            $template = $this->getphptemplate(@fread($fp, filesize($filename)));
            fclose($fp);
        } else {
            $template = file_get_contents($source);
        }
        $this->file = $source;

        $var_regexp = "((\\\$[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*(\-\>)?[a-zA-Z0-9_\x7f-\xff]*)(\[[a-zA-Z0-9_\-\.\"\'\[\]\$\x7f-\xff]+\])*)";
        $const_regexp = "([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)";
        $headerexists = preg_match("/{(sub)?template\s+[\w:\/]+?header\}/", $template);
        $this->subtemplates = array();
        for ($i = 1; $i <= 3; $i++) {
            if (strexists($template, '{subtemplate')) {
                $template = @preg_replace("/[\n\r\t]*(\<\!\-\-)?\{subtemplate\s+([a-z0-9_:\/]+)\}(\-\-\>)?[\n\r\t]*/ies", "\$this->loadsubtemplate('\\2')", $template);
            }
        }
        $template = @preg_replace("/([\n\r]+)\t+/s", "\\1", $template);
        $template = @preg_replace("/\<\!\-\-\{(.+?)\}\-\-\>/s", "{\\1}", $template);
        $template = @preg_replace("/\{lang\s+(.+?)\}/ies", "\$this->languagevar('\\1')", $template);
        $template = @preg_replace("/[\n\r\t]*\{date\((.+?)\)\}[\n\r\t]*/ie", "\$this->datetags('\\1')", $template);
        $template = @preg_replace("/[\n\r\t]*\{eval\}\s*(\<\!\-\-)*(.+?)(\-\-\>)*\s*\{\/eval\}[\n\r\t]*/ies", "\$this->evaltags('\\2')", $template);
        $template = @preg_replace("/[\n\r\t]*\{eval\s+(.+?)\s*\}[\n\r\t]*/ies", "\$this->evaltags('\\1')", $template);
        $template = @str_replace("{LF}", "<?=\"\\n\"?>", $template);
        $template = @preg_replace("/\{(\\\$[a-zA-Z0-9_\-\>\[\]\'\"\$\.\x7f-\xff]+)\}/s", "<?=\\1?>", $template);
        $template = @preg_replace("/\<\?\=\<\?\=$var_regexp\?\>\?\>/es", "\$this->addquote('<?=\\1?>')", $template);

        $headeradd = $headerexists ? "" : '';
        $template = "<? if(!defined('TICKY_PATH')) exit('Access Denied'); {$headeradd}?>\n$template";

        $template = @preg_replace("/[\n\r\t]*\{template\s+([a-z0-9_:\/]+)\}[\n\r\t]*/ies", "\$this->loadtemplate('\\1')", $template);
        $template = @preg_replace("/[\n\r\t]*\{template\s+(.+?)\}[\n\r\t]*/ies", "\$this->loadtemplate('\\1')", $template);

        $template = @preg_replace("/[\n\r\t]*\{echo\s+(.+?)\}[\n\r\t]*/ies", "\$this->stripvtags('<? echo \\1; ?>')", $template);

        $template = @preg_replace("/([\n\r\t]*)\{if\s+(.+?)\}([\n\r\t]*)/ies", "\$this->stripvtags('\\1<? if(\\2) { ?>\\3')", $template);
        $template = @preg_replace("/([\n\r\t]*)\{elseif\s+(.+?)\}([\n\r\t]*)/ies", "\$this->stripvtags('\\1<? } elseif(\\2) { ?>\\3')", $template);
        $template = @preg_replace("/\{else\}/i", "<? } else { ?>", $template);
        $template = @preg_replace("/\{\/if\}/i", "<? } ?>", $template);

        $template = @preg_replace("/[\n\r\t]*\{loop\s+(\S+)\s+(\S+)\}[\n\r\t]*/ies", "\$this->stripvtags('<? if(is_array(\\1)) foreach(\\1 as \\2) { ?>')", $template);
        $template = @preg_replace("/[\n\r\t]*\{loop\s+(\S+)\s+(\S+)\s+(\S+)\}[\n\r\t]*/ies", "\$this->stripvtags('<? if(is_array(\\1)) foreach(\\1 as \\2 => \\3) { ?>')", $template);
        $template = @preg_replace("/\{\/loop\}/i", "<? } ?>", $template);

        $template = @preg_replace("/\{$const_regexp\}/s", "<?=\\1?>", $template);
        if (!empty($this->replacecode)) {
            $template = @str_replace($this->replacecode['search'], $this->replacecode['replace'], $template);
        }
        $template = @preg_replace("/ \?\>[\n\r]*\<\? /s", " ", $template);

        $template = @preg_replace("/\"(http)?[\w\.\/:]+\?[^\"]+?&[^\"]+?\"/e", "\$this->transamp('\\0')", $template);
        $template = @preg_replace("/\<script[^\>]*?src=\"(.+?)\"(.*?)\>\s*\<\/script\>/ies", "\$this->stripscriptamp('\\1', '\\2')", $template);
        $template = @preg_replace("/[\n\r\t]*\{block\s+([a-zA-Z0-9_\[\]]+)\}(.+?)\{\/block\}/ies", "\$this->stripblock('\\1', '\\2')", $template);
        $template = @preg_replace("/\<\?(\s{1})/is", "<?php\\1", $template);
        $template = @preg_replace("/\<\?\=(.+?)\?\>/is", "<?php echo \\1;?>", $template);

        $this->_valueMap = $values;
        file_put_contents($destFile, $template);
    }

    function loadtemplate($file) {
        $filename = APP_PATH . BIND_MODULE . $this->_config['templateDir'] . DS . $file . $this->_config['suffix'];
        if (($content = @implode('', file($filename))) || ($content = $this->getphptemplate(@implode('', file(substr($filename, 0, -4) . '.php'))))) {
            $this->subtemplates[] = $filename;
            return $content;
        } else {
            return '<?php include "' . $filename . '" ?>';
        }
    }

    function languagevar($var) {
        return L($var);
    }

    function transamp($str) {
        $str = str_replace('&', '&amp;', $str);
        $str = str_replace('&amp;amp;', '&amp;', $str);
        $str = str_replace('\"', '"', $str);
        return $str;
    }

    function datetags($parameter) {
        $parameter = stripslashes($parameter);
        $i = count($this->replacecode['search']);
        $this->replacecode['search'][$i] = $search = "<!--DATE_TAG_$i-->";
        $this->replacecode['replace'][$i] = "<?php echo dgmdate($parameter);?>";
        return $search;
    }

    function addquote($var) {
        return str_replace("\\\"", "\"", preg_replace("/\[([a-zA-Z0-9_\-\.\x7f-\xff]+)\]/s", "['\\1']", $var));
    }

    function loadsubtemplate($file) {
        $tplfile = template($file, 0, '', 1);
        $filename = APP_PATH . $tplfile;
        if (($content = @implode('', file($filename))) || ($content = $this->getphptemplate(@implode('', file(substr($filename, 0, -4) . '.php'))))) {
            $this->subtemplates[] = $tplfile;
            return $content;
        } else {
            return '<!-- ' . $file . ' -->';
        }
    }

    function getphptemplate($content) {
        $pos = strpos($content, "\n");
        return $pos !== false ? substr($content, $pos + 1) : $content;
    }

    function stripvtags($expr, $statement = '') {
        $expr = str_replace("\\\"", "\"", preg_replace("/\<\?\=(\\\$.+?)\?\>/s", "\\1", $expr));
        $statement = str_replace("\\\"", "\"", $statement);
        return $expr . $statement;
    }

    function stripscriptamp($s, $extra) {
        $extra = str_replace('\\"', '"', $extra);
        $s = str_replace('&amp;', '&', $s);
        return "<script src=\"$s\" type=\"text/javascript\"$extra></script>";
    }

    function stripblock($var, $s) {
        $s = str_replace('\\"', '"', $s);
        $s = preg_replace("/<\?=\\\$(.+?)\?>/", "{\$\\1}", $s);
        preg_match_all("/<\?=(.+?)\?>/e", $s, $constary);
        $constadd = '';
        $constary[1] = array_unique($constary[1]);
        foreach ($constary[1] as $const) {
            $constadd .= '$__' . $const . ' = ' . $const . ';';
        }
        $s = preg_replace("/<\?=(.+?)\?>/", "{\$__\\1}", $s);
        $s = str_replace('?>', "\n\$$var .= <<<EOF\n", $s);
        $s = str_replace('<?', "\nEOF;\n", $s);
        $s = str_replace("\nphp ", "\n", $s);
        return "<?\n$constadd\$$var = <<<EOF\n" . $s . "\nEOF;\n?>";
    }

    function evaltags($php) {
        $php = str_replace('\"', '"', $php);
        $i = count($this->replacecode['search']);
        $this->replacecode['search'][$i] = $search = "<!--EVAL_TAG_$i-->";
        $this->replacecode['replace'][$i] = "<? $php?>";
        return $search;
    }

}
