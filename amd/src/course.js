define([ 'jquery', 'jqueryui', 'core/ajax', 'core/templates', 'core/notification' ], function($, _, ajax, tpl, popup) {
    function call(methodname, args, done) {
        ajax.call([ { methodname: methodname, args: args } ])[0].done(done).fail(popup.exception);
    }
    return {
        init: function() {
            var Y = window.Y, M = window.M;
            var $content = $('.block_favorites .content');
            function reload_content() {
                call('block_favorites_content', {}, function(content) {
                    tpl.render('block_favorites/content', content).done(function(html) {
                        $content.html(html).find('.type_activity').draggable({ revert: true });
                    }).fail(popup.exception);
                });
            }
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
                        reload_content();
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
                        var newcm = Y.Node.create(response.fullcontent);
                        Y.one(event.target).one('ul.section').appendChild(newcm);
                        Y.use('moodle-course-coursebase', function() {
                            M.course.coursebase.invoke_function('setup_for_resource', newcm);
                        });
                        if (M.core.actionmenu && M.core.actionmenu.newDOMNode) {
                            M.core.actionmenu.newDOMNode(newcm);
                        }
                    });
                }
            });
            $('ul.section li.activity').each(function() { setup_star_icon(this); });
            Y.on('domready', function() {
                var newDOMNode = M.core.actionmenu.newDOMNode;
                M.core.actionmenu.newDOMNode = function(element) {
                    newDOMNode.call(M.core.actionmenu, element);
                    setup_star_icon(element.getDOMNode());
                };
                var send_request = M.course.resource_toolbox.send_request;
                M.course.resource_toolbox.send_request = function(data, spinner, callback, config) {
                    if (data.action === 'DELETE' || data.field === 'updatetitle') {
                        var original = callback;
                        callback = function() {
                            if (original) {
                                original.apply(this, arguments);
                            }
                            reload_content();
                        };
                    }
                    send_request.call(M.course.resource_toolbox, data, spinner, callback, config);
                };
            });
        }
    };
});
