define(['controller/_common'], function(app) {
    var date = new Date();

    require.config({
        shim: {
            underscore: {
                exports: '_'
            }
          },
        paths: {
            crossroads: 'vendor/crossroads.min',
            jquery: 'vendor/jquery-2.min',
            jquerySortable: 'vendor/jquery.sortable.min',
            nunjucks: 'vendor/nunjucks.min',
            signals: 'vendor/signals.min',
            tablesaw: 'vendor/tablesaw.min',
            underscore: 'vendor/underscore.min'
        },
        urlArgs: "" + date.getFullYear() + date.getMonth() + date.getDate()
    });

    app.init();
});
