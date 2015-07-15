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

        $rawdeploys.each(function(index, deploy) {
            var $deploy = $(deploy),
                id = $deploy.data('deploy');

            $deploys[id] = $deploy;
        });

        // valid view = pool deployments
        if (this.views.hasOwnProperty(selectedView)) {
            var $sections = this.poolDeployments(this.views[selectedView], $deploys);

        } else {
            // otherwise, unpool everything
            var $sections = [
                this.buildPoolDOM('', $deploys)
            ]
        }

        this.$poolContainer
            .empty()
            .append($sections);
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

                pools[name] = {
                    elements: [],
                    ids: JSON.parse(deploys)
                };
            });

            views[id] = pools;
        });

        console.log(views);
        return views;
    },
    poolDeployments: function(pools, $deploys) {
        var $sections = [];

        // It is important to use pools as the basis for building the new sections, because deployments
        //  are sorted by the backend. The dom order is not guaranteed to be sorted correctly.
        for(var pool in pools) {
            pools[pool].elements = [];

            for (var deploy in pools[pool].ids) {
                if ($deploys.hasOwnProperty(deploy)) {
                    pools[pool].elements.push($deploys[deploy]);
                    delete $deploys[deploy];
                }
            }

            if (pools[pool].elements.length > 0) {
                $sections.push(this.buildPoolDOM(pool, pools[pool].elements));
            }
        }

        // if there are deploys remaining "unpool", add them to another section
        if (Object.keys($deploys).length > 0) {
            $sections.push(this.buildPoolDOM('Unpooled', $deploys));
        }

        return $sections;
    },
    buildPoolDOM: function(pool_name, $deploys) {
        var $ul = $('<ul class="' + this.listClass + '">');
        for(var index in $deploys) {
            $ul.append($deploys[index]);
        }

        var $section = $('<div>')
            .append($ul);

        if (pool_name.length > 0) {
            $section
                .prepend('<h3>' + pool_name + '</h3>');
        }

        return $section;
    }
};
