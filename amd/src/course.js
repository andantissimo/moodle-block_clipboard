define(
    ['jquery', 'jqueryui', 'core/yui', 'core/ajax', 'core/templates', 'core/notification'],
    /**
     * @param {JQueryStatic} $
     * @param {Object} _
     * @param {YUI} Y
     * @param {module:core/ajax} ajax
     * @param {module:core/templates} templates
     * @param {module:core/notification} notification
     * @returns {Object}
     */
    function($, _, Y, ajax, templates, notification) {
        var M = window.M;
        var courseid = +$('body').attr('class').match(/course-(\d+)/)[1];
        var $content = $('.block_favorites .content');

        /**
         * Call a single ajax request.
         *
         * @param {String} methodname
         * @param {Object} args
         * @return {Promise}
         */
        function call(methodname, args) {
            return ajax.call([{
                methodname: methodname,
                args: args || {}
            }])[0];
        }

        /**
         * On favorite item dropped.
         *
         * @param {Event} event
         * @param {Object} ui
         */
        function ondrop(event, ui) {
            var lightbox = M.util.add_lightbox(Y, Y.one(event.target));
            var args = {
                courseid: courseid,
                section: +event.target.id.match(/(\d+)$/)[1],
                cmid: +ui.draggable.attr('class').match(/starred-(\d+)/)[1]
            };
            call('block_favorites_duplicate', args).then(function(response) {
                var newcm = Y.Node.create(response.fullcontent);
                Y.one(event.target).one('ul.section').appendChild(newcm);
                Y.use('moodle-course-coursebase', function() {
                    M.course.coursebase.invoke_function('setup_for_resource', newcm);
                });
                if (M.core.actionmenu && M.core.actionmenu.newDOMNode) {
                    M.core.actionmenu.newDOMNode(newcm);
                }
                ui.draggable.css({left: 0, top: 0});
                lightbox.hide();
            }).catch(function() {
                ui.draggable.css({left: 0, top: 0});
                lightbox.hide();
            });
            lightbox.show();
        }

        /**
         * Make course sections droppable.
         */
        function droppable() {
            $('li.section').droppable({
                accept: '.type_activity',
                hoverClass: 'highlight',
                drop: ondrop
            });
        }

        /**
         * Make block items draggable.
         */
        function draggable() {
            $content.find('.type_activity').draggable({
                revert: 'invalid'
            });
        }

        /**
         * Reload the block content.
         */
        function reload() {
            call('block_favorites_get_tree').then(function(tree) {
                return templates.render('block_favorites/content', tree);
            }).then(function(html) {
                $content.html(html);
                draggable();
            }).catch(notification.exception);
        }

        /**
         * Put a star icon on the left of the activity.
         *
         * @param {JQuery} $cm
         */
        function putstar($cm) {
            var cmid = +$cm.attr('id').match(/(\d+)$/)[1];
            if ($cm.find('.block_favorites-icon').length) {
                return;
            }
            var $icon = $('<div class="block_favorites-icon"/>');
            if ($content.find('.starred-' + cmid).length) {
                $icon.addClass('starred');
            }
            $icon.on('click', function() {
                var starred = !$icon.hasClass('starred');
                call('block_favorites_star', {cmid: cmid, starred: starred}).then(function() {
                    $icon[starred ? 'addClass' : 'removeClass']('starred');
                    reload();
                }).catch(notification.exception);
            });
            $cm.prepend($icon);
        }

        return {
            setup: function() {
                droppable();
                draggable();

                $('ul.section li.activity').each(function() {
                    putstar($(this));
                });

                var observer = new MutationObserver(function(mutations) {
                    mutations.forEach(function(mutation) {
                        Array.prototype.some.call(mutation.addedNodes, function(node) {
                            if (node.classList &&
                                node.classList.contains('moodle-core-dragdrop-draghandle')) {
                                putstar($(mutation.target).closest('li.activity'));
                                reload();
                            }
                            return true;
                        });
                        Array.prototype.some.call(mutation.removedNodes, function(node) {
                            if (node.classList &&
                                node.classList.contains('inplaceeditable-text') &&
                                node.classList.contains('updating')) {
                                reload();
                            }
                            return true;
                        });
                    });
                });
                observer.observe(document.querySelector('.course-content'), {
                    childList: true,
                    subtree: true
                });
            }
        };
    }
);
