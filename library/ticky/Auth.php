<?php

/**
 * +----------------------------------------------------------------------
 * | TickyPHP [ This is a freeware ]
 * +----------------------------------------------------------------------
 * | Copyright (c) 2015 All rights reserved.
 * +----------------------------------------------------------------------
 * | Author: luomingui <e-mail:minguiluo@163.com> <QQ:271391233>
 * | SVN: $Id: Auth.php 29529 2018-2-12 luomingui $
 * +----------------------------------------------------------------------
 * | Description：权限认证类
 *  $auth=new Auth();
 *  $auth->check('规则名称','用户id')
 * +----------------------------------------------------------------------
 */
/*
  数据库

  DROP TABLE IF EXISTS tky_auth_role;
  CREATE TABLE tky_auth_role (
  roleid MEDIUMINT (8) UNSIGNED NOT NULL auto_increment COMMENT '编号',
  title CHAR (200) NOT NULL DEFAULT '' COMMENT '名称',
  status TINYINT (1) NOT NULL DEFAULT '1' COMMENT '状态',
  rules text COMMENT '规则',
  PRIMARY KEY (roleid)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT = '角色';

  DROP TABLE IF EXISTS tky_auth_role_member;
  CREATE TABLE tky_auth_role_member (
  uid MEDIUMINT (8) UNSIGNED NOT NULL DEFAULT '0' COMMENT '用户编号',
  roleid MEDIUMINT (8) UNSIGNED NOT NULL DEFAULT '0' COMMENT '组编号',
  UNIQUE KEY uid_role_id (uid, roleid)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT = '用户角色';

  DROP TABLE IF EXISTS tky_auth_rule;
  CREATE TABLE tky_auth_rule (
  ruleid MEDIUMINT (8) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '编号',
  module VARCHAR (200) NOT NULL DEFAULT '' COMMENT '规则所属module',
  type TINYINT (1) NOT NULL DEFAULT '1' COMMENT '类型 1-url;2-主菜单',
  name CHAR (800) NOT NULL DEFAULT '' COMMENT '规则唯一英文标识',
  title CHAR (200) NOT NULL DEFAULT '' COMMENT '规则中文描述',
  regex CHAR (200) NOT NULL DEFAULT '' COMMENT '规则表达式',
  status TINYINT (1) NOT NULL DEFAULT '1' COMMENT '状态',
  PRIMARY KEY (ruleid)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT = '规则表';

 */

namespace ticky;

class Auth {

    //默认配置
    protected $config = array(
        'AUTH_ON' => true, // 认证开关
        'AUTH_TYPE' => 1, // 认证方式，1为实时认证；2为登录认证。
        'AUTH_GROUP' => 'tky_auth_role', // 用户组数据表名
        'AUTH_GROUP_ACCESS' => 'tky_auth_role_member', // 用户-用户组关系表
        'AUTH_RULE' => 'tky_auth_rule', // 权限规则表
        'AUTH_USER' => 'tky_member'             // 用户信息表
    );

    public function __construct() {
        $prefix = Config::get('prefix', 'auth');
        $this->config['AUTH_GROUP'] = $prefix . $this->config['AUTH_GROUP'];
        $this->config['AUTH_RULE'] = $prefix . $this->config['AUTH_RULE'];
        $this->config['AUTH_USER'] = $prefix . $this->config['AUTH_USER'];
        $this->config['AUTH_GROUP_ACCESS'] = $prefix . $this->config['AUTH_GROUP_ACCESS'];
        if (Config::get('', 'auth')) {
            //可设置配置项 AUTH_CONFIG, 此配置项为数组。
            $this->config = array_merge($this->config, Config::get('', 'auth'));
        }
    }

