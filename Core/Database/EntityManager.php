<?php
    namespace Sebastian\Core\Database;

    use Sebastian\Core\Entity\Entity;
    use Sebastian\Core\Exception\SebastianException;
    use Sebastian\Core\Repository\Repository;
    use Sebastian\Utility\Collection\Collection;
    use Sebastian\Utility\Configuration\Configuration;
    
    /**
     * EntityManager
     * 
     * @author Tyler <tyler@sbstn.ca>
     * @since  Oct. 2015
     */
    class EntityManager {
        protected static $tag = "EntityManager";

        private $context;
        private $definitions; // orm definitions
        private $repositories;

        public function __construct($context, Configuration $config = null) {
            $this->context = $context;

            $this->repositories = $config;
            //var_dump($config);
            $this->definitions = Configuration::fromFilename('orm.yaml');//$context->loadConfig("orm.yaml");
            $this->logger = $context->getLogger(self::$tag);

            $this->repositoryStore = new Collection();
        }

        public function delete($object) {
            if (!is_array($object)) $object = [$object];

            foreach ($object as $mObject) {
                $class = $this->getBestGuessClass(get_class($mObject)); // hack
                $repo = $this->getRepository($class);
                
                if ($repo != null) {
                    if (!$repo->delete($mObject)) {
                        throw new SebastianException("something went wrong deleting the object");
                    }
                } else {
                    throw new SebastianException("something went wrong");
                }
            }
        }
        
        public function persist($object, $parent = null) {
            if (!is_array($object)) $object = [$object];

            foreach ($object as $mObject) {
                $class = get_class($mObject);

                $repo = $this->getRepository($class);
                if ($repo != null) {
                    if (!$repo->persist($mObject, $parent)) {
                        throw new SebastianException("something went wrong persisting the object");
                    }
                } else {
                    throw new SebastianException("something went wrong");
                }
            }
        }

        public function persistOne($object, $parent) {
            return $this->persist([$object], $parent);
        }

        public function computeColumnSets($class, $joins, $aliases) {
            $columns = array_map(function($column) use ($class, $aliases) {
                return "{$aliases[$class]}.{$column}";
            }, $this->getNonForeignColumns($class));
            
            foreach ($joins as $join) {
                $mColumns = array_map(function($column) use ($join, $aliases) {
                    $key = "{$join['column']}_{$join['table']}";
                    $columnName = "{$aliases[$key]}.{$column}";
                    $columnAlias = "{$join['column']}_{$column}";
                    return [$columnAlias => $columnName];
                }, $this->getNonForeignColumns($join['entity']));

                $columns = array_merge($columns, $mColumns);
            }

            return $columns;
        }

        /**
         * [computeJoinSets description]
         * @param  [type] $class [description]
         * @return [type]        [description]
         *
         * @todo need to attempt to resolve unknown class
         */
        public function computeJoinSets($class) {
            if (!$this->definitions->has($class)) {
                throw new SebastianException("Unknown class '{$class}'");//SebastianException
            }

            $joins = [];    
            $table = $this->getDefinition($class)->get('table');
            $fks = $this->getOneToOneMappedColumns($class);


            foreach ($fks as $foreignKey) {
                $table = $this->getTable($foreignKey['entity']);
                $fields = $this->getDefinition($foreignKey['entity'])->sub('fields');
                $field = $fields->sub($foreignKey['foreign']);

                $joins[] = [
                    'entity' => $foreignKey['entity'],
                    'column' => $foreignKey['column'],
                    'table' => $table,
                    'foreign' => $field->get('column')
                ];
            }

            return $joins;
        }

        public function generateTableAliases($class, $joins = []) {
            $mTables = [];

            $mTables[$class] = substr($this->getTable($class), 0, 1);

            foreach ($joins as $join) {
                $table = $join['table'];
                $alias = substr($table, 0, 1);

                $index = 0;
                while (in_array($alias, $mTables)) {
                    $alias = $alias . $index;
                }

                $mTables["{$join['column']}_{$table}"] = $alias;
            }

            return $mTables;
        }

        /**
         * ({Namespace}:)?{Component}:{Class}
         * @param  [type] $class [description]
         * @return [type]        [description]
         */
        public function normalizeClass($class) {
            if (strstr($class, ':')) {
                $class = explode(':', $class);

                if (count($class) == 3) {
                    $namespace = $class[0];
                    $component = $class[1];
                    $class = $class[2];
                } else if (count($class) == 2) {
                    $namespace = $this->context->getNamespace();
                    $component = $class[0];
                    $class = $class[1];
                } else {
                    throw new \Exception();
                }
            } else {
                $namespace = $this->context->getNamespace();
                //$component
            }


        }

        /**
         * turns \{Namespace}\{Class} into 
         * @param  [type] $class [description]
         * @return [type]        [description]
         */
        public function getBestGuessClass($class) {




            //var_dump($class);
            $namespace = $this->context->getNamespace();
            $components = $this->context->getComponents();
            $bestGuessSimpleClass = strstr($class, '\\') ? substr($class, strrpos($class, '\\') + 1) : $class;


            //var_dump($bestGuessSimpleClass);
            //var_dump($class);
            //var_dump($this->repositories);
            if ($this->repositories->has($class)) {
                //print ("here");
                //return $this->repositories->
                return $class;
            }

            foreach ($components as $component) {
                $path = $component->getNamespacePath();

                $classPath = "{$namespace}\\{$path}\\Entity\\{$bestGuessSimpleClass}";
                //print ($classPath);

                if ($classPath == $class) {
                    return $bestGuessSimpleClass;
                }
            }

            //print ("null");

            return null;
        }

        public function getConnection() {
            return $this->context->getConnection();
        }

        public function getDefinition($class) {
            return $this->definitions->sub($class);
        }

        /**
         * [getForeignKeys description]
         * @param  [type] $entityA [description]
         * @param  [type] $entityB [description]
         * @return [type]          [description]
         *
         * @todo implement
         */
        public function getForeignKeys($entityA, $entityB) {
            if (!in_array($this->defitions[$entityA]) || !in_array($this->definitions[$entityB])) {
                throw new \Exception("No definition found for either '{$entityA}' or '{$entityB}'");
            }
        }

        public function getMultiMappedFields($entity) {
            if (!$this->definitions->has($entity)) {
                throw new SebastianException("Unknown entity '{$entity}'");//SebastianException
            }

            $fields = array_filter($this->definitions[$entity]['fields'], function($entity) {
                return isset($entity['relation']) && (
                    strtolower($entity['relation']) == 'many' ||
                    strtolower($entity['relation']) == 'onetomany' || 
                    strtolower($entity['relation']) == '1:x'
                );
            }); 

            return array_map(function($field) {
                return [
                    'mapped' => $field['mapped'],
                    'with' => $field['with'],
                    'entity' => $field['entity']
                ];
            }, $fields);
        }

        public function getNamespacePath($class) {
            if ($this->repositories->has($class)) {
                // todo shrink
                $entityInfo = $this->repositories[$class];
                $entity = $entityInfo['entity'];

                $entity = explode(':', $entity);
                $component = $entity[0];
                $path = str_replace('/', '\\', $entity[1]);
                $namespace = $this->context->getNamespace();

                $repoClass = "\\{$namespace}\\{$component}\\{$path}";

                return $repoClass;
            } else return null;
        }

        public function getNonForeignColumns($entity) {
            if (!$this->definitions->has($entity)) {
                throw new SebastianException("Unknown entity '{$entity}'");//SebastianException
            }

            $columnDefinitions = array_filter($this->definitions[$entity]['fields'], function($entity) {
                return !isset($entity['relation']) && !isset($entity['with']);
            }); 

            return array_values(array_map(function($column) {
                return $column['column'];
            }, $columnDefinitions));
        }

        public function getOneToOneMappedColumns($entity) {
            if (!$this->definitions->has($entity)) {
                throw new SebastianException("Unknown entity '{$entity}'");//SebastianException
            }

            $definition = $this->definitions->get($entity);

            $columnDefinitions = array_filter($definition['fields'], function($entity) {
                return isset($entity['relation']) && (
                    strtolower($entity['relation']) == 'one' ||
                    strtolower($entity['relation']) == 'onetoone' || 
                    strtolower($entity['relation']) == '1:1'
                );
            }); 

            return array_values(array_map(function($column) {
                return [
                    'column' => $column['column'],
                    'foreign' => $column['with'],
                    'entity' => $column['entity']
                ];
            }, $columnDefinitions));
        }

        public function getRepository($class) {
            if ($class instanceof Entity) $class = get_class($class);
            if (!$this->repositories->has($class)) $class = $this->getBestGuessClass($class);

            //if ($this->repositoryStore->has($class)) return $this->repositoryStore->get($class);

            if ($this->repositories->has($class)) {
                $namespace = $this->context->getNamespace();
                $info = $this->repositories->sub($class);

                if ($info->has('repository')) {
                    $repo = $info->get('repository');
                
                    if (strstr($repo, ':')) {
                        $repo = explode(':', $repo);
                        $component = $repo[0];

                        $path = str_replace('/', '\\', $repo[1]);
                        $repoClass = "\\{$namespace}\\{$component}\\{$path}Repository";
                    } else {
                        $components = $this->context->getComponents(true);
                        foreach ($components as $component) {
                            $path = $component->getNamespacePath();
                            $possibleFile = \APP_ROOT . "/{$namespace}/{$path}/{$repo}Repository.php";

                            if (file_exists($possibleFile)) {
                                $path = str_replace('/', '', $path);
                                $repo = str_replace('/', '\\', $repo);

                                if ($path != "") $path = "\\{$path}";

                                $repoClass = "\\{$namespace}{$path}\\{$repo}Repository";
                            }
                        }
                    }
                    
                    $repo = new $repoClass($this->context, $this, $class);
                    //$this->repositoryStore->set($class, $repo);
                    return $repo;
                } else {
                    return new Repository($this->context, $this, $class);
                }
            }

            throw new SebastianException("No repository found for '{$class}'");
        }

        /**
         * [getTable description]
         * @param  [type] $class [description]
         * @return [type]        [description]
         *
         * @todo resolve class if not found
         */
        public function getTable($class) {
            //var_dump($this->definitions);
            if (!$this->definitions->has($class)) {
                throw new SebastianException("Unknown class '{$class}'");//SebastianException
            }

            return $this->definitions->get("{$class}.table");
        }
    }