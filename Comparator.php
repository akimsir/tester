<?php
class Comparator
{

    const SEARCH_URLS_TABLE = 'search_urls';

    const ADVERTS_URLS_TABLE = 'advert_urls';

    /**
     * @var Zend_Db_Adapter_Mysqli
     */
    public $db = null;

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

    public $serviceMessages = [];

    public function __construct($entities, $logger)
    {
        $this->logger   = $logger;
        $this->entities = $entities;
    }

    public function init()
    {
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

        $this->setDb($db);

        $this->cache = new Zend_Cache_Backend_Memcached(
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
                } else {
                    throw new Exception('На данный момент тулза заточена только под крышу');
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
                $this->logger->log(PHP_EOL);
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

        $tableName = self::SEARCH_URLS_TABLE;
        if (strpos($url, 'a/show/')) {
            $tableName = self::ADVERTS_URLS_TABLE;
        }

        $validator = new Zend_Validate_Db_NoRecordExists(
            [
                'table'   => $tableName,
                'field'   => 'url',
                'adapter' => $this->db
            ]
        );

        $url = preg_replace('|http://[^/]+/|', '', $url);

        if ($validator->isValid($url)) {
            $this->db->insert($tableName, ['url' => $url]);
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
            $this->logger->log('');
        } else {
            $this->logger->log($query . ':');
            $this->logger->log('identical');
            $this->logger->log('');
            $isIdentical = true;
        }

        return $isIdentical;
    }

    public function getTextValue($dom, $query)
    {
        $results = $dom->query($query);

        if (count($results) > 1) {
            $this->serviceMessages[] = 'По запросу ' . $query . ' найдено несколько результатов';
        }

        return $results->current()->textContent;
    }

    public function getAttrValue($dom, $query, $attrName)
    {
        $results = $dom->query($query);

        if (count($results) > 1) {
            $this->serviceMessages[] = 'По запросу ' . $query . ' - ' . $attrName . ' найдено несколько результатов';
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
