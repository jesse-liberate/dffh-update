define(["jquery", "profilefield_menuwithfreetext/chosen"], function ($) {
    return {
        initField: function (selector) {
            $(selector).chosen({
                width: '100%',
            });
        },
    };
});
