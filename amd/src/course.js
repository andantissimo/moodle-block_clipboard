/**
 * @package   block_favorites
 * @copyright 2018 MALU {@link https://github.com/andantissimo}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(
    ['jquery', 'jqueryui', 'core/yui', 'core/ajax', 'core/templates', 'core/notification'],
    /**
     * @param {JQueryStatic} $
     * @param {any} _
     * @param {YUI} Y
     * @param {module:core/ajax} ajax
     * @param {module:core/templates} templates
     * @param {module:core/notification} notification
     * @returns {{setup:function():void}}
     */
    function($, _, Y, ajax, templates, notification) {
        var courseid = +$.map(document.body.classList, function(token) {
            return /^course-(\d+)$/.exec(token);
        })[1];
        var $content = $('.block_favorites .content');

        /**
         * Call a single ajax request.
         *
         * @param {string} methodname
         * @param {object} args
         * @returns {Promise<object>}
         */
        function call(methodname, args) {
            return ajax.call([{
                methodname: methodname,
                args: args || {}
            }])[0];
        }

        /**
         * On favorite item dropped into a section.
         *
         * @param {Event} event
         * @param {JQueryUI.DroppableEventUIParam} ui
         */
        function ondrop(event, ui) {
            var lightbox = M.util.add_lightbox(Y, Y.one(event.target));
            var args = {
                courseid: courseid,
                section: +/^section-(\d+)$/.exec(event.target.id)[1],
                cmid: +ui.draggable.attr('data-cmid')
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
                return;
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
            if ($('li.section').droppable('instance')) {
                $content.find('.type_activity').draggable({
                    revert: 'invalid'
                });
            }
        }

        /**
         * Reload the block content.
         */
        function reload() {
            call('block_favorites_get_tree').then(function(tree) {
                return templates.render('block_favorites/content', {
                    config: M.cfg, // Workaround: {{config}} is set only in php renderer
                    courses: tree.courses
                });
            }).then(function(html) {
                $content.html(html);
                draggable();
                return;
            }).catch(notification.exception);
        }

        /**
         * On star icon clicked.
         *
         * @this {HTMLElement}
         */
        function onclick() {
            var cmid = +this.getAttribute('data-cmid');
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
                return; // Invalid argument
            }
            if ($cm.find('.block_favorites_icon').length) {
                return; // Already exists
            }
            var cmid = +$cm.attr('id').match(/^module-(\d+)$/)[1];
            var $icon = $('<div class="block_favorites_icon"/>');
            $icon.attr('data-cmid', cmid);
            if ($content.find('[data-cmid=' + cmid + ']').length) {
                $icon.addClass('starred');
            }
            $icon.on('click', onclick);
            $cm.prepend($icon);
        }

        /**
         * Setup drag'n'drop and star icons.
         *
         * @param {object} capabilities 
         * @param {boolean} capabilities.backup
         * @param {boolean} capabilities.restore
         */
        function setup(capabilities) {
            if (capabilities.restore) {
                droppable();
                draggable();
            }

            if (capabilities.backup) {
                $('ul.section li.activity').each(function() {
                    puticon($(this));
                });
            }

            var dragging = false;
            document.documentElement.addEventListener('mousedown', function() {
                dragging = true;
            }, {capture: true, passive: true});
            document.documentElement.addEventListener('mouseup', function() {
                dragging = false;
            }, {capture: true, passive: true});
            var observer = new MutationObserver(function(mutations) {
                if (dragging) {
                    return;
                }
                mutations.some(function(mutation) {
                    // Activity moved or duplicated
                    if (mutation.target.classList &&
                        mutation.target.classList.contains('editing_move') &&
                        mutation.target.classList.contains('moodle-core-dragdrop-draghandle')) {
                        if (capabilities.backup) {
                            puticon($(mutation.target).closest('li.activity'));
                        }
                        reload();
                        return true;
                    }
                    return Array.prototype.some.call(mutation.addedNodes, function(node) {
                        // Section moved
                        if (node.classList &&
                            node.classList.contains('section_add_menus') &&
                            mutation.removedNodes.length === 0) {
                            reload();
                            return true;
                        }
                        return false;
                    }) || Array.prototype.some.call(mutation.removedNodes, function(node) {
                        // Activity name updated
                        if (node.classList &&
                            node.classList.contains('updating') &&
                            node.getAttribute('data-itemtype') === 'activityname') {
                            reload();
                            return true;
                        }
                        // Activity deleted
                        if (/^module-\d+$/.test(node.id)) {
                            reload();
                            return true;
                        }
                        return false;
                    });
                });
            });
            setTimeout(function() {
                observer.observe(document.querySelector('.course-content'), {
                    childList: true,
                    subtree: true
                });
            }, 1000);
        }

        return {
            setup: setup
        };
    }
);
