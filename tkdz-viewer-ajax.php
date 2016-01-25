<?php
require_once "Comparator.php";
require_once "Logger.php";

try {
    $out = [
        'data'  => '',
        'error' => ''
    ];

    if (!isset($_GET['url'])) {
        $_GET['url'] = '';
    }

    $logger     = new Logger();
    $comparator = new Comparator([['url' => $_GET['url']]], $logger);
    $comparator->init();

    if (isset($_GET['cmd'])) {
        switch ($_GET['cmd']) {
            case 'get-search-urls':
                $result = $comparator->db->fetchAll('select * from `search_urls` order by id desc');

                $out['data'] = $result;
                echo json_encode($out);

                break;
            case 'get-advert-urls':
                $result = $comparator->db->fetchAll('select * from `advert_urls` order by id desc');

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

    if (isset($_GET['compare-data'])) {
        $comparator->compareData = $_GET['compare-data'];
    }

    if (isset($_GET['compare-host']) && $_GET['compare-host']) {
        $comparator->compareHost = $_GET['compare-host'];
    }

    if (isset($_GET['canonical-hosts'])) {
        $comparator->canonicalHosts = $_GET['canonical-hosts'];
    }

    $comparator->doWork();

    if (!empty($comparator->serviceMessages)) {
        $out['data'] = '<div style="color:red">' . join('<br/>', $comparator->serviceMessages) . '</div>';
    }

    if ($comparator->compareResult == 'identical') {
        $out['data'] .= $comparator->compareWith . ': <div style="color:green">all-data-identical</div>';
    } else {
        $out['data'] .= '<pre>' . $logger->data . '</pre>';
    }

    echo json_encode($out);

} catch (Exception $e) {
    $out['error'] = $e->getMessage();
    echo json_encode($out);
}

return;
