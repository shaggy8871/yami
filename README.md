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
2. Put your yaml file in the root of the folder
3. Create your migration files:

```php
<?php

use Yami\Migration\AbstractMigration;

class BasicExample extends AbstractMigration
{
    public function up()
    {
        $rootNode = $this->get('.');
        $rootNode->add(['yami' => 'value']);

        $this->save();
    }
}
```

This will add a node to the root of the YAML file as follows:

```yaml
yami: value
```

Save the file using the filename &lt;date&gt;_basic_example.php where the file name must match the class name.

Then run `vendor/bin/yami migrate --verify` to verify the output, and `vendor/bin/yami migrate` to commit and save the migration.
