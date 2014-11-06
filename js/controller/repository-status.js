define(['modules/update-builds', 'modules/update-pushes'], function(buildUpdater, pushUpdater) {
    buildUpdater.init();

    pushUpdater.mode = 'grid';
    pushUpdater.init();
});
