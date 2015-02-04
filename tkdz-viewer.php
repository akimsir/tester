<?php
/**
 * Created by Akim.
 * Date: 25.06.14
 * Time: 9:59
 */
?>
<html>
<head>
    <script type="application/javascript" src="/js/jquery-1.11.1.min.js"></script>
    <style>
        body {
            font-family: Tahoma, Verdana, Arial, sans-serif;
        }
        .compare-data textarea {
            width: 800px;
        }
        .compare-data {
            display: none;
        }
        .url-block {
            padding-bottom: 15px;
        }
    </style>
</head>
<body>
    <h2>Сравнение ТКДЗ продакшина с указанным урлом</h2><br/>

    <a href="http://c2n.me/inQ8v5.png">Help!!! </a><br/>
    Testing sets:<a href="#" data-cmd="get-search-urls" class="command">search urls</a>
    <a href="#" data-cmd="get-advert-urls" class="command">advert urls</a><br/><br/>

    <input type="button" value="check" class="do-check" />
    <input placeholder="Хост для сравнения" class="compare-host"> <small>по умолчанию krisha.kz</small> <br/><br/>

    <div class="url-block">
        <a class="compare-with" style="font-size: 11px;margin-bottom: 5px;" href="#">Compare with text</a><br/>
        <div class="compare-data">
            <textarea class="title" placeholder="Title"></textarea><br/>
            <textarea class="description" placeholder="Description"></textarea><br/>
            <textarea class="h1" placeholder="h1"></textarea>
        </div>
        <input type="text" style="width:500px" placeholder="test url"/>
        <div class="result">

        </div>
    </div>
    <br/>
<a href="#" class="add-url">add one url</a>




<script>
    $(document).ready(function(){

        $(".compare-with").click(function(){
            $(this).closest('.url-block').find('.compare-data').toggle();
            return false;
        });

        $(".command").click(function() {

            var
                urlBlock = $(".url-block").last(),
                $this    = $(this);

            $.ajax({
                url     : 'tkdz-viewer-ajax.php',
                data    : {'cmd': $this.data('cmd')},
                dataType: 'json',
                success : function (response) {
                    if (typeof (response['data']) != 'undefined') {
                        $.each(response['data'], function(i, v){
                            urlBlock.after('<div class="url-block"><input type="text" value="'+v['url']+
                                '"style="width:500px" placeholder="test url"/><div style="width: 600px" class="result"></div></div>'
                            );
                        });
                    }
                }
            });

            return false;
        });

        $(".add-url").click(function(){
            var urlBlock = $(".url-block").last();
            var newUrlBlock = $('<div class="url-block">' + urlBlock.html() + '</div>');
            urlBlock.after(newUrlBlock);
            $(".compare-with", newUrlBlock).click(function(){
                $(this).closest('.url-block').find('.compare-data').toggle();
                return false;
            });
            return false;
        });

        $(".do-check").click(function () {
            var firstBlock = $(".url-block:first");
            sendRequest(firstBlock, firstBlock.next('.url-block:first'));

            return false;
        });

        function sendRequest(block, nextBlock)
        {
            var input = $('input', block);
            var resultBlock = $('.result', block);
            var compareData = {};

            if ($('.compare-data', block).is(':visible')) {
                $('.compare-data textarea', block).each(function(){
                    compareData[$(this).attr('class')] = $(this).val();
                });
            }

            block.prepend('<img src="/i/loading.gif" class="loading-progress" />');

            $.ajax({
                url    : 'tkdz-viewer-ajax.php',
                data   : {'url' : input.val(), 'compare-data':compareData, 'compare-host':$('.compare-host').val()},
                dataType:'json',
                success: function (response) {
                    if (typeof (response['data']) != 'undefined') {
                        if (response['error']) {
                            resultBlock.html('<div style="color:red">' + response['error'] + '</div>');
                        } else {
                            resultBlock.html(response['data']);
                        }

                        $('.loading-progress', block).remove();
                        if (nextBlock.length) {
                            sendRequest(nextBlock, nextBlock.next('.url-block'));
                        }
                    }
                }
            });
        }
    })


</script>
</body>
</html>


