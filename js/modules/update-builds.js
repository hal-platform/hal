define(['jquery'], function($) {
    return {
        interval: 5000,
        pendingClass: 'status-before--other',
        thinkingClass: 'status-before--thinking',
        successClass: 'status-before--success',
        failureClass: 'status-before--error',
        init: function() {
            var _this = this;
            var $builds = $('[data-build]');

            // builds
            $builds.each(function(index, item) {
                var $item = $(item);
                var status = $item.text();

                if (status == 'Waiting' || status == 'Building') {
                    // add some kind of spinner to the element here?
                    $item
                        .removeClass(_this.pendingClass)
                        .addClass(_this.thinkingClass);

                    _this.startUpdateTimer($item);
                }
            });
        },
        checkStatus: function($elem) {
            var _this = this;
            var id = $elem.data('build');

            $.getJSON('/api/build/' + id, function(data) {
                var currentStatus = data.content.status;
                $elem.text(currentStatus);

                // console.log('Build ' + id + ' status: ' + currentStatus);

                if (currentStatus == 'Waiting' || currentStatus == 'Building') {
                    // If still pending, fire up a countdown for the next callback in the chain.
                    _this.startUpdateTimer($elem);

                } else if (currentStatus == 'Success') {
                    $elem
                        .removeClass(_this.thinkingClass)
                        .addClass(_this.successClass);

                    // Add push link if present
                    $elem
                        .closest('tr')
                        .children('.js-build-push')
                        .html('<a href="/build/' + id + '/push">Push</a>');

                } else {
                    $elem
                        .removeClass(_this.thinkingClass)
                        .addClass(_this.failureClass);
                }

                // Add end time if present
                $elem
                    .closest('tr')
                    .children('.js-build-date')
                    .text(data.content.end.text);

            });
        },
        startUpdateTimer: function($elem) {
            var _this = this;

            var timer = window.setTimeout(function() {
                _this.checkStatus($elem);
            }, _this.interval);
        }
    };
});
