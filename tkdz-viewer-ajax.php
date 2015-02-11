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
require_once 'Zend/Dom/Query.php';
require_once 'Zend/Cache/Backend/Memcached.php';

$autoloader = Zend_Loader_Autoloader::getInstance();
$autoloader->registerNamespace('Zend_');

$parameters = [
    'host'     => '127.0.0.1',
    'username' => 'root',
    'password' => '1q2w3e4',
    'dbname'   => 'test',
    'port'     => 3306,
    'charset'  => 'utf8',
];

$db = Zend_Db::factory('Mysqli', $parameters);

$out = [
    'data'  => '',
    'error' => ''
];

if (isset($_GET['cmd'])) {
    switch ($_GET['cmd']) {
        case 'get-search-urls':
            $result = $db->fetchAll('select * from `search_urls` order by id desc');

            $out['data'] = $result;
            echo json_encode($out);

            break;
        case 'get-advert-urls':
            $result = $db->fetchAll('select * from `advert_urls` order by id desc');

            $out['data'] = $result;
            echo json_encode($out);

            break;
    }

    return;
}

if (!isset($_GET['url']) || !$_GET['url']) {
    $out['error'] = 'нужен урл';
    echo json_encode($out);

    return;
}

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

try {
    $logger = new Logger();
    $tester = new Test([['url' => $_GET['url']]], $logger, $cache);
    $tester->setDb($db);

    if (isset($_GET['compare-data'])) {
        $tester->compareData = $_GET['compare-data'];
    }

    if (isset($_GET['compare-host']) && $_GET['compare-host']) {
        $tester->compareHost = $_GET['compare-host'];
    }

    $tester->doWork();

    if ($tester->compareResult == 'identical') {
        $out['data'] = $tester->compareWith . ': <div style="color:green">all-data-identical</div>';
    } else {
        $out['data'] = '<pre>' . $logger->data . '</pre>';
    }

    echo json_encode($out);

} catch (Exception $e) {
    $out['error'] = $e->getMessage();
    echo json_encode($out);
}

return;

class Test
{

    /**
     * @var Zend_Db_Adapter_Mysqli
     */
    protected $db = null;

    protected $logger = null;

    protected $entities = [];

    public $compareData = null;

    public $compareResult = 'different';

    public $compareWith = null;

    public $compareHost = null;

    public $testHost = null;

    public $cache = null;

    public $cacheTime = 7200;

    protected $domObjectsLocalCache = [];

    public function __construct($entities, $logger, $cache)
    {
        $this->logger   = $logger;
        $this->entities = $entities;
        $this->cache    = $cache;
    }

    public function doWork()
    {
        $isIdentical = true;

        foreach ($this->entities as $v) {
            $url = $v['url'];

            if (null !== $this->compareData) {
                $this->cacheTime   = 1;
                $isIdentical       = $this->compareWithData($url);
                $this->compareWith = 'compare with accepted data';
            } else {
                $compareUrl = null;

                if (strpos($url, 'krisha') !== false) {
                    if (!$this->compareHost) {
                        $this->compareHost = 'krisha.kz';
                    }

                    $compareUrl = preg_replace('/http:\/\/[^\/]+/', 'http://' . $this->compareHost, $url);
                    preg_match('/http:\/\/([^\/]+)/', $url, $matches);
                    if (isset($matches[1])) {
                        $this->testHost = $matches[1];
                    } else {
                        throw new Exception('Не определён урл для сравнения');
                    }
                }

                if (strpos($url, 'market') !== false) {
                    $compareUrl = preg_replace('/http:\/\/[^\/]+/', 'http://market.kz', $url);
                }

                if (strpos($url, 'kolesa') !== false) {
                    $compareUrl = preg_replace('/http:\/\/[^\/]+/', 'http://kolesa.kz', $url);
                }

                if (!$compareUrl) {
                    throw new Exception('Неподходящий урл для сравнения');
                }

                $this->compareWith = 'compare with ' . $compareUrl;
                $this->logger->log('compare with ' . $compareUrl);
                $this->logger->log('');
                $isIdentical = $this->compare($compareUrl, $url, 'title') && $isIdentical;
                $isIdentical = $this->compare($compareUrl, $url, 'meta[name="description"]', 'content') && $isIdentical;
                $isIdentical = $this->compare($compareUrl, $url, 'h1') && $isIdentical;
                $isIdentical = $this->compare($compareUrl, $url, 'link[rel="canonical"]', 'href') && $isIdentical;
                $isIdentical = $this->compare($compareUrl, $url, 'meta[name="robots"]', 'content') && $isIdentical;

                $this->saveInSetUrl($url);
            }
        }

        if ($isIdentical) {
            $this->compareResult = 'identical';
        }
    }

    /**
     * @param null $db
     */
    public function setDb($db)
    {
        $this->db = $db;
    }

    /**
     * Сохранение урла в набор
     *
     * @param $url
     * @throws Zend_Db_Adapter_Exception
     */
    protected function saveInSetUrl($url)
    {
        $validator = new Zend_Validate_Db_NoRecordExists(
            array(
                'table' => 'search_urls',
                'field' => 'url',
                'adapter' => $this->db
            )
        );

        $url = preg_replace('|http://[^/]+/|', '', $url);

        if ($validator->isValid($url)) {
            $this->db->insert('search_urls', ['url' => $url]);
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
        $key = 'getDom.' . md5($url);

        if (isset($this->domObjectsLocalCache[$key])) {
            return $this->domObjectsLocalCache[$key];
        }

        $result = $this->cache->load($key);

        if (strpos($url, $this->testHost) !== false) {
            $result = false;;
        }

        if (false === $result) {
            $result = new Zend_Dom_Query(file_get_contents($url));
            $this->cache->save($result, $key, [], $this->cacheTime);
        }

        $this->domObjectsLocalCache[$key] = $result;

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
                $attrValue = str_replace([$this->compareHost, $this->testHost], ['', ''], $attrValue);
            }

            return $attrValue;
        }

        return null;
    }
}

class Logger
{
    public $data = '';

    public function log($data)
    {
        $this->data .= "\n" . $data;

        /*file_put_contents(
            'tkdz-viewer-log',
            "\n" . date('Y-m-d H:i:s') . ' ' . $data,
            FILE_APPEND
        );*/
    }
}
