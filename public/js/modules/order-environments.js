define(['jquery', 'jquerySortable'], function($) {
    return {
        target: '#env-reorder',
        $currentTarget: null,
        init: function() {
            var _this = this;

            var parent = $(this.target)
                // hide second columns
                .find('thead th:nth-child(2)').addClass('js-sortable-hidden').end()
                .find('tbody td:nth-child(2)').addClass('js-sortable-hidden').end();

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
});
