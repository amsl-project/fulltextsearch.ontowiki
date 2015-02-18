$(document).ready(function () {

    var loader = new CanvasLoader('loader');
    loader.setColor('#cf5300');
    loader.setDiameter(30);
    loader.show();

    $('.indexBox').each(function () {
        var indexname = $('.indexname', this).text();
        var countDiv = $('.count', this);
        var indexBox = $(this);
        $.ajax({
            url: urlBase + 'fulltextsearch/countobjects',
            data: {indexname: indexname},
            dataType: 'json',
            success: function (count) {
                countDiv.html(buildString(count));
                indexBox.fadeIn({
                    duration: 300,
                    easing: 'easeInOutQuad'
                })
            }
        });

        $('.indexfooter', this).html('<a id="refresh">refresh view</a>, <a id="reindex">reindex</a> or <a id="delete">delete</a>');

        $('#reindex', this).click(function () {
            reindex(indexname, countDiv, loader);
        });

        $('#delete', this).click(function () {
            deleteIndex(indexname, countDiv, loader);
        });

        $('#refresh', this).click(function () {
            refreshView(indexname, countDiv, loader);
        });

    });

    // hide loader when all request have finished
    $(document).ajaxStop(function() {
        loader.hide();
    });
});

function reindex(indexname, indexbox, loader) {

    $.ajax({
        url: urlBase + 'fulltextsearch/reindex',
        data: {indexname: indexname},
        beforeSend: function () {
            loader.show();
        }
    });
    refreshView(indexname, indexbox, loader);
}

function refreshView(indexname, indexBox, loader) {

    $.ajax({
        url: urlBase + 'fulltextsearch/countobjects',
        data: {indexname: indexname},
        dataType: 'json',
        beforeSend: function () {
            loader.show(); // Hidden by default
            indexBox.css('color', 'lightgray');
        },
        success: function (count) {
            indexBox.html(buildString(count));
            indexBox.css('color', 'black');
            loader.hide();
        }
    });
}

// transforms the result json to a html div container
function buildString(result) {
    var newHTML = [];
    $.each(result, function (classname, countValue) {
        newHTML.push('<div class="class-count-container">');
        newHTML.push('<div class="class-count-container-headline">');
        newHTML.push(classname);
        newHTML.push('</div><div class="class-count-container-content">count: <strong>');
        newHTML.push(countValue);
        newHTML.push('</strong></div></div>')
    });
    return newHTML.join("");
}

function deleteIndex(indexname, indexbox, loader) {
    loader.show();

    $( "#dialog-confirm" ).dialog({
        resizable: false,
        modal: true,
        buttons: {
            "Delete": function() {
                $( this ).dialog( "close" );
                $.ajax({
                    url: urlBase + 'fulltextsearch/deleteIndex',
                    data: {indexname: indexname},
                    success: function (result) {
                        console.log(result);
                        refreshView(indexname, indexbox, loader);
                    }
                });
            },
            Cancel: function() {
                $( this ).dialog( "close" );
                loader.hide();
            }
        }
    });

}



