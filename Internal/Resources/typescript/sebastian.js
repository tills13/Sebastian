var Sebastian = (function () {
    function Sebastian() {
        this.router = new Router();
    }
    Sebastian.prototype.registerRoutes = function (routes) {
        this.router.registerRoutes(routes);
    };
    return Sebastian;
}());
var sebastian = new Sebastian();
