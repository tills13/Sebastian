<?php
    namespace Sebastian\Core\Database;

    use Sebastian\Core\Cache\CacheManager;
    use Sebastian\Core\Entity\EntityInterface;
    use Sebastian\Core\Exception\SebastianException;
    use Sebastian\Core\Repository\Repository;
    use Sebastian\Core\Repository\Transformer\DatetimeTransformer;
    use Sebastian\Core\Repository\Transformer\TransformerInterface;
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

        protected static $_objectReferenceCache;

        public function __construct($context, Configuration $config = null) {
            $this->context = $context;

            $this->repositories = $config;
            //var_dump($config);
            $this->definitions = Configuration::fromFilename('orm.yaml');//$context->loadConfig("orm.yaml");
            $this->logger = $context->getLogger(self::$tag);
            $this->logger->setTag(self::$tag);

            $this->repositoryStore = new Collection();
            $this->transformers = new Collection();

            // volatile storage to keep objects until the FPM process dies i.e. per Request
            if (EntityManager::$_objectReferenceCache == null) {
                EntityManager::$_objectReferenceCache = new CacheManager(
                    $this->context,
                    new Configuration([
                        'driver' => CacheManager::ARRAY_DRIVER,
                        'logging' => true
                    ])
                );
            }

            $this->addTransformer(new DatetimeTransformer());
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

        /**
         * refreshed an object from the Database
         * @param  Entity $object the entity to refresh
         * @return Entity $object
         */
        public function refresh($object) {
            $class = get_class($mObject);
            $repo = $this->getRepository($class);
            return $repo->refresh($object);
        }

        /**
         * computes all the changed fields
         * @param  Entity $object
         * @return array []
         */
        public function computeObjectChanges($object) {
            $objectKey = $this->getObjectCache()->generateKey($object);
            $cached = $this->getObjectCache()->load($objectKey);

            $class = $this->getBestGuessClass(get_class($object));
            $definition = $this->getDefinition($class);
            $repo = $this->getRepository($class);
            
            $changed = [];
            foreach ($definition->sub('fields') as $name => $field) {
                $objectVal = $repo->getFieldValue($object, $name);
                $cachedVal = $repo->getFieldValue($cached, $name);

                if ($field->has('entity')) {
                    if (in_array($field->get('relation'), ['1:1', 'one', 'onetoone'])) {
                        $mRepo = $this->getRepository(get_class($objectVal));
                        $keysA = $mRepo->getPrimaryKeys($objectVal);
                        $keysB = $mRepo->getPrimaryKeys($cachedVal);

                        if ($keysA !== $keysB) $changed[] = $name;
                        else {
                            // todo figure this shit out
                            // $mChanges = $this->computeObjectChanges($objectVal);
                            // if (count($mChanges) != 0) $changed[] = $name;
                        }
                    } else if (in_array($field->get('relation'), ['1:x', 'many'])) {
                    } else if ($field->has('join')) {
                    } else { /* ???? */ }
                } else {
                    if ($objectVal != $cachedVal) $changed[] = $name;
                }  
            }

            return $changed;
        }

        public function computeColumnSets($class, $joins, $aliases) {
            $columns = array_map(function($column) use ($class, $aliases) {
                return "{$aliases[$class]}.{$column}";
            }, $this->getNonForeignColumns($class));
            
            foreach ($joins as $key => $join) {
                if (array_key_exists('entity', $join)) {
                    $mColumns = array_map(function($column) use ($join, $aliases) {
                        if ($join['type'] == Repository::JOIN_TYPE_FK) {
                            $key = "{$join['column']}_{$join['table']}";
                        } else if ($join['type'] == Repository::JOIN_TYPE_JOIN_TABLE) {
                            $mJoin = $join['join'];
                            $key = "{$mJoin['joinColumnForeign']}_{$mJoin['joinTableForeign']}";
                        }

                        $columnName = "{$aliases[$key]}.{$column}";
                        $columnAlias = "{$join['column']}_{$column}";
                        return [$columnAlias => $columnName];
                    }, $this->getNonForeignColumns($join['entity']));
                } else {
                    if ($join['type'] == Repository::JOIN_TYPE_FK) {
                        //$mJoin = $join['join'];
                    } else if ($join['type'] == Repository::JOIN_TYPE_JOIN_TABLE) {
                        $mJoin = $join['join'];
                        $column = explode(':', $mJoin['joinColumnForeign'])[1];
                        $key = "{$mJoin['joinColumnForeign']}_{$mJoin['joinTableForeign']}";
                    }

                    $columnName = "{$aliases[$key]}.{$column}";
                    $columnAlias = "{$join['column']}_{$column}";
                    $mColumns = [ $column => [$columnAlias => $columnName] ];
                }
                

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
            $joinColumns = $this->getOneToOneMappedColumns($class);

            foreach ($joinColumns as $join) { 
                $mJoin = [ 'column' => $join['column'] ];

                if (array_key_exists('join', $join)) {
                    $mJoin['type'] = Repository::JOIN_TYPE_JOIN_TABLE;
                    $mJoin['join'] = $join['join'];
                } else {
                    // todo
                    if (array_key_exists('entity', $join)) {
                        $table = $this->getTable($join['entity']);
                        $fields = $this->getDefinition($join['entity'])->sub('fields');
                        $field = $fields->sub($join['foreign']);
                        $mJoin['entity'] = $join['entity'];
                        $column = $field->get('column');
                    } else {
                        if (isset($join['table'])) $table = $join['table'];
                        else {
                            if (array_key_exists('join', $join)) { // 
                                $mJoin = $join['join'];
                            }
                        }

                        $column = $join['foreign'];
                    }
                   
                    $mJoin['type'] = Repository::JOIN_TYPE_FK;
                    $mJoin['table'] = $table;
                    $mJoin['foreign'] = $column;//$field->get('column');
                }

                $joins[] = $mJoin;
            }

            $manyRelation = $this->getMultiMappedFields($class);

            foreach ($manyRelation as $field => $many) {
                //print_r($field); die();
                $mJoin = [];

                if (array_key_exists('entity', $many)) $mJoin['entity'] = $many['entity'];
                else if (array_key_exists('table', $many)) $mJoin['table'] = $many['table'];
                else {
                    if (!array_key_exists('join', $many)) {
                        throw new \Exception("Either entity or table must be specified.");
                    }
                }

                if (array_key_exists('join', $many)) {
                    $mJoin['type'] = Repository::JOIN_TYPE_JOIN_TABLE;
                    $mJoin['join'] = $many['join'];


                    $mColumns = explode(':', $mJoin['join']['joinColumnLocal']);
                    $mJoin['column'] = $field;
                } else {
                    $mJoin['type'] = Repository::JOIN_TYPE_FK;
                    $mJoin['table'] = isset($mJoin['table']) ? $mJoin['table'] : $this->getTable($mJoin['entity']);
                    $mJoin['column'] = $many['with'];
                    $mJoin['foreign'] = $column;
                }

                $joins[] = $mJoin;
            }

            return $joins;
        }

        public function generateTableAliases($class, $joins = []) {
            $mTables = [];
            $mTables[$class] = substr($this->getTable($class), 0, 1);

            foreach ($joins as $join) {
                if ($join['type'] == Repository::JOIN_TYPE_FK) {
                    $table = $join['table'];
                    $alias = substr($table, 0, 1);
                    $column = $join['column'];
                } else if ($join['type'] == Repository::JOIN_TYPE_JOIN_TABLE) {
                    $mJoin = $join['join'];
                    $table = $mJoin['joinTableLocal'];
                    $alias = substr($table, 0, 1);
                    $column = $mJoin['joinColumnLocal'];

                    $index = 0;
                    while (in_array($alias, $mTables)) $alias = $alias . $index;

                    $mTables["{$column}_{$table}"] = $alias;

                    $column = $mJoin['joinColumnForeign'];
                    $table = $mJoin['joinTableForeign'];
                    $alias = substr($table, 0, 1);
                }

                $index = 0;
                while (in_array($alias, $mTables)) {
                    $alias = $alias . $index;
                }

                $mTables["{$column}_{$table}"] = $alias;
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
                if (array_key_exists('entity', $field)) {
                    $mField = [ 'entity' => $field['entity'] ];    
                } else {
                    if (!isset($field['table'])) {
                        if (!isset($field['join']) && !isset($field['join']['joinTableLocal'])) {
                            throw new \Exception("An entity must be specified or a table must be declared either under the object field spec. or under the join field for relations");    
                        } else $mField = [ 'table' => $field['join']['joinColumnLocal'] ];
                    } else {
                        $mField = [ 'table' => $field['table'] ];
                    }
                }

                if (array_key_exists('join', $field)) {
                    $mField['type'] = Repository::JOIN_TYPE_JOIN_TABLE;
                    $mField['join'] = $field['join'];
                } else {
                    $mField['type'] = Repository::JOIN_TYPE_FK;
                    $mField['mapped'] = $field['mapped'];
                    $mField['with'] = $field['with'];
                }

                return $mField;
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

            return array_map(function($column) {
                if (array_key_exists('entity', $column)) {
                    $mColumn = [ 'entity' => $column['entity'] ];    
                } else {
                    if (!isset($column['table'])) {
                        if (!isset($column['join']) && !isset($column['join']['joinTableLocal'])) {
                            throw new \Exception("An entity must be specified or a table must be declared either under the object field spec. or under the join field for relations");    
                        } else {
                            $mColumn = [ 'table' => $column['join']['joinColumnLocal'] ];
                        }
                    } else {
                        $mColumn = [ 'table' => $column['table'] ];
                    }
                }

                // join table
                if (array_key_exists('join', $column)) {
                    $mColumn['join'] = $column['join'];

                    $mColumns = $column['join']['joinColumnLocal'];
                    $mColumns = explode(':', $mColumns);
                    $mColumn['column'] = $mColumns[0];
                } else {
                    $mColumn['foreign'] = $column['with'];
                    $mColumn['column'] = $column['column'];
                }
                
                return $mColumn;
            }, $columnDefinitions);
        }

        public function getObjectCache() {
            return self::$_objectReferenceCache;
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
                    
                    $config = new Configuration($info->get('config', []));
                    $repo = new $repoClass($this->context, $this, $class, $config);
                    //$this->repositoryStore->set($class, $repo);
                    return $repo;
                } else {
                    return new Repository($this->context, $this, $class);
                }
            }

            throw new SebastianException("No repository found for '{$class}'");
        }

        public function addTransformer(TransformerInterface $transformer) {
            return $this->setTransformer($transformer->getName(), $transformer);
        }

        public function setTransformer($name, $transformer) {
            return $this->transformers->set($name, $transformer);
        }

        public function getTransformer($name) {
            return $this->transformers->get($name, null);
        }

        public function getTransformers() {
            return $this->transformers;
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