import 'jquery';

const FIELD = 'vcs'
const VALID_TYPES = ['gh', 'ghe', 'git'];

const FORM_SELECTOR = `.js-${FIELD}-form`;
const SELECTOR = `[data-${FIELD}-select]`;
const OPTION_DATA_ATTRIBUTE = `${FIELD}-type`;
const HIDEABLE_FIELDS = '[data-type-specific]';

function initApplicationForm() {
    let $container = $(FORM_SELECTOR);
    if ($container.length > 0) {
        attach($container);
    }
}

function attach($container) {
    let $options = $container
        .find(SELECTOR)
        .filter(`[data-${OPTION_DATA_ATTRIBUTE}]`);

    toggle($container, $options);

    $container
        .find(SELECTOR)
        .on('change', changeHandler($container));
}

function changeHandler($container) {
    return (e) => {
        let $options = $(e.target)
            .filter(`[data-${OPTION_DATA_ATTRIBUTE}]`);

        toggle($container, $options);
    };
}

function toggle($container, $options) {
    let selectedType = $options
        .filter(':checked')
        .data(OPTION_DATA_ATTRIBUTE);

    $container
        .find(HIDEABLE_FIELDS)
        .hide();

    if (VALID_TYPES.includes(selectedType)) {
        $container
            .find(`[data-${selectedType}]`)
            .show();
    }
}

export { initApplicationForm };
