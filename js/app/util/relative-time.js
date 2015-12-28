import 'jquery';
import formatter from './time-formatter';

module.exports = {
    interval: 10,

    init: function() {
        this.refreshTimes();

        // we cannot cache the times $elements to update because they can be added later by build/push updaters.
        window.setInterval(this.refreshTimes, this.interval * 1000);
    },
    refreshTimes: function() {
        var $times = $('time[datetime]');
        $times.each(function() {
            var $this = $(this),
                time = $this.attr('datetime'),
                formatted;

            if (time.charAt(0) === 'P') {
                formatted = formatter.formatDuration(time);

            } else {
                formatted = formatter.formatTime(time);
            }

            $this.text(formatted.relative);
            $this.attr('title', formatted.absolute);

        });
    }
};
