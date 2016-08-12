interface RouteInterface {
    route: string;
    match: string;
    component?: string;
    controller?: string;
    method?: string;
    methods?: string[];
}

interface FrontRouteInterface extends RouteInterface {
    callback?: Function;
}

class Router {
    protected routes: { [name: string]: RouteInterface };
    protected frontRoutes: { [name: string]: FrontRouteInterface };

    constructor() {
        this.routes = {};
        this.frontRoutes = {};

        this.initialize();
    }

    initialize(): void {
        window.onhashchange = this.onHashChange.bind(this);
        let hash = (location.hash || '#').split('#')[1];
    }

    registerRoutes(routes: { [name: string]: RouteInterface }): void {
        this.routes = routes;
    }

    private onHashChange(event: HashChangeEvent): any {
        let oldUrl = event.oldURL;
        let newUrl = event.newURL;

        if (oldUrl === newUrl) return;

        var hash = newUrl.split("#")[1];
        this.resolve(hash);
    }

    private resolve(hash: string): void {
        $.each(this.frontRoutes, (index, route: FrontRouteInterface) => {
            
        });
    }

    generateUrl(name: string, parameters: any = {}): string {
        let route = this.routes[name];
        if (!route) return null;

        let mRoute = route.route;

        $.each(parameters, (argument, value) => {
            let regex = new RegExp(`\{(${argument}(?:\:[^\}]*)?)\}`);

            if (mRoute.match(regex)) {
                mRoute = mRoute.replace(regex, parameters[argument]);
                delete parameters[argument];
            }
        });

        return `${mRoute}?${$.param(parameters)}`;
    }
}