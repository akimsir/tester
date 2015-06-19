$(document).ready(function () {

    $(".compare-with").click(function () {
        $(this).closest('.url-block').find('.compare-data').toggle();

        return false;
    });

    $(".command").click(function () {
        var
            urlBlock = $(".url-block").last(),
            $this = $(this),
            compareHostSets = $(".compare-host-sets").val().trim();

        if (!compareHostSets) {
            compareHostSets = 'krisha.ak.dev';
        }

        if (!/http/.test(compareHostSets)) {
            compareHostSets = 'http://' + compareHostSets
        }

        compareHostSets = trimSlashes(compareHostSets);

        $.ajax({
            url:      'tkdz-viewer-ajax.php',
            data:     {'cmd': $this.data('cmd')},
            dataType: 'json',
            success:  function (response) {
                if (typeof (response['data']) != 'undefined') {
                    urlBlock.hide();
                    $.each(response['data'], function (i, v) {
                        urlBlock.after(
                            '<div class="url-block"><input type="text" class="test-url" value="' +
                            compareHostSets + '/' + v['url'] + '" placeholder="test url"/>' +
                            '<div style="width: 600px" class="result"></div></div>'
                        );
                    });
                } else {
                    alert("response['data'] is undefined");
                }
            },
            error:    function (jqXHR, textStatus, errorThrown) {
                alert("Неправильный формат ответа");
                console.debug(jqXHR, textStatus, errorThrown);
            }
        });

        return false;
    });

    $(".add-url").click(function () {
        var urlBlock = $(".url-block").last();
        var newUrlBlock = $('<div class="url-block">' + urlBlock.html() + '</div>');
        urlBlock.after(newUrlBlock);
        $(".compare-with", newUrlBlock).click(function () {
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

    $(".url-block input").keypress(function (event) {
        if (event.keyCode == 13) {
            $(".do-check").click();
        }
    });

    $(".url-sets-block-open").click(function () {
        $(".url-sets").slideToggle(200);
        return false;
    });

    $(".settings-open").click(function () {
        $(".settings").slideToggle(200);
        return false;
    });

    onChange($('.compare-host'), function () {
        $(".current-compare-host").text($(this).val());
    });

    canonicalChange($('.expected-canonical'), $('.current-canonical'));
});


function canonicalChange($expected, $current) {
    onChange($current, function () {
        setCanonicalText($current.val(), $expected.val());
    });

    onChange($expected, function () {
        setCanonicalText($current.val(), $expected.val());
    });
}

function setCanonicalText(currentVal, expectedVal) {
    $(".canonical-settings").html(
        'Хосты канонкиала <span>' + currentVal + '</span> и <span>' + expectedVal +
        '</span> при сравнении будут считаться одинаковыми'
    );
}


function onChange($element, cbFunction) {
    $element.keyup(cbFunction);
}

function sendRequest(block, nextBlock) {
    var input = $('input', block);
    var resultBlock = $('.result', block);
    var compareData = {};

    if ($('.compare-data', block).is(':visible')) {
        $('.compare-data textarea', block).each(function () {
            compareData[$(this).attr('class')] = $(this).val();
        });
    }

    block.prepend('<img src="/i/loading.gif" class="loading-progress" />');

    $.ajax({
        url:      'tkdz-viewer-ajax.php',
        data:     {
            'url':            input.val(),
            'compare-data':   compareData,
            'compare-host':   $('.compare-host').val(),
            'canonical-hosts': {
                'current':  processHttp($('.current-canonical').val()),
                'expected': processHttp($('.expected-canonical').val())
            }
        },
        dataType: 'json',
        success:  function (response) {
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

function trimSlashes(string) {
    return string.replace(/^\/+|\/+$/gm, '');
}

function processHttp(url)
{
    url = url.trim();

    if (url && !/http/.test(url)) {
        url = 'http://' + url
    }

    return url;
}