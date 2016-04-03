define([ 'jquery', 'core/ajax' ], function($, ajax) {
    function call(methodname, args) {
        return ajax.call([ { methodname: methodname, args: args || {} } ])[0];
    }
    return {
        init: function() {
            var $content = $('.block_favorites .content');
            function refresh() {
                call('block_favorites_content').done(function(html) {
                    $content.html(html);
                });
            }
            $('.section li.activity').each(function() {
                var $activity = $(this), cmid = Number($activity.attr('id').match(/(\d+)$/)[1]);
                var $icon = $('<div class="block_favorites-icon"/>');
                if ($content.find('.fav-' + cmid).length) {
                    $icon.addClass('starred');
				}
                $icon.on('click', function() {
                    call('block_favorites_star', { cmid: cmid }).done(function(starred) {
                        if (starred) {
                            $icon.addClass('starred');
                        } else {
                            $icon.removeClass('starred');
                        }
                        refresh();
                    });
                });
                $activity.prepend($icon);
            });
            $('.sitetopic .section li.activity').css('margin-left', '10px');
        }
    };
});
