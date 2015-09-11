/**
 * Javascript to insert the field tags into the textarea.
 * Used when editing a data template
 */
require(['jquery'], function($) {
    // JQuery is available via $
    $( document ).ready(function() {
        $('.editable').jinplace();
    });
});
