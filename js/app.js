var components = require('./components');
var terminal = require('./app/util/terminal');
var nofunzone = require('./app/util/nofunzone');
var reltime = require('./app/util/relative-time');

var app = {
    componentsAttr: "jsComponents",
    init: function() {

        var headData = document.querySelector('head').dataset,
            requestedComponents;

        if (headData.hasOwnProperty(this.componentsAttr)) {
            requestedComponents = headData[this.componentsAttr];

            // Load components
            requestedComponents.split(' ').map(function(component) {
                if (components.hasOwnProperty(component)) {
                    component = components[component];
                    component();
                } else {
                    console.log("Component not found: " + component);
                }

            });
        }
    },
    globals: function() {
        terminal.init();
        nofunzone.init();
        reltime.init();
    }
};

app.init();
app.globals();
