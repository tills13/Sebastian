# SEBASTIAN
that's my last name

#### What am I?
I'm a dinky little side-project PHP framework written because I'm too lazy to use other free, easy to access, open source frameworks.

I spun out of a work term topic paper, and ended up a pretty decent, in my opinion, solution.

#### How do I work?

```php
require_once(__DIR__ . '/../vendor/autoload.php');

$app = new Kernel("prod"); // or load the env from a config (see env.yaml below)
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
    app_class: ExampleApp
    debug: false

database:
    dbname: dbname
    hostname: hostname
    port: 5432
    username: postgres
    password: password

cache:
    #driver: Sebastian\Core:NullDriver
    driver: Sebastian\Core:APCUDriver
    key_generation_strategy: 
        object: '{class}_{component}_{id}' # defaults
        other: '{hash}' # defaults

service:
    test_service:
        class: 'Common:Service\TestService'
        params: { '@request' } # coming, not quite yet

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
# orm.yaml

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

# the relation definitions are still in flux
# ...
```

