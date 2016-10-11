var Router = (function () {
    function Router() {
        this.routes = {};
        this.frontRoutes = {};
        this.initialize();
    }
    Router.prototype.initialize = function () {
        window.onhashchange = this.onHashChange.bind(this);
        var hash = (location.hash || '#').split('#')[1];
    };
    Router.prototype.registerFrontRoutes = function (routes) {
        this.frontRoutes = $.extend(this.frontRoutes, routes);
    };
    Router.prototype.registerRoutes = function (routes) {
        this.routes = $.extend(this.routes, routes);
    };
    Router.prototype.onHashChange = function (event) {
        var oldUrl = event.oldURL;
        var newUrl = event.newURL;
        if (oldUrl === newUrl)
            return;
        var hash = newUrl.split("#")[1];
        this.resolve(hash);
    };
    Router.prototype.resolve = function (hash) {
        $.each(this.frontRoutes, function (index, route) {
        });
    };
    Router.prototype.generateUrl = function (name, parameters) {
        if (parameters === void 0) { parameters = {}; }
        var route = this.routes[name];
        if (!route)
            return null;
        var mRoute = route.route;
        $.each(parameters, function (argument, value) {
            var regex = new RegExp("{(" + argument + "(?::[^}]*)?)}");
            if (mRoute.match(regex)) {
                mRoute = mRoute.replace(regex, parameters[argument]);
                delete parameters[argument];
            }
        });
        return mRoute + "?" + $.param(parameters);
    };
    return Router;
}());
