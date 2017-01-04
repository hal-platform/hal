import 'jquery';

module.exports = {
    target: '.js-toggle-container',
    checkTarget: '.js-pushable-deployment',
    toggled: false,
    $checks: null,
    init: function() {
        var $container = $(this.target);
        this.$checks = $(this.checkTarget);

        if ($container.length && this.$checks.length > 1) {
            this.attach($container);
        }
    },
    toggle: function() {
        this.toggled = !this.toggled;
        this.$checks.prop('checked', this.toggled);
    },
    attach: function($container) {
        var _this = this;
        var $toggler = $('<a href="#">Select All</a>');

        $toggler.click(function(e) {
            e.preventDefault();
            _this.toggle();
        });

        $container.html($toggler);
    }
};
