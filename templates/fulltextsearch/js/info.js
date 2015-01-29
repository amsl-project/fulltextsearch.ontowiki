$(document).ready(function () {
    $('form[name=create-index]').submit(function (e) {
        e.preventDefault();
        $("#create-index-response").text('creating index ...');
        $.ajax({
            type: 'POST',
            cache: false,
            url: urlBase + 'fulltextsearch/createindex',
            data: $(this).serialize(),
            success: function (msg) {
                $("#create-index-response").text('OK ✔');
                location.reload();
            },
            error: function (jqXHR, textStatus, errorThrown) {
                $("#create-index-response").text(textStatus);
            },
            complete: function (jqXHR, textStatus) {
            }
        });
    });

    $('.indexBox').each(function () {
        var indexname = $('.indexname', this).text();
        var count = jQuery.parseJSON(retrieveCount(indexname, 'http://vocab.ub.uni-leipzig.de/bibrm/Budget'));
        var countDiv = $('.count', this);

        // transforming the result into html containers
        var newHTML = [];
        $.each(count, function (classname, countValue) {
            newHTML.push('<div class="class-count-container">');
            newHTML.push('<div class="class-count-container-headline">');
            newHTML.push(classname);
            newHTML.push('</div><div class="class-count-container-content">count: <strong>');
            newHTML.push(countValue);
            newHTML.push('</strong></div></div>')
        });

        countDiv.html(newHTML.join(""));

        $('.indexfooter', this).html('<a id="reindex">reindex</a> or <a id="delete">delete</a>');

        $('#reindex', this).click(function () {
            reindex(indexname);
        });

        $('#delete', this).click(function () {
            deleteIndex(indexname);
        });

    });
});

function strStartsWith(str, prefix) {
    return str.indexOf(prefix) === 0;
}


function retrieveCount(indexname, classname) {
    return $.ajax({
        url: urlBase + 'fulltextsearch/countobjects',
        data: {indexname: indexname, classname: classname},
        async: false
    }).responseText;
}

function reindex(indexname) {
    alert(indexname);
}

function deleteIndex(indexname) {
    alert(indexname);
}


