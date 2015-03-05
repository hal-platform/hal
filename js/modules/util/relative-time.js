define(['jquery', 'moment'], function($, moment) {
    return {
        interval: 5,

        init: function() {
            // we cannot cache the times $elements to update because they can be added later by push/push updaters.
            window.setInterval(this.refreshTimes, this.interval * 1000);
        },
        refreshTimes: function() {
            $times = $('time[datetime]');
            $times.each(function() {
                var $this = $(this),
                    time = $this.attr('datetime');

                time = moment(time);
                var reltime = time.fromNow(),
                    formattedtime = time.format('MMM D, YYYY h:mm A');

                $this.text(reltime);
                $this.attr('title', formattedtime);
            });
        }
    };
});
