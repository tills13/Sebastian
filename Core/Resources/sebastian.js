var Sebastian = function(options = {}) {
	this.options = $.extend({

	}, options);
}

Sebastian.prototype = {
	generateUrl: function(path, args = {}) {
		args = args ? args : {};
		var route = this.routes[path].match;

		$.each(args, function(arg, value) {
			var regexp = new RegExp("\{(" + arg + "(?:\:[^\}]*)?)\}");

			if (route.match(regexp, value)) {
				route = route.replace(regexp, value);	
				delete args[arg];
			}
		});


		if (Object.keys(args).length > 0) {
			args = (Object.keys(args)).map(function(currentValue) {
				return currentValue + '=' + args[currentValue];
			});
			
			route += ('?' + args.join('&'));
		}

		return route;
	}
}

window.sebastian = new Sebastian();
//sebastian.init();