    /**
     * 检查权限
     * @param name string|array  需要验证的规则列表,支持逗号分隔的权限规则或索引数组
     * @param uid  int           认证用户的id
     * @param string mode        执行check的模式
     * @param relation string    如果为 'or' 表示满足任一条规则即通过验证;如果为 'and'则表示需满足所有规则才能通过验证
     * @return boolean           通过验证返回true;失败返回false
     */
    public function check($name, $uid, $type = 1, $mode = 'url', $relation = 'or') {
        if (!$this->config['AUTH_ON'])
            return true;
        $authList = $this->getAuthList($uid, $type); //获取用户需要验证的所有有效规则列表
        if (is_string($name)) {
            $name = strtolower($name);
            if (strpos($name, ',') !== false) {
                $name = explode(',', $name);
            } else {
                $name = array($name);
            }
        }
        $list = array(); //保存验证通过的规则名
        if ($mode == 'url') {
            $REQUEST = unserialize(strtolower(serialize($_REQUEST)));
        }
        foreach ($authList as $auth) {
            $query = preg_replace('/^.+\?/U', '', $auth);
            if ($mode == 'url' && $query != $auth) {
                parse_str($query, $param); //解析规则中的param
                $intersect = array_intersect_assoc($REQUEST, $param);
                $auth = preg_replace('/\?.*$/U', '', $auth);
                if (in_array($auth, $name) && $intersect == $param) {  //如果节点相符且url参数满足
                    $list[] = $auth;
                }
            } else if (in_array($auth, $name)) {
                $list[] = $auth;
            }
        }
        if ($relation == 'or' and ! empty($list)) {
            return true;
        }
        $diff = array_diff($name, $list);
        if ($relation == 'and' and empty($diff)) {
            return true;
        }
        return false;
    }

    /**
     * 根据用户id获取用户组,返回值为数组
     * @param  uid int     用户id
     * @return array       用户所属的用户组 array(
     *     array('uid'=>'用户id','group_id'=>'用户组id','title'=>'用户组名称','rules'=>'用户组拥有的规则id,多个,号隔开'),
     *     ...)
     */
    public function getGroups($uid) {
        static $groups = array();
        if (isset($groups[$uid]))
            return $groups[$uid];
        $user_groups = M()
                        ->table($this->config['AUTH_GROUP_ACCESS'] . ' a')
                        ->where("a.uid='$uid' and g.status='1'")
                        ->join($this->config['AUTH_GROUP'] . " g on a.roleid=g.roleid")
                        ->field('a.uid,a.roleid,g.title,g.rules')->select();
        $groups[$uid] = $user_groups ?: array();
        return $groups[$uid];
    }

    /**
     * 获得权限列表
     * @param integer $uid  用户id
     * @param integer $type
     */
    protected function getAuthList($uid, $type) {
        static $_authList = array(); //保存用户验证通过的权限列表
        $t = implode(',', (array) $type);
        if (isset($_authList[$uid . $t])) {
            return $_authList[$uid . $t];
        }

        if ($this->config['AUTH_TYPE'] == 2 && isset($_SESSION['_AUTH_LIST_' . $uid . $t])) {
            return $_SESSION['_AUTH_LIST_' . $uid . $t];
        }

        //读取用户所属用户组
        $groups = $this->getGroups($uid);
        $ids = array(); //保存用户所属用户组设置的所有权限规则id
        foreach ($groups as $g) {
            $ids = array_merge($ids, explode(',', trim($g['rules'], ',')));
        }
        $ids = array_unique($ids);
        if (empty($ids)) {
            $_authList[$uid . $t] = array();
            return array();
        }

        $map = array(
            'ruleid' => array('in', $ids),
            'type' => $type,
            'status' => 1,
        );
        //读取用户组所有权限规则
        $rules = M()->table($this->config['AUTH_RULE'])->where($map)->field('condition,name')->select();

        //循环规则，判断结果。
        $authList = array();   //
        foreach ($rules as $rule) {
            if (!empty($rule['condition'])) { //根据condition进行验证
                $user = $this->getUserInfo($uid); //获取用户信息,一维数组

                $command = preg_replace('/\{(\w*?)\}/', '$user[\'\\1\']', $rule['condition']);
                //dump($command);//debug
                (eval('$condition=(' . $command . ');'));
                if ($condition) {
                    $authList[] = strtolower($rule['name']);
                }
            } else {
                //只要存在就记录
                $authList[] = strtolower($rule['name']);
            }
        }
        $_authList[$uid . $t] = $authList;
        if ($this->config['AUTH_TYPE'] == 2) {
            //规则列表结果保存到session
            $_SESSION['_AUTH_LIST_' . $uid . $t] = $authList;
        }
        return array_unique($authList);
    }

    /**
     * 获得用户资料,根据自己的情况读取数据库
     */
    protected function getUserInfo($uid) {
        static $userinfo = array();
        if (!isset($userinfo[$uid])) {
            $userinfo[$uid] = M()->where(array('uid' => $uid))->table($this->config['AUTH_USER'])->find();
        }
        return $userinfo[$uid];
    }

}
