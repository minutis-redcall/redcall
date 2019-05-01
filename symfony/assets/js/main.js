
/*
    Enables jQuery global variables
 */
import $ from 'jquery';
window.jQuery = $;
window.$ = $;

/*
    Code taken from https://stackoverflow.com/a/7732379/731138
    Get an argument coming from the query string
 */
function qs(key) {
    key = key.replace(/[*+?^$.\[\]{}()|\\\/]/g, "\\$&");
    var match = location.search.match(new RegExp("[?&]" + key + "=([^&]+)(&|$)"));
    return match && decodeURIComponent(match[1].replace(/\+/g, " "));
}

/*
    Code taken from http://phpjs.org/functions/nl2br:480
    JavaScript equivalent for nl2br
 */
function nl2br(str, is_xhtml) {
    if (typeof str === 'undefined' || str === null) {
        return '';
    }
    var breakTag = (is_xhtml || typeof is_xhtml === 'undefined') ? '<br />' : '<br>';
    return (str + '').replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g, '$1' + breakTag + '$2');
}