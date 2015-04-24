define(['jquery'], function($) {
    // WORST JAVSCRIPT EVER
    return {
        selectTarget: '#kraken__add-property #config-prop',
        labelTarget: '#kraken__add-property .config-description',
        genericTarget: '#kraken__add-property #config-value',
        explicitTarget: '#kraken__explicit-values li',
        listParentTarget: '#kraken__explicit-values li #config-strings',

        removalAnchor: '<a class="config-strings__icon"><svg class="icon"><use xmlns:xlink="http://www.w3.org/1999/xlink" xlink:href="/icons.svg#cross"></use></svg></a>',
        appendAnchor: '<a class="config-strings__icon--add"><svg class="icon"><use xmlns:xlink="http://www.w3.org/1999/xlink" xlink:href="/icons.svg#add"></use></svg></a>',
        listContainer: '<p class="config-strings-container"></p>',

        init: function() {
            var $select = $(this.selectTarget),
                $labels = $(this.labelTarget),
                $explicit = $(this.explicitTarget),
                $listParent = $(this.listParentTarget);

            if ($select.length) {

                // nuke it
                $(this.genericTarget).parent().remove();
                $('#kraken__explicit-values').show();
                this.attachAddToList($listParent);
                this.attachRemoveFromList($listParent);

                var regenerator = this.regenerate($select, $labels, $explicit);
                $select.on('change', regenerator);

                // Fire off once to start
                regenerator();
            }
        },
        regenerate: function($select, $labels, $explicit) {

            return function(event) {
                $labels.hide();
                $explicit.hide();

                if ($select.val().length > 0) {
                    $labels
                        .filter('[data-schema="' + this.value + '"]')
                        .show();

                    var dataType = $select.find(':selected').data('type');

                    $('#kraken__explicit-values li #config-' + dataType).closest('li').show();
                }

                if (event) {
                    event.preventDefault();
                }
            };
        },

        attachAddToList: function($parent) {
            var appendAnchor = this.appendAnchor,
                appendToList = this.appendToList($parent);

            var $append = $(appendAnchor)
                .on('click', appendToList);

            $parent.closest('p').append($append);
        },
        appendToList: function($parent) {
            var removalAnchor = this.removalAnchor,
                removalHandler = this.removeFromList,
                listContainer = this.listContainer;

            return function(event) {
                $clone = $parent.clone();
                $clone
                    .val('')
                    .removeAttr('id');

                var $removal = $(removalAnchor).on('click', removalHandler);

                $(listContainer)
                    .append($clone, $removal)
                    .appendTo($parent.closest('li'));
            };
        },
        attachRemoveFromList: function($parent) {
            var removalAnchor = this.removalAnchor,
                removalHandler = this.removeFromList;

            // iterate through lists and attach removal anchors
            $parent.closest('p').siblings('p').each(function (index, entry) {
                var $removal = $(removalAnchor).on('click', removalHandler);
                $(entry).append($removal);
            });

        },
        removeFromList: function(event) {
            $(this).parent().remove();
            event.preventDefault();
        }
    };
});
