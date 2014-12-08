define(['jquery'], function($) {
    return {
        interval: 5,
        mode: 'table', // "table" for global build table, "build" for individual build status page
        pendingClass: 'status-before--other',
        thinkingClass: 'status-before--thinking',
        successClass: 'status-before--success',
        failureClass: 'status-before--error',
        buildTarget: '[data-build]',

        init: function() {
            var _this = this;

            var $builds = $(this.buildTarget);
            $builds.each(function(index, item) {
                var $item = $(item);
                var status = $item.text().trim();

                if (status == 'Waiting' || status == 'Building') {
                    $item
                        .removeClass(_this.pendingClass)
                        .addClass(_this.thinkingClass);

                    _this.startUpdateTimer($item);
                }
            });

            return $builds;
        },
        generateUrl: function(buildId, type) {
            if (type === 'api-update') {
                return '/api/builds/' + buildId;

            } else if (type === 'push') {
                return '/builds/' + buildId + '/push';
            }
        },
        checkStatus: function($elem) {
            var _this = this;
            var id = $elem.data('build');
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

                // console.log('Build ' + id + ' status: ' + currentStatus);

                if (currentStatus == 'Waiting' || currentStatus == 'Building') {
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

                if (_this.mode == 'table') {
                    _this.updateTable(data, $elem);
                } else {
                    _this.updateBuild(data, $elem);
                }
            });
        },
        startUpdateTimer: function($elem) {
            var _this = this;

            var timer = window.setTimeout(function() {
                _this.checkStatus($elem);
            }, _this.interval * 1000);
        },
        updateBuild: function(data, $elem) {
            var $container = $elem.closest('dl');
            var $hdr;

            if (data.status == 'Success') {
                // Add push link if present
                $('.js-build-push')
                    .html('<a class="btn--action" href="' + this.generateUrl(data.id, 'push') + '">Push Build</a>');

                // Replace success messaging
                $hdr = $('[data-success]');
                if ($hdr.length == 1) {
                    $hdr.text($hdr.data('success'));
                }

            } else if (data.status == 'Error') {
                // Replace success messaging
                $hdr = $('[data-failure]');
                if ($hdr.length == 1) {
                    $hdr.text($hdr.data('failure'));
                }
            }

            if (data.start !== null) {
                $container
                    .children('.js-build-start')
                    .text(data.start.text);
            }

            if (data.end !== null) {
                $container
                    .children('.js-build-end')
                    .text(data.end.text);
            }
        },
        updateTable: function(data, $elem) {
            var $container = $elem.closest('tr');

            if (data.status == 'Success') {
                // Add push link if present
                $container
                    .children('.js-build-push')
                    .html('<a href="' + this.generateUrl(data.id, 'push') + '">Push</a>');
            }
        }
    };
});
