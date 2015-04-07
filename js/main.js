require.config({
    // Make sure "shim" and "paths" match the same values in optimizer.json!
    "shim": {
        "underscore": {
            "exports": "_"
        }
    },
    "paths": {
        "crossroads": "vendor/crossroads.min",
        "jquery": "vendor/jquery-2.min",
        "jquerySearchable": "vendor/jquery.searchable",
        "jquerySortable": "vendor/jquery.sortable.min",
        "jqueryTablesaw": "vendor/jquery.tablesaw-1.0.4",
        "moment": "vendor/moment-2.9.0",
        "nunjucks": "vendor/nunjucks.min",
        "signals": "vendor/signals.min",
        "tablesaw": "vendor/tablesaw.min",
        "underscore": "vendor/underscore.min",
        "json": "vendor/requirejs-plugins/json",
        "text": "vendor/requirejs-plugins/text",
        "svg4everybody": "vendor/svg4everybody-1.0.0"
    }
});

require(['controller/_common', 'json!require-config.json'], function(app, config) {

    // further requirejs config
    require.config(config);
    app.init();
});
