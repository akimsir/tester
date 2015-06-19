<html>
<head>
    <script type="application/javascript" src="/js/jquery-1.11.1.min.js"></script>
    <script type="application/javascript" src="/js/scripts.js"></script>
    <link href="css.css" media="screen" rel="stylesheet" type="text/css"/>
</head>
<body>
<div class="gray-block">
    <h2 style="display: inline">Сравнение ТКДЗ продакшина с указанным тестовым сайтом</h2>
    <a class="help" title="Помощь" href="http://c2n.me/3bUcjLj.png"></a>
</div>

<div class="content">
    <a class="flink url-sets-block-open" href="#">Использовать раннее сравниваемые урлы</a>

    <div class="url-sets">
        <br/>
        <input placeholder="Хост для тестовых урлов" class="compare-host-sets">
        <a href="#" data-cmd="get-search-urls" title="Набор сравниваемых ссылок при поиске обьявлений" class="command">search
            urls</a>
        <a href="#" data-cmd="get-advert-urls" title="Набор сравниваемых ссылок страниц обьявлений" class="command">advert
            urls</a>
    </div>

    <br/><br/>
    <input type="button" value="Начать сравнение" class="do-check"/>
    с
    <input placeholder="Хост для сравнения" class="compare-host">
    <small>по умолчанию krisha.kz</small>
    <br/><br/>

    <div class="url-block">
        <a class="compare-with flink" style="font-size: 11px;margin-bottom: 5px;" href="#">Задать текст</a>
        <br/>

        <div class="compare-data">
            <textarea class="title" placeholder="Title"></textarea><br/>
            <textarea class="description" placeholder="Description"></textarea><br/>
            <textarea class="h1" placeholder="h1"></textarea>
        </div>
        <input class="test-url" type="text" placeholder="Введите урл для сравнения"/>

        <div class="result">

        </div>
    </div>
    <br/>
    <a href="#" title="Добавить ещё одно поле ввода для урла" class="flink add-url">Добавить поле</a>
</div>
</body>
</html>
