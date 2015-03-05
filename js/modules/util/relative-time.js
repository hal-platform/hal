define(['jquery', 'moment'], function($, moment) {
    return {
        $times: null,
        interval: 5,

        init: function() {
            this.$times = $('time[datetime]');

            if (this.$times.length > 0) {
                var refresher = this.refreshTimes.bind(this);
                this.refreshTimes();

                window.setInterval(refresher, this.interval * 1000);
            }
        },
        refreshTimes: function() {
            this.$times.each(function() {
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
