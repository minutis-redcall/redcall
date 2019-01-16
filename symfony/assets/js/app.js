import $ from '../js/jquery.min.js';

(function() {
    var lastAnswerId = 0;
    var answers = $('#answers');

    function addAnswer() {
        $('#answers').append($('#answer-prototype').html());
        var formGroup = $('#answers .form-group').last();
        formGroup.attr('id', 'answer_id_' + lastAnswerId);
        formGroup.attr('data-answer-index', lastAnswerId);

        var prototypeInput = $('#answer-prototype input');
        var prototypeId = prototypeInput.attr('data-prototype-id');
        var prototypeValue = prototypeInput.attr('data-prototype-value');

        var input = $('#answers .form-group input').last();
        input.attr('id', prototypeId + '_' + lastAnswerId);
        input.attr('value', prototypeValue + ' ' + lastAnswerId);

        var close = $('#answers .form-group .input-group-text').last();
        close.attr('data-answer-id', 'answer_id_' + lastAnswerId);

        updatePreview();
        lastAnswerId++;
    }

    function removeAnswer(answerId) {
        $('#' + answerId).remove();
        updatePreview();
    }

    function updatePreview() {
        var textArea = $('#card-message textarea');
        var content = '<p>' + textArea.val() + '</p>';
        $('#answers .form-group').each(function () {
            var input = $(this).find('input');
            content += '<p>' + $(this).attr('data-answer-index') + ' : ' + input.val() + '</p>';

        });

        $('#preview').html(content);
    }

    addAnswer();
    addAnswer();

    $('#add-answer').click(function () {
        addAnswer();
    });

    $('#answers .input-group-text').click(function () {
        removeAnswer($(this).attr('data-answer-id'));
    });

    $('.preview-control').keyup(function () {
        updatePreview();
    });

    $('#send-communication').click(function () {
        if ($(this).is(':checked')) {
            $('#card-message').show();
        } else {
            $('#card-message').hide();
        }
    })
})();