<?php
/**
 * WP_Migrations: a WordPress migration utility class.
 *
 * @author Adhi Kerta <adhi.kerta@balabali.com>
 * @version 1.0.1
 * @since 1.0.0
 */

if ( ! class_exists( 'WP_Migrations' ) ) {

	/**
	 * WP_Migrations provides plugin's incremental control on plugin's upgrade or downgrade.
	 *
	 * It will call the following methods on registered classes that implement WP_Migration interface,
	 * and listed as array members on `get_migrations()` method. The methods are:
	 * `up()` on newer plugin version or upgrades,
	 * `down()` on lower plugin version or downgrades.
	 *
	 * @see WP_Migration
	 * @version 1.0.1
	 * @since 1.0.0
	 */
	abstract class WP_Migrations {

		/**
		 * Default directory for the migration files will be `migrations/`
		 *
		 * @since 1.0.0
		 * @var string
		 */
		protected $migration_dir = 'migrations/';

		/**
		 * Return with incremental migration files registration, grouped by their plugin version.
		 *
		 * Register them using the following conditions:
		 *
		 * Each migration file and class inside it must have identical name, e.g.
		 * 'My_Migrate_Users.php' and 'My_Migrate_Users' as class name.
		 *
		 * All migration files must be placed under `migration_dir` with each class
		 * implements WP_Migration interface.
		 *
		 * Group each version and migration classes in the following arrays in
		 * ascending order.
		 *
		 * For example:
		 *
		 * ```
		 * return array(
		 *     '1.0.0' => array(
		 *         'My_Migrate_Users',
		 *     ),
		 *     '1.1.0' => array(
		 *         'My_Migrate_Stuff_1',
		 *         'My_Migrate_Stuff_2',
		 *         'My_Migrate_Stuff_3',
		 *     ),
		 *     '2.0.0' => array(
		 *         'My_Migrate_Users_Again',
		 *         'My_Migrate_Stuff_Again',
		 *    ),
		 * );
		 * ```
		 *
		 * @return array
		 *
		 */
		protected abstract function get_migrations();

		/**
		 * Return with your plugin's current version.
		 * For example:
		 *
		 * ```
		 * return YOUR_PLUGIN_VERSION_CONSTANT;
		 * ```
		 *
		 * where `YOUR_PLUGIN_VERSION_CONSTANT` is defined already before somewhere on your plugin file.
		 *
		 * @return string
		 */
		protected abstract function get_current_plugin_version();

		/**
		 * Override this to inform WP_Migrations about your plugin version on database.
		 * Use '0.0.0' as fallback that will be useful for fresh install.
		 * For example:
		 *
		 * ```
		 * return get_option( YOUR_PLUGIN_VERSION_CONSTANT, '0.0.0' );
		 * ```
		 *
		 * @return string
		 */
		protected abstract function get_saved_plugin_version();

		/**
		 * (Optional) callback after migrate() is done.
		 */
		protected function post_migrate() {
		}

		/**
		 * Perform migrations. Depends on plugin version number comparison,
		 * it can be updates or rollbacks.
		 *
		 * @since 1.0.0
		 */
		public function migrate() {
			// '0.0.0' for fresh install
			$saved_version = $this->get_saved_plugin_version();

			// curent plugin version
			$current_version = $this->get_current_plugin_version();

			if ( $saved_version === $current_version ) {
				// no need to proceed, right?
				return;
			}

			// get migrations version and files
			$migrations = $this->get_migrations();

			// compares the plugin version as specified on the plugin file
			// with '$current_version' on database if found
			if ( version_compare( $saved_version, $current_version, '<=' ) ) {

				// this is a fresh install or upgrade so we do incremental migration
				foreach ( $migrations as $version => $classes ) {

					// make sure to stop migrate until $current_version
					if ( version_compare( $version, $current_version, '<=' ) &&
					     version_compare( $version, $saved_version, '>' ) ) {

						foreach ( $classes as $class ) {
							// do commit..
							include_once $this->migration_dir . $class . '.php';
							if ( class_exists( $class ) ) {
								$migrate_class = new $class();
								$migrate_class->up();
							}
						}

					}

				}

			} else {
				// do rollback
				$this->rollback( $saved_version, $current_version );
			}

			// callback after migration is done
			$this->post_migrate();

		} // end of migrate()

		/**
		 * Perform rollbacks by calling `down()` on migration classes from $saved_version until $current_version
		 * in descending order, as specified on `get_migrations()` array.
		 *
		 * If both parameters are null, `down()` will be called on all migration files.
		 * This can be used during un-installation process, e.g. on `unistall.php`.
		 *
		 * @param null|string $saved_version The plugin's version number that we can get from `get_option()`.
		 *                                   if $saved_version is null, rollback will start at the last array position
		 *                                   of `get_migrations()`.
		 *
		 * @param null|string $current_version The plugin's current version from plugin's main file or constant.
		 *                                     If $current_version is null, rollback will be performed to the
		 *                                     first array element of `get_migrations()`.
		 *
		 * @since 1.0.1
		 *
		 */
		public function rollback( $saved_version = null, $current_version = null ) {
			$migrations = $this->get_migrations();

			if ( $saved_version === null ) {
				end( $migrations );
				$saved_version = key( $migrations );
			}

			if( $current_version === null ) {
				$current_version = '0.0.0';
			}

			for ( end( $migrations ); ( $version = key( $migrations ) ) !== null; prev( $migrations ) ) {

				// execute migration files with version number that are bigger than $current_version,
				// and less than or equals $saved_version
				if ( version_compare( $version, $current_version, '>' ) &&
				     version_compare( $version, $saved_version, '<=' ) ) {

					$classes = current( $migrations );

					// loop from last array element and push to queue using 'rollback' as action
					for ( end( $classes ); key( $classes ) !== null; prev( $classes ) ) {
						$class = current( $classes );

						// do rollback..
						include_once $this->migration_dir . $class . '.php';
						if ( class_exists( $class ) ) {
							$migrate_class = new $class();
							$migrate_class->down();
						}
					}
				}

			}
		}

	}

}