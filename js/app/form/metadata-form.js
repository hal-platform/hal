import 'jquery';

let target = 'js-metadata';

let initMetadataForm = () => {
    let $container = $(`.${target} tbody`);
    if ($container.length !== 0) {
        attach($container);
    }
};

let attach = ($container) => {
    $('<small><a href="#">Add more</a></small>')
        .insertAfter($container)
        .on('click', function(e) {
            e.preventDefault();

            $container
                .find('tr:last-child')
                    .clone()
                    .appendTo($container)
                .find('input')
                    .val('');
        });
};

export { initMetadataForm };
