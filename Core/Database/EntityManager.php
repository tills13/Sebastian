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
        protected $context;
        protected $definitions; // orm definitions
        protected $repositories;
        protected $transformers;
        protected $logger;

        protected static $_objectReferenceCache;

        public function __construct($context, Configuration $config = null) {
            $this->context = $context;

            $this->repositories = $config;
            //var_dump($config);
            $this->definitions = Configuration::fromFilename('orm.yaml');//$context->loadConfig("orm.yaml");
            $this->logger = $context->getLogger();

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
            $objects = func_get_args();
            foreach ($objects as $object) {
                $class = $this->getBestGuessClass(get_class($object)); // todo verify
                $repo = $this->getRepository($class);
                
                if ($repo) $repo->delete();
            }
        }
        
        public function persist() {
            $objects = func_get_args();

            foreach ($objects as $object) {
                $class = $this->getBestGuessClass(get_class($object)); // todo verify
                $repo = $this->getRepository($class);

                if ($repo) $repo->persist($object);
            }
        }

        /**
         * refreshed an object from the Database
         * @param  Entity $object the entity to refresh
         * @return Entity $object
         */
        public function refresh() {
            $objects = func_get_args();

            foreach ($objects as &$object) {
                $class = $this->getBestGuessClass(get_class($object)); // todo verify
                $repo = $this->getRepository($class);

                if ($repo) $repo->refresh($object);
            }
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
                        $key = "{$join['field']}_{$join['table']}";
                        $column = $join['foreign'];
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
                    $mJoin['column'] = $many['local'];
                    $mJoin['foreign'] = $many['foreign'];
                    $mJoin['field'] = $many['field'];
                }

                $joins[] = $mJoin;
            }

            return $joins;
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

        public function generateTableAliases($class, $joins = []) {
            $mTables = [];
            $mTables[$class] = substr($this->getTable($class), 0, 1);

            foreach ($joins as $join) {
                if ($join['type'] == Repository::JOIN_TYPE_FK) {
                    $table = $join['table'];
                    $alias = substr($table, 0, 1);
                    
                    if (array_key_exists('entity', $join)) $local = $join['column'];
                    else $local = $join['field'];
                } else if ($join['type'] == Repository::JOIN_TYPE_JOIN_TABLE) {
                    $mJoin = $join['join'];
                    $table = $mJoin['joinTableLocal'];
                    $alias = substr($table, 0, 1);
                    $local = $mJoin['joinColumnLocal'];

                    $index = 0;
                    while (in_array($alias, $mTables)) $alias = $alias . $index;

                    $mTables["{$local}_{$table}"] = $alias;

                    $local = $mJoin['joinColumnForeign'];
                    $table = $mJoin['joinTableForeign'];
                    $alias = substr($table, 0, 1);
                }

                $index = 0;
                while (in_array($alias, $mTables)) {
                    $alias = $alias . $index;
                }

                $mTables["{$local}_{$table}"] = $alias;
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
            $namespace = $this->context->getNamespace();
            $components = $this->context->getComponents();
            $bestGuessSimpleClass = strstr($class, '\\') ? substr($class, strrpos($class, '\\') + 1) : $class;

            if ($this->repositories->has($class)) {
                return $class;
            }

            foreach ($components as $component) {
                $path = $component->getNamespacePath();
                $classPath = "{$namespace}\\{$path}\\Entity\\{$bestGuessSimpleClass}";

                if ($classPath == $class) return $bestGuessSimpleClass;
            }

            return null;
        }

        public function getConnection() {
            return $this->context->getConnection();
        }

        public function getDefinition($class) {
            return $this->definitions->sub($class);
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

            return array_map(function($key, $field) {
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
                    $mField['local'] = $field['local'];
                    $mField['foreign'] = $field['foreign'];
                    $mField['field'] = $key;
                }

                return $mField;
            }, array_keys($fields), $fields);
        }

        /**
         * @todo split entities and repos
         * @return [type]
         */
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
                return !isset($entity['relation']) && !isset($entity['foreign']);
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
                    $mColumn['foreign'] = $column['foreign'];
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
            if (!$this->definitions->has($class)) {
                throw new SebastianException("Unknown class '{$class}'");
            }

            return $this->definitions->get("{$class}.table");
        }
    }