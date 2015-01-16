define(['modules/update-builds', 'modules/update-pushes', 'modules/repository-status-overload'], function(buildUpdater, pushUpdater, overloader) {
    buildUpdater.init();

    pushUpdater.mode = 'grid';
    pushUpdater.init();

    overloader.init();
});
