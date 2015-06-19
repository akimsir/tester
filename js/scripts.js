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
                            'http://' + compareHostSets + '/' + v['url'] + '" placeholder="test url"/>' +
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
    })

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
            data:     {'url': input.val(), 'compare-data': compareData, 'compare-host': $('.compare-host').val()},
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

    $(".url-sets-block-open").click(function () {
        $(".url-sets").slideToggle(200);
        return false;
    })
});