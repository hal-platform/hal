import components from './components';
import terminal from './app/util/terminal';
import nofunzone from './app/util/nofunzone';
import reltime from './app/util/relative-time';
import svg4everybody from 'svg4everybody';

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
svg4everybody();
