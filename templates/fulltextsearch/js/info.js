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
                // $("#create-index-response").text(msg);
                $("#create-index-response").text('OK ✔');
            }
        });   
    });
});

