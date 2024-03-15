<?php

namespace Clientexec\Utils;

class Cookie
{
    public static function get($name)
    {
        $value = array_key_exists('CE_' . $name, $_COOKIE) ? $_COOKIE['CE_' . $name] : '';
        return $value;
    }

    public static function set($name, $value, $expires = 0)
    {
        $secure = false;
        if (\CE_Lib::isHttps()) {
            $secure = true;
        }
        return setcookie('CE_' . $name, $value, $expires, '/', null, $secure, true);
    }

    public static function delete($name)
    {
        unset($_COOKIE['CE_' . $name]);
        return setcookie('CE_' . $name, null, time() - 3600, '/');
    }
}
