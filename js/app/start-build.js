import 'jquery';
import filterSearch from './util/filter-search';

module.exports = {
    searchBox: '#js-search-input',
    searchResults: '.js-search-results',
    searchResultItem: '.js-search-item',
    searchQueryPrimary: '.js-search-primary',
    searchQueryShowClass: 'js-search-item-display',
    searchQueryHideClass: 'js-search-item-hidden',

    $searchBox: null,
    $searchResults: null,
    $searchContainer: $('.js-search-drop'),
    $searchParent: $('.js-search-container'),

    $validOptions: $('.js-search-list li input'),
    $tabAnchors: $('.js-tabs li a'),
    $warning: $('.js-build-warning'),
    $submitButtons: $('form[name="start-build"] input[type="submit"]'),

    init: function() {
        var _this = this;

        this.$searchBox = $(this.searchBox);
        this.$searchResults = $(this.searchResults);

        filterSearch.init(this.$searchBox, {
            searchItem: '.js-search-item',
            searchQuery: 'span',

            onHide: function(item) {
                item
                    .addClass(_this.searchQueryHideClass)
                    .removeClass(_this.searchQueryShowClass);
            },
            onShow: function(item) {
                item
                    .addClass(_this.searchQueryShowClass)
                    .removeClass(_this.searchQueryHideClass);
            },

            onFocus : function(box) {
                _this.showSearchListings();
            },

            onEmpty : function(box) {
                _this.$searchResults
                    .children(_this.searchResultItem)
                    .addClass(_this.searchQueryShowClass)
                    .removeClass(_this.searchQueryHideClass);
            }
        });

        // Add fake blur handler to parent
        this.$searchBox.on('herpderp', function() {
            _this.justwhatexactlyareyoutryingtododave();
            _this.hideSearchListings();
        });

        // Ugh
        $(document.body).on('click', function() {
            _this.$searchBox.trigger('herpderp');
        });

        this.$searchParent.on('click', function(e) {
            e.stopPropagation();
        });

        // if fragment provided, attempt to select by it
        // only run if search box is empty
        if (window.location.hash && this.$searchBox.val().length === 0) {
            this.searchByFragment(window.location.hash);
        }

        // add handler for selecting a ref from a valid radio input
        this.$validOptions.on('click', function() {
            _this.selectOption(this);
            _this.$searchBox.trigger('change');
        });

        // add handler for selecting a search result item
        this.$searchResults.on('click', this.searchResultItem, function(event) {
            _this.selectSearchResult(this);
            _this.$searchBox.trigger('change');
            _this.$searchBox.trigger('herpderp');
        });

        // add handler for showing/hiding tabs
        this.$tabAnchors.on('click', function(event) {
            _this.selectTab(this);
            event.preventDefault();
        });

        // Auto select a ref it there is only one visible
        this.$searchBox.trigger('change');
        this.justwhatexactlyareyoutryingtododave();

    },
    searchByFragment: function(fragment) {
        var searchBy,
            $visible;

        if (fragment.slice(0, 3) === '#pr') {
            searchBy = 'PR #' + fragment.slice(3);
        } else {
            searchBy = fragment.slice(1);
        }

        this.$searchBox.val(searchBy);
        this.$searchBox.trigger('change');
        this.justwhatexactlyareyoutryingtododave();
    },

    selectTab: function(el) {
        var $el = $(el),
            anchor = $el.attr('name');

        // show tab
        $('#' + anchor).show().siblings().hide();

        // tab anchor turn active
        $el.parent('li')
            .addClass('active')
            .siblings()
            .removeClass('active');
    },
    selectSearchResult: function(element) {
        var $selected = $(element),
            parentId = $selected.data('parent'),
            tabName = $selected.data('tab'),
            cleanedText = $selected.children(this.searchQueryPrimary).text().trim(),
            targetRadio = $('#' + parentId);

        // put title in search box
        this.$searchBox.val(cleanedText);

        // switch tabs
        this.selectTab('a[name="' + tabName + '"]');

        // select radio
        targetRadio.prop('checked', true);
        this.hideWarning();
    },
    selectOption: function(element) {
        var parentId = $(element).attr('id'),
            $selected = $('[data-parent="' + parentId + '"]'),
            cleanedText = $selected.children(this.searchQueryPrimary).text().trim();

        this.$searchBox.val(cleanedText);
        this.hideWarning();
    },

    justwhatexactlyareyoutryingtododave: function() {
        if (this.$searchBox.val().length === 0) {
            return;
        }

        var $visible = this.$searchResults.children('.' + this.searchQueryShowClass);

        // if no results, display a warning for the user. We will try to resolve it on the backend.
        if ($visible.length === 0) {
            this.showWarning();

            // deselect previously selected radio
            this.$validOptions.filter(':checked').prop('checked', false);

        } else if ($visible.length === 1) {
            // if only 1 result, auto select it
            this.selectSearchResult($visible);
        }
    },

    showSearchListings: function() {
        this.$searchContainer.slideDown('fast');
    },
    hideSearchListings: function() {
        this.$searchContainer.slideUp('fast');
    },
    hideWarning: function() {
        this.$warning.hide();
        this.$submitButtons
            .removeClass('btn--action')
            .val(this.$warning.data('label'));
    },
    showWarning: function() {
        this.$warning.show();
        this.$submitButtons
            .addClass('btn--action')
            .val(this.$warning.data('label-warning'));
    }
};
