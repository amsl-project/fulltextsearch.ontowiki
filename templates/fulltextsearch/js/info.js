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
});

function strStartsWith(str, prefix) {
    return str.indexOf(prefix) === 0;
}