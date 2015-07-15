var $ = require('jquery');

// @todo rewrite this pile of shit

module.exports = {
    pickerTarget: '.js-pool-picker',
    poolTarget: '.js-pool',
    viewsTarget: '[data-view]',
    cardTarget: '[data-deploy]',

    $picker: null,
    $poolContainer: null,
    views: null,
    listClass: null,

    init: function() {
        this.$picker = $(this.pickerTarget);
        this.$poolContainer = $(this.poolTarget);
        this.views = this.buildViews();

        if (Object.keys(this.views).length > 0 && this.$picker.length > 0) {
            this.attach(this.$picker);
        }
    },
    attach: function($picker) {
        var switchView = this.switchView.bind(this);

        this.listClass = $('.cards').first().attr('class');

        $picker.on('change', switchView);
    },
    switchView: function(event) {

        var selectedView = this.$picker.val(),
            $rawdeploys = $(this.cardTarget),
            $deploys = {};

        // no deploys, die
        if ($rawdeploys.length === 0) {
            return;
        }

        var $unpooled = this.unpool(selectedView, $rawdeploys);

        // Move elements to unpooled
        this.$poolContainer
            .empty()
            .append($unpooled);

        this.persistView(selectedView);

        // valid view = pool deployments
        if (this.views.hasOwnProperty(selectedView)) {
            var $section,
                pools = this.views[selectedView];

            for(var pool in pools) {
                $section = this.poolDeployments(pool, pools[pool], $unpooled);
                if ($section !== null) {
                    $section.insertBefore($unpooled);
                }
            }
        }
    },
    buildViews: function() {
        var $views = $(this.viewsTarget)
            views = {};

        if ($views.length == 0) {
            return views;
        }

        $views.each(function (index, view) {
            var $view = $(view),
                id = $view.data('view'),
                $pools = $view.find('[data-pool]'),
                pools = {};

            $pools.each(function (index, pool) {
                var $pool = $(pool),
                    name = $pool.data('pool'),
                    deploys = $pool.text();

                pools[name] = JSON.parse(deploys);
            });

            views[id] = pools;
        });

        return views;
    },

    unpool: function (selectedView, $rawdeploys) {

        var pool_name = '';
        if (selectedView.length > 0) {
            pool_name = 'Unpooled';
        }

        var $section = this.buildPoolDOM(pool_name);
        $section
            .find('.js-card-list')
            .append($rawdeploys);

        return $section;
    },
    poolDeployments: function(pool_name, pool, $unpooled) {

        var $section = this.buildPoolDOM(pool_name),
            $ul = $section.find('.js-card-list'),
            hasDeploys = false;

        // It is important to use pools as the basis for building the new sections, because deployments
        //  are sorted by the backend. The dom order is not guaranteed to be sorted correctly.
        for(var index in pool) {

            var $deploy = $unpooled.find('[data-deploy="' + pool[index] + '"]');
            if ($deploy.length > 0) {
                $ul.append($deploy);
                hasDeploys = true;
            }
        }

        // Skip empty pools, if one got in somehow
        if (hasDeploys) {
            return $section;
        }

        return null;
    },
    buildPoolDOM: function(pool_name) {
        var $section = $('<div>')
            .append('<ul class="js-card-list ' + this.listClass + '">');

        if (pool_name.length > 0) {
            $section
                .prepend('<h3>' + pool_name + '</h3>');
        }

        return $section;
    },
    persistView: function (selectedView) {
        var action = this.$picker.closest('form').attr('action');

        if (action.length === 0) {
            return;
        }
        $.ajax(action, {
            type: 'POST',
            dataType: 'json',
            contentType: 'application/json',
            data: JSON.stringify({
                'view': selectedView
            })
        });
    }
};
