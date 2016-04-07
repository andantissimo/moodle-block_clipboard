define([ 'core/ajax', 'core/templates', 'core/notification' ], function(ajax, templates, notification) {
    return {
		call: function(name, args, done) {
	        var promise = ajax.call([ { methodname: 'block_favorites_' + name, args: args } ])[0];
            promise.done(done);
            promise.fail(notification.exception);
		},
        render: function(name, context, done) {
            var promise = templates.render('block_favorites/' + name, context);
            promise.done(done);
            promise.fail(notification.exception);
        }
    };
});
