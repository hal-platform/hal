define(['jquery'], function($) {
    return {
        parent: '.js-overloader-parent',
        overloaded: '.js-data-overload',

        $overloaded: null,
        $overloader: null,
        status: false,

        expandText: 'Expand deployment details',
        collapseText: 'Collapse deployment details',

        init: function() {

            var $parent = $(this.parent);
            if ($parent.length > 0) {
                this.$overloaded = $(this.overloaded);
                this.attachOverloader($parent);
            }
        },
        attachOverloader: function($parent) {
            var _this = this;

            this.$overloader = $('<a href="#">' + this.expandText + '</a>');

            var $wrapper = $('<p class="header--sub"></p>');
            $wrapper.append(this.$overloader);
            $parent.append($wrapper);

            this.$overloader.on('click', function(event) {
                _this.overloadToggler();
                event.preventDefault();
            });
        },
        overloadToggler: function() {

            var text = this.collapseText;
            if (this.status) {
                text = this.expandText;
            }

            this.$overloader.text(text);
            if (this.status) {
                this.$overloaded.hide();
            } else {
                this.$overloaded.show();
            }

            this.status = !this.status;
        }
    };
});
