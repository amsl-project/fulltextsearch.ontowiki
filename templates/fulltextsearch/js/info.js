$(document).ready(function () {

    var pathname = window.location.pathname;

    // make sure we are in the right controller
    if (pathname == "/OntoWiki/fulltextsearch/info") {

        var $indices = $('#indices');

        // initialize grid layout with masonry
        $indices.masonry({
            columnWidth: 400,
            itemSelector: '.indexBox',
            transitionDuration: '0.2s',
            "gutter": 10,
            visibleStyle: {opacity: 1},
            hiddenStyle: {opacity: 0}
        });

        $.ajax({
            url: urlBase + 'fulltextsearch/availableindices',
            dataType: 'json',
            success: function (models) {
                console.log(models);


                $.each(models, function (indexname, value) {
                    var countDiv;
                    $.ajax({
                        url: urlBase + 'fulltextsearch/countobjects',
                        data: {indexname: indexname},
                        dataType: 'json',
                        success: function (count) {

                            var indexBox = $('<div class="indexBox"><div class="indexname">'
                            + indexname
                            + '</div><div class="count">'
                            + buildString(count)
                            + '</div><div class="indexfooter">'
                            + '<a id="refresh">refresh view</a>, <a id="reindex">reindex</a> or <a id="delete">delete</a>'
                            + '</div></div>');

                            //countDiv.html(buildString(count));
                            NProgress.inc((1 / Object.keys(models).length) * 0.75); // inc progressbar
                            $indices.append(indexBox).masonry('appended', indexBox);

                            countDiv = indexBox.find(".count");

                            indexBox.find("#reindex").click(function () {
                                reindex(indexname, countDiv);
                            });

                            indexBox.find("#delete").click(function () {
                                deleteIndex(indexname, countDiv);
                            });

                            indexBox.find("#refresh").click(function () {
                                refreshView(indexname, countDiv);
                            });
                        }
                    });
                });
            }
        });
    }
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
    $("#dialog-confirm").dialog({
        resizable: false,
        modal: true,
        buttons: {
            "Delete": function () {
                $(this).dialog("close");
                $.ajax({
                    url: urlBase + 'fulltextsearch/deleteIndex',
                    data: {indexname: indexname},
                    success: function (result) {
                        refreshView(indexname, indexbox);
                    }
                });
            },
            Cancel: function () {
                $(this).dialog("close");
            }
        }
    });

}



