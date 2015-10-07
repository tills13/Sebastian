# SEBASTIAN
that's my last name

#### What am I?
I'm a dinky little side-project PHP framework written because I'm too lazy to use other free, easy to access, open source frameworks.

I spun out of a work term topic paper, and ended up a pretty decent, in my opinion, solution.

#### How do I work?

```php
require_once(__DIR__ . '/../vendor/autoload.php');

$envConfig = yaml_parse_file(__DIR__ . "/../config/env.yaml");
$config = yaml_parse_file(__DIR__ . "/../config/config_{$envConfig['environment']}.yaml");
$ormConfig = yaml_parse_file(__DIR__ . "/../config/orm_config.yaml");

$app = new Kernel($config, $orgConfig);
$request = Request::fromGlobals();
$response = $app->handleRequest($request);
$response->send();
```

easy mode

#### Config

```yaml
#env.yaml

environment: prod
```

```yaml
#config_{$env}.yaml

application:
    name: example_app
    namespace: ExampleApp
    debug: false

database:
    hostname: hostname
    dbname: dbname
    port: 5432
    username: postgres
    password: password
    connection:
        lazy: true
        tagging: true

orm:
    lazy: false # don't know about this, yet

cache:
    enabled: true

services:
	# service name: [optional component namespace:][servicename]
    some_service: Common:SomeService

# components
components:
    Default:
        weight: 0
        requirements: []
        path: /Common

# entities and repos
entity:
	# define the entity's repository and entity class
	# format:
	# 	repository: [optional component namespace:][path to class]
	# entity is optional, though you will have to override methods in your
	# custom repo class as Repository expects objects
    SomeEntity:
        repository: Common:Repository/SomeEntity
        entity: Common:Entity/SomeEntity
```

```yaml
# orm_config.yaml
SomeEntity:
    table: some_entity_table
    keys: [id] # primary keys
    fields:
        id:
            column: id
            type: serial
        item:
            column: item
            type: string
        parent:
            column: parent
            type: int
            relation: parent
            with: id
        owner:
            column: owner
            entity: User
            with: id
            relation: one

# ...
```

