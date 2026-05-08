define(['jquery', 'profilefield_multiselect/chosen'], function($) {
    return {
        initField: function(selector) {
            $(selector).chosen({
                width: '100%',
            });
        },
    };
});
