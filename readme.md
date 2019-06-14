# WP Migrations

WP Migrations can be used by WordPress plugins to handle specific actions during plugin upgrades or downgrades, 
in a structured and more readable approach, to specify processes that will be executed at which plugin version, 
by using `up()` and `down()` method on `WP_Migration` interface.

Check out the [example plugin](https://github.com/Adhik/wp-migrations-example) on how to use it.

## Usage

### `WP_Migrations`

Create a class that extends `WP_Migrations` and override some methods and attribute:

#### `protected function get_migrations()`

Return with an array of migration files, grouped by plugin version in ascending order.

#### `protected $migration_dir`

**Optional**. The variable is set to `migrations` directory under the same path of `WP_Migrations` file by default. 
Change the value accordingly if you want to have different location for the migration files.

#### `protected function get_current_plugin_version()`

Specify with the current plugin's version number.

#### `protected function get_saved_plugin_version()`

It is a good tips to save your plugin version number on database upon a successful installation, for example by using 
`add_option()` or `update_option`. Then, you can use the saved value as return value here. *The saved* plugin's 
version number on database will be compared against *the current* version number during migration process to check 
whether any upgrade or rollback action is needed.

#### `protected function post_migrate()`

**Optional**, will be called after the migration process is done.


For example, the class would be like this:

```php
class MyPlugin_Migrations extends WP_Migrations {

    protected function get_migrations() {
        return array(
            '1.0.0' => array(
                'MyPlugin_Initial_Migrate',
            ),
            '1.1.0' => array(
                'MyPlugin_Migrate_110',
                'MyPlugin_Migrate_111',
            ),
        );
    }
 
    protected function get_current_plugin_version() {
        return MYPLUGIN_VERSION;
    }
    
    protected function get_saved_plugin_version() {
        return get_option('myplugin_version', '0.0.0');
    }
    
    protected function post_migrate() {
        update_option('myplugin_version', MYPLUGIN_VERSION);
    }
}
```

assuming `MYPLUGIN_VERSION` is a constant that is used to hold the value for your plugin version number and 
`myplugin_version` as the option key, which is used to save the version number upon a successful install.

The migration files array (as specified on `get_migrations()` method) must be placed under the same directory,
From the sample above, `MyPlugin_Initial_Migrate.php`, `MyPlugin_Migrate_110.php`, and `MyPlugin_Migrate_111.php` 
files can be found in `migrations` directory by default.

### `WP_Migration`

Each migration class must implement `WP_Migration` interface and override two methods:

#### `public function up()`

Can be used to execute tasks on plugin upgrades, for example: to create or update tables, indexes, update plugin 
options, insert required data specific to the new version, etc.


#### `public function down()`

Specify rollback or revert action against `up()` here. For example: dropping tables, indexes, options, removing 
specific data that was created on `up()`, etc.

## Known Issues

`WP_Migrations` compares plugin's version number to perform commit or rollback, so server's caches implementation 
(such as PHP OPCache) might affect the result. Clearing caches before plugin's upgrade/downgrade is recommended.

## License

[GPLv2](http://www.gnu.org/licenses/gpl-2.0.html)