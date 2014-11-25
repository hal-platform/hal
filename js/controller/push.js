define(['modules/update-pushes', 'modules/event-log'], function(pushUpdater, eventLog) {
    pushUpdater.mode = 'push';
    pushUpdater.init();

    eventLog.init();
});
