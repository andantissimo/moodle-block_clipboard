/**
 * @package   block_clipboard
 * @copyright 2019 MALU {@link https://github.com/andantissimo}
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
        var $block = $('.block_clipboard');
        var $header = $block.find('#' + $block.attr('aria-labelledby'));
        var $content = $block.find('.content');
        var params = {
            capabilities: { backup: false, restore: false },
            actions: { copytoclipboard: '' }
        };
        /** @type {Promise<void>} */
        var reloading = null;

        /**
         * Call a single ajax request.
         *
         * @param {string} methodname
         * @param {object} [args]
         * @returns {Promise<object>}
         */
        function call(methodname, args) {
            return ajax.call([{
                methodname: methodname,
                args: args || {}
            }])[0];
        }

        /**
         * Check if the item is copied in clipboard.
         *
         * @param {number} cmid
         * @returns {boolean}
         */
        function copied(cmid) {
            return $content.find('[data-cmid="' + cmid + '"]').length !== 0;
        }

        /**
         * Reload the block content.
         *
         * @returns {Promise<void>}
         */
        function reload() {
            if (!reloading) {
                var spinner = M.util.add_spinner(Y, Y.Node($header[0]));
                spinner.show();
                reloading = call('block_clipboard_get_tree').then(function(tree) {
                    return templates.render('block_clipboard/content', {
                        config: M.cfg, // Workaround: {{config}} is set only in php renderer
                        courses: tree.courses
                    });
                }).then(function(html) {
                    $content.html(html);
                    copyable();
                    draggable();
                    deletable();
                    reloading = null;
                    spinner.hide();
                    return;
                }).catch(function(reason) {
                    spinner.hide();
                    notification.exception(reason);
                });
            }
            return reloading;
        }

        /**
         * On clipboard item dropped into a section.
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
            call('block_clipboard_paste', args).then(function(response) {
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
                $(event.target).find('ul.section li.activity').each(function() {
                    arrange($(this));
                });
                return;
            }).catch(function() {
                ui.draggable.css({left: 0, top: 0});
                lightbox.hide();
            });
            lightbox.show();
        }

        /**
         * Arrange activity edit actions.
         *
         * @param {JQuery} $cm
         */
        function arrange($cm) {
            if (!params.capabilities.backup) {
                return;
            }
            if (!$cm.length || !$cm.attr('id')) {
                return; // Invalid argument
            }
            var cmid = +$cm.attr('id').match(/^module-(\d+)$/)[1];
            var $menu = $cm.find('.section-cm-edit-actions');
            var $copy = $menu.find('[data-action="copytoclipboard"]');
            if (!$copy.length) {
                $copy = $(params.actions.copytoclipboard.replace(/<a href="([^"]*)"/, function(_, base) {
                    return '<a href="' + base + '?sesskey=' + M.cfg.sesskey + '&copy=' + cmid + '"';
                }));
                var $dupe = $menu.find('[data-action="duplicate"]');
                if ($dupe.hasClass('dropdown-item')) { // Boost theme
                    $copy.addClass('dropdown-item');
                }
                $copy.insertAfter($dupe);
            }
            if (copied(cmid)) {
                $copy.addClass('disabled');
            } else {
                $copy.removeClass('disabled');
            }
        }

        /**
         * Make activities copyable to clipboard.
         */
        function copyable() {
            if (params.capabilities.backup) {
                $('ul.section li.activity').each(function() {
                    arrange($(this));
                });
            }
        }

        /**
         * Make course sections droppable.
         */
        function droppable() {
            if (params.capabilities.restore) {
                $('li.section').droppable({
                    accept: '.type_activity',
                    hoverClass: 'highlight',
                    drop: ondrop
                });
            }
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
         * Make block items deletable.
         */
        function deletable() {
            $content.find('[data-action="delete"]').on('click', function(e) {
                e.preventDefault();
                var $item = $(this).closest('[data-cmid]');
                var cmid = $item.attr('data-cmid');
                call('block_clipboard_delete', {cmid: cmid});
                $item.remove();
                var $cm = $('#module-' + cmid);
                $cm.find('[data-action="copytoclipboard"]').removeClass('disabled');
            });
        }

        /**
         * Observe DOM changes.
         */
        function observe() {
            var dragging = false;
            var root = document.documentElement;
            var opts = {capture: true, passive: true};
            root.addEventListener('mousedown', function() { dragging = true; }, opts);
            root.addEventListener('mouseup', function() { dragging = false; }, opts);
            var observer = new MutationObserver(function(mutations) {
                if (dragging) {
                    return;
                }
                mutations.some(function(mutation) {
                    // Activity moved or duplicated
                    if (mutation.target.classList &&
                        mutation.target.classList.contains('editing_move') &&
                        mutation.target.classList.contains('moodle-core-dragdrop-draghandle')) {
                        var $cm = $(mutation.target).closest('li.activity');
                        if (copied($cm.attr('id').match(/^module-(\d+)$/)[1])) {
                            reload();
                        } else {
                            copyable();
                        }
                        return true;
                    }
                    return Array.prototype.some.call(mutation.addedNodes, function(node) {
                        // Section moved
                        if (node.classList &&
                            node.classList.contains('section_add_menus') &&
                            mutation.removedNodes.length === 0) {
                            droppable();
                            reload();
                            return true;
                        }
                        return false;
                    }) || Array.prototype.some.call(mutation.removedNodes, function(node) {
                        // Activity name updated
                        if (node.classList &&
                            node.classList.contains('updating') &&
                            node.getAttribute('data-itemtype') === 'activityname') {
                            if (copied(node.getAttribute('data-itemid'))) {
                                reload();
                            }
                            return true;
                        }
                        // Activity deleted
                        if (/^module-(\d+)$/.test(node.id)) {
                            if (copied(RegExp.$1)) {
                                reload();
                            }
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

        /**
         * Setup copy-to-clipboard action and drag'n'drop.
         *
         * @param {object} capabilities
         * @param {boolean} capabilities.backup
         * @param {boolean} capabilities.restore
         * @param {object} actions
         * @param {string} actions.copytoclipboard
         */
        function setup(capabilities, actions) {
            params = { capabilities: capabilities, actions: actions };
            copyable();
            droppable();
            $('body').on(
                'click keypress',
                'li.activity a.cm-edit-action[data-action="copytoclipboard"]',
                function(e) {
                    if (e.type === 'keypress' && e.keyCode !== 13) {
                        return;
                    }
                    var $item = $(this);
                    var $cm = $item.closest('li.activity');
                    var cmid = $cm.attr('id').match(/^module-(\d+)$/)[1];
                    e.preventDefault();
                    if ($item.hasClass('disabled')) {
                        return;
                    }
                    var spinner = M.util.add_spinner(Y, Y.Node($cm.find('.actions')[0]));
                    spinner.show();
                    call('block_clipboard_copy', {cmid: cmid}).then(reload).then(function() {
                        spinner.hide();
                        return;
                    }).catch(function(reason) {
                        spinner.hide();
                        notification.exception(reason);
                    });
                }
            );
            reload().then(function() {
                observe();
                return;
            }).catch(notification.exception);
        }

        return {
            setup: setup
        };
    }
);
