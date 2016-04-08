define([ 'jquery', 'jqueryui', 'block_favorites/ajax', 'block_favorites/event' ], function($, _, ajax, event) {
    return {
        init: function() {
            var Y = window.Y, M = window.M;
            var $content = $('.block_favorites .content');
            function reload_content() {
                ajax.call('content', {}, function(content) {
                    ajax.render('content', content, function(html) {
                        $content.html(html).find('.type_activity').draggable({ revert: 'invalid' });
                    });
                });
            }
            function setup_star_icon(cmid) {
                var $icon = $('<div class="block_favorites-icon"/>');
                if ($content.find('.starred-' + cmid).length) {
                    $icon.addClass('starred');
                }
                $icon.on('click', function() {
                    var starred = !$icon.hasClass('starred');
                    ajax.call('star', { cmid: cmid, starred: starred }, function() {
                        if (starred) {
                            $icon.addClass('starred');
                        } else {
                            $icon.removeClass('starred');
                        }
                        reload_content();
                    });
                });
                $('#module-' + cmid).prepend($icon);
            }
            $content.find('.type_activity').draggable({ revert: 'invalid' });
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
                    var lightbox = M.util.add_lightbox(Y, Y.one(event.target));
                    lightbox.show();
                    ajax.call('duplicate', args, function(response) {
                        var newcm = Y.Node.create(response.fullcontent);
                        Y.one(event.target).one('ul.section').appendChild(newcm);
                        Y.use('moodle-course-coursebase', function() {
                            M.course.coursebase.invoke_function('setup_for_resource', newcm);
                        });
                        if (M.core.actionmenu && M.core.actionmenu.newDOMNode) {
                            M.core.actionmenu.newDOMNode(newcm);
                        }
                    }).done(function() {
                        ui.draggable.css({ left: 0, top: 0 });
                        lightbox.hide();
                    }).fail(function() {
                        ui.draggable.css({ left: 0, top: 0 });
                        lightbox.hide();
                    });
                }
            });
            $('ul.section li.activity').each(function() {
                setup_star_icon(/(\d+)$/.exec(this.id)[1]);
            });
            event.on('course-module-created', setup_star_icon);
            event.on('course-module-updated', reload_content);
            event.on('course-module-deleted', reload_content);
        }
    };
});
