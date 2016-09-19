# Stash Queries

Stash Queries is a PHP SQL library providing an easy, eloquent, and fluent yet expressive way to select, create, update, and delete SQL records. The layer fluency provides a direct way to interact with SQL using actual SQL files.

This library is originally part of Skyfire's PHP framework database layer known as *DB* service.

## Requirements

- PHP >=5.3.6+
- PDO_MYSQL PHP extensions

## Code Examples

```php
// setting the DB display encoding type (if needed)
FixCollation::charset('utf-8', FixCollation::TEXT_HTML);

// setting Database credentials
DB::define('stash_dir',   getcwd());
DB::define('host',       'localhost');
DB::define('dbname',     'test_db1');
DB::define('dbuser',     'root');
DB::define('dbpassword', '');


// SQL select query (with prepare variables)
$prepare = array
(
    'label' => 'test'
);
$data = DB::select('get.HomeTextByLabel')->prepare($prepare);
var_dump($data);


// SQL simple select query
$data = DB::select('get.AllHomeTextData')->execute();
var_dump($data);


// raw SQL query (with prepare variables)
$data = DB::query('SELECT * FROM test WHERE data IS NOT NULL AND id > :count AND data != :text', array
(
    ':id'   => 10,
    ':text' => 'test'
))->execute();
var_dump($data);


// displays the prepare update statement in plain text (ideal for debugging queries)
$query = DB::update('PostfromTestById')->text($prepare);
echo $query;
```

## Injections:

To assign dynamic variables outside queries without binding (secured injections) such as table names and fields which are not possible by PDO binding:
```php
$data = DB::select('get.fieldData.byId')->inject(array
(
    'field' => $data->field,
    'table' => $table_name
))->prepare(array('id' => (int) $record->id));
```

## Persistent Connections:

To have persistent connections for all queries, this can be done by defining it in the DB configurations - as such:
```php
DB::define('persistent', TRUE);
// DB::define('persistent', 'yes');
```
Both examples above work perfectly, only TRUE or 'yes' are allowed.

## Installation:
Injecting the code mamually, you only have to include 'StashQueries.php' as such:
```php
require_once 'DB/StashQueries.php';
```
Also, externally through composer by adding 'SkyfirePHP/DB' to composer.json:
```json
{
    "require": {
        "skyfirephp/db": "dev-master"
    }
}
```

## License

Stash Queries is licensed under the [MIT License](http://opensource.org/licenses/MIT).

Copyright 2015-2016 [Travis van der Font](http://travisfont.com)
