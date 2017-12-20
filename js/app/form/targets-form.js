import 'jquery';

const FORM_SELECTOR = '.js-targets-form';
const SELECTOR = '[data-target-select]';
const OPTION_DATA_ATTRIBUTE = 'target-type';
const HIDEABLE_FIELDS = '[data-type-specific]';

const VALID_TYPES = ['rsync', 'eb', 'cd', 's3', 'elb', 'script'];

function initTargetForm() {
    let $container = $(FORM_SELECTOR);
    if ($container.length > 0) {
        attach($container);
    }
}

function attach($container) {
    let $options = $container
        .find(SELECTOR)
        .find(`[data-${OPTION_DATA_ATTRIBUTE}]`);

    toggle($container, $options);

    $container
        .find(SELECTOR)
        .on('change', changeHandler($container));
}

function changeHandler($container) {
    return (e) => {
        let $options = $(e.target)
            .find(`[data-${OPTION_DATA_ATTRIBUTE}]`);

        toggle($container, $options);
    };
}

function toggle($container, $options) {
    let selectedType = $options
        .filter(':selected')
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

export { initTargetForm };
