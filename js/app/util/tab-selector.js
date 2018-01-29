import 'jquery';

const CONTAINER_SELECTOR = '.js-tabs';
const TAB_SELECTOR = `${CONTAINER_SELECTOR} > a`;

var initTabSelector = () => {
    $(TAB_SELECTOR).on('click', function(e) {
        selectTab(this);
        e.preventDefault();
        e.stopPropagation();
    });
};

function selectTab(el) {
    var $el = $(el),
        anchor = $el.attr('name');

    // show tab
    $('#' + anchor).show().siblings().hide();

    // tab anchor turn active
    $el.addClass('active')
        .siblings()
        .removeClass('active');
}

export { initTabSelector, selectTab };
