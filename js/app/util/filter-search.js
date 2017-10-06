import 'jquery';
import Fuse from 'fuse.js';

let defaults = {
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
    onEmpty: false,
    onNoMatch: false
};

var $search = null,
    data = [],
    fuse  = null,
    settings = {};

var init = function(element, config) {
    settings = $.extend({}, defaults, config);

    $search = $(element);

    fuse = new Fuse(data, {
        keys: ['search'],
        shouldSort: false,
        id: 'index',

        threshold: 0.1,
        maxPatternLength: 200
    });

    refreshItems();
    bindEvents();
};

function bindEvents() {
    $search.on('change keyup', function() {
        search($(this).val());
    });

    if (settings.onFocus) {
        $search.on('focus', settings.onFocus);
    }

    if (settings.onBlur) {
        $search.on('blur', settings.onBlur);
    }

    if ($search.val() !== '') {
        $search.trigger('change');
    }
}

function search(term) {
    var matched = false;

    if ($.trim(term).length > 0) {
        matched = fuse.search(term);
    }

    for (var i = 0; i < data.length; i++) {
        if (matched !== false && -1 !== $.inArray((i + 1).toString(), matched)) {
            settings.onShow(data[i].$elem);
        } else {
            settings.onHide(data[i].$elem);
        }
    }

    if (Array.isArray(matched) && matched.length === 0) {
        if (settings.onNoMatch) {
            settings.onNoMatch($search);
        }
    }

    if (matched === false) {
        if (settings.onEmpty) {
            settings.onEmpty($search);
        }
    }
}

function refreshItems() {
    var newData = [],
        $searchItems = $(settings.searchItem),
        $elem;

    for (var i = 0; i < $searchItems.length; i++) {
        $elem = $($searchItems[i]);

        newData.push({
            search: getItemContent($elem),
            $elem: $elem,
            index: i + 1
        });
    }

    data = fuse.setCollection(newData);
}

function getItemContent($elem) {
    var $search,
        txt,
        content = [];

    $search = $elem;
    if (settings.searchQuery) {
        $search = $search.find(settings.searchQuery);
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

export default init;
