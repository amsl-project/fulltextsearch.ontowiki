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
        url: urlBase + 'fulltextsearch/fulltextsearch?query=%QUERY',
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
    var input = $('#searchtext-input');
    input.off().unbind().addClass('typeahead');
    // dynamically set input value to value of search text. 
    // This is needed to search for input if enter was pressed.
    input.on("input", function() {
        input = $('#searchtext-input').val();
        $("#actual-input").text(input);
    });
    // every result gets a paragraph containing the title and a visualization of the
    // the part elasticsearch has matched (highlight)
    var source = '<p>';
    source += '<strong class="highlight-title">{{title}}</strong><br><span class="origin-index">{{{originIndex}}}</span><br>';
    source += '<span class="hint--bottom" data-hint="{{highlightKey}}">';
    source += '<span class="uri-suggestion">{{{highlight}}}</span>';
    source += '</span>';
    source += '</p>';
    var noResults = 'No results found';
    var trigger = 'Press enter to trigger an advanced search';
    // indices
    var indices = 'bibo:periodical,bibrm:contractitem';
    $('#searchtext-input.typeahead').typeahead(null, {
        name: 'best-matches',
        displayKey: 'title',
        source: titles.ttAdapter(),
        templates: {
            empty: ['<div class="empty-message">', '<strong>' + noResults + '</strong><p>' + trigger + '</p?>', '</div>'].join('\n'),
            suggestion: Handlebars.compile(source),
            footer: '<div class="empty-message">Maximal 7 results are shown. Press Enter to see all.</div>'
        }
    }).on('typeahead:selected typeahead:autocompleted', function(event, datum) {
        // if a autocomplete-generated result is selected the user will be directed there directly
        window.location = urlBase + 'resource/properties?r=' + encodeURIComponent(datum['uri']);
    }).on('change', function(event, datum) {
        // if no autocomplete-generated result is selected the advanced search is triggered after
        // the enter button has been pressed
        $('#searchtext-input').keyup(function(event) {
            var keycode = (event.keyCode ? event.keyCode : event.which);
            if (keycode == '13') {
                window.location = urlBase + 'fulltextsearch/search?input=' + input + '&from=0';
            }
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
        $("#json-result").slideToggle("slow", function() {
            $("#json-result").is(":visible") ? $('#show-json-result').text('[\u2212] ' + hideResults) : $('#show-json-result').text('[+] ' + showAsJson);
        });
    });
    $("#show-query").click(function() {
        $("#query").slideToggle("slow", function() {
            $("#query").is(":visible") ? $('#show-query').text('[\u2212] ' + hideQuery) : $('#show-query').text('[+] ' + showQuery);
        });
    });
    $("#show-filter").click(function() {
        $("#filter-list").slideToggle("slow", function() {
            $("#filter-list").is(":visible") ? $('#show-filter-btn').text(hideFilter) : $('#show-filter-btn').text(showFilter);
        });
    });
    // cycle through the filter checkboxes and build the filter URI
    $("#filter-apply-btn").click(function() {
        var chkArray = [];
        // look for all checkboxes check if it was checked
        $("#filter-list input:checked").each(function() {
            chkArray.push($(this).val());
        });
        // we join the array separated by the comma and remove the last comma
        var selected;
        selected = chkArray.join(',') + ",";
        selected = selected.substr(0, selected.length - 1);
        // append parameters to href of button link
        var _href = $("#filter-apply-btn").attr("href");
        $("#filter-apply-btn").attr("href", _href + '&indices=' + selected + '&from=0');
    });
});
// if the indics parameter is missing or empty all indices have been searched. 
// to prevent confusion about the not-checked checkboxes, they need to be checked. 
$(document).ready(function() {
    if (getUrlParameter('indices') === undefined) {
        $("#filter-list input").each(function() {
            $(this).attr("checked", true);
        });
    }
});
// since jQuery does not come with a function to get URL parameters we
// have to do that by ourselves
function getUrlParameter(sParam) {
    var sPageURL = window.location.search.substring(1);
    var sURLVariables = sPageURL.split('&');
    for (var i = 0; i < sURLVariables.length; i++) {
        var sParameterName = sURLVariables[i].split('=');
        if (sParameterName[0] == sParam) {
            return sParameterName[1];
        }
    }
}