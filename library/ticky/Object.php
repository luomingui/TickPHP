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
 * | SVN: $Id: Object.php 29529 2018-3-6 luomingui $
 * +----------------------------------------------------------------------
 * | Description：Object
 * +----------------------------------------------------------------------
 */

namespace ticky;

class Object {

    public function __set($name, $value) {
        $setter = 'set' . $name;
        if (method_exists($this, $setter)) {
            return $this->$setter($value);
        } elseif ($this->canGetProperty($name)) {
            throw new Exception('The property "' . get_class($this) . '->' . $name . '" is readonly');
        } else {
            throw new Exception('The property "' . get_class($this) . '->' . $name . '" is not defined');
        }
    }

    public function __get($name) {
        $getter = 'get' . $name;
        if (method_exists($this, $getter)) {
            return $this->$getter();
        } else {
            throw new Exception('The property "' . get_class($this) . '->' . $name . '" is not defined');
        }
    }

    public function __call($name, $parameters) {
        throw new Exception('Class "' . get_class($this) . '" does not have a method named "' . $name . '".');
    }

    public function canGetProperty($name) {
        return method_exists($this, 'get' . $name);
    }

    public function canSetProperty($name) {
        return method_exists($this, 'set' . $name);
    }

    public function __toString() {
        return get_class($this);
    }

    public function __invoke() {
        return get_class($this);
    }

    public function offsetExists($offset) {
        return array_key_exists($offset, get_object_vars($this));
    }

    public function offsetUnset($key) {
        if (array_key_exists($key, get_object_vars($this))) {
            unset($this->{$key});
        }
    }

    public function offsetSet($offset, $value) {
        $this->{$offset} = $value;
    }

    public function offsetGet($var) {
        return $this->$var;
    }

}
