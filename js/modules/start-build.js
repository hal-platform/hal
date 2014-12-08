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
        formEle: $('form[name="start-build"]'),
        commitId: $('.js-commitId'),
        commitRegEx: /^[0-9 A-F a-f]{40,40}$/,
        errorText: $('.js-error'),
        init: function() {
            var _this = this,
                queryHash = window.location.hash;

            this.formEle.on("submit", function(){
                var txt = _this.searchInput.val();
                var isNotCommit = !_this.commitRegEx.test(txt);

                if (isNotCommit && txt !== '') {
                     _this.searchRadio(txt);
                }
            });

            this.searchInput.on({
                blur: function(){
                    _this.searchOutput.slideUp("slow");

                    _this.delay(function(){
                        var txt = _this.searchInput.val();
                        if (_this.commitRegEx.test(txt)){
                             _this.commitId.val(txt);
                        } else {
                            if (txt !== ''){
                              _this.searchRadio(txt);
                            }
                        }

                    }, 100);
                },
                focus: function(e){
                    _this.searchItems();
                    _this.errorText.text('');
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
                keyup: function(e){
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
                if (_this.commitId.val() !== ''){
                    _this.commitId.val('');
                }

                if (_this.errorText.text() !== ''){
                    _this.errorText.text('');
                }
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
            var _this = this,
                searchVal = this.searchInput.val().toLowerCase(),
                count = 0;

            this.searchResultList.html('');

            this.searchListRadio.each(function(){
                var itemVal = $(this).val(),
                    searchTxt = $(this).attr("data-search"),
                    itemType = $(this).closest('ul').attr("data-type"),
                    labelTxt = $("label[for='pr" + searchTxt +"'] .js-title").text().toLowerCase(),
                    svg = _this.setSvg(itemType),
                    statClass = $(this).attr("data-status"),
                    displayTxt = _this.displayValue(searchTxt.toLowerCase(), itemType),
                    pullSearch = ((itemType == 'pull') ? 'PR #' + searchTxt : searchTxt);

                if (pullSearch.toLowerCase().indexOf(searchVal) === 0 || searchTxt.indexOf(searchVal) === 0){
                    $("<li class='js-search-item' data-val='" + itemVal + "'><span class='icon " + statClass + "'><svg viewBox='0 0 32 32'><use xlink:href='" + svg + "'></use></svg></span> " + displayTxt + "</li>").appendTo(_this.searchResultList).slideDown("fast");
                }

                if (labelTxt.indexOf(searchVal) === 0 && itemType == 'pull'){
                    $("<li class='js-search-item' data-val='" + itemVal + "'><span class='icon " + statClass + "'><svg viewBox='0 0 32 32'><use xlink:href='" + svg + "'></use></svg></span> " + labelTxt + "</li>").appendTo(_this.searchResultList).slideDown("fast");
                }

                count++;
                // this runs if the list is already present and the user focuses on the input field again
                _this.searchOutput.show().slideDown("slow");
            });

            if (count > 12){
                this.searchOutput.css("max-height", "300px");
            }
        },
        searchRadio: function(txt){
            var _this = this;
            var found = 0;

            this.searchListRadio.each(function(){
                var itemType = $(this).closest('ul').attr("data-type"),
                    searchStr = $(this).attr("data-search"),
                    labelTxt = $("label[for='pr" + searchStr + "'] .js-title").text().toLowerCase(),
                    tabId = $(this).closest("div").attr("data-id"),
                    currentTab = $(_this.tabsContainer + ' a[name="'+ tabId +'"]').closest("li"),
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

            if (found === 0){
                this.errorText.text("Sorry, Dave. I am afraid I can't do that.");
            }
        },
        setSvg: function(itemType){
            if (itemType == 'branch'){
               return '#branch';
            } else if (itemType == 'tag') {
               return '#tag';
            } else {
               return '#pull';
            }

        },
        displayValue: function(valString, valType){
            if(valType == 'pull'){
                valString = 'PR #' + valString;
            }

            return valString;
        },
        selectItem: function(element){
            var eleVal = $(element).attr("data-val"),
                currentRadio = $(this.searchList + ' input[value="' + eleVal + '"]'),
                tabId = currentRadio.closest("div").attr("data-id"),
                currentTab = $(this.tabsContainer + ' a[name="'+ tabId +'"]').closest("li"),
                _this = this;

            this.searchInput.val($.trim($(element).text()));
            currentRadio.closest("div").show().siblings().hide();

            currentTab.addClass('active').siblings().removeClass('active');
            currentRadio.prop("checked", true);
        },
        selectRadio: function(element){
              var type = $(element).closest('ul').attr("data-type");

              this.searchInput.val(this.displayValue($(element).attr("data-search"), type));
        },
        delay: function(callback, ms){
            var timer = 0;
            clearTimeout(timer);
            timer = setTimeout(callback, ms);
        }
    };
});
