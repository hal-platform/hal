define(['jquery'], function($) {
    return {
        $validGitRefs: $('.js-search-list li input'),
        $gitRefSearchListings: $('.js-search-list .js-search-item'),

        searchList: '.js-search-list', // ?

        searchBox: '#js-search-input',
        searchResults: '.js-search-results',
        searchQuery: 'span',
        searchQueryPrimary: '.js-search-primary',

        $searchBox: null,
        $searchResults: null,
        $searchContainer: $('.js-search-drop'),

        searchResultItem: '.js-search-item', // ?

        tabAnchorsContainer: '.js-tabs',
        $tabAnchors: $('.js-tabs li a'),

        // $form: $('form[name="start-build"]'),
        $error: $('.js-build-error'),

        commitRegEx: /^[0-9 A-F a-f]{40,40}$/,

        init: function() {
            var _this = this;

            this.$searchBox = $(this.searchBox);
            this.$searchResults = $(this.searchResults);


            // build search listings
            _this.buildSearchResults();

            // make search listings searchable
            this.$searchResults.searchable({
                selector      : 'li',
                childSelector : this.searchQuery,
                searchField   : this.searchBox,
                striped       : false,
                searchType    : 'fuzzy'
            });

            // this.$form.on("submit", function(){
            //     var txt = _this.$searchBox.val();
            //     var isNotCommit = !_this.commitRegEx.test(txt);

            //     if (isNotCommit && txt !== '') {
            //          _this.selectBySearch(txt);
            //     }
            // });

            // this.$searchBox.on('blur', function() {
            //     _this.$searchContainer.slideUp('slow');

            //     _this.delay(function() {
            //         var searchTxt = _this.$searchBox.val();
            //         if (_this.commitRegEx.test(searchTxt)) {
            //              _this.$selectedGitRef.val(txt);
            //         } else {
            //             if (txt !== ''){
            //               _this.selectBySearch(txt);
            //             }
            //         }

            //     }, 100);
            // });


            this.$searchBox.on('focus', function() {
                _this.showSearchListings();
                _this.$error.hide();
            });

            this.$searchBox.on('blur', function() {
                _this.hideSearchListings();
            });

            // this.$searchBox.on('paste', function() {
            //     _this.delay(function() {
            //         var searchTxt = _this.$searchBox.val();
            //         if (_this.commitRegEx.test(searchTxt)) {
            //             _this.$selectedGitRef.val(searchTxt);
            //         } else {
            //             _this.buildSearchResults();
            //         }
            //     }, 100);
            // });

            // this.$searchBox.on('keyup', function() {
            //     _this.delay(function() {
            //         var searchTxt = _this.$searchBox.val();
            //         if (_this.commitRegEx.test(searchTxt)) {
            //              _this.$selectedGitRef.val(searchTxt);
            //         } else {
            //             _this.buildSearchResults();
            //         }
            //     }, 100);
            // });

            if (window.location.hash) {
                this.searchByFragment(window.location.hash);
            }

            // match search listings size to search box size
            this.$searchContainer.width(this.$searchBox.width() + 25);

            // resize search listings on window resize
            $(window).on('resize', function() {
                _this.$searchContainer.width(_this.$searchBox.width() + 25);
            });

            // add handler for selecting a ref from a valid radio input
            this.$validGitRefs.on('click', function() {
                _this.selectValidGitRef(this);
            });

            // add handler for selecting a search result item
            this.$searchResults.on('click', this.searchResultItem, function() {
                _this.selectSearchResult(this);
            });

            // add handler for showing/hiding tabs
            this.$tabAnchors.on('click', function(event) {
                _this.selectTab(this);
                event.preventDefault();
            });
        },
        searchByFragment: function(fragment) {
            var searchBy;

            if (fragment.slice(0, 3) === '#pr') {
                searchBy = 'PR #' + fragment.slice(3);
            } else {
                searchBy = fragment.slice(1);
            }

            this.selectBySearch(searchBy);
        },

        selectTab: function(ele) {
            var anchor = $(ele).attr('name');

            // show tab
            $('#' + anchor).show().siblings().hide();

            // tab anchor turn active
            $(ele).parent('li').addClass('active').siblings().removeClass('active');
        },
        selectSearchResult: function(element) {
            var $el = $(element),
                parentId = $el.data('parent'),
                tabName = $el.data('tab'),
                cleanedText = $el.children(this.searchQueryPrimary).text().trim(),
                targetRadio = $('#' + parentId);

            // put title in search box
            this.$searchBox.val(cleanedText);

            // switch tabs
            this.selectTab('a[name="' + tabName + '"]');

            // select radio
            targetRadio.prop('checked', true);
        },
        selectValidGitRef: function(element) {
            _this.$error.hide();
            // @todo add flavor text to "Start Build?" button when HAL is cannot validate a ref.

            var searchQuery = $(element).data('search');
            this.$searchBox.val(searchQuery);
        },

        selectBySearch: function(txt) {
            var _this = this;
            var found = 0;

            this.$validGitRefs.each(function(){
                var itemType = $(this).closest('ul').data('type'),
                    searchStr = $(this).data('search'),
                    labelTxt = $("label[for='pr" + searchStr + "'] .js-title").text().toLowerCase(),
                    tabId = $(this).closest("div").attr('id'),
                    currentTab = $(_this.tabAnchorsContainer + ' a[name="'+ tabId +'"]').closest("li"),
                    pullSearch = 'PR #' + searchStr;

                if (pullSearch.toLowerCase() === txt.toLowerCase() || searchStr.toLowerCase().indexOf(txt.toLowerCase()) === 0 || labelTxt.toLowerCase().indexOf(txt.toLowerCase()) === 0){
                    // select this radio change to tab
                    $(this).closest("div").show().siblings().hide();
                    currentTab.addClass('active').siblings().removeClass('active');
                    $(this).prop("checked", true);
                    found = 1;
                    return false;
                }
            });

            if (found === 0) {
                _this.$error.show();
            }
        },

        buildSearchResults: function() {
            var count = this.$gitRefSearchListings.length;
            var mover = function(index, el) {
                $(el).appendTo(this.$searchResults);
            }.bind(this);

            // move search listings out of tabs and into central search container
            this.$gitRefSearchListings.each(mover);

            if (count > 12) {
                this.$searchContainer.css('max-height', '300px');
            }
        },
        showSearchListings: function() {
            this.$searchContainer.show().slideDown('slow');
        },
        hideSearchListings: function() {
            this.$searchContainer.show().slideUp('fast');
        },

        // displayValue: function(valString, valType){
        //     if(valType == 'pull'){
        //         valString = 'PR #' + valString;
        //     }

        //     return valString;
        // },

        delay: function(callback, ms){
            var timer = 0;
            clearTimeout(timer);
            timer = setTimeout(callback, ms);
        }
    };
});
