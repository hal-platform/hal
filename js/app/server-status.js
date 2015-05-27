var $ = require('jquery');

module.exports = {
    target: '[data-server-status]',

    successClass: 'status-icon--success',
    failureClass: 'status-icon--error',
    infoClass: 'status-icon--info',

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
            info = this.infoClass;

        $.getJSON(endpoint, function(data) {
            console.log('server: ' + data.id + ', status: ' + data.status);

            if (data.status == 'up') {
                $elem
                    .addClass(success)
                    .removeClass(info)
                    .prop('title', 'The server is up!');

            } else if (data.status == 'down') {
                $elem
                    .addClass(failure)
                    .removeClass(info)
                    .prop('title', 'Cannot connect to server. It may be down.');
            }
        });
    }
};
