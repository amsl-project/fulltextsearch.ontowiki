$(document).ready(function () {

    // Whenever an Ajax request is about to be sent, show the loader
    $( document ).ajaxStart(function() {
        //loader.show();
        NProgress.start();
    });
    // hide loader when all request have finished
    $(document).ajaxStop(function() {
        //loader.hide();
        NProgress.done();
    });

    /**
     *  Filling the indexboxes with content
     */
    var indexBoxes = $('.indexBox');
    var numberOfIndexBoxes = indexBoxes.length;
    if (numberOfIndexBoxes > 0) {
        NProgress.configure({ trickle: false }); // disable auto increment of progressbar
    }

    indexBoxes.each(function () {
        var indexname = $('.indexname', this).text();
        var countDiv = $('.count', this);
        var indexBox = $(this);
        $.ajax({
            url: urlBase + 'fulltextsearch/countobjects',
            data: {indexname: indexname},
            dataType: 'json',
            success: function (count) {
                countDiv.html(buildString(count));
                NProgress.inc((1 / numberOfIndexBoxes) * 0.75); // inc progressbar
                indexBox.fadeIn({
                    duration: 300,
                    easing: 'easeInOutQuad'
                })
            }
        });

        $('.indexfooter', this).html('<a id="refresh">refresh view</a>, <a id="reindex">reindex</a> or <a id="delete">delete</a>');

        $('#reindex', this).click(function () {
            reindex(indexname, countDiv);
        });

        $('#delete', this).click(function () {
            deleteIndex(indexname, countDiv);
        });

        $('#refresh', this).click(function () {
            refreshView(indexname, countDiv);
        });

    });

});

function reindex(indexname, indexbox) {

    $.ajax({
        url: urlBase + 'fulltextsearch/reindex',
        data: {indexname: indexname}
    });
    refreshView(indexname, indexbox);
}

function refreshView(indexname, indexBox) {

    $.ajax({
        url: urlBase + 'fulltextsearch/countobjects',
        data: {indexname: indexname},
        dataType: 'json',
        beforeSend: function () {
            indexBox.css('color', 'lightgray');
        },
        success: function (count) {
            indexBox.html(buildString(count));
            indexBox.css('color', 'black');
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

function deleteIndex(indexname, indexbox) {
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
                        refreshView(indexname, indexbox);
                    }
                });
            },
            Cancel: function() {
                $( this ).dialog( "close" );
            }
        }
    });

}



