define(['jquery', 'core/log'], function($, log) {
    return {
        init: function(elementId) {
            // Find your quickform element (Moodle prefixes element IDs with 'id_')
            var $myElement = $('#id_' + elementId);

            $myElement.on('click', function() {
                window.top.alert('Form element changed value to: ' + $(this).html());
                // Add your custom frontend logic here
            });
        }
    };
});