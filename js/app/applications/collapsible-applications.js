import 'jquery';

const MSG_HIDE = 'Collapse';
const MSG_SHOW = 'Show All';

let target = '.js-collapsible .js-collapsible-global',
    rowTarget = '.js-collapsible-row',
    sectionTarget = '.js-collapsible tbody';

var initApplicationTable = () => {
    var $container = $(target),
        $sections = $(sectionTarget),
        $rows = $(rowTarget);

    if ($sections.length && $rows.length > 1) {
        attachSections($sections, rowTarget);
        attachGlobal($container, $sections);
    }
};

function attachGlobal($container, $sections) {
    let $toggler = $(`<a href="#" data-hidden="0">${MSG_HIDE}</a>`);

    $toggler.on('click', (e) => {
        e.preventDefault();

        var currentHidden = $toggler.data('hidden');
        $toggler.data('hidden', currentHidden === '1' ? '0' : '1');

        // force data-hidden to match global
        $sections.each((i, element) => {
            $(element).find('th a').data('hidden', currentHidden);
        });

        // click
        $sections.each((i, element) => {
            $(element).find('th a').trigger('click');
        });

        var text = currentHidden === '1' ? MSG_HIDE : MSG_SHOW;
        $toggler.text(text);
    });

    $container.html($toggler);
}

function attachSections($sections, target) {

    var template = `<a href="#" style="float: right" data-hidden="0">Hide</a>`;

    $sections.each((i, element) => {
        var $toggler = $(template),
            $container = $(element),
            $target = $container.find('th'),
            $children = $container.children(target);

        if ($children.length < 1) {
            return;
        }

        $toggler.on('click', (e) => {
            e.preventDefault();

            if (toggle($toggler)) {
                $children.show();
                $toggler.text('Hide');
            } else {
                $children.hide();
                $toggler.text('Show');
            }
        });

        $target.append($toggler);
    });
}

function toggle($el) {
    // return: bool whether to show or hide target element
    var currentHidden = $el.data('hidden');
    $el.data('hidden', currentHidden === '1' ? '0' : '1');

    return currentHidden === '1' ? true : false;
}

export { initApplicationTable };
