var $ = require('jquery');

module.exports = {
    target: '[data-expand-btn]',
    rowTarget: '[data-expand-target]',

    init: function() {
        // hide all data
        $(this.rowTarget).hide();

        $(this.target).each(function (index, btn) {
            $(btn).on('click', function(event) {
                var id = $(btn).data('expand-btn');

                $('[data-expand-target=' + id + ']').toggle();

                event.preventDefault();
            });
        });
    }
};
