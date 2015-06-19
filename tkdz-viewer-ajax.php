<?php
require_once "Comparator.php";
require_once "Logger.php";

try {
    $logger     = new Logger();
    $comparator = new Comparator([['url' => $_GET['url']]], $logger);
    $comparator->init();

    if (isset($_GET['compare-data'])) {
        $comparator->compareData = $_GET['compare-data'];
    }

    if (isset($_GET['compare-host']) && $_GET['compare-host']) {
        $comparator->compareHost = $_GET['compare-host'];
    }

    $comparator->doWork();

    $out = [
        'data'  => '',
        'error' => ''
    ];

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
