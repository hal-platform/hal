import 'jquery';

const FORM_SELECTOR = '.js-credentials-form';
const SELECTOR = '[data-credential-select]';
const OPTION_DATA_ATTRIBUTE = 'credential-type';
const HIDEABLE_FIELDS = '[data-type-specific]';

const VALID_TYPES = ['aws_role', 'aws_static', 'privatekey'];

function initCredentialForm() {
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

    if (!selectedType) {
        return;
    }

    $container
        .find(HIDEABLE_FIELDS)
        .hide();

    if (VALID_TYPES.includes(selectedType)) {
        $container
            .find(`[data-${selectedType}]`)
            .show();
    }
}

export { initCredentialForm };
