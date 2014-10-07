define(['jquery'], function($) {
    return {
        searchList: '.js-search-list',
        searchListRadio: $('.js-search-list li input'),
        searchInput: $('.js-search-input'),
        searchOutput: $('.js-search-drop'),
        searchResultList: $('.js-search-results'),
        searchResultItems: $('.js-search-results li'),
        searchItem: '.js-search-item',
        searchListItems: $('.js-search-item'),
        tabsContainer: '.js-tabs',
        tabsLink: $('.js-tabs li a'),
        commitId: $('.js-commitId'),
        commitRegEx: /^[0-9 A-F a-f]{40,40}$/,
        init: function() {
            var _this = this;
            var queryHash = window.location.hash;

            this.searchInput.on({
                blur: function(){
                    _this.searchOutput.slideUp("slow");
                },
                focus: function(e){
                    _this.searchItems();
                },
                paste: function(){
                    _this.delay(function(){
                        var searchTxt = _this.searchInput.val();
                        if (_this.commitRegEx.test(searchTxt)){
                            _this.commitId.val(searchTxt);
                        } else {
                            _this.searchItems();
                        }
                    }, 100);
                },
                keyup: function(){
                    _this.delay(function(){
                        var searchTxt = _this.searchInput.val();
                        if (_this.commitRegEx.test(searchTxt)){
                             _this.commitId.val(searchTxt);
                        } else {
                            _this.searchItems();
                        }
                    }, 100);
                }
            });

            if (queryHash){
                this.queryLookup(queryHash);
            }

            this.searchOutput.width(this.searchInput.width() + 25);

            $(window).on("resize", function(){
                _this.searchOutput.width(_this.searchInput.width() + 25);
            });

            this.searchListRadio.on("click",function(){
                _this.selectRadio(this);
            });

            this.searchResultList.on("click", this.searchItem, function(){
                _this.selectItem(this);
            });

            this.tabsLink.on("click", function(e){
                _this.tabs(e, this);
            });
        },
        queryLookup: function(query){
            query = query.split('pr')[1];
            this.selectItem($("<li class='js-search-item' data-val='pull/" + query + "'>" + query + "</li>"));
        },
        tabs: function(ev, ele){
            var currentTab = $(ele).attr('name');
            //content gets shown or hidden
            $('.' + currentTab).show().siblings().hide();
            //tabs turn active
            $(ele).parent('li').addClass('active').siblings().removeClass('active');
            ev.preventDefault();
        },
        searchItems: function(){
            var _this = this;
            var searchVal = this.searchInput.val().toLowerCase();
            var count = 0;
          
            this.searchResultList.html('');
            this.searchListRadio.each(function(){
                var itemVal = $(this).val();
                var txt = _this.cleanValue(itemVal.toLowerCase());

                var labelTxt = $("label[for='pr" + txt +"'] .js-title").text().toLowerCase();

                if (txt.indexOf(searchVal) === 0){
                    $("<li class='js-search-item' data-val='" + itemVal + "'>" + txt + "</li>").appendTo(_this.searchResultList).slideDown("fast");
                }

                if (labelTxt.indexOf(searchVal) === 0){
                    $("<li class='js-search-item' data-val='" + itemVal + "'>" + labelTxt + "</li>").appendTo(_this.searchResultList).slideDown("fast");
                }

                count++;
                // this runs if the list is already present and the user focuses on the input field again
                _this.searchOutput.show().slideDown("slow");
            });

            if (count > 12){
                this.searchOutput.css("max-height", "300px");
            }
        },
        cleanValue: function(valString){
            var isTag = (valString.substring(0, 4) == 'tag/');
            var isPull = (valString.substring(0, 5) == 'pull/');

            if ((isTag) || (isPull)){
                valString = valString.split('/')[1];
            }

            return valString;
        },
        selectItem: function(element){
            var eleVal = $(element).attr("data-val");
            var currentRadio = $(this.searchList + ' input[value="' + eleVal + '"]');
            var tabId = currentRadio.closest("div").attr("data-id");
            var currentTab = $(this.tabsContainer + ' a[name="'+ tabId +'"]').closest("li");
            var _this = this;

            this.searchInput.val($(element).text());
            currentRadio.closest("div").show().siblings().hide();

            currentTab.addClass('active').siblings().removeClass('active');
            currentRadio.prop("checked", true);
        },
        selectRadio: function(element){
            this.searchInput.val(this.cleanValue($(element).val()));
        },
        delay: function(callback, ms){
            var timer = 0;
            clearTimeout(timer);
            timer = setTimeout(callback, ms);
        }
    };
});
