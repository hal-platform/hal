var $ = require('jquery');

exports.module = {
    interval: 5,
    mode: 'table', // "table" for global push table, "grid" for global push grid, "push" for individual push status page
    pendingClass: 'status-before--other',
    thinkingClass: 'status-before--thinking',
    successClass: 'status-before--success',
    failureClass: 'status-before--error',
    pushTarget: '[data-push]',

    init: function() {
        var _this = this;

        var $pushes = $(this.pushTarget);
        $pushes.each(function(index, item) {
            var $item = $(item);
            var status = $item.text().trim();

            if (status == 'Waiting' || status == 'Pushing') {
                $item
                    .removeClass(_this.pendingClass)
                    .addClass(_this.thinkingClass);

                _this.startUpdateTimer($item);
            }
        });

        return $pushes;
    },
    generateUrl: function(pushId, type) {
        if (type === 'api-update') {
            return '/api/pushes/' + pushId;
        }
    },
    checkStatus: function($elem) {
        var _this = this;
        var id = $elem.data('push');
        var endpoint = this.generateUrl(id, 'api-update');
        console.log(endpoint);

        // Requires these properties:
        // - id
        // - status
        // - start.text
        // - end.text
        $.getJSON(endpoint, function(data) {
            var currentStatus = data.status;
            $elem.text(currentStatus);

            // console.log('Push ' + id + ' status: ' + currentStatus);

            if (currentStatus == 'Waiting' || currentStatus == 'Pushing') {
                // If still pending, fire up a countdown for the next callback in the chain.
                _this.startUpdateTimer($elem);

            } else if (currentStatus == 'Success') {
                $elem
                    .removeClass(_this.thinkingClass)
                    .addClass(_this.successClass);

            } else {
                $elem
                    .removeClass(_this.thinkingClass)
                    .addClass(_this.failureClass);
            }

            if (_this.mode == 'push') {
                _this.updatePush(data, $elem);
            } else if (_this.mode == 'table') {
                _this.updateTable(data, $elem);
            } else if (_this.mode == 'grid') {
                _this.updateGrid(data, $elem);
            }
        });
    },
    startUpdateTimer: function($elem) {
        var _this = this;

        var timer = window.setTimeout(function() {
            _this.checkStatus($elem);
        }, _this.interval * 1000);
    },
    updatePush: function(data, $elem) {
        var $container = $elem.closest('dl');

        if (data.start !== null) {
            var $start = $container.children('.js-push-start');
            if ($start.length > 0 && $start.children('time').length === 0) {
                $start.html('<time datetime="' + data.start.datetime + '"></time>');
            }
        }

        if (data.end !== null) {
            var $end = $container.children('.js-push-end');
            if ($end.length > 0 && $end.children('time').length === 0) {
                $end.html('<time datetime="' + data.end.datetime + '"></time>');
            }
        }
    },
    updateTable: function(data, $elem) {
        // derp
    },
    updateGrid: function(data, $elem) {
        // derp
    }
};
