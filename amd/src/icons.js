define([ 'jquery', 'core/ajax', 'core/templates', 'core/notification' ], function($, ajax, templates, notification) {
    function call(methodname, args, done) {
        ajax.call([ { methodname: methodname, args: args } ])[0].done(done).fail(notification.exception);
    }
    return {
        init: function() {
            var $content = $('.block_favorites .content');
            function refresh() {
                call('block_favorites_content', {}, function(context) {
                    templates.render('block_favorites/content', context).done(function(html) {
                        $content.html(html);
                    }).fail(notification.exception);
                });
            }
            $('.section li.activity').each(function() {
                var $activity = $(this), cmid = Number($activity.attr('id').match(/(\d+)$/)[1]);
                var $icon = $('<div class="block_favorites-icon"/>');
                if ($content.find('.fav-' + cmid).length) {
                    $icon.addClass('starred');
				}
                $icon.on('click', function() {
                    call('block_favorites_star', { cmid: cmid }, function(starred) {
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
