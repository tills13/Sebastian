<?php
	namespace Sebastian\Core\Repository;

	use Sebastian\Application;
	use Sebastian\Core\Cache\CacheManager;
	use Sebastian\Core\Database\EntityManager;
	use Sebastian\Core\Database\Query\Expression\ExpressionFactory;
	use Sebastian\Core\Database\Query\Part\Join;
	use Sebastian\Core\Database\Query\QueryFactory;
	use Sebastian\Core\Exception\SebastianException;
	use Sebastian\Core\Repository\Transformer\ColumnTransformerInterface;
	use Sebastian\Utility\Collection\Collection;
	use Sebastian\Utility\Configuration\Configuration;
	use Sebastian\Utility\Utility\Utils;

	/**
	 * Repository
	 *
	 * fetches and loads database information into objects defined by a .yaml file
	 * 
	 * @author Tyler <tyler@sbstn.ca>
	 * @since Oct. 2015
	 */
	class Repository {
		protected static $tag = "Repository";

		protected $config;

		protected $entity;
		protected $class;
		protected $definition;
		
		protected $connection;
		protected $em;
		protected $cm;
		protected $orc;
		
		public function __construct(EntityManager $entityManager, CacheManager $cacheManager = null, Logger $logger = null, Configuration $config = null, $entity = null) {
			$this->entityManager = $entityManager;
			$this->cacheManager = $cacheManager;
			$this->logger = $logger;
			$this->connection = $entityManager->getConnection();

			if ($config == null) $config = new Configuration();
			$this->config = $config->extend([ 'use_reflection' => true ]);

			$this->entity = $entity;
			$this->class = $entityManager->getNamespacePath($this->entity);
			$this->orc = $entityManager->getObjectReferenceCache();

			$this->initCalled = false;
			$this->init();
		}

		/**
		 * classes can override/inject their own init before this one. 
		 * The expectation is that they also call this init.
		 * 
		 * convenience class so the user doesn't have to worry about these things
		 * only setting the class name (maybe they shouldn't even have to do that)
		 * @return none
		 */
		public function init() {
			$this->initCalled = true;

			if ($this->entity != null) {
				$this->definition = $this->entityManager->getDefinition($this->entity);
				$this->nsPath = $this->entityManager->getNamespacePath($this->entity);

				$this->table = $this->definition->get('table');
				$this->keys = $this->definition->get('keys');
				$this->fields = $this->definition->sub('fields');

				$this->joins = $this->entityManager->computeJoinSets($this->entity);
				$this->aliases = $this->entityManager->generateTableAliases($this->entity, $this->joins);
				$this->columns = $this->entityManager->computeColumnSets($this->entity, $this->joins, $this->aliases);
			}

			$this->reflection = new \ReflectionClass($this->class);
			$this->columnMap = $this->generateColumnMap();
		}

		/**
		 * [build description]
		 * @param  [type] $object [description]
		 * @param  array  $params [description]
		 * @return [type]         [description]
		 */
		public function build($object = null, $fields = []) {
			if (!$object) {
				$classPath = $this->entityManager->getNamespacePath($this->entity);
				$object = new $classPath();
			}

			if (!$fields) $fields = [];

			$useReflection = $this->config->get('use_reflection', false);

			foreach ($fields as $field => $value) {
				$object = $this->setFieldValue($object, $field, $value);
			}

			return $object;
		}

		public function delete($object) {
			$qf = QueryFactory::getFactory();
			$ef = ExpressionFactory::getFactory();

			$qf = $qf->delete()->from($this->getTable());

			$mEf = ExpressionFactory::getFactory();
			foreach ($this->keys as $key) {
				$fieldName = $this->columnMap->get($key);
				$value = $this->getFieldValue($object, $fieldName);

				$ef->reset()->expr($key)->equals($value);
				$mEf->andExpr($ef->getExpression());
			}

			$qf = $qf->where($mEf->getExpression());
			$query = $qf->getQuery();

			try {
				$result = $this->connection->execute($query, []);

				$key = $this->cacheManager->generateKey($object);
				$this->cacheManager->invalidate($key);

				return true;
			} catch (\Exception $e) {
				throw new SebastianException("Could not delete {$this->getClass()}: {$e->getMessage()}");
			}
		}

		public function find($where = [], $options = []) {
			$where = $where ? $where : [];
			$mSkeletons = [];
			$qf = QueryFactory::getFactory();
			$ef = ExpressionFactory::getFactory();

			$qf = $qf->select($this->columns)->from([$this->aliases[$this->entity] => $this->getTable()]);

			/*foreach ($this->joins as $key => $join) {
				$withEntityKey = "{$this->aliases[$this->entity]}.{$join['column']}";
				$foreignTableAlias = $this->aliases["{$join['column']}_{$join['table']}"];

				$expression = $ef->reset()->expr($withEntityKey)
					->equals("{$foreignTableAlias}.{$join['foreign']}")->getExpression();

				$qf = $qf->join(Join::TYPE_LEFT, [$foreignTableAlias => $join['table']], $expression);
			}*/

			$expression = null;
			foreach ($where as $with => $param) {
				if ($param instanceof Expression) {

				} else {
					$lhs = "{$this->aliases[$this->entity]}.{$with}";
					$ef = $ef->reset()->expr($lhs);

					if (is_array($param)) {
						print ("IN");
						//$ef->in($param);
					}

					// todo allow for tables like [['table' => 'column'] => param]
					if (preg_match("/(!|not|>|<) ?(.+)/i", $param, $matches) >= 1) {
						$operator = $matches[1];
						$param = $matches[2];

						if ($operator == '!' || $operator == 'not') $ef = $ef->notEquals($param);
						else if ($operator == '<') $ef = $ef->lessThan($param);
						else if ($operator == '>') $ef = $ef->greaterThan($param);
					} else {
						//$param = "{$this->aliases[$this->entity]}.{$param}";
						$qf->bind($with, $param);
						$ef = $ef->equals(":$with");
					}

					if ($expression) {
						$mExpression = $ef->getExpression();
						$ef = $ef->reset()->with($mExpression)->andExpr($expression);
					}

					$expression = $ef->getExpression();
				}
			}

			if ($expression) $qf = $qf->where($expression);
			if ($options && count($options) != 0) {
				if (isset($options['limit'])) $qf = $qf->limit($options['limit']);
				if (isset($options['offset'])) $qf = $qf->offset($options['offset']);
				if (isset($options['orderBy'])) {
					$column = array_pop(array_keys($options['orderBy']));
					$direction = $options['orderBy'][$column];
					$qf = $qf->orderBy($column, $direction);
				}
			}

			$query = $qf->getQuery();
			$result = $this->connection->execute($query, $query->getBinds());
			$results = $result->fetchAll() ?: [];

			foreach ($results as $mResult) {
				$skeleton = $this->build(null, $mResult);

				foreach ($this->entityManager->getOneToOneMappedColumns($this->entity) as $mapped) {
					$repo = $this->entityManager->getRepository($mapped['targetEntity']);

					$params = [];
					foreach ($mResult as $key => $value) {
						$nMatches = preg_match("/{$mapped['column']}_([a-zA-Z0-9_]+)/", $key, $matches);
						if ($nMatches != 0) $params[$matches[1]] = $mResult[$matches[0]];
					}
					
					try {
						$entity = $repo->get($params);
					} catch (SebastianException $e) { $entity = null; }
					
					$skeleton = $this->build($skeleton, [$mapped['column'] => $entity]);
				}

				foreach ($this->entityManager->getMultiMappedFields($this->entity) as $key => $field) {
					$repo = $this->entityManager->getRepository($field['targetEntity']);

					$foreign = $mResult[$field['local']];
					$entities = $repo->find([$field['foreign'] => $foreign]);
					$skeleton = $this->build($skeleton, [$key => $entities]);
				}

				$mSkeletons[] = $skeleton;
			}

			return $mSkeletons;
		}

		/**
		 * loads an object from the database based off a 
		 * set of rule defined in an orm config file
		 * @param array $params initial paramters to seed the object with
		 * @return Entity a completed, possibly lazily loaded Entity
		 */
		const JOIN_TYPE_FK = 0;
		const JOIN_TYPE_JOIN_TABLE = 1;
		public function get($params) {
			if (!is_array($params)) {
				if (count($this->keys) != 1) throw new SebastianException(
					"Cannot use simplified method signature when entity has more than one primary key"
				);

				$params = [ $this->keys[0] => $params ];
			}

			$qf = QueryFactory::getFactory();
			$ef = ExpressionFactory::getFactory();

			if (empty(array_intersect($this->keys, array_keys($params)))) {
				$keys = implode(', ', $this->keys);
				throw new SebastianException("One of [{$keys}] must be provided for entity {$this->entity}", 500);
			}

			// check temp cache
			$skeleton = $this->build(null, $params);
			foreach ($this->keys as $key) {
				if ($this->getFieldValue($skeleton, $key) == null) {
					return null;
				}
			}

			$orcKey = $this->orc->generateKey($skeleton);

			if ($this->orc->isCached($orcKey)) {
				//$this->logger->info("hit _orc with {$orcKey}", "repo_log");
				return $this->orc->load($orcKey);
			} else {
				$this->orc->cache($orcKey, $skeleton);
			}

			// then check long term cache
			$cmKey = $this->cacheManager->generateKey($skeleton);
			if ($this->cacheManager->isCached($cmKey)) {
				return $this->cacheManager->load($cmKey);
			}

			$qf = $qf->select($this->columns)
					 ->from([$this->aliases[0] => $this->getTable()]);

			foreach ($this->joins as $field => $join) {
				$fieldConfig = $this->fields->sub($field);

				if ($fieldConfig->has('targetEntity')) {
					$target = $fieldConfig->get('targetEntity');
					$mEntityConfig = $this->entityManager->getDefinition($target);
					$table = $mEntityConfig->get('table');

					$foreignColumn = $join->get(
						'foreignColumn', 
						$this->entityManager->mapFieldToColumn($target, $join->get('foreign'))
					);
				} else {
					$table = $join->get('table');
					$foreignColumn = $join->get('foreignColumn');
				}

				$localColumn = $this->entityManager->mapFieldToColumn($this->entity, $join->get('local'));
				$withEntityKey = "{$this->aliases[0]}.{$localColumn}";

				$alias = $this->aliases[$field];
				$expression = $ef->reset()->expr($withEntityKey)
						->equals("{$alias}.{$foreignColumn}")
						->getExpression();

				$qf = $qf->join(Join::TYPE_LEFT, [$alias => $table], $expression);
			}

			$ef->reset();
			$mExFactory = ExpressionFactory::getFactory();
			foreach ($this->keys as $key) {
				$value = $this->getFieldValue($skeleton, $key);

				$column = $this->entityManager->mapFieldToColumn($this->entity, $key);
				$mExFactory->reset()->expr("{$this->aliases[0]}.{$column}")
					->equals(":$key"); // generates the individual expression

				$qf->bind($key, $value);
				$ef->andExpr($mExFactory->getExpression()); // handles generating the final expression
			}

			$qf = $qf->where($ef->getExpression());
			$query = $qf->getQuery();

			$statement = $this->connection->execute($query, $query->getBinds());
			$results = $statement->fetchAll();

			if ($results) {
				$fields = $this->entityManager->getNonForeignColumns($this->entity);

				foreach ($this->fields as $field => $config) {
					if (in_array($field, array_keys($fields))) {
						$key = strtolower($this->entity) . "_" . $this->entityManager->mapFieldToColumn($this->entity, $field);
						$value = $results[0][$key];
					} else {
						$join = $config->sub('join');

						if ($config->has('targetEntity')) {
							$target = $config->get('targetEntity');
							$targetRepo = $this->entityManager->getRepository($target);
							$targetFields = $this->entityManager->getNonForeignColumns($target);
							
							if (in_array($join->get('type', 'one'), ['one', 'onetoone', '1:1'])) {
								array_walk($targetFields, function(&$value, $key) use($target, $field, $results) {
									$key = strtolower("{$field}_{$key}");
									$value = $results[0][$key];
								});

								$value = $targetRepo->get($targetFields);
							} else {
								$seenKeys = [];
								$keys = $targetRepo->getObjectKeys();

								$realColumns = array_map(function($mField) use ($target, $field) {
									return strtolower("{$field}_{$mField}");
								}, array_keys($targetFields));

								$realKeys = array_map(function($mField) use ($target, $field) {
									return strtolower("{$field}_{$mField}");
								}, $keys);

								$slice = $results;
								array_walk($slice, function(&$row, $index) use ($realColumns) {
									$row = array_intersect_key($row, array_flip($realColumns));
								});

								$value = array_filter($slice, function($row, $index) use (&$seenKeys, $realKeys) {
									$mKeys = array_intersect_key($row, array_flip($realKeys));
									$hash = md5(implode(array_values($mKeys)));

									if (in_array($hash, $seenKeys)) return false;
									else {
										$seenKeys[] = $hash;
										return array_reduce(array_intersect_key($row, array_flip($realKeys)), function($carry, $value) {
											return $carry && $value != null;
										}, true);
									}
								}, ARRAY_FILTER_USE_BOTH);

								array_walk($value, function(&$row, $index) use ($field, $targetRepo, $targetFields) {
									$mValue = array_walk($targetFields, function(&$value, $key) use ($row, $field) {
										$key = strtolower("{$field}_{$key}");
										$value = $row[$key];
									});

									$row = $targetRepo->get($targetFields);
								});

								$value = array_values($value);
							}
						} else {
							$columns = $join->get('columns');
							$idColumns = $join->get('idColumns', [$columns[0]]);
							$realColumns = array_map(function($column) use ($field) { return "{$field}_{$column}"; }, $columns);
							$realIdColumns = array_map(function($column) use ($field) { return "{$field}_{$column}"; }, $idColumns);

							if (in_array($join->get('type', 'one'), ['one', 'onetoone', '1:1'])) {
								$row = $results[0];
								$value = array_intersect_key($row, array_flip($realColumns));
							} else {
								$seenKeys = [];

								$slice = $results;
								array_walk($slice, function(&$row, $index) use ($realColumns) {
									$row = array_intersect_key($row, array_flip($realColumns));
								});

								$value = array_values(array_filter($slice, function($row, $index) use (&$seenKeys, $realIdColumns) {
									$keys = array_intersect_key($row, array_flip($realIdColumns));
									$hash = md5(implode(array_values($keys)));

									if (in_array($hash, $seenKeys)) return false;
									else {
										$seenKeys[] = $hash;
										return array_reduce(array_intersect_key($row, array_flip($realIdColumns)), function($carry, $value) {
											return $carry && $value != null;
										}, true);
									}
								}, ARRAY_FILTER_USE_BOTH));
							}
						}
					}

					$skeleton = $this->setFieldValue($skeleton, $field, $value);
				}
			} else {
				$this->orc->invalidate($orcKey); // get rid of the reference
				return null;
			}
			
			// persist it
			$this->orc->cache($orcKey, $skeleton);
			$this->cacheManager->cache(null, $skeleton);

			return clone $skeleton; // necessary to "sever" the object from the reference cache
		}

		//public function proc

		const PERSIST_MODE_INSERT = 0;
		const PERSIST_MODE_UPDATE = 1;
		const AUTO_GENERATED_TYPES = ['serial'];
		public function persist(&$object) {
			$qf = QueryFactory::getFactory();
			$mode = Repository::PERSIST_MODE_UPDATE;

			foreach ($this->keys as $key) {
				$fieldName = $this->columnMap->get($key);
				$value = $this->getFieldValue($object, $fieldName);
				if ($value == null) {
					$type = $this->getDefinition()->get("fields.{$key}.type");
					if (!in_array($type, self::AUTO_GENERATED_TYPES)) {
						throw new SebastianException("non auto-generated primary key columns cannot be null ({$type} - {$key})");
					}

					$mode = Repository::PERSIST_MODE_INSERT;
				} else {
					if ($this->get($value) == null) {
						$mode = Repository::PERSIST_MODE_INSERT;
					}
 				}
			}

			if ($mode == Repository::PERSIST_MODE_INSERT) {
				$columns = $this->entityManager->getNonForeignColumns($this->entity);

				foreach ($columns as $column) {
					$fieldName = $this->columnMap->get($column);
					$value = $this->getFieldValue($object, $fieldName);

					if ($value != null && !in_array($fieldName, $this->keys)) {
						$qf->insert($column, $value);
					}
				}

				$foreign = $this->entityManager->getOneToOneMappedColumns($this->entity);

				// todo: do I have to persist these foreign objects?
				foreach ($foreign as $name => $foreign) { // lmfao
					$column = $foreign['column'];
					$fieldName = $this->columnMap->get($column);

					$fObject = $this->getFieldValue($object, $fieldName);

					if ($fObject != null) {
						$fRepo = $this->entityManager->getRepository($foreign['targetEntity']);
						$fFieldName = $fRepo->getColumnMap()->get($foreign['foreign']);

						$value = $fRepo->getFieldValue($fObject, $fFieldName);

						$qf->insert($column, $value);
					}
				}

				foreach ($this->keys as $key) {
					$qf->returning([$key => null]);
				}

				$qf->into($this->getTable());
				$query = $qf->getQuery();

				//print($query);
				//die();
				$result = $this->getConnection()->execute($query, $query->getBinds());

				$object = $this->build($object, $result->fetchAll()[0]);
				//$this->refresh($object);
			} else {
				$changed = $em->computeObjectChanges($object);



				//$qf = $qf->update($this->getTable());
				//print ($qf->getQuery());
			}

			$key = $this->cacheManager->generateKey($object);
			$this->cacheManager->invalidate($key);
			
			return $object;
		}

		public function refresh(&$object) {
			$object = $object;//$this->get()
		}

		public function generateColumnMap() {
			$columnMap = new Collection();
			$definition = $this->getDefinition();

			foreach ($definition->sub('fields') as $key => $field) {
				if (!$field->has('column')) $columnMap->set($key, $key);
				else $columnMap->set($field->get('column'), $key);
			}

			return $columnMap;
		}

		public function setFieldValue($object, $fieldName, $value) {
			$useReflection = $this->config->get('use_reflection', false);
			$field = $this->fields->sub($fieldName);
			$type = $field->get('type', null);

			if ($type != null) {
				if ($field->has('transformer')) {
					$mTransformer = $field->get('transformer');	
					$transformer = $this->entityManager->getTransformer($mTransformer);

					if ($transformer == null) {
						throw new SebastianException("Unable to find transformer {$mTransformer}.");
					}
				} else $transformer = $this->entityManager->getTransformer($type);
				
				if ($transformer != null) {
					$value = $transformer->transform($value);
				}
			}

			if ($useReflection) {
				$field = $this->reflection->getProperty($fieldName);
				$inaccessible = $field->isPrivate() || $field->isProtected();
				
				if ($inaccessible) {
					$field->setAccessible(true);
					$field->setValue($object, $value);
					$field->setAccessible(false); // reset
				} else {
					$field->setValue($object, $value);
				}
			} else {
				$method = $this->getSetterMethod($fieldName, false);
				if ($method) $object->{$method}($value);
			}

			return $object;
		}

		public function getFieldValue($object, $fieldName) {
			$useReflection = $this->config->get('use_reflection', false);
			$field = $this->fields->sub($fieldName);
			$type = $field->get('type', null);

			if ($useReflection) {
				$field = $this->reflection->getProperty($fieldName);
				$inaccessible = $field->isPrivate() || $field->isProtected();
					
				if ($inaccessible) {
					$field->setAccessible(true);
					$value = $field->getValue($object);
					$field->setAccessible(false); // reset
				} else $value = $field->getValue($object);
			} else {
				$method = $this->getGetterMethod($field);
				$value = $object->{$method}();
			}

			if ($type != null) {
				$transformer = $this->entityManager->getTransformer($type);

				if ($transformer != null) {
					$value = $transformer->reverseTransform($value);
				}
			}

			return $value;
		}

		public function getGetterMethod($key, $die = true) {
			foreach (['get','is','has'] as $prefix) {
				$methodName = $key;
				$methodName[0] = strtoupper($methodName[0]);
				$methodName = $prefix . $methodName;

				if (method_exists($this->entityManager->getNamespacePath($this->entity), $methodName)) {
					return $methodName;
				}
			}
			
			if ($die) {
				throw new \Exception("No 'get' method found for {$key} in {$this->entity}");	
			} else return null;
		}

		public function getSetterMethod($key, $die = true) {
			foreach (['set','add','put'] as $prefix) {
				$methodName = $key;
				$methodName[0] = strtoupper($methodName[0]);
				$methodName = $prefix . $methodName;

				if (method_exists($this->entityManager->getNamespacePath($this->entity), $methodName)) {
					return $methodName;
				}
			}
			
			if ($die) {
				throw new \Exception("No 'set' method found for {$key} in {$this->entity}");
			} else return null;
		}

		public function getColumnMap() {
			return $this->columnMap;
		}

		public function getConnection() {
			return $this->entityManager->getConnection();
		}

		public function getDefinition() {
			return $this->definition;
		}

		public function getObjectKeys() {
			return $this->keys;
		}

		public function getPrimaryKeys($object = null) {
			if ($object == null) return $this->keys;
			else {
				$self = $this;
				return array_map(function($key) use ($object, $self) {
					return $self->getFieldValue($object, $key);
				}, $this->keys);
			}
		}

		public function getTable() {
			return $this->table;
		}

		public function setTransformer(ColumnTransformerInterface $transformer) {
			$this->transformer = $transformer;
		}

		public function getTransformer() {
			return $this->transformer;
		}
	}