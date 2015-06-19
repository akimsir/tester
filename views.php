<?php
/**
 * Created by Akim.
 * Date: 10.02.2015
 * Time: 10:50
 */
set_include_path(
    get_include_path() . PATH_SEPARATOR .
    dirname(__FILE__) . '/vendors/Zend/library'
);

require_once 'Zend/Loader/Autoloader.php';
require_once 'Zend/Dom/Query.php';
require_once 'Zend/Cache/Backend/Memcached.php';

$autoloader = Zend_Loader_Autoloader::getInstance();
$autoloader->registerNamespace('Zend_');

$cache = new Zend_Cache_Backend_Memcached(
    [
        'servers'     => array(
            array(
                'host' => '127.0.0.1',
                'port' => '11211'
            )
        ),
        'compression' => false
    ]
);

$key = 'test-views';
$nb = $cache->load($key);
if (!$nb) {
    $nb = 0;
}
$nb++;

echo 'nb: ' . $nb;

$cache->save($nb, $key, [], 18000);