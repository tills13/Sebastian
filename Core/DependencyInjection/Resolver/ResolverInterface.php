<?php
    namespace Sebastian\Core\DependencyInjection\Resolver;

    use \ReflectionParameter;
    use Sebastian\Core\DependencyInjection\Injector;

    interface ResolverInterface {
        public function canResolve(ReflectionParameter $symbol) : bool;
        public function resolve(Injector $injector, ReflectionParameter $symbol);
    }