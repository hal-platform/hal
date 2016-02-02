import 'jquery';
import filterSearch from '../util/filter-search';

let searchBox = '#js-search-input',
    searchResults = '.js-search-results',
    searchResultItem = '.js-search-item',
    searchQueryPrimary = '.js-search-primary',
    searchQueryShowClass = 'js-search-item-display',
    searchQueryHideClass = 'js-search-item-hidden';

var $searchBox = null,
    $searchResults = null,
    $searchContainer = $('.js-search-drop'),
    $searchParent = $('.js-search-container'),

    $validOptions = $('.js-search-list li input'),
    $tabAnchors = $('.js-tabs li a'),
    $warning = $('.js-build-warning'),
    $submitButtons = $('form[name="start-build"] input[type="submit"]');

var init = () => {
    $searchBox = $(searchBox);
    $searchResults = $(searchResults);

    filterSearch($searchBox, {
        searchItem: '.js-search-item',
        searchQuery: 'span',

        onHide: function(item) {
            item
                .addClass(searchQueryHideClass)
                .removeClass(searchQueryShowClass);
        },
        onShow: function(item) {
            item
                .addClass(searchQueryShowClass)
                .removeClass(searchQueryHideClass);
        },

        onFocus : function() {
            showSearchListings();
        },

        onEmpty : function() {
            $searchResults
                .children(searchResultItem)
                .addClass(searchQueryShowClass)
                .removeClass(searchQueryHideClass);
        }
    });

    attachHandlers();
};

function attachHandlers() {
    // Add fake blur handler to parent
    $searchBox.on('herpderp', function() {
        justwhatexactlyareyoutryingtododave();
        hideSearchListings();
    });

    // Ugh
    $(document.body).on('click', function(e) {
        $searchBox.trigger('herpderp');
    });

    $searchParent.on('click', function(e) {
        e.stopPropagation();
    });

    // if fragment provided, attempt to select by it
    // only run if search box is empty
    if (window.location.hash && $searchBox.val().length === 0) {
        searchByFragment(window.location.hash);
    }

    // add handler for selecting a ref from a valid radio input
    $validOptions.on('click', function() {
        selectOption(this);
        $searchBox.trigger('change');
    });

    // add handler for selecting a search result item
    $searchResults.on('click', searchResultItem, function() {
        selectSearchResult(this);
        $searchBox.trigger('change');
        $searchBox.trigger('herpderp');
    });

    // add handler for showing/hiding tabs
    $tabAnchors.on('click', function(e) {
        selectTab(this);
        e.preventDefault();
        e.stopPropagation();
    });

    // Add trigger for submit in case user tabs from search box
    $submitButtons.on('click focus', function() {
        $searchBox.trigger('herpderp');
    });

    // Auto select a ref it there is only one visible
    $searchBox.trigger('change');
    justwhatexactlyareyoutryingtododave();
}

function searchByFragment(fragment) {
    var searchBy;

    if (fragment.slice(0, 3) === '#pr') {
        searchBy = 'PR #' + fragment.slice(3);
    } else {
        searchBy = fragment.slice(1);
    }

    $searchBox.val(searchBy);
    $searchBox.trigger('change');
    justwhatexactlyareyoutryingtododave();
}

function selectTab(el) {
    var $el = $(el),
        anchor = $el.attr('name');

    // show tab
    $('#' + anchor).show().siblings().hide();

    // tab anchor turn active
    $el.parent('li')
        .addClass('active')
        .siblings()
        .removeClass('active');
}

function selectSearchResult(element) {
    var $selected = $(element),
        parentId = $selected.data('parent'),
        tabName = $selected.data('tab'),
        cleanedText = $selected.children(searchQueryPrimary).text().trim(),
        targetRadio = $('#' + parentId);

    // put title in search box
    $searchBox.val(cleanedText);

    // switch tabs
    selectTab('a[name="' + tabName + '"]');

    // select radio
    targetRadio.prop('checked', true);
    hideWarning();
}

function selectOption(element) {
    var parentId = $(element).attr('id'),
        $selected = $('[data-parent="' + parentId + '"]'),
        cleanedText = $selected.children(searchQueryPrimary).text().trim();

    $searchBox.val(cleanedText);
    hideWarning();
}

function justwhatexactlyareyoutryingtododave() {
    if ($searchBox.val().length === 0) {
        return;
    }

    var $visible = $searchResults.children('.' + searchQueryShowClass);

    // if no results, display a warning for the user. We will try to resolve it on the backend.
    if ($visible.length === 0) {
        showWarning();

        // deselect previously selected radio
        $validOptions.filter(':checked').prop('checked', false);

    } else if ($visible.length === 1) {
        // if only 1 result, auto select it
        selectSearchResult($visible);
    }
}

function showSearchListings() {
    $searchContainer.slideDown('fast');
}

function hideSearchListings() {
    $searchContainer.slideUp('fast');
}

function hideWarning() {
    $warning.hide();
    $submitButtons.val($warning.data('label'));
}

function showWarning() {
    $warning.show();
    $submitButtons.val($warning.data('label-warning'));
}

export default init
