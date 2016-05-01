var Sebastian = function(options) {
	this.options = $.extend({}, options);
	this.routes = {};

	//window.onHashChange = this.router.resolve.bind(this);
	window.onhashchange = this.router.resolveEvent.bind(this);
	//$.on('hashchange', this.router.resolve.bind(this));
	this.router.initialize();
}

Sebastian.prototype = {
	initialize: function() {
		var hash = (location.hash || '#').split("#")[1];
		this.router.resolve(hash);
	},

	generateUrl: function(path, args) {
		args = args ? args : {};
		var route = this.routes[path];//.route;
		if (route) route = route.route;
		else return null;

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
	},

	router: {
		routes: {},
		initialize: function() {

		},

		generateRouteRegex: function() {
			return /\/portal\/([0-9]+)/;
		},

		register: function(name, route, callback) {
			var regex = this.generateRouteRegex(route);
			this.routes[name] = { 
				name: name,
				route: route, 
				regex: regex,
				callback: callback 
			};
		},

		resolveEvent: function(event) {
			var oldUrl = event.oldURL;
			var newUrl = event.newURL;

			var hash = newUrl.split("#")[1];
			sebastian.router.resolve(hash);
		},

		resolve: function(hash) {
			console.log("resolving", hash);
			$.each(this.routes, function(name, route) {
				var routeRegex = route.regex;
				var groups = routeRegex.exec(hash);	

				if (groups) route.callback.apply(this, groups.slice(1));
			});
		}
	}
}

window.sebastian = new Sebastian();
//sebastian.init();