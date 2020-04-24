import $ from 'jquery';


$(document).ready(function() {
    //Disables button that has data-timeout-disabled attribute. The value passed will be used for the duration of disable state
    $('input[type="submit"][data-timeout-disabled], button[data-timeout-disabled]').each(function(ind, element) {
        if(typeof $(element).data('timeout-disabled') !== 'undefined') {
            $(element).click(function() {
                var $el = $(this);
                //Disable button
                //Set timeout is set at 0 to prevent the submit of a form
                setTimeout(function() {
                    $el.prop('disabled', true);
                    $el.addClass('disabled');
                }, 0);

                //Enable button
                setTimeout(function() {
                    $el.prop('disabled', false);
                    $el.removeClass('disabled');
                }, 30000);
            })
        }
    });
});