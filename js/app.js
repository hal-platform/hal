import components from './components';
import terminal from './app/util/terminal';
import nofunzone from './app/util/nofunzone';
import reltime from './app/util/relative-time';
import svg4everybody from 'svg4everybody';
import 'babel-polyfill';

let routingSelector = 'data-js-components';

function start() {

    var requested = document.querySelector('head').getAttribute(routingSelector);
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
}

// Start up
start();

// global utilities
terminal.init();
nofunzone.init();
reltime.init();

// Vendor utilities
svg4everybody();
