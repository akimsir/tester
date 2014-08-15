<?php
/**
 * Created by Akim.
 * Date: 25.06.14
 * Time: 10:09
 */
set_include_path(
    get_include_path() . PATH_SEPARATOR .
    dirname(__FILE__) . '/vendors/Zend/library'
);

require_once 'Zend/Loader/Autoloader.php';

$out = [
    'data'  => '',
    'error' => ''
];

if (isset($_GET['cmd'])) {
    switch ($_GET['cmd']) {
        case 'get-testing-urls':

            $autoloader = Zend_Loader_Autoloader::getInstance();
            $autoloader->registerNamespace('Zend_');

            $parameters = [
                'host'     => 'db.kolesa.dev',
                'username' => 'akim',
                'password' => 'CJfFGcbyZp23qd8E',
                'dbname'   => 'ak_krisha',
                'port'     => 3306,
                'charset'  => 'utf8',
            ];

            $db     = Zend_Db::factory('Mysqli', $parameters);
            $result = $db->fetchAll('select * from `tests` order by id desc');

            $out['data'] = $result;
            echo json_encode($out);

            return;

            break;
    }
}


if (!isset($_GET['url']) || !$_GET['url']) {
    $out['error'] = 'нужен урл';
    echo json_encode($out);

    return;
}
require_once 'Zend/Dom/Query.php';
require_once 'Zend/Cache/Backend/Memcached.php';


$cache = new Zend_Cache_Backend_Memcached(
    [
        'servers'     => array(
            array(
                'host' => '127.0.0.1',
                'port' => '11211'
            )
        ),
        'compression' => true
    ]);

$logger = new Logger();
$tester = new Test([['url' => $_GET['url']]], $logger, $cache);

if (isset($_GET['compare-data'])) {
    $tester->compareData = $_GET['compare-data'];
}
$tester->doWork();
if ($tester->compareResult == 'identical') {
    $out['data'] = $tester->compareWith . ': <div style="color:green">all-data-identical</div>';
} else {
    $out['data'] = '<pre>' . $logger->data . '</pre>';
}
echo json_encode($out);
return;


class Test
{

    protected $logger = null;

    protected $entities = [];

    public $compareData = null;

    public $compareResult = 'different';

    public $compareWith = null;

    public $prodHost = null;

    public $testHost = null;

    public $cache = null;

    function __construct($entities, $logger, $cache)
    {

        $this->logger = $logger;

        $this->entities = $entities;

        $this->cache = $cache;
    }

    public function doWork()
    {
        $isIdentical = true;

        foreach ($this->entities as $v) {
            $url = $v['url'];

            if (null !== $this->compareData) {
                $isIdentical       = $this->compareWithData($url);
                $this->compareWith = 'compare with accepted data';
            } else {
                if (strpos($url, 'krisha') !== false) {
                    $prodUrl        = preg_replace('/http:\/\/[^\/]+/', 'http://krisha.kz', $url);
                    $this->prodHost = 'krisha.kz';
                    $this->testHost = 'krisha.ak.dev';
                }

                if (strpos($url, 'market') !== false) {
                    $prodUrl = preg_replace('/http:\/\/[^\/]+/', 'http://market.kz', $url);
                }

                if (strpos($url, 'kolesa') !== false) {
                    $prodUrl = preg_replace('/http:\/\/[^\/]+/', 'http://kolesa.kz', $url);
                }

                $this->compareWith = 'compare with ' . $prodUrl;
                $this->logger->log('compare with ' . $prodUrl);
                $this->logger->log('');
                $isIdentical = $this->compare($prodUrl, $url, 'title') && $isIdentical;
                $isIdentical = $this->compare($prodUrl, $url, 'meta[name="description"]', 'content') && $isIdentical;
                $isIdentical = $this->compare($prodUrl, $url, 'h1') && $isIdentical;
                $isIdentical = $this->compare($prodUrl, $url, 'link[rel="canonical"]', 'href') && $isIdentical;
                $isIdentical = $this->compare($prodUrl, $url, 'meta[name="robots"]', 'content') && $isIdentical;
            }
        }

        if ($isIdentical) {
            $this->compareResult = 'identical';
        }
    }

    protected function compareWithData($url)
    {
        $this->logger->log('compare with input data');
        $this->logger->log('');
        $isIdentical = false;

        foreach ($this->compareData as $query => $data) {
            $attr = null;

            if ($query == 'description') {
                $query = 'meta[name="description"]';
                $attr  = 'content';
            }

            $testData = $this->parseData($url, $query, $attr);

            $isIdentical = $this->compareDataAndLog($data, $testData, $query);
        }

        return $isIdentical;
    }

    public function compare($url, $testUrl, $query, $attr = null)
    {
        $prod = $this->parseData($url, $query, $attr);
        $test = $this->parseData($testUrl, $query, $attr);

        return $this->compareDataAndLog($prod, $test, $query);
    }

    public function parseData($url, $query, $attr = null)
    {
        $dom = $this->getDom($url);

        if ($query == 'title' || $query == 'h1') {
            $return = $this->getTextValue($dom, $query);
        } else {
            $return = $this->getAttrValue($dom, $query, $attr);
        }

        return $return;
    }

    public function getDom($url)
    {
        $key    = 'getDom.' . md5($url);
        $result = $this->cache->load($key);

        if (strpos($url, $this->testHost) !== false) {
            $result = false;
        }

        if (false === $result) {
            $result = new Zend_Dom_Query(file_get_contents($url));
            $this->cache->save($result, $key, 86400);
        }

        return $result;
    }

    public function compareDataAndLog($data1, $data2, $query)
    {
        $isIdentical = false;
        if (strcmp($data1, $data2) !== 0) {
            $this->logger->log($query . ':');
            $this->logger->log('test: ' . $data2);
            $this->logger->log('prod: ' . $data1);

            /*  $this->logger->log('xdiff_string_bdiff:' . xdiff_string_diff (
                  $prod,
                  $test
              ));*/
            $this->logger->log('');

        } else {
            $this->logger->log($query . ':');
            $this->logger->log('identical' . "\n");
            $isIdentical = true;
        }

        return $isIdentical;
    }

    public function getTextValue($dom, $query)
    {
        $results = $dom->query($query);

        if (count($results) > 1) {
            echo 'error: count > 1';
        }

        return $results->current()->textContent;
    }

    public function getAttrValue($dom, $query, $attrName)
    {
        $results = $dom->query($query);

        if (count($results) > 1) {
            echo 'error: count > 1';
        }

        if ($results && $results->current()) {
            $attrValue = $results->current()->getAttribute($attrName);
            if (0 === strcasecmp($attrName, 'href')) {
                $attrValue = str_replace([$this->prodHost, $this->testHost], ['', ''], $attrValue);
            }

            return $attrValue;
        }

        return null;
    }
}

class Logger
{
    public $data = '';

    public function  log($data)
    {
        $this->data .= "\n" . $data;
    }
}