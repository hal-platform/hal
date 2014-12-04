define(['jquery'], function($) {
    return {
        filter: '.js-filter',
        inputTarget: '.js-filter__input',

        toggleBtn: '.js-hide-btn',
        hideContent: '.js-hide-box',

        init: function() {
            this.toggle();
            this.filterRepos();
        },
        filterRepos: function() {
            $(this.filter).searchable({
                selector      : 'li',
                childSelector : 'a',
                searchField   : this.inputTarget,
                striped       : false,
                searchType    : 'fuzzy'
            });
        },
        toggle: function(){
            var btn = $(this.toggleBtn);
            var box = $(this.hideContent);

            btn.click(function(){
                box.slideToggle("slow");
            });
        },
        delay: function(callback, ms) {
            var timer = 0;
            clearTimeout(timer);
            timer = setTimeout(callback, ms);
        }
    };
});
