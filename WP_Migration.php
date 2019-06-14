<?php
/**
 * Main interface to perform specific changes on plugin's upgrade or downgrade.
 *
 * @author Adhi Kerta <adhi.kerta@balabali.com>
 * @since 1.0.0
 */

if ( ! interface_exists( 'WP_Migration' ) ) {

	/**
	 * Interface WP_Migration provides incremental and historical actions.
	 * It is recommended to use `$wpdb->query()` instead of `dbDelta()` for
	 * database operations on the following `up()` and `down()` methods.
	 *
	 * @since 1.0.0
	 */
	interface WP_Migration {

		/**
		 * Execute the migration, for example to create or update tables, update plugin options,
		 * insert required data, etc.
		 *
		 * @return void
		 */
		public function up();

		/**
		 * Perform rollback or revert action against up().
		 *
		 * @return void
		 */
		public function down();

	}

}