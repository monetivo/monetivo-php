<?php

/** Helper for loading classes
 * Applicable only when Composer is not used!
 * Most often these cases refer to situations when library is a part of plugin for e.g. Prestashop or Magento
 * Default and prefered way to install Monetivo library is to use composer
 * Please refer to the documentation how to install this library properly
 * @param $dir
 * @throws Exception
 */
function loadMonetivo($dir)
{
    // load and parse composer.json file for PSR-4 namespaces
    $file = $dir.DIRECTORY_SEPARATOR.'composer.json';
    if(!file_exists($file) || !is_readable($file))
    {
        throw new Exception('composer.json file for Monetivo package is not accessible. Please contact support.');
    }

    $composer = json_decode(file_get_contents($file), 1);
    if(json_last_error() !== JSON_ERROR_NONE || !isset($composer['autoload']['psr-4']))
    {
        throw new Exception('composer.json file for Monetivo package is corrupted. Please re-download the library and try again.');
    }
    $namespaces = $composer['autoload']['psr-4'];

    // load desired classes
    foreach ($namespaces as $namespace => $classpath) {
        spl_autoload_register(function ($classname) use ($namespace, $classpath, $dir) {
            if (preg_match('#^' .preg_quote($namespace). '#', $classname)) {
                $classname = str_replace($namespace, '', $classname);
                $filename = preg_replace("#\\\\#", '/', $classname). '.php';
                include_once sprintf('%s/%s/%s', $dir, $classpath, $filename);
            }
        }, true, true);
    }
}

loadMonetivo(__DIR__);