$(document).ready(function() {
    $('form[name=create-index]').submit(function(e) {
        e.preventDefault();
        $("#create-index-response").text('creating index ...');
        $.ajax({
            type: 'POST',
            cache: false,
            url: urlBase + 'fulltextsearch/createindex',
            data: $(this).serialize(),
            success: function(msg) {
                if (strStartsWith(msg, 'true')) {
                    $("#create-index-response").text('OK ✔');
                } else {
                    $("#create-index-response").text(msg);
                }
                location.reload();
            }
        });
    });
});

function strStartsWith(str, prefix) {
    return str.indexOf(prefix) === 0;
}