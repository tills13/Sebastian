<?php
    namespace Sebastian\Core\Database;

    use Sebastian\Core\Repository\Repository;
    use Sebastian\Core\Entity\Entity;

    use Sebastian\Core\Utility\Logger;
    use Sebastian\Core\Utility\Utils;

    /**
     * EntityManager
     * 
     * @author Tyler <tyler@sbstn.ca>
     * @since  Oct. 2015
     */
    class EntityManager {
        public static $logger;
        protected static $tag = "EntityManager";

        private $context;
        private $definitions; // orm definitions
        private $repositories;

        public function __construct($app) {
            $this->context = $app;

            //$this->
            $this->definitions = $app->loadConfig("orm.yaml");
            $this->repositories = $app->getConfig("entity");

            if (!EntityManager::$logger) {
                EntityManager::$logger = new Logger($app->getLogFolder(), null, ['filename' => 'orm']);
                EntityManager::$logger->setTag(EntityManager::$tag);
            }
        }

        // make it check to see if there is a 'persist' method defined in the the 
        // repo class
        // if so, use it, even if it's not an object
        // actually I think that's current functionality
        // should like... maybe not persist right away?
        // like ysmfony, use 'flush'
        public function persist($object, $parent = null) {
            if (!is_array($object)) $object = [$object];

            foreach ($object as $mObject) {
                $namespace = $this->context->getAppNamespace();
                $class = get_class($mObject);

                $components = $this->context->getComponents();
                $bestGuessSimpleClass = $this->getBestGuessClass($class);

                foreach ($components as $components) {
                    $path = $components['path'];
                    $path = str_replace('/', '\\', $path);

                    $classPath = "{$namespace}{$path}\\Entity\\{$bestGuessSimpleClass}";

                    if ($classPath == $class) {
                        $repo = $this->getRepository($bestGuessSimpleClass);
                        return $repo->persist($mObject, $parent);
                    }
                }

                throw new \Exception("unable to persist {$class}, cannot figure out the entity class", 1);
            }
        }

        public function persistOne($object, $parent) {
            return $this->persist([$object], $parent);
        }

        public function setLazy($lazy) {
            $this->options['lazy'] = $lazy;
        }

        public function getLazy() {
            return $this->options['lazy'];
        }

        public function getConnection() {
            return $this->context->getConnection();
        }

        public function getQueryBuilder($options = []) {
            return $this->getConnection()->getQueryBuilder($options);
        }

        public function getDefinition($path = null) {
            return isset($this->definitions[$path]) ? $this->definitions[$path] : [];
        }

        public function getRepository($class) {
            if ($class instanceof Entity) $class = get_class($class);
            if (!in_array($class, array_keys($this->repositories ?: []))) $class = $this->getBestGuessClass($class);

            if (in_array($class, array_keys($this->repositories ?: []))) {
                $namespace = $this->context->getAppNamespace();
                $entityInfo = $this->repositories[$class];

                if (isset($entityInfo['repository'])) {
                    $repo = $entityInfo['repository'];
                
                    if (strstr($repo, ':')) {
                        $repo = explode(':', $repo);
                        $component = $repo[0];

                        $path = str_replace('/', '\\', $repo[1]);
                        $repoClass = "\\{$namespace}\\{$component}\\{$path}Repository";
                    } else {
                        $components = $this->context->getComponents(true);
                        foreach ($components as $component) {
                            $path = $component['path'];
                            $possibleFile = \APP_ROOT . "/{$namespace}{$path}/{$repo}Repository.php";

                            if (file_exists($possibleFile)) {
                                $path = str_replace('/', '', $path);
                                $repo = str_replace('/', '\\', $repo);

                                if ($path != "") $path = "\\{$path}";

                                $repoClass = "\\{$namespace}{$path}\\{$repo}Repository";
                            }
                        }
                    }
                    
                    return new $repoClass($this->context, $this, $class);
                }
            }

            return new Repository($this->context, $this, $this->getBestGuessClass($class));
        }

        public function getBestGuessClass($class) {
            $namespace = $this->context->getAppNamespace();
            $components = $this->context->getComponents();
            $bestGuessSimpleClass = strstr($class, '\\') ? substr($class, strrpos($class, '\\') + 1) : $class;

            $entities = $this->context->getConfig('entity', []);
            if (in_array($class, array_keys($entities))) {
                return $class;
            }

            foreach ($components as $components) {
                $path = $components['path'];
                $path = str_replace('/', '\\', $path);

                $classPath = "{$namespace}{$path}\\Entity\\{$bestGuessSimpleClass}";

                if ($classPath == $class) return $bestGuessSimpleClass;
            }

            //var_dump("MISSED: $class"); print "<br/>";

            return null;
        }

        public function getNamespacePath($class) {
            if (in_array($class, array_keys($this->repositories))) {
                // todo shrink
                $entityInfo = $this->repositories[$class];
                $entity = $entityInfo['entity'];

                $entity = explode(':', $entity);
                $component = $entity[0];
                $path = str_replace('/', '\\', $entity[1]);
                $namespace = $this->context->getAppNamespace();

                $repoClass = "\\{$namespace}\\{$component}\\{$path}";

                return $repoClass;
            } else return new Entity();
        }

        public function getForeignKeys($entityA, $entityB) {
            if (!in_array($this->defitions[$entityA]) || !in_array($this->definitions[$entityB])) {
                throw new \Exception("No definition found for either '{$entityA}' or '{$entityB}'");
            }
        }

        public function getNonForeignColumns($entity) {
            if (!in_array($entity, array_keys($this->definitions))) {
                throw new \Exception("Unknown entity '{$entity}'");//SebastianException
            }

            $columnDefinitions = array_filter($this->definitions[$entity]['fields'], function($entity) {
                return !isset($entity['relation']) && !isset($entity['with']);
            }); 

            return array_values(array_map(function($column) {
                return $column['column'];
            }, $columnDefinitions));
        }
    }