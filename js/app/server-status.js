var $ = require('jquery');

exports.module = {
    target: '[data-server-status]',

    successClass: 'status-before--success',
    failureClass: 'status-before--error',
    unknownClass: 'status-before--unknown',

    $servers: null,

    minDelay: 200,
    maxDelay: 4000,

    init: function() {
        this.$servers = $(this.target);
        if (this.$servers.length !== 0) {
            this.bombsAway();
        }
    },

    bombsAway: function() {
        var checkStatus = this.checkStatus.bind(this),
            $servers = this.$servers,
            minDelay = this.minDelay,
            maxDelay = this.maxDelay;

        var delayedUpdate = function(elem, delay) {
            return window.setTimeout(function() {
                checkStatus(elem);
            }, delay);
        };

        $servers.each(function() {
            var delay = Math.round(Math.random() * (maxDelay - minDelay)) + minDelay;
            delayedUpdate(this, delay);
        });
    },

    checkStatus: function(elem) {
        var $elem = $(elem),
            id = $elem.data('server-status'),
            endpoint = '/admin/server-status/' + id,
            failure = this.failureClass,
            success = this.successClass,
            unknown = this.unknownClass;

        $.getJSON(endpoint, function(data) {
            console.log('server: ' + data.id + ', status: ' + data.status);

            if (data.status == 'up') {
                $elem
                    .addClass(success)
                    .removeClass(unknown)
                    .prop('title', 'The server is up!');

            } else if (data.status == 'down') {
                $elem
                    .addClass(failure)
                    .removeClass(unknown)
                    .prop('title', 'Cannot connect to server. It may be down.');
            }
        });
    }
};
