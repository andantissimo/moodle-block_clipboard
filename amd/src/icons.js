define([ 'jquery', 'jqueryui', 'core/ajax', 'core/templates', 'core/notification' ], function($, _, ajax, tpl, popup) {
    function call(methodname, args, done) {
        ajax.call([ { methodname: methodname, args: args } ])[0].done(done).fail(popup.exception);
    }
    return {
        init: function() {
            var $content = $('.block_favorites .content');
            function setup_star_icon(activity) {
                var $activity = $(activity), cmid = Number($activity.attr('id').match(/(\d+)$/)[1]);
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
            }
            $content.find('.type_activity').draggable({ revert: true });
            $('li.section').droppable({
                accept: '.type_activity',
                hoverClass: 'highlight',
                drop: function(event, ui) {
                    var $section = $(event.target);
                    var args = {
                        courseid: Number($('body').attr('class').match(/course-(\d+)/)[1]),
                        section: Number($section.attr('id').match(/(\d+)$/)[1]),
                        cmid: Number(ui.draggable.attr('class').match(/starred-(\d+)/)[1])
                    };
                    call('block_favorites_duplicate', args, function(response) {
                        var Y = window.Y, M = window.M;
                        var newcm = Y.Node.create(response.fullcontent);
                        Y.one(event.target).one('ul.section').appendChild(newcm);
                        Y.use('moodle-course-coursebase', function() {
                            M.course.coursebase.invoke_function('setup_for_resource', newcm);
                        });
                        if (M.core.actionmenu && M.core.actionmenu.newDOMNode) {
                            M.core.actionmenu.newDOMNode(newcm);
                        }
                        setup_star_icon(newcm.getDOMNode());
                    });
                }
            });
            $('ul.section li.activity').each(function() { setup_star_icon(this); });
        }
    };
});
