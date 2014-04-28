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
    limit: 7 ,
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
    // $('twitter-typeahead')
    $('#searchtext-input.typeahead').typeahead(null, {
        name: 'best-matches',
        displayKey: 'title',
        source: titles.ttAdapter(),
        templates: {
            empty: [
                '<div class="empty-message">',
                '<strong>No results found</strong><p>Unable to find any results that match the current query</p?>',
                '</div>'
            ].join('\n'),
            suggestion: Handlebars.compile('<p><strong>{{title}}</strong><br><span class="hint--right" data-hint="{{highlightKey}}"><span class="uri-suggestion">{{{highlight}}}</span></span></p>')
        }
    })
        .on('typeahead:selected typeahead:autocompleted', function(e, datum) {
            console.log(datum);
            window.location = urlBase + 'resource/properties?r=' + encodeURIComponent(datum['uri']);
        });

    // hide inner labels on click
    $('input.inner-label').innerLabel().blur();
});