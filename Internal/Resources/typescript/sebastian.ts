class Sebastian {
    router: Router;

    constructor() {
        this.router = new Router();
    }

    registerRoutes(routes: { [name: string]: RouteInterface }): void {
        this.router.registerRoutes(routes);
    }
}

var sebastian = new Sebastian();