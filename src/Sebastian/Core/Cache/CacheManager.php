<?php
	namespace Sebastian\Core\Cache;

	use Sebastian\Core\Entity\Entity;
	use Sebastian\Core\Utility\Logger;

	/**
	 * CacheManager
	 * @author Tyler <tyler@sbstn.ca>
	 * @since  Oct. 2015
	 */
	class CacheManager {
		public static $defaultTTL = 120;
		public static $tag = "CacheManager";
		public static $logger;

		public $enabled;

		public function __construct($app) {
			if (!CacheManager::$logger) {
				CacheManager::$logger = new Logger($app->getLogFolder(), null, ['filename' => 'cache']);
				CacheManager::$logger->setTag(CacheManager::$tag);
			}
			
			$this->context = $app;
			$this->enabled = $app->getConfig('cache.enabled', true);
		}

		public function clear($which = "") {
			CacheManager::$logger->info("clearing\t>\t{$which}");
			return apc_clear_cache($which);
		}

		public function cache($thing, $override = true, $ttl = null) {
			$key = $this->getKeyFor($thing);
			$ttl = $ttl ?: self::$defaultTTL;

			CacheManager::$logger->info("store\t>\t{$key} \tttl: {$ttl}");

			if ($override || (!$override && !$this->isCached($thing))) {
				apcu_store($key, $thing, $ttl);
			}

			return $this;
		}

		public function invalidate($thing) {
			$key = $this->getKeyFor($thing);
			CacheManager::$logger->info("invalidate\t>\t{$key}");

			apcu_delete($key);
		}

		public function isCached($thing) {
			if (!$this->enabled) return false;

			$key = $this->getKeyFor($thing);

			return apc_exists($key);
		}

		public function load($thing) {
			$key = $this->getKeyFor($thing);	
			
			$value = apc_fetch($key, $success);

			if ($success) return $value;
			else return null;
		}

		public function getKeyFor($thing) {
			if (is_string($thing)) return $thing;
			$val = strtolower(substr(strrchr(get_class($thing), '\\'), 1));
			$val = str_replace(['\/','\\','-','[',']','(',')','{','}',' '], '', $val);

			if ($thing instanceof Entity && !is_null($thing->getId())) {
				return implode('_', ['entity', $val, $thing->getId()]);
			} else {
				//fix me pls
				$component = array_keys($this->context->getComponents(true))[0];
				return implode('_', ['entity', $component, $val]);
			}
		}
	}