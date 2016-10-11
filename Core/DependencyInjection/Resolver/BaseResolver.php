<?php
    namespace Sebastian\Core\DependencyInjection\Resolver;

    use \ReflectionParameter;
    
    use Sebastian\Core\DependencyInjection\Injector;
    
    class BaseResolver implements ResolverInterface {
        public function canResolve(ReflectionParameter $symbol) : bool {
            return true;
        }

        public function resolve(Injector $injector, ReflectionParameter $symbol) {
            $class = $symbol->getClass();
            $param = $class ? $class->getShortName() : $symbol->getName();

            $dependency =
                $injector->getDependency("\${$param}") ?? 
                $injector->getDependency("@{$param}") ??
                $injector->getDependency("{$param}") ??
                ($symbol->isDefaultValueAvailable() ? $symbol->getDefaultValue() : null);
            
            return $dependency;
        }
    }