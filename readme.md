# SEBASTIAN
that's my last name

#### What am I?
I'm a dinky little side-project PHP framework written because I'm too lazy to use other free, easy to access, open source frameworks.

I spun out of a work term topic paper, and ended up a pretty decent, in my opinion, solution.

#### How do I work?

```php
require_once('/path/to/autoload.php');

$kernel = new Namespace\Kernel("prod");
$response = $kernel->run();
$response->send();

// this step is optional but can be used to close connections, etc
$kernel->shutdown($response);
```

Your `Namespace\Kernel` only needs to be of the form 

```php
class Kernel extends Sebastian\Kernel {
    public function __construct(string $environment) {
        parent::__construct($environment);

        $this->registerComponents([
            ...
        ]);
        
        $this->boot();
    }
}
```

After calling `boot`, you can interact with registered components, adding functionality, etc. 

easy mode

#### Example Config

```yaml
#config_{$environment}.yaml

application:
    name: example_app
    namespace: ExampleApp
    # optional, if specified, your Application class should reside in
    # the root of your app's namespace and extend Sebastian\Application 
    app_class: ExampleApp 
    debug: false

database:
    driver: Sebastian\Core:PostgresPDO
    hostname: hostname
    dbname: dbname
    port: 5432
    username: postgres
    password: password
    connection:
        tagging: true # if you write your own PDO extension, you'll have to implement this
        persistent: false

cache:
    enabled: true
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

