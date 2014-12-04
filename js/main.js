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
        "jqueryTablesaw": "vendor/jquery.tablesaw",
        "nunjucks": "vendor/nunjucks.min",
        "signals": "vendor/signals.min",
        "tablesaw": "vendor/tablesaw.min",
        "underscore": "vendor/underscore.min",
        "json": "vendor/requirejs-plugins/json",
        "text": "vendor/requirejs-plugins/text"
    }
});

require(['controller/_common', 'json!require-config.json'], function(app, config) {

    // further requirejs config
    require.config(config);
    app.init();
});
