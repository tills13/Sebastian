<?php
	// maps namespaces to dirs in the project folder
	// eg 'MyApp' => 'app' -> /project-dir/app/[now in MyApp namespace]
	$map = [];

	$autoLoader = function($className) use ($map, $overrides) {
		if (isset($overrides[$className])) {
			$className = $overrides[$className];
		}

		$className = explode('\\', $className);
		$rootNamespace = $className[0];

		$className = implode('/', $className);

		if (isset($map[$rootNamespace])) {
			$path = __DIR__ . "/../{$map[$rootNamespace]}/{$className}.php";
		} else {
			$path = __DIR__ . "/{$className}.php";
		}

		require_once($path);
	};

	spl_autoload_register($autoLoader);