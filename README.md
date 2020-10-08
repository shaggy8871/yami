# Yami

[![Build Status](https://travis-ci.org/shaggy8871/yami.svg?branch=master)](https://travis-ci.org/shaggy8871/yami)

Yami is a PHP migration tool for YAML files. It is ideal for systems that maintain YAML files outside of source code repositories, such as when used for credentials or secrets management (`.env` files). Some key benefits include:

- It maintains a history of changes to one or more YAML files, and the ability to roll back in batches or to a particular point in time.
- It focuses on the structure of the YAML file rather than the data, and allows the data to be injected from multiple sources including a Secrets Manager or environment variables during migration.
- It's ideal for CI systems and can be implemented as part of a larger workflow of updates during testing or deployment.
- Writing migrations is easy and doesn't require an additional set of skills or use of command line utilities.

## Table of Contents

1. [Installation](#installation)
2. [Getting Started](#getting-started)
3. [Creating Migrations](#creating-migrations)
    - [Migration Methods](#migration-methods)
        - [get()](#this-get)
        - [exists()](#this-exists)
        - [save()](#this-save)
    - [Node Methods](#node-methods)
        - [add()](#node-add)
        - [set()](#node-set)
        - [remove()](#node-remove)
        - [has()](#node-has)
        - [containsArray()](#node-containsArray)
        - [containsType()](#node-containsType)
        - [dump()](#node-dump)
4. [Running Migrations](#running-migrations)
5. [Rolling Back](#rolling-back)
    - [Steps](#steps)
    - [Targets](#targets)
6. [Configuration Options](#configuration-options)
    - [Custom Config Files](#custom-config-files)
    - [YAML Adapters](#yaml-adapters)
7. [Securing Data](#securing-data)
    - [Secrets](#secrets)
    - [Masking Values](#masking-values)
8. [Command Line Options](#command-line-options)

## Installation:

In composer.json:
```yaml
{
  "repositories": [
    {
      "type": "vcs",
      "url": "https://github.com/shaggy8871/php-diff"
    }
  ],
  "require": {
    "shaggy8871/yami": "dev-master"
  },
  "minimum-stability": "dev"
}
```

Then run:
```
composer install
```

## Getting Started

Create a configuration file by running `vendor/bin/yami config` from your command line. A basic configuration script will be created as follows.

```php
<?php

return [
    'environments' => [
        'default' => [
            'yaml' => [
                'adapter' => 'file',
                'file' => 'default.yaml',
            ],
            'migrations' => [
                'path' => './migrations',
            ],
        ],
    ],
    'save' => [
        'indentation' => 2,
    ]
];
```

The configuration file supports one or more environments. An environment requires a minimum of two keys:

- `yaml.adapter` - the type of adapter to use. See [YAML Adapters](#yaml-adapters) below for further options.
- `yaml.file` - the path to the YAML file, relative to root of your Yami install.
- `migrations.path` - the path to the `migrations` directory, relative to root of your Yami install.

Further configuration options are outlined below.

If you don't have a directory for your migrations, you can create one as follows:
```bash
mkdir migrations
```

## Creating Migrations

To create a migration script, run:
```
vendor/bin/yami create --migration=TestMigration -e default
```

The `migration` parameter is mandatory and should be a CamelCase style name to describe your migration. Yami will create a migration script in the `path` specified by the environment. The `-e default` parameter specifies which environment's settings to use. If an environment is not specified, the first one found in your configuration script is used as default.

A basic migration script looks as follows:

```php
<?php

use Yami\Migration\AbstractMigration;

class TestMigration extends AbstractMigration
{

    public function up()
    {
        $node = $this->get('.');
        $node->add(['foo' => 'bar']);

        $this->save();
    }

    public function down()
    {
        $node = $this->get('.');
        $node->remove('foo');

        $this->save();
    }

}
```

The `up()` method is called when migrating. The `down()` method is called when rolling back.

Each migration follows a simple set of steps:

1. Find the base node.
2. Make changes to the contents within.

Once you have completed your manipulation of the node, call `$this->save()` to save your changes back to the file. If the `--dry-run` flag is set, changes will be output to the screen, but no changes will be written to the file.

## Migration methods

### *$this->get()*

The `$this->get()` method expects a search string that follows primitive `jq`-style formatting. The most basic search is `.` which represents the root of the YAML file. Calling `$this->get('.')` will allow you to manipulate the entire YAML file. This search should only be used when adding a node to the bottom of the YAML file.

Searching for a specific element within the root can be done by specifying the node name immediately after the `'.'`. If your YAML file looks as follows:

```yaml
foo:
  bar: baz
```

you can access and manipulate the contents of `foo` by calling `$this->get('.foo')`. Similarly you can access the `foo` > `bar` node by calling `$this->get('.foo.bar')`.

To search for specific elements within an array, add the array index in `[]` brackets. For instance, to access the first element of an array called `foo` > `bar`, call `$this->get('.foo.bar.[0])`.

Once you have a node, you can perform one or more operations on it.

### *$this->exists()*

Use `$this->exists()` to test whether a node exists in the tree, before attempting to perform operations on it. This function accepts the same parameters as `$this->get()`. If you attempt to perform node operations on a non-existent tree element, it will fail.

### *$this->save()*

Once you've made all the changes to the node you require, call `$this->save()` to save your changes back to the tree. If any step in a migration fails, no changes will be written.

> There's no need to call `$this->save()` if you plan to manipulate another node. Simple call `$this->get()` and the previous node will be saved back to the tree automatically.

## Node methods

### *$node->add()*

The `add()` method allows you to add to an existing node, or the root of the YAML file. You cannot `add()` if you try to append a value to a scalar value. So using the example YAML above, this will work:

```php
$node = $this->get('.');
$node->add(['foo' => 'bar']);
```

But this won't:

```php
$node = $this->get('.foo.bar');
$node->add(['new' => 'value']);
```

This is because `foo` > `bar` contains a scalar string value of `baz` that cannot be turned into an array.

The `add()` method supports adding of either a scalar value, or an array of key/value pairs. If a scalar value is supplied, it will be added as an array element. If a key/value pair is added, it will be added as a map element.

### *$node->set()*

The `set()` method allows you to overwrite the entire contents of the node. Be careful with this as it could remove entire trees from within a node. The `set()` method should not be used on the root `.` node as it will replace the entire YAML file with whatever value is specified.

### *$node->remove()*

The `remove()` method will remove one or more maps or elements from the node. You can pass a single key as a string, for example `$node->remove('bar')` or an array of keys to remove multiple sub-nodes, for instance `$node->remove(['bar', 'baz'])`. Calling `remove()` on a node with array values will remove the specified values from the array by name.

### *$node->has()*

Returns true if the node contains a sub-node with the name specified. For example `$node->has('foo')`.

### *$node->containsArray()*

Identical to `has()` but also checks if the sub-node is an array. Use as `$node->containsArray('foo')`.

### *$node->containsType()*

Identical to `has()` but also validates the type of scalar content. Usage is `$node->containsType('foo', 'string')`. Valid types includes `integer`, `string`, `float` and `boolean`.

### *$node->dump()*

Dumps the node's contents to `stdout`.

## Running Migrations

To test the migrations without overriding the YAML file, run `vendor/bin/yami migrate --dry-run`.

To commit and save the migration, run `vendor/bin/yami migrate`.

Caution: If you don't specify an environment using the `--env=<environment>` or `-e <environment>` parameter, the default environment will be used which may not be what is expected.

## Rolling Back

If you need to roll back migrations, run `vendor/bin/yami rollback`. You may optionally pass in the `--dry-run` parameter to see the outcome without committing changes.

If no additional parameters are specified, Yami will roll back the last batch of changes.

### Steps

To specify a specific number of migrations to roll back, use the `--step=X` or `-s X` parameter. For instance `vendor/bin/yami rollback -s 1` will roll back a single migration only.

### Targets

To roll back to a specific target migration, use the `--target=X` or `-t X` parameter. For instance `vendor/bin/yami rollback -t 2020_04_03_001122_test_migration` will roll back up to and including this migration, but no further.

## Configuration Options

The following configuration options may be added to your config file to customise the behaviour of Yami.

| option             | description | default |
|--------------------|---|---|
| *load*
| - asObject           | See [Yaml::PARSE_OBJECT](https://symfony.com/doc/current/components/yaml.html#object-parsing-and-dumping) | false |
| - asYamlMap          | See [Yaml::PARSE_OBJECT_FOR_MAP](https://symfony.com/doc/current/components/yaml.html#parsing-and-dumping-objects-as-maps) | false |
| *save*
| - indentation        | the number of spaces to indent | 2 |
| - maskValues         | save values as `(masked)` instead of actual value | false |
| - removeEmptyNodes   | remove all empty nodes | true |
| - inlineFromLevel    | how many levels to support before outputting values in JSON object notation | 10 |
| - asObject           | See [Yaml::DUMP_OBJECT](https://symfony.com/doc/current/components/yaml.html#object-parsing-and-dumping) | false |
| - asYamlMap          | See [Yaml::DUMP_OBJECT_AS_MAP](https://symfony.com/doc/current/components/yaml.html#parsing-and-dumping-objects-as-maps) | false |
| - asMultilineLiteral | See [Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK](https://symfony.com/doc/current/components/yaml.html#dumping-multi-line-literal-blocks) | false |
| - base64BinaryData   | See [Yaml::DUMP_BASE64_BINARY_DATA](https://symfony.com/doc/current/components/yaml.html#parsing-and-dumping-of-binary-data) | false |
| - nullAsTilde        | See [Yaml::DUMP_NULL_AS_TILDE](https://symfony.com/doc/current/components/yaml.html#dumping-null-values) | false |
| - withBackup         | create a backup of the YAML before saving | false |
| historyFile          | the file where migration history is stored | ./history.log |

You can also add any of these configuration options within specific environments to customise how each environment behaves. For example:

```php
<?php

return [
    'environments' => [
        /**
         * The development.yaml file may be committed if it's masked
         */
        'development' => [
            'yaml' => [
                'adapter' => 'file',
                'file' => 'development.yaml',
            ],
            'migrations' => [
                'path' => './migrations',
            ],
            'save' => [
                'maskValues' => true,
                'indentation' => 4,
            ]
        ],
        /**
         * The production.yaml file is not masked, so should not be committed
         */
        'production' => [
            'yaml' => [
                'adapter' => 'file',
                'file' => 'production.yaml',
            ],
            'migrations' => [
                'path' => './migrations',
            ],
        ]
    ],
    'save' => [
        'indentation' => 2,
    ]
];
```

Environment specific values will override general configuration values when using that environment.

### Custom Config Files

To support multiple projects, you can set up multiple configuration files. To use a different configuration file to the default, simple pass it in using the `--config=<file>` or `-c <file>` parameter. For example:

```bash
vendor/bin/yami migrate -c ./projects/api/config.php
```

will run migrations using the default environment specified in this configuration file.

### YAML Adapters

By default, Yami will look for your YAML file in the local file system. The adapter `file` requires a corresponding file name pointing to the location of your YAML file on disk.

The following adapters are available natively. You can also configure your own adapter by implementing the `Yami\Yaml\YamlAdapterInterface` interface, and pointing to the fully qualified class name through the `yaml.adapter` parameter.

| adapter            | mandatory parameters | optional parameters |
|--------------------|---|---|
| file | - `file` | None |
| stream | None, the YAML must be passed in via `stdin` and will be output via `stderr`.<br>Example: `yami migrate < in.yaml 2>out.yaml` | None |
| s3 | - `credentials.region`<br>- `s3.bucket`<br>- `s3.key` | - `credentials.profile`<br>- `credentials.version`<br>- `s3.saveACL` (if not specified, will default to `private`) |

## Securing Data

To keep credentials and other sensitive data secure, Yami introduces two complementary features.

### Secrets

Instead of hard coding values into migrations, which may accidentally end up in source code repositories, Yami can look them up while running migrations.

Secrets may be pulled from a third party Secrets Manager, or passed in via environment variables.

Secrets will be validated prior to running migrations, and will fail if the supplied data doesn't match what is expected.

To configure a Secrets Manager, add the following in your config file, within the appropriate environments:

```php
<?php

return [
    'environments' => [
        'default' => [
            'yaml' => [
                'adapter' => 'file',
                'file' => 'default.yaml',
            ],
            'migrations' => [
                'path' => './migrations',
            ],
            'secretsManager' => [
                'adapter' => 'ssm',
                'credentials' => [
                    'profile' => 'default',
                    'region' => 'us-east-1'
                ]
            ]
        ]
    ]
];
```

Currently only `local` (using environment variables) and `ssm` (using AWS SSM) adapters are supported as native. If no Secrets Manager is specified, it will default to `local`. Using SSM requires the installation of the [AWS SDK for PHP](https://github.com/aws/aws-sdk-php).

If you prefer to write your own Secrets Manager class, it must implement the `Yami\Secrets\SecretsManagerInterface` interface, and the fully qualified class name should be passed in via the `secretsManager.adapter` parameter.

```php
<?php

use Yami\Migration\AbstractMigration;

class TestClass extends AbstractMigration
{
    public function up()
    {
        $node = $this->get('.');
        $node->add([
            'foo' => 'bar',
            'access_key_id' => $this->secret('/api/production/s3/access_key_id', [
                'type' => 'string'
            ]),
            'secret_access_key' => $this->secret('/api/production/s3/secret_access_key', [
                'type' => 'string'
            ])
        ]);

        $this->save();
    }
}
```

Secret key names will be normalised to valid environment variables, so `/api/production/s3/secret_key_id` will look for an environment variable called `api_production_s3_SecretKeyId`.

When running the migration, environment variables may be passed in via command line as follows:

```bash
export api_production_s3_AccessKeyId=<value>
export api_production_s3_SecretAccessKey=<value>
vendor/bin/yami migrate
```

### Masking Values

Migrations don't need actual values to run, as they are mainly concerned with the structure of the file. To allow developers to test migrations without exposing sensitive data, Yami allows you to mask values.

A utility has been provided to mask all values within an existing YAML file. Simply call:

```bash
vendor/bin/yami mask
```

You can optionally pass in `-c` and `-e` parameters to customise the config file and environment accordingly.

The original file will be backed up in the same location prior to masking, in case you need to recover the data.

Masked YAML files are safe to commit. Unmasked YAML files should never be committed to a source code repository.

To ensure that changes remain masked even after migrations, use the `maskValues` [configuration option](#configuration-options).

## Command Line Options

| option             | description | default |
|--------------------|---|---|
| *general*
| -c  --config | Specify the config file | ./config.php |
| -d  --dry-run | Test the migration or rollback but don't commit | - |
| -e  --env | Specify the target environment | default |
| -n  --no-ansi | Disable ansi colour coding in output | colours are enabled |
| *create*
| -m  --migration | The name of the migration | - |
| *rollback*
| -s  --step | The number of steps to roll back | - |
| -t  --target | The target migration to roll back to | - |
| *config*
| -p  --project | Where to place the config file | current directory |
