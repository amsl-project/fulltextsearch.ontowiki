/**
 * This file is part of the {@link http://ontowiki.net OntoWiki} project.
 *
 * @copyright Copyright (c) 2013, {@link http://aksw.org AKSW}
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 */

/**
 * This function evaluates the regex for the typeahead plugin.
 * @param  {[String]} strs The pool of strings
 */
var substringMatcher = function(strs) {
    return function findMatches(q, cb) {
        var matches, substringRegex;
        // an array that will be populated with substring matches
        matches = [];
        // regex used to determine if a string contains the substring `q`
        substrRegex = new RegExp(q, 'i');
        // iterate through the pool of strings and for any string that
        // contains the substring `q`, add it to the `matches` array
        $.each(strs, function(i, str) {
            if (substrRegex.test(str)) {
                // the typeahead jQuery plugin expects suggestions to a
                // JavaScript object, refer to typeahead docs for more info
                matches.push({
                    value: str
                });
            }
        });
        cb(matches);
    };
};

/**
 * Twitter Typehead tokenizer initialization.
 */
var titles = new Bloodhound({
    datumTokenizer: function(d) {
        return Bloodhound.tokenizers.whitespace(d.value);
    },
    queryTokenizer: Bloodhound.tokenizers.whitespace,
    limit: 7,
    remote: {
        url: '/OntoWiki/fulltextsearch/fulltextsearch?query=%QUERY',
        ajax: {
            type: "POST"
        }
    }
});
titles.initialize();

/**
 * Now the existing search field will be reused for the new search function.
 * To make sure the old search function is not interfering with the new one,
 * all existing event handlers are removed/unbound from the search field and
 * the new class typeahead is added.
 */
$(document).ready(function() {
    $('#searchtext-input').off().unbind().addClass('typeahead');

    // dynamically set input value to value of search text. 
    // This is needed to search for input if enter was pressed.
    $("#searchtext-input").on("input", function() {
        input = $('#searchtext-input').val();
        $("#actual-input").text(input);
    });


    // every result gets a paragraph containing the title and a visualization of the
    // the part elasticsearch has matched (highlight)
    var source = '<p>';
    source += '<strong class="highlight-title">{{title}}</strong><br>';
    source += '<span class="hint--bottom" data-hint="{{highlightKey}}">';
    source += '<span class="uri-suggestion">{{{highlight}}}</span></span>';
    source += '</p>';

    $('#searchtext-input.typeahead').typeahead(null, {
        name: 'best-matches',
        displayKey: 'title',
        source: titles.ttAdapter(),
        templates: {
            empty: [
                '<div class="empty-message">',
                '<strong>No results found</strong><p>Press <em>enter</em> to trigger an advanced search.</p?>',
                '</div>'
            ].join('\n'),
            suggestion: Handlebars.compile(source)
        }
    })
        .on('typeahead:selected typeahead:autocompleted', function(event, datum) {
            // if a autocomplete-generated result is selected the user will be directed there directly
            window.location = urlBase + 'resource/properties?r=' + encodeURIComponent(datum['uri']);
        })
        .on('change', function(event, datum) {
            // if no autocomplete-generated result is selected the advanced search is triggered after
            // the enter button has been pressed
            $('#searchtext-input').keyup(function(event) {
                var keycode = (event.keyCode ? event.keyCode : event.which);
                if (keycode == '13') {
                    window.location = urlBase + 'fulltextsearch/search?input=' + input;
                };
            });
        });

    // hide inner labels on click
    $('input.inner-label').innerLabel().blur();

});

/**
 * Show the result as json.
 */
$(document).ready(function() {
    $("#show-json-result").click(function() {
        $("#json-result").toggle("slow", function() {
            $("#json-result").is(":visible") ? $('#show-json-result').text('[-] Show results as JSON') : $(
                '#show-json-result').text('[+] Show results as JSON');
        });
    });
});