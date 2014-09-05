define(['jquery'], function($) {
    return {
        searchList: '.js-search-list',
        searchInput: '.js-search-input',
        searchOutput: '.js-search-drop',
        searchResultList: '.js-search-results',
        searchItem: '.js-search-item',
        tabsContainer: '.js-tabs',
        init: function() {
            var _this = this;
            //to do
            // refactor everything
              // js
                 // use bind instead of _this
                 // all dom calls to the top
              // css
            // user selects radio
              // text replaces what is in input if populated
           // prettify drop down
                // make drop same size as input
               // animation issues with drop down
                  // no animation initially
                  // when list is short, draws to max height then redraws to actual height
                  // try css animations instead
               // when div is visible should be scolled to top
           // tabs
             // first tab has active class just to show it.  needs rethinking.  try to remove that class
             // prettify all tabs
            $(this.searchInput).on({
                blur: function(){
                  $(_this.searchOutput).slideUp("slow");
                },
                focus: function(){
                  $(_this.searchOutput).slideDown("slow");
                  _this.searchItems();
                  $(_this.searchOutput).scrollTop();
                },
                keyup: function(){
                  _this.delay(function(){
                      _this.searchItems();
                  }, 100);
                }
            });

            $(this.searchList + ' li input').on("click",function(){
                //console.log("selected");
                _this.selectRadio(this);
            });

            $(this.searchResultList).on("click", this.searchItem, function(){
                _this.selectItem(this);
            });
            //prototype ... refactor tabs
            $(this.tabsContainer + ' li a').on("click", function(e){
                //console.log("click tab");
                var currentTab = $(this).attr('name');
                $('.' + currentTab).show().siblings().hide();
                $(this).parent('li').addClass('active').siblings().removeClass('active');
                e.preventDefault();
            });

         },
         searchItems: function(){
             var searchList = $(this.searchList + '> li input');
             var searchField = $(this.searchInput);
             var searchDrop = $(this.searchOutput);
             var searchResults = $(this.searchResultList);
             var _this = this;
             var searchVal = searchField.val().toLowerCase();

                searchResults.html('');
                searchList.each(function(){
                    var txt = $(this).val().toLowerCase();
                    var itemVal = $(this).val();
                    var tagCheck = (txt.substring(0, 4) == 'tag/');
                    var pullCheck = (txt.substring(0, 5) == 'pull/');

                    if ((tagCheck) || (pullCheck)){
                        txt = txt.split('/')[1];
                    }

                    if (txt.indexOf(searchVal) === 0){
                        searchDrop.slideDown("slow");
                        searchResults.append("<li class='js-search-item' data-val='" + itemVal + "'>" + txt + "</li>");
                    }
                });
         },
         selectItem: function(element){
              var searchBox = $(this.searchInput);
              var eleVal = $(element).attr("data-val");
              var currentRadio = $('input[value="' + eleVal + '"]');
              var tabId = currentRadio.closest("div").attr("data-id");
              var currentTab = $(this.tabsContainer + ' li[data-id="'+ tabId +'"]');
              var _this = this;

              searchBox.val($(element).text());
              currentRadio.closest("div").show().siblings().hide();

              currentTab.addClass('active').siblings().removeClass('active');
              currentRadio.prop("checked", true);
              currentRadio.parent().addClass('js-highlight');
              setTimeout(function(){
                  console.log(currentRadio);
                  currentRadio.parent().removeClass('js-highlight');
              }, 5000);
         },
         selectRadio: function(element){
           var searchBox = $(this.searchInput);
           searchBox.val($(element).val());
           console.log("select radio");
           $(element).parent().addClass('js-highlight');
           setTimeout(function(){
             $(element).parent().removeClass('js-highlight');
           }, 5000);

         },
         delay: function(callback, ms){
             var timer = 0;
             clearTimeout(timer);
             timer = setTimeout(callback, ms);
         }

     };
});
