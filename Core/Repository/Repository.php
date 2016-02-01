<?php
	namespace Sebastian\Core\Repository;

	use Sebastian\Core\Utility\Logger;
	use Sebastian\Core\Utility\Utils;

	use Sebastian\Core\Cache\CacheManager;
	use Sebastian\Core\Configuration\Configuration;

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
		protected static $logger;

		protected $config;

		protected $class;
		protected $definition;
		protected $context;
		protected $connection;

		protected $em;
		protected $cm;
		protected static $_objectReferenceCache;
		
		public function __construct($context, $em, $class = null) {
			$this->context = $context;
			$this->class = $class;

			$this->initCalled = false;

			// convenience
			$this->em = $em;
			$this->cm = $context->getCacheManager();
			$this->connection = $em->getConnection();

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

			if ($this->class != null) {
				$this->definition = $this->em->getDefinition($this->class);
				$this->nsPath = $this->em->getNamespacePath($this->class);
			}

			if (Repository::$_objectReferenceCache == null) {
				Repository::$_objectReferenceCache = new CacheManager(
					$this->context,
					new Configuration(['driver' => CacheManager::ARRAY_DRIVER])
				);
			}
		}

		/**
		 * loads an object from the database based off a 
		 * set of rule defined in an orm config file
		 * @param  array $params initial paramters to seed the object with
		 * @return Entity a completed, possibly lazily loaded Entity
		 */
		public function get($params) {
			$table = $this->definition->get('table');
			$keys = $this->definition->get('keys');
			$fields = $this->definition->get('fields');

			if (empty(array_intersect($keys, array_keys($params)))) {
				$keys = implode(', ', $keys);
				throw new \Exception("One of {$keys} must be provided in {$this->class}", 500);
			}

			$skeleton = $this->initializeObject($params);

			$orcKey = self::$_objectReferenceCache->generateKey($skeleton);
			if (self::$_objectReferenceCache->isCached($orcKey)) {
				return self::$_objectReferenceCache->load($orcKey);
			} else {
				self::$_objectReferenceCache->cache(null, $skeleton);	
			}

			$qb = $this->em->getQueryBuilder();
			
			// columns in the object's table
			$commonAttributes = array_filter($fields, function($field) {
				return !isset($field['table']);
			});

			$cols = $this->em->getNonForeignColumns($this->class);

			$qb = $qb->select(implode(',', $cols))
					 ->from($table);

			foreach ($keys as $key) {
				$qb = $qb->where("{$key} = " . Utils::escapeSQL($skeleton->{$self->getGetterMethod($key)}()));
				//$qb->bind($key, $skeleton->{$self->getGetterMethod($key)}()))
			}

			$query = $qb->getQuery();

			$result = $this->connection->execute($query);
			$results = $result->fetchFirst();

			if ($results) {
				foreach ($commonAttributes as $key => $attributes) {
					// for example, if a referenced column is not named the same
					// in the referenced table
					if (isset($results[$attributes['column']])) {
						$skeleton->{$this->getSetterMethod($key)}($results[$attributes['column']]);
					}
				}
			} else return null;

			foreach ($otherAttributes as $key => $attributes) {
				$repo = $this->forEntity($attributes['entity']);

				if ($attributes['relation'] === 'many') $result = []; // 1:x relationship
				else $result = null; // 1:1 relationship

				if ($attributes['relation'] === 'one') {
					$result = $repo->get([$attributes['with'] => $results[$attributes['column']]]);
				} else if ($attributes['relation'] === 'many') {
					$table = $repo->definition['table'];
					
					$qb = $this->em->getQueryBuilder();
					$qb->select('*')
					   ->from($table)
					   ->where("{$attributes['with']} = " . Utils::escapeSQL($skeleton->getId()));

					if (isset($attributes['filter'])) $qb = $qb->where($attributes['filter']);
					if (isset($attributes['order'])) $qb = $qb->orderBy($attributes['order'] . " ASC");

					$query = $qb->getQuery();
					$results = $this->conn->execute($query)
									->fetch();

					foreach ($results as $mResult) {
						$entity = $repo->build($mResult, $skeleton);
						if ($entity) $result[] = $entity;
					}
				} else {
					$result = $repo->get([$attributes['with'] => $results[$attributes['column']]]);
				}

				$skeleton->{$this->getSetterMethod($key)}($result);
			}

			$skeleton->reset(); // clears entity's 'touched' parameter
			//$this->cm->cache($skeleton);

			return $skeleton;
		}

		public function find($where = [], $options = []) {
			if (!$this->initCalled) throw new \Exception('(Repository|parent)::init() must be called', 1);
			//self::$logger->info("find: {$this->class}\n\t\tusing " . implode(',', array_keys($where)));

			$qb = $this->em->getQueryBuilder();
			$qb = $qb->select('*')
					 ->from($this->getTable());

			foreach ($where as $key => $value) {
				if (!is_array($value)) $value = [$value];
				foreach ($value as $mKey => $mValue) {
					if (Utils::startsWith($mValue, '!')) {
						$mode = 0;
						$mValue = substr($mValue, 1);
					} else $mode = 1;

					$mValue = Utils::escapeSQL($mValue);
					
					if ($mode == 0) {
						$qb = $qb->where("{$key} != {$mValue}");
					} else {
						$qb = $qb->where("{$key} = {$mValue}");
					}
				}
			}

			if (isset($options['limit'])) $qb = $qb->limit($options['limit']);
			if (isset($options['offset'])) $qb = $qb->offset($options['offset']);
			if (isset($options['order'])) $qb = $qb->orderBy(implode(' ', $options['order']));

			$query = $qb->getQuery();
			$result = $this->conn->execute($query);

			$return = [];
			foreach ($result->fetch() as $mResult) {
				$return[] = $this->get($mResult);
			}

			return $return;
		}

		public function computeJoinSets() {

		}


		protected function computeJoinColumns(&$joins = []) {
			$fields = $this->definition['fields'];

			$mJoins = array_filter($fields, function($field) {
				return isset($field['relation']) && $field['relation'] == 'one';
			});

			$mJoins = array_unique($mJoins);

			foreach ($mJoins as &$join) {
				$entity = $join['entity'];

				if (in_array($entity, array_keys($joins))) continue;

				//print ($entity);

				$repo = $this->em->getRepository($entity);

				$nonFks = $this->em->getNonForeignColumns($entity);
				$joins[$entity] = $nonFks;

				$repo->computeJoinColumns($joins);
			}

			return $joins;
		}

		// todo refactor
		public function build($data, &$parent = null) {
			if (!$this->initCalled) throw new \Exception('(Repository|parent)::init() must be called', 1);
			//self::$logger->info("build: {$this->class}\n\t\tusing " . implode(',', array_keys($data)) . "\n\t\tparent: " . ($parent ? 'yes' : 'no'));

			$keys = $this->definition['keys'];
			$returnedKeys = array_intersect(array_keys($data), $keys);

			$key = array_shift($returnedKeys); // TODO first for now but should be multi
			$skeleton = $this->initializeObject([$key => $data[$key]]);

			$normalizedFieldKeys = [];
			foreach ($this->definition['fields'] as $key => $value) {
				$normalizedFieldKeys[strtolower($key)] = $key; 
			}

			foreach ($data as $key => $value) {
				$normalizedKey = @$normalizedFieldKeys[strtolower($key)];
				if (!$normalizedKey) continue;

				$config = $this->definition['fields'][$normalizedKey];

				if (@$config['entity']) {
					if (isset($config['relation']) && $config['relation'] === 'parent') {
						$value = $parent;
					} else {
						$repo = $this->forEntity($config['entity']);
						$with = $config['with'] ?: $key;

						$value = $repo->get([$with => $value]);
					}
				} else {
					$type = isset($config['type']) ? $config['type'] : null; // might break something
					$value = Utils::cast($value, $type);
				}
				
				$set = $this->getSetterMethod($key, false);
				
				if ($set) $skeleton->{$set}($value);
				//else print("nothing for {$key}");
			}

			$remaining = array_diff(array_keys($normalizedFieldKeys), array_keys($data));
			
			foreach ($remaining as $fieldName) {
				$key = $normalizedFieldKeys[$fieldName];
				$field = $this->definition['fields'][$key];
				$repo = $this->forEntity($field['entity']);

				if ($field['relation'] === 'many') $result = []; // 1:x relationship
				else $result = null; // 1:1 relationship

				if ($field['relation'] == 'one') {
					$result = $repo->get([$field['with'] => $data[$field['column']]]);
				} else {
					// em->find woruld be great for this $repo->find(['parent' => x])
					$table = isset($field['table']) ? $field['table'] : $repo->getTable();
					$qb = $this->em->getQueryBuilder();
					$qb = $qb->select('*')
							 ->from($table)
							 ->where("{$field['with']} = " . Utils::escapeSQL($skeleton->getId()));

					if (isset($field['filter'])) $qb = $qb->where($field['filter']);
					if (isset($field['order'])) $qb = $qb->orderBy($field['order'] . " ASC");
							   
					$query = $qb->getQuery();
					$results = $this->conn->execute($query)
									->fetch();

					foreach ($results as $mResult) {
						$entity = $repo->build($mResult, $skeleton);
						if ($mResult) $result[] = $entity;
					}
				}

				$skeleton->{$this->getSetterMethod($key)}($result);
			}

			return $skeleton;
		}

		const PERSIST_MODE_UPDATE = 0;
		const PERSIST_MODE_INSERT = 1;

		public function persist($object, $parent) {
			if (!$this->initCalled) throw new \Exception('(Repository|parent)::init() must be called', 1);
			//self::$logger->info("persist: {$this->class}");

			if (!is_object($object)) {
				throw new \Exception("must be an object", 1);
			} else if (!$object->isTouched()) return;

			$keys = $this->definition['keys'];
			$fields = $this->definition['fields'];

			$qb = $this->em->getQueryBuilder();

			$commonAttributes = array_filter($fields, function($field) {
				return ((!isset($field['table']) || 
						 $field['table'] === $this->getTable()) && !isset($field['entity']));
			});

			$otherAttributes = array_filter($fields, function($field) {
				return ((isset($field['table']) && $field['table'] != $this->getTable()) || 
						 isset($field['entity']));
			});

			$self = $this;
			$keys = array_filter($keys, function($key) use ($object, $self) {
				$getterMethod = $self->getGetterMethod($key);
				return ($object->{$getterMethod}() !== null);
			});

			// var_dump($keys); print('<br/>');

			if (!empty($keys)) {
				$qb = $qb->select(implode(',', $keys))
						   ->from($this->definition['table']); // todo fixme
				foreach ($keys as $key) {
					$get =  $this->getGetterMethod($key);
					$qb = $qb->where("{$key} = " . Utils::escapeSQL($object->{$get}()));
				}

				$query = $qb->getQuery();
				$results = $this->conn->execute($query)->fetch();

				if (!$results) $mode = self::PERSIST_MODE_INSERT;
				else $mode = self::PERSIST_MODE_UPDATE;
			} else $mode = self::PERSIST_MODE_INSERT;

			$commonUpdateFields = array_filter($commonAttributes, function($value, $key) use ($object) {
				$touched = $object->isTouched($key);
				return $touched;
			}, ARRAY_FILTER_USE_BOTH);

			$otherUpdateFields = array_filter($otherAttributes, function($value, $key) use ($object) {
				$touched = $object->isTouched($key);
				return $touched;
			}, ARRAY_FILTER_USE_BOTH);

			if (!empty($commonUpdateFields) || !empty($otherUpdateFields)) {
				if ($mode == self::PERSIST_MODE_INSERT) {
					$qb = $qb->clean()
						 ->insert($this->definition['table']);
				} else {
					$this->cm->invalidate($object); // clear it right away
					if (empty(array_intersect($keys, $this->definition['keys']))) {
						$keys = implode(', ', $keys);
						throw new \Exception("One of {$keys} must be provided", 500);
					}

					$qb = $qb->clean()
						 ->update($this->definition['table']);

					foreach ($keys as $key) {
						$get = $this->getGetterMethod($key);
						$qb = $qb->where("{$key} = " . Utils::escapeSQL($object->{$get}()));
					}
				}

				foreach ($commonUpdateFields as $key => $field) {
					$get = $this->getGetterMethod($key);
					$mObject = $object->{$get}();

					if (isset($field['relation']) && $field['relation'] == 'parent') {
						//$get = $this->getGetterMethod($key);
						// todo fix me
						$qb = $qb->set($field['column'], $parent->getId());
					} else {
						$qb = $qb->set($field['column'], $object->{$get}());	
					}
				}

				//print $this->class; print "</br>";
				//var_dump($otherUpdateFields); print "</br>";

				foreach ($otherUpdateFields as $key => $field) {
					$get = $this->getGetterMethod($key);
					$mObject = $object->{$get}();

					// set up some stuff
					$entity = $this->definition['fields'][$key];
					//var_dump($entity); print "</br>";
					$repo = $this->forEntity($field['entity']);
					$withMethod = $repo->getGetterMethod($field['with']);

					if ($entity['relation'] == 'one' || $entity['relation'] == 'parent') {
						$this->em->persist($mObject, $parent);
						$value = $mObject ? $mObject->{$withMethod}() : null;

						if (!isset($field['column'])) continue;
						$qb = $qb->set($field['column'], $value);
					}
				}

				if ($mode == self::PERSIST_MODE_INSERT) {
					$qb = $qb->returning(implode(',', $this->definition['keys']));
				}

				$query = $qb->getQuery();
				$results = $this->conn->execute($query);
				$result = $results->fetchFirst();

				if ($mode == self::PERSIST_MODE_INSERT) {
					foreach ($result as $key => $value) {
						$set = $this->getSetterMethod($key);
						$object->{$set}($value);
					}
				}

				foreach ($otherUpdateFields as $key => $field) {
					// get a copy of the object
					$get = $this->getGetterMethod($key);
					$mObject = $object->{$get}();

					// set up some stuff
					$entity = $this->definition['fields'][$key];
					$repo = $this->forEntity($field['entity']);
					$withMethod = $repo->getGetterMethod($field['with']);

					if ($entity['relation'] == 'many' && is_array($mObject)) {
						foreach ($mObject as $nObject) {
							$this->em->persist($nObject, $object);
						}
					}
				}
			}
		}

		public function getSetterMethod($key, $die = true) {
			foreach (['set','add','put'] as $prefix) {
				$methodName = $key;
				$methodName[0] = strtoupper($methodName[0]);
				$methodName = $prefix . $methodName;

				if (method_exists($this->em->getNamespacePath($this->class), $methodName)) {
					return $methodName;
				}
			}
			
			if ($die) {
				throw new \Exception("No 'set' method found for {$key} in {$this->class}");
			} else return null;
		}

		public function getGetterMethod($key, $die = true) {
			foreach (['get','is','has'] as $prefix) {
				$methodName = $key;
				$methodName[0] = strtoupper($methodName[0]);
				$methodName = $prefix . $methodName;

				if (method_exists($this->em->getNamespacePath($this->class), $methodName)) {
					return $methodName;
				}
			}
			
			if ($die) {
				throw new \Exception("No 'get' method found for {$key} in {$this->class}");	
			} else return null;
		}

		// MISC
		private function initializeObject($params = []) {
			$object = $this->em->getNamespacePath($this->class);
			$object = new $object();

			foreach ($params as $key => $value) {
				$setterMethod = $this->getSetterMethod($key, false);
				if ($setterMethod) $object->{$setterMethod}($value);
			}

			return $object;
		}

		public function getConnection() {
			return $this->em->getConnection();
		}
	}