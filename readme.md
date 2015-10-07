# SEBASTIAN

## What am I?
I'm a dinky little side-project PHP framework written because I'm too lazy to use other free, easy to access, open source frameworks.

I spun out of a work term topic paper, and ended up a pretty decent, in my opinion, solution.

## to use

```php
	$envConfig = yaml_parse_file(__DIR__ . "/../config/env.yaml");
	$config = yaml_parse_file(__DIR__ . "/../config/config_{$envConfig['environment']}.yaml");
	$ormConfig = yaml_parse_file(__DIR__ . "/../config/orm_config.yaml");

	$app = new Kernel($config, $orgConfig);
	$request = Request::fromGlobals();
	$response = $app->handleRequest($request);
	$response->send();
```

easy mode

