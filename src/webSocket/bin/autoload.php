<?php
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 2017/3/26 0026
 * Time: 16:06
 */

defined('STDIN') || define('STDIN', fopen('php://stdin', 'rb'));
defined('STDOUT') ||define('STDOUT', fopen('php://stdout', 'wb'));

$prefix = 'inhere\\librarys\\webSocket';

spl_autoload_register(function($name) use ($prefix) {
    if (false === strpos($name, $prefix)) {
        return false;
    }

    $name = str_replace($prefix, '', $name);
    $path = str_replace('\\', DIRECTORY_SEPARATOR ,$name);

    if(is_file($file = dirname(__DIR__) . "$path.php")) {
        require_once $file;

        if( class_exists($name, false) ) {
            return true;
        }
    }

    return false;
});
