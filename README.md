# Yami

Yami is a PHP migration tool for YAML configuration files.

## Installation:

In composer.json:
```
"require": {
    "shaggy8871/yami": "dev-master"
}
```

Then run:
```
composer install
```

1. Create a folder called `migrations`.
2. Place your YAML file in the root of the folder.
3. Create your configuration by running `vendor/bin/yami config`. Edit the created `config.php` file as necessary to point to your YAML file.
4. Create a sample migration file by running `vendor/bin/yami create --class=TestClass`. It will create a file with name &lt;date&gt;_test_class.php as follows:

```php
<?php

use Yami\Migration\AbstractMigration;

class TestClass extends AbstractMigration
{
    public function up()
    {
        $rootNode = $this->get('.');
        $rootNode->add(['foo' => 'bar']);

        $this->save();
    }
}
```

This will add a node to the root of your YAML file as follows:

```yaml
foo: bar
```

To dry run the migrations without saving, run `vendor/bin/yami migrate --dry-run`.

To commit and save the migration, run `vendor/bin/yami migrate`.
