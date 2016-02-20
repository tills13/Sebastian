<?php
	namespace Sebastian\Core\Repository;

	use Sebastian\Utility\Collection\Collection;
	use Sebastian\Core\Cache\CacheManager;
	use Sebastian\Utility\Configuration\Configuration;
	use Sebastian\Core\Database\Query\Expression\ExpressionFactory;
	use Sebastian\Core\Database\Query\Part\Join;
	use Sebastian\Core\Database\Query\QueryFactory;
	use Sebastian\Core\Exception\SebastianException;

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
		protected static $_objectReferenceCache;
		
		public function __construct($context, $em, $entity = null, Configuration $config = null) {
			$this->context = $context;

			if ($config == null) $config = new Configuration();
			$this->config = $config->extend([
				'use_reflection' => true
			]);

			$this->em = $em;
			$this->entity = $entity;
			$this->class = $this->em->getNamespacePath($this->entity);

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
				$this->fields = $this->definition->get('fields');

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

			if (Repository::$_objectReferenceCache == null) {
				Repository::$_objectReferenceCache = new CacheManager(
					$this->context,
					new Configuration(['driver' => CacheManager::ARRAY_DRIVER])
				);
			}
		}

		public function build($object = null, $params = []) {
			if (!$object) $object = $this->initializeObject();

			$useReflection = $this->config->get('use_reflection', false);

			foreach ($params as $key => $value) {
				$fieldName = $this->columnMap->get($key);
				if ($fieldName == null) continue;

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
			}

			return $object;
		}

		public function delete($object) {
			$qf = QueryFactory::getFactory();
			$ef = ExpressionFactory::getFactory();

			$qf = $qf->delete()->from($this->getTable());

			$mEf = ExpressionFactory::getFactory();
			foreach ($this->keys as $key) {
				$value = $this->getFieldValue($object, $key);

				$ef->reset()->expr($key)->equals($value);
				$mEf->andExpr($ef->getExpression());
			}

			$qf = $qf->where($mEf->getExpression());
			$query = $qf->getQuery();

			$result = $this->connection->execute($query, []);

			if ($result->getError() == null) return true;
			else {
				throw new SebastianException("Could not delete {$this->getClass()}: {$result->getError()}");
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
					$with = "{$this->aliases[$this->entity]}.{$with}";
					$ef = $ef->reset()->expr($with);

					// todo allow for tables like [['table' => 'column'] => param]
					if (preg_match("/(!|not|>|<) ?(.+)/i", $param, $matches) >= 1) {
						$operator = $matches[1];
						$param = $matches[2];

						if ($operator == '!' || $operator == 'not') $ef = $ef->notEquals($param);
						else if ($operator == '<') $ef = $ef->lessThan($param);
						else if ($operator == '>') $ef = $ef->greaterThan($param);
					} else {
						//$param = "{$this->aliases[$this->entity]}.{$param}";
						$ef = $ef->equals($param);
					}

					if ($expression) {
						$mExpression = $ef->getExpression();
						$ef = $ef->reset()->with($mExpression)->andExpr($expression);
					}

					$expression = $ef->getExpression();
				}
			}

			$qf = $qf->where($expression);
			$result = $this->connection->execute($qf->getQuery(), []);
			$results = $result->fetchAll() ?: [];

			foreach ($results as $mResult) {
				$skeleton = $this->build(null, $mResult);

				foreach ($this->em->getOneToOneMappedColumns($this->entity) as $mapped) {
					$repo = $this->em->getRepository($mapped['entity']);
					$this->logger->info("{$this->entity} repo {$mapped['entity']}");

					$params = [];
					foreach ($mResult as $key => $value) {
						$nMatches = preg_match("/{$mapped['column']}_([a-zA-Z0-9_]+)/", $key, $matches);
						if ($nMatches != 0) $params[$matches[1]] = $mResult[$matches[0]];
					}
					
					$entity = $repo->get($params);
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

		/**
		 * loads an object from the database based off a 
		 * set of rule defined in an orm config file
		 * @param array $params initial paramters to seed the object with
		 * @return Entity a completed, possibly lazily loaded Entity
		 */
		public function get($params) {
			if (!is_array($params)) {
				if (count($this->keys) != 1) throw new SebastianException(
					"Cannot use simplified method signature when entity has more than one key"
				);

				$params = [
					$this->keys[0] => $params
				];
			}

			$qf = QueryFactory::getFactory();
			$ef = ExpressionFactory::getFactory();

			if (empty(array_intersect($this->keys, array_keys($params)))) {
				$keys = implode(', ', $this->keys);
				throw new SebastianException("One of [{$keys}] must be provided for entity {$this->entity}", 500);
			}

			$skeleton = $this->initializeObject($params);
			$orcKey = self::$_objectReferenceCache->generateKey($skeleton);

			if (self::$_objectReferenceCache->isCached($orcKey)) {
				$this->logger->info("hit _orc with {$orcKey}");
				return self::$_objectReferenceCache->load($orcKey);
			} else {
				self::$_objectReferenceCache->cache($orcKey, $skeleton);
			}

			$qf = $qf->select($this->columns)->from([$this->aliases[$this->entity] => $this->getTable()]);

			foreach ($this->joins as $key => $join) {
				$withEntityKey = "{$this->aliases[$this->entity]}.{$join['column']}";
				$foreignTableAlias = $this->aliases["{$join['column']}_{$join['table']}"];

				$expression = $ef->reset()->expr($withEntityKey)
					->equals("{$foreignTableAlias}.{$join['foreign']}")
					->getExpression();

				$qf = $qf->join(Join::TYPE_LEFT, [$foreignTableAlias => $join['table']], $expression);
			}

			$ef->reset();
			$mExFactory = ExpressionFactory::getFactory();
			foreach ($this->keys as $key) {
				$withEntityKey = "{$this->aliases[$this->entity]}.{$key}";
				$value = $this->getFieldValue($skeleton, $key);

				if ($value == null) throw new SebastianException("Primary Key {$key} cannot be null/blank");

				// generates the individual expression
				$mExFactory->reset()->expr($withEntityKey)
					->equals($value);

				//$qf->bind($withEntityKey, $value);

				// handles generating the final expression
				$ef->andExpr($mExFactory->getExpression());
			}

			$qf = $qf->where($ef->getExpression());
			$query = $qf->getQuery();

			$result = $this->connection->execute($query, []);
			$results = $result->fetchFirst();

			if ($results) {
				$skeleton = $this->build($skeleton, $results);

				foreach ($this->em->getOneToOneMappedColumns($this->entity) as $mapped) {
					$repo = $this->em->getRepository($mapped['entity']);

					$params = [];
					foreach ($results as $key => $value) {
						$nMatches = preg_match("/{$mapped['column']}_([a-zA-Z0-9_]+)/", $key, $matches);
						if ($nMatches != 0) $params[$matches[1]] = $results[$matches[0]];
					}
					
					$entity = $repo->get($params);
					$skeleton = $this->build($skeleton, [$mapped['column'] => $entity]);
				}
				
				foreach ($this->em->getMultiMappedFields($this->entity) as $key => $field) {
					$repo = $this->em->getRepository($field['entity']);
					
					$with = $results[$field['with']];
					$entities = $repo->find([$field['mapped'] => $with]);
					$skeleton = $this->build($skeleton, [$key => $entities]);
				}
			} else return null;
			
			$this->cm->cache(null, $skeleton);
			return $skeleton;
		}

		const PERSIST_MODE_INSERT = 0;
		const PERSIST_MODE_UPDATE = 1;
		const AUTO_GENERATED_TYPES = ['serial'];
		public function persist($object) {
			$qf = QueryFactory::getFactory();
			$mode = Repository::PERSIST_MODE_UPDATE;

			foreach ($this->keys as $key) {
				$value = $this->getFieldValue($object, $key);
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
					$field = $this->columnMap->get($column);
					$value = $this->getFieldValue($object, $field);

					if ($value != null && !in_array($field, $this->keys)) {
						$qf->insert($column, $value);
					}
				}

				$foreign = $this->em->getOneToOneMappedColumns($this->entity);

				// todo: do I have to persist these foreign objects?
				foreach ($foreign as $name => $foreign) {
					// lmfao
					$column = $foreign['column'];
					$field = $this->columnMap->get($column);

					$fObject = $this->getFieldValue($object, $field);
					$fRepo = $this->em->getRepository($foreign['entity']);
					$fField = $fRepo->getColumnMap()->get($foreign['foreign']);

					$value = $fRepo->getFieldValue($fObject, $fField);

					$qf->insert($column, $value);
				}

				$qf->into($this->getTable());
				$query = $qf->getQuery();
				$this->getConnection()->execute($query, $query->getBinds());
			} else {
				$qf = $qf->update($this->getTable());
			}
			
			return true;
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

		public function getFieldValue($object, $field) {
			$useReflection = $this->config->get('use_reflection', false);
			if ($useReflection) {
				$fieldName = $this->columnMap->get($field);
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

		public function getTable() {
			return $this->table;
		}
	}