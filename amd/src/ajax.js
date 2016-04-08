define([ 'core/ajax', 'core/templates', 'core/notification' ], function(ajax, templates, notification) {
    return {
        /**
         * @param {String} name
         * @param {Object} args
         * @param {Function} done
         * @returns {Promise}
         */
        call: function(name, args, done) {
            var promise = ajax.call([ { methodname: 'block_favorites_' + name, args: args } ])[0];
            return promise.then(done, notification.exception);
        },
        /**
         * @param {String} name
         * @param {Object} context
         * @param {Function} done
         * @returns {Promise}
         */
        render: function(name, context, done) {
            var promise = templates.render('block_favorites/' + name, context);
            return promise.then(done, notification.exception);
        }
    };
});
