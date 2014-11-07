define(['modules/update-builds', 'modules/update-pushes'], function(buildUpdater, pushUpdater) {
    buildUpdater.init();
    pushUpdater.init();
});
