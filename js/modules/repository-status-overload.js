define(['jquery'], function($) {
    return {
        parent: '.js-overloader-parent',
        overload: '.js-data-overload',

        init: function() {

            var $parent = $(this.parent);
            if ($parent.length > 0) {
                this.attachOverloader($parent);
            }
        },
        attachOverloader: function($parent) {
            var _this = this;

            var $overloader = $('<a href="#">Expand deployment details</a>');
            $overloader.click(function(event) {
                event.preventDefault();
                $(this).remove();
                $(_this.overload).show();
            });

            var $wrapper = $('<p class="header--sub"></p>');
            $wrapper.append($overloader);

            $parent.append($wrapper);
        },
    };
});
