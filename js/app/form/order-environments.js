var $ = require('jquery');

exports.module = {
    target: '#env-reorder',
    $currentTarget: null,
    init: function() {
        var _this = this;

        var parent = $(this.target);
        this.$currentTarget = parent.find('tbody')
            // enable sorting
            .sortable({
                items: 'tr',
                handle: 'td:first-child',
                colspan: 3
            })
            .on('sortupdate', function(event) {
                _this.updateOrders();
            });

    },
    updateOrders: function() {
        this.$currentTarget.find('input').each(function(index, child) {
            $(child).val(index + 1);
        });
    }
};
