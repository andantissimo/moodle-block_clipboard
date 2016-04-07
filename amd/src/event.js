define([ 'core/yui' ], function(Y) {
    var M = window.M,
        listeners = {};
    function trigger(name) {
        if (listeners[name]) {
            var args = Array.prototype.slice.call(arguments, 1);
            for (var i = 0; i < listeners[name].length; i++) {
                listeners[name][i].apply(this, args);
            }
        }
    }
    function before(filter, original) {
        return function() {
            return original.apply(this, filter.apply(this, arguments));
        };
    }
    function after(original, receiver) {
        return function() {
            return receiver.apply(original.apply(this, arguments), arguments);
        };
    }
    Y.on('domready', function() {
        M.core.actionmenu.newDOMNode = after(M.core.actionmenu.newDOMNode, function(element) {
            trigger('course-module-created', Y.Moodle.core_course.util.cm.getId(element));
            return this;
        });
        M.course.resource_toolbox.send_request = before(function(data, spinner, callback, config) {
            var receiver = null;
            if (data.field === 'updatetitle') {
                receiver = function() { trigger('course-module-updated', data.id); };
            } else if (data.action === 'DELETE') {
                receiver = function() { trigger('course-module-deleted', data.id); };
            }
            if (receiver) {
                callback = callback ? after(callback, receiver) : receiver;
            }
            return [ data, spinner, callback, config ];
        }, M.course.resource_toolbox.send_request);
    });
    return {
        on: function(name, callback) {
            listeners[name] = listeners[name] || [];
            listeners[name].push(callback);
        }
    };
});
