import 'jquery';
import filterSearch from '../util/filter-search';

let searchTable = '.js-search-table',
    searchBox = '#js-search-input',
    searchItem = '.js-search-item',
    searchGroup = '.js-search-group',
    searchQueryShowClass = 'js-search-item-show',
    searchQueryHideClass = 'js-search-item-hidden';

var $searchBox = null,
    $searchItems = null,
    $searchGroups = null,
    $childless = null,
    $emptyMessage = null;

var hideParent = (item) => {
    var $parent = item.closest(searchGroup);

    if ($parent.find('.' + searchQueryShowClass).length === 0) {
        $parent.hide();
    }
};
var showParent = (item) => {
    item
        .closest(searchGroup)
        .show();
};

var init = () => {
    $searchBox = $(searchBox);
    $searchItems = $(searchItem);
    $searchGroups = $(searchGroup);

    $emptyMessage = $('<tbody><tr><td colspan="3">No applications match your search criteria.</td></tr></tbody>')
        .hide()
        .appendTo(searchTable);

    $childless = $searchGroups.filter(function() {
        return $(this).find(searchItem).length === 0;
    });

    filterSearch($searchBox, {
        searchItem: searchItem,
        searchQuery: 'span',

        onHide: function(item) {
            item
                .removeClass(searchQueryShowClass)
                .addClass(searchQueryHideClass);

            hideParent(item);
            $childless.hide();
        },
        onShow: function(item) {
            item
                .removeClass(searchQueryHideClass)
                .addClass(searchQueryShowClass);

            showParent(item);
            $emptyMessage.hide();
        },
        onNoMatch: function() {
            $emptyMessage.show();
        },
        onEmpty: function() {
            $emptyMessage.hide();
            $searchGroups.show();
            $searchItems.removeClass(searchQueryHideClass);
        }
    });

};

export default init;
