define([ 'jquery', 'jqueryui', 'core/ajax', 'core/templates', 'core/notification' ], function($, _, ajax, tpl, popup) {
    function call(methodname, args, done) {
        ajax.call([ { methodname: methodname, args: args } ])[0].done(done).fail(popup.exception);
    }
    return {
        init: function() {
            var $content = $('.block_favorites .content');
            $content.find('.type_activity').draggable({ revert: true });
            $('div.sitetopic, li.section').droppable({
                accept: '.type_activity',
                hoverClass: 'highlight',
                drop: function(event, ui) {
                    var $target = $(event.target);
                    var courseid = Number($('body').attr('class').match(/course-(\d+)/)[1]);
                    var section = $target.hasClass('sitetopic') ? 0 : Number($target.attr('id').match(/(\d+)$/)[1]);
                    var cmid = Number(ui.draggable.attr('class').match(/starred-(\d+)/)[1]);
                    window.console.log('courseid = ' + courseid + ', section = ' + section + ', cmid = ' + cmid);
                }
            });
            $('.section li.activity').each(function() {
                var $activity = $(this), cmid = Number($activity.attr('id').match(/(\d+)$/)[1]);
                var $icon = $('<div class="block_favorites-icon"/>');
                if ($content.find('.starred-' + cmid).length) {
                    $icon.addClass('starred');
                }
                $icon.on('click', function() {
                    var starred = !$icon.hasClass('starred');
                    call('block_favorites_star', { cmid: cmid, starred: starred }, function() {
                        if (starred) {
                            $icon.addClass('starred');
                        } else {
                            $icon.removeClass('starred');
                        }
                        call('block_favorites_content', {}, function(content) {
                            tpl.render('block_favorites/content', content).done(function(html) {
                                $content.html(html).find('.type_activity').draggable({ revert: true });
                            }).fail(popup.exception);
                        });
                    });
                });
                $activity.prepend($icon);
            });
            $('.sitetopic .section li.activity').css('margin-left', '10px');
        }
    };
});
