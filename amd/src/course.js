/**
 * @package   block_favorites
 * @copyright 2018 MALU {@link https://github.com/andantissimo}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
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
         * On star icon clicked.
         */
        function onclick() {
            var cmid = +this.id.match(/(\d+)$/)[1];
            var star = !this.classList.contains('starred');
            call('block_favorites_star', {cmid: cmid, starred: star}).then(function() {
                this.classList[star ? 'add' : 'remove']('starred');
                reload();
            }.bind(this)).catch(notification.exception);
        }

        /**
         * Put a star icon on the left of the activity.
         *
         * @param {JQuery} $cm
         */
        function puticon($cm) {
            if (!$cm.length || !$cm.attr('id')) {
                return; // invalid argument
            }
            var cmid = +$cm.attr('id').match(/(\d+)$/)[1];
            if ($cm.find('.block_favorites-icon').length) {
                return; // already exists
            }
            var $icon = $('<div class="block_favorites-icon"/>');
            $icon.attr('id', 'block_favorites-icon-' + cmid);
            if ($content.find('.starred-' + cmid).length) {
                $icon.addClass('starred');
            }
            $icon.on('click', onclick);
            $cm.prepend($icon);
        }

        return {
            setup: function() {
                droppable();
                draggable();

                $('ul.section li.activity').each(function() {
                    puticon($(this));
                });

                var observer = new MutationObserver(function(mutations) {
                    mutations.some(function(mutation) {
                        // activity moved or duplicated
                        if (mutation.target.classList &&
                            mutation.target.classList.contains('moodle-core-dragdrop-draghandle')) {
                            puticon($(mutation.target).closest('li.activity'));
                            reload();
                            return true;
                        }
                        return Array.prototype.some.call(mutation.addedNodes, function(node) {
                            // section moved
                            if (node.classList &&
                                node.classList.contains('section_add_menus') &&
                                mutation.removedNodes.length === 0) {
                                reload();
                                return true;
                            }
                            return false;
                        }) || Array.prototype.some.call(mutation.removedNodes, function(node) {
                            // activity name updated
                            if (node.classList &&
                                node.classList.contains('updating') &&
                                node.getAttribute('data-itemtype') === 'activityname') {
                                reload();
                                return true;
                            }
                            // activity deleted
                            if (/^module-\d+$/.test(node.id)) {
                                reload();
                                return true;
                            }
                            return false;
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
