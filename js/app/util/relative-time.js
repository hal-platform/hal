var $ = require('jquery');
var moment = require('moment');

exports.module = {
    interval: 10,

    init: function() {
        this.refreshTimes();

        // we cannot cache the times $elements to update because they can be added later by build/push updaters.
        window.setInterval(this.refreshTimes, this.interval * 1000);
    },
    refreshTimes: function() {
        $times = $('time[datetime]');
        $times.each(function() {
            var $this = $(this),
                time = $this.attr('datetime');

            if (time.charAt(0) === 'P') {

                var duration = moment.duration(time),
                    relative = duration.humanize(),
                    absolute = duration.minutes() + " minutes, " + duration.seconds() + " seconds";

            } else {

                var momenttime = moment(time),
                    relative = momenttime.fromNow(),
                    absolute = momenttime.format('MMM D, YYYY h:mm A');

            }

            $this.text(relative);
            $this.attr('title', absolute);
        });
    }
};
