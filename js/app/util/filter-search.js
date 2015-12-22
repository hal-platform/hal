var $ = require('jquery');
var fuse = require('fuse.js');

module.exports = {
    $search: null,
    data: [],
    fuse : null,

    settings: {},
    defaults: {
        searchItem: '',
        searchQuery: '',

        onHide: function($item) {
            $item.hide();
        },
        onShow: function($item) {
            $item.show();
        },

        onFocus: false,
        onBlur: false,
        onEmpty: false
    },

    init: function(element, config) {
        this.settings = $.extend({}, this.defaults, config);

        this.$search = $(element);

        this.fuse = new fuse(this.data, {
            keys: ['search'],
            shouldSort: false,
            id: 'index',

            threshold: 0.3,
            maxPatternLength: 200
        });

        this.refreshItems();
        this.bindEvents();
    },

    bindEvents: function() {
        var _this = this;

        this.$search.on('change keyup', function() {
            _this.search($(this).val());
        });

        if (this.settings.onFocus) {
            this.$search.on('focus', this.settings.onFocus);
        }

        if (this.settings.onBlur) {
            this.$search.on('blur', this.settings.onBlur);
        }

        if ( this.$search.val() !== '' ) {
            this.$search.trigger('change');
        }
    },

    search: function(term) {
        var matched = false;

        if ($.trim(term).length > 0) {
            matched = this.fuse.search(term);
        }

        for (var i = 0; i < this.data.length; i++) {

            if (matched !== false && -1 !== $.inArray(i + 1, matched)) {
                this.settings.onShow(this.data[i].$elem);
            } else {
                this.settings.onHide(this.data[i].$elem);
            }
        }

        if (matched === false) {
            if (this.settings.onEmpty) {
                this.settings.onEmpty(this.$search);
            }
        }
    },

    refreshItems: function() {
        var data = [],
            $searchItems = $(this.settings.searchItem),
            $elem;

        for (var i = 0; i < $searchItems.length; i++) {
            $elem = $($searchItems[i]);

            data.push({
                search: this.getItemContent($elem),
                $elem: $elem,
                index: i + 1
            });
        }

        this.data = this.fuse.set(data);
    },
    getItemContent: function($elem) {
        var $search,
            txt,
            content = [];

        $search = $elem;
        if (this.settings.searchQuery) {
            $search = $search.find(this.settings.searchQuery);
        }

        for (var x = 0; x < $search.length; x++) {
            txt = $($search[x]).text();
            txt = $.trim(txt);

            if (txt.length > 0) {
                content.push(txt);
            }
        }

        return content;
    }
};
