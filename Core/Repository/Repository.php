<?php
	namespace Sebastian\Core\Repository;

	use Sebastian\Application;
	use Sebastian\Core\Database\EntityManager;
	use Sebastian\Core\Database\Query\Expression\ExpressionFactory;
	use Sebastian\Core\Database\Query\Part\Join;
	use Sebastian\Core\Database\Query\QueryFactory;
	use Sebastian\Core\Exception\SebastianException;
	use Sebastian\Core\Repository\Transformer\ColumnTransformerInterface;
	use Sebastian\Utility\Collection\Collection;
	use Sebastian\Utility\Configuration\Configuration;

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

		protected $context;
		protected $config;

		protected $entity;
		protected $class;
		protected $definition;
		
		protected $connection;
		protected $em;
		protected $cm;
		protected $orc;
		
		public function __construct(Application $context, EntityManager $em, $entity = null, Configuration $config = null) {
			$this->context = $context;

			if ($config == null) $config = new Configuration();
			$this->config = $config->extend([ 'use_reflection' => true ]);

			$this->em = $em;
			$this->entity = $entity;
			$this->class = $this->em->getNamespacePath($this->entity);
			$this->orc = $this->em->getObjectCache();

			$this->initCalled = false; // don't know why
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
				$this->definition = $this->em->getDefinition($this->entity);
				$this->nsPath = $this->em->getNamespacePath($this->entity);

				$this->table = $this->definition->get('table');
				$this->keys = $this->definition->get('keys');
				$this->fields = $this->definition->sub('fields');

				$this->joins = $this->em->computeJoinSets($this->entity);
				$this->aliases = $this->em->generateTableAliases($this->entity, $this->joins);
				$this->columns = $this->em->computeColumnSets($this->entity, $this->joins, $this->aliases);
			}

			$this->reflection = new \ReflectionClass($this->class);
			$this->columnMap = $this->generateColumnMap();

			// convenience
			$this->cm = $this->context->getCacheManager();
			$this->logger = $this->context->getLogger();
			$this->connection = $this->em->getConnection();
		}

		/**
		 * [build description]
		 * @param  [type] $object [description]
		 * @param  array  $params [description]
		 * @return [type]         [description]
		 */
		public function build($object = null, $params = []) {
			if (!$object) $object = $this->initializeObject();

			$useReflection = $this->config->get('use_reflection', false);

			foreach ($params as $key => $value) {
				$fieldName = $this->columnMap->get($key);
				$field = $this->fields->sub($fieldName);
				if ($fieldName == null) continue;

				$object = $this->setFieldValue($object, $fieldName, $value);
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

				$key = $this->cm->generateKey($object);
				$this->cm->invalidate($key);

				return true;
			} catch (\Exception $e) {
				throw new SebastianException("Could not delete {$this->getClass()}: {$e->getMessage()}");
			}
		}

		public function find($where = []) {
			$mSkeletons = [];
			$qf = QueryFactory::getFactory();
			$ef = ExpressionFactory::getFactory();

			$qf = $qf->select($this->columns)->from([$this->aliases[$this->entity] => $this->getTable()]);

			foreach ($this->joins as $key => $join) {
				$withEntityKey = "{$this->aliases[$this->entity]}.{$join['column']}";
				$foreignTableAlias = $this->aliases["{$join['column']}_{$join['table']}"];

				$expression = $ef->reset()->expr($withEntityKey)
					->equals("{$foreignTableAlias}.{$join['foreign']}")->getExpression();

				$qf = $qf->join(Join::TYPE_LEFT, [$foreignTableAlias => $join['table']], $expression);
			}

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

			$query = $qf->getQuery();
			$result = $this->connection->execute($query, $query->getBinds());
			$results = $result->fetchAll() ?: [];

			foreach ($results as $mResult) {
				$skeleton = $this->build(null, $mResult);

				foreach ($this->em->getOneToOneMappedColumns($this->entity) as $mapped) {
					$repo = $this->em->getRepository($mapped['entity']);
					//$this->logger->info("{$this->entity} repo {$mapped['entity']}");

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

				foreach ($this->em->getMultiMappedFields($this->entity) as $key => $field) {
					$repo = $this->em->getRepository($field['entity']);

					$with = $mResult[$field['with']];
					$entities = $repo->find([$field['mapped'] => $with]);
					$skeleton = $this->build($skeleton, [$key => $entities]);
				}

				$mSkeletons[] = $skeleton;
			}

			return $mSkeletons;
		}

		public function findOne($where = []) {
			
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
			$skeleton = $this->initializeObject($params);
			$orcKey = $this->orc->generateKey($skeleton);

			if ($this->orc->isCached($orcKey)) {
				$this->logger->info("hit _orc with {$orcKey}", "repo_log");
				return $this->orc->load($orcKey);
			} else {
				$this->orc->cache($orcKey, $skeleton);
			}

			// then check long term cache
			$cmKey = $this->cm->generateKey($skeleton);
			if ($this->cm->isCached($cmKey)) {
				return $this->cm->load($cmKey);
			}

			$qf = $qf->select($this->columns)->from([$this->aliases[$this->entity] => $this->getTable()]);

			foreach ($this->joins as $key => $join) {
				if ($join['type'] == Repository::JOIN_TYPE_FK) {
					$withEntityKey = "{$this->aliases[$this->entity]}.{$join['column']}";
					$foreignTableAlias = $this->aliases["{$join['column']}_{$join['table']}"];

					$expression = $ef->reset()->expr($withEntityKey)
						->equals("{$foreignTableAlias}.{$join['foreign']}")
						->getExpression();

					$qf = $qf->join(Join::TYPE_LEFT, [$foreignTableAlias => $join['table']], $expression);
				} else if ($join['type'] == Repository::JOIN_TYPE_JOIN_TABLE) {
					foreach (['Local', 'Foreign'] as $type) {
						$mJoin = $join['join'];
						$mColumn = $mJoin["joinColumn{$type}"];
						$mTable = $mJoin["joinTable{$type}"];
						$tableAlias = $this->aliases["{$mColumn}_{$mTable}"];

						$columns = explode(':', $mColumn);
						if ($type == 'Local') $withEntityKey = "{$this->aliases[$this->entity]}.{$columns[0]}";
						else {
							$columnAlias = "{$mJoin['joinColumnLocal']}_{$mJoin['joinTableLocal']}";
							$withEntityKey = "{$this->aliases[$columnAlias]}.{$columns[0]}";
						}

						$expression = $ef->reset()->expr($withEntityKey)
							->equals("{$tableAlias}.{$columns[1]}")
							->getExpression();

						$qf = $qf->join(Join::TYPE_LEFT, [$tableAlias => $mTable], $expression);
					}
				}
			}

			$ef->reset();
			$mExFactory = ExpressionFactory::getFactory();
			foreach ($this->keys as $name => $key) {
				$fieldName = $this->columnMap->get($key);
				$value = $this->getFieldValue($skeleton, $fieldName);
				if ($value != null) {
						//throw new SebastianException("Primary Key {$key} cannot be null/blank");

					$mExFactory->reset()->expr("{$this->aliases[$this->entity]}.{$key}")
						->equals(":$fieldName"); // generates the individual expression

					$qf->bind($fieldName, $value);

					$ef->andExpr($mExFactory->getExpression()); // handles generating the final expression
				} else {
					throw new SebastianException("Primary Key {$key} cannot be null/blank");
				}
			}

			$qf = $qf->where($ef->getExpression());
			$query = $qf->getQuery();

			$statement = $this->connection->execute($query, $query->getBinds());
			$results = $statement->fetchAll();

			if ($results) {
				$skeleton = $this->build($skeleton, $results[0]);

				foreach ($this->em->getOneToOneMappedColumns($this->entity) as $key => $mapped) {
					if (array_key_exists('entity', $mapped)) {
						$repo = $this->em->getRepository($mapped['entity']);	

						$row = $results[0];
						$objectParams = [];
						foreach ($row as $key => $column) {
							$nMatches = preg_match("/{$mapped['column']}_([a-zA-Z0-9_]+)/", $key, $matches);
							if ($nMatches != 0) $objectParams[$matches[1]] = $row[$matches[0]];	
						}

						try {
							$value = $repo->get($objectParams);
						} catch (SebastianException $e) {
							$value = null;
						}
					} else {
						if (array_key_exists('join', $mapped)) {
							$column = explode(':', $mapped['join']['joinColumnForeign'])[1];
							$value = $results["{$key}_{$column}"];
						} else {
							$value = $results["{$key}_{$mapped['table']}"];
						}
					}
					
					$skeleton = $this->build($skeleton, [$mapped['column'] => $value]);
				}
				
				foreach ($this->em->getMultiMappedFields($this->entity) as $key => $field) {
					if (array_key_exists('entity', $field)) {
						$repo = $this->em->getRepository($field['entity']);
					
						if (array_key_exists('join', $field)) { // join table
							foreach ($results as $row) {
								$objectParams = [];
								foreach ($row as $mKey => $column) {
									$nMatches = preg_match("/{$key}_([a-zA-Z0-9_]+)/", $mKey, $matches);
									if ($nMatches != 0) $objectParams[$matches[1]] = $row[$matches[0]];	
								}

								$values[] = $repo->get($objectParams);
							}
						} else { // fk
							$with = $results[0][$field['with']];
							$values = $repo->find([$field['mapped'] => $with]);
						}
					}
					
					$skeleton = $this->build($skeleton, [$key => $values]);
				}
			} else return null;
			
			$this->orc->cache($orcKey, $skeleton);
			$this->cm->cache(null, $skeleton);

			return clone $skeleton; // necessary to "sever" the object from the reference cache
		}

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
				}
			}

			if ($mode == Repository::PERSIST_MODE_INSERT) {
				$columns = $this->em->getNonForeignColumns($this->entity);

				foreach ($columns as $column) {
					$fieldName = $this->columnMap->get($column);
					$value = $this->getFieldValue($object, $fieldName);

					if ($value != null && !in_array($fieldName, $this->keys)) {
						$qf->insert($column, $value);
					}
				}

				$foreign = $this->em->getOneToOneMappedColumns($this->entity);

				// todo: do I have to persist these foreign objects?
				foreach ($foreign as $name => $foreign) { // lmfao
					$column = $foreign['column'];
					$fieldName = $this->columnMap->get($column);

					$fObject = $this->getFieldValue($object, $fieldName);

					if ($fObject != null) {
						$fRepo = $this->em->getRepository($foreign['entity']);
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

				$result = $this->getConnection()->execute($query, $query->getBinds());
				$object = $this->build($object, $result->fetchAll()[0]);
				$this->refresh($object);
			} else {
				$changed = $em->computeObjectChanges($object);



				//$qf = $qf->update($this->getTable());
				//print ($qf->getQuery());
			}

			$key = $this->cm->generateKey($object);
			$this->cm->invalidate($key);
			
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
					$transformer = $this->em->getTransformer($mTransformer);

					if ($transformer == null) {
						throw new SebastianException("Unable to find transformer {$mTransformer}.");
					}
				} else $transformer = $this->em->getTransformer($type);
				
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
				$transformer = $this->em->getTransformer($type);

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

				if (method_exists($this->em->getNamespacePath($this->entity), $methodName)) {
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

				if (method_exists($this->em->getNamespacePath($this->entity), $methodName)) {
					return $methodName;
				}
			}
			
			if ($die) {
				throw new \Exception("No 'set' method found for {$key} in {$this->entity}");
			} else return null;
		}

		// MISC
		private function initializeObject($params = []) {
			$object = $this->em->getNamespacePath($this->entity);
			$object = new $object();

			return $this->build($object, $params);
		}

		public function getColumnMap() {
			return $this->columnMap;
		}

		public function getConnection() {
			return $this->em->getConnection();
		}

		public function getDefinition() {
			return $this->definition;
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