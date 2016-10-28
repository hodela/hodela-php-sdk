<?php
/**
 * @Package:
 * @Author: nguyen dinh the
 * @Date: 10/28/16
 * @Time: 3:41 PM
 */
$rootDir = dirname(__FILE__);
if (@ini_get('open_basedir'))
{
    set_include_path($rootDir . PATH_SEPARATOR . '.');
}
else
{
    set_include_path($rootDir . PATH_SEPARATOR . '.' . PATH_SEPARATOR . get_include_path());
}
spl_autoload_register(function($class){
    $rootDir = dirname(__FILE__);
    if (class_exists($class, false) || interface_exists($class, false))
    {
        return true;
    }

    if ($class == 'utf8_entity_decoder')
    {
        return true;
    }

    if (substr($class, 0, 6) == 'Hodela')
    {
        if(substr($class, 0, 7) == 'Hodela\\'){
            $class = substr($class,7);
        }
        $filename = $rootDir . '/' . str_replace(array('_', '\\'), '/', $class) . '.php';
        if (!$filename)
        {
            return false;
        }

        if (file_exists($filename))
        {
            include($filename);
            return (class_exists($class, false) || interface_exists($class, false));
        }else{
            return false;
        }
    }else{
        return false;
    }
});