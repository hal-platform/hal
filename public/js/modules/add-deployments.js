define(['jquery'], function($) {
    return {
        target: '.js-newdeployment',
        init: function() {
            var _this = this;

            var $target = $(this.target);
            if ($target.length !== 0) {
                return _this.attach($target);
            }
        },
        attach: function($elem) {
            var _this = this;
            $elem.on('blur.deployment', function(event) {
                var $target = $(event.currentTarget);

                if ($target.val().length > 0) {
                    var $tr = $target.closest('tr');

                    // clone the entire row and clear the clone inputs
                    var $clone = $tr.clone();
                    $clone.find('input, select').val('');

                    // add the clone to the end of the row and attach event handlers
                    $tr.parent().append($clone);
                    _this.attach($clone.find(_this.target));

                    // only allow it to fire once
                    $target.off('blur.deployment');
                }
            });

        }
    };
});
