define(['modules/update-builds', 'modules/event-log'], function(buildUpdater, eventLog) {
    buildUpdater.mode = 'build';
    buildUpdater.init();

    eventLog.init();
});
