<?php
    namespace Sebastian\Core\DependencyInjection\Resolver;

    use \ReflectionParameter;
    
    use Sebastian\Core\DependencyInjection\Injector;
    
    class BaseResolver implements ResolverInterface {
        public function canResolve(ReflectionParameter $symbol) : bool {
            return true;
        }

        public function resolve(Injector $injector, ReflectionParameter $symbol) {
            $name = $symbol->getName();
            $class = $symbol->getClass() ? $symbol->getClass()->getShortName() : null;

            if (!is_null($class)) {
                $dependency = 
                    $injector->getDependency("@{$class}") ?? 
                    $injector->getDependency("{$class}");

                if (!is_null($dependency)) return $dependency;
            }

            $dependency = 
                $injector->getDependency("\${$name}") ?? 
                $injector->getDependency("{$name}");

            return $dependency ?? ($symbol->isDefaultValueAvailable() ? $symbol->getDefaultValue() : null);
        }
    }