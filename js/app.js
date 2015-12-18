var components = require('./components');
var terminal = require('./app/util/terminal');
var nofunzone = require('./app/util/nofunzone');
var reltime = require('./app/util/relative-time');

var app = {
    componentsAttr: "data-js-components",
    init: function() {

        var requested = document.querySelector('head').getAttribute(this.componentsAttr);
        if (requested === "undefined" || requested === null) {
            return;
        }

        // Load components
        requested.split(' ').map(function(component) {
            if (components.hasOwnProperty(component)) {
                component = components[component];
                component();
            } else {
                console.log("Component not found: " + component);
            }

        });
    },
    globals: function() {
        terminal.init();
        nofunzone.init();
        reltime.init();
    }
};

app.init();
app.globals();
