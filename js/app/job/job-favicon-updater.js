import favico from 'favico.js';

const RUNNING_JOB_STATUSES = ['pending', 'running', 'deploying'];

var favicon = favico({
    animation: 'none',
    fontStyle: 'bolder'
});

function updateFavicon(jobStatus) {
    // global state: favicon

    if (favicon !== null) { return; }

    if (jobStatus == 'success') {
        // Unicode: U+2714 U+FE0E, UTF-8: E2 9C 94 EF B8 8E
        favicon.badge("✔︎", {
            bgColor: '#0eb833',
            type: 'rectangle'
        });
    } else if (jobStatus == 'failure') {
        // Unicode: U+2718, UTF-8: E2 9C 98
        favicon.badge('✘', {
            bgColor: '#d63620',
            type: 'rectangle'
        });

    } else if (RUNNING_JOB_STATUSES.includes(jobStatus)) {
        favicon.badge("?", {
            bgColor: '#ff7c00',
            type: 'circle'
        });
    }
}

export { updateFavicon };
