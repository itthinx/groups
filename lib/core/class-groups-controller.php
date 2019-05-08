<?php
/**
 * class-groups-controller.php
 *
 * Copyright (c) "kento" Karim Rahimpur www.itthinx.com
 *
 * This code is released under the GNU General Public License.
 * See COPYRIGHT.txt and LICENSE.txt.
 *
 * This code is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This header and all notices must be kept intact.
 *
 * @author Karim Rahimpur
 * @package groups
 * @since groups 1.0.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin controller
 */
class Groups_Controller {

	/**
	 * Version 2.0.0 number
	 * @var string
	 */
	const GROUPS_200 = '2.0.0';

	/**
	 * Cache-safe switching in case any multi-site hiccups might occur.
	 *
	 * Clears the cache after switching to the given blog to avoid using
	 * another blog's cached values.
	 *
	 * Some implementations don't have wp_cache_switch_to_blog() nor the deprecated
	 * wp_cache_reset(), e.g. WP Engine's object-cache.php which has wp_cache_flush().
	 *
	 * See  wp_cache_reset() in wp-includes/cache.php
	 * @see wp_cache_switch_to_blog()
	 * @see wp_cache_flush()
	 * @see wp_cache_reset()
	 * @link http://core.trac.wordpress.org/ticket/14941
	 *
	 * @param int $blog_id
	 */
	public static function switch_to_blog( $blog_id ) {
		switch_to_blog( $blog_id );
		if ( function_exists( 'wp_cache_switch_to_blog' ) ) {
			wp_cache_switch_to_blog( $blog_id ); // introduced in WP 3.5.0
		} else if ( function_exists( 'wp_cache_flush' ) ) {
			wp_cache_flush();
		} else if ( function_exists( 'wp_cache_reset' ) ) {
			wp_cache_reset(); // deprecated in WP 3.5.0
		}
	}

	/**
	 * Switch back to previous blog.
	 */
	public static function restore_current_blog() {
		restore_current_blog();
	}

	/**
	 * Boot the plugin.
	 * @see Groups_Registered::wpmu_new_blog()
	 */
	public static function boot() {
		register_activation_hook( GROUPS_FILE, array( __CLASS__, 'activate' ) );
		register_deactivation_hook( GROUPS_FILE, array( __CLASS__, 'deactivate' ) );
		add_action( 'init', array( __CLASS__, 'init' ) );
		add_filter( 'load_textdomain_mofile', array( __CLASS__, 'load_textdomain_mofile' ), 10, 2 );
		// priority 9 because it needs to be called before Groups_Registered's
		// wpmu_new_blog kicks in
		add_action( 'wpmu_new_blog', array( __CLASS__, 'wpmu_new_blog' ), 9, 2 );
		add_action( 'delete_blog', array( __CLASS__, 'delete_blog' ), 10, 2 );
	}

	/**
	 * Run activation for a newly created blog in a multisite environment.
	 * 
	 * @param int $blog_id
	 */
	public static function wpmu_new_blog( $blog_id, $user_id ) {
		if ( is_multisite() ) {
			$active_sitewide_plugins = get_site_option( 'active_sitewide_plugins', array() );
			if ( key_exists( 'groups/groups.php', $active_sitewide_plugins ) ) {
				self::switch_to_blog( $blog_id );
				self::setup();
				self::restore_current_blog();
			}
		}
	}

	/**
	 * Run deactivation for a blog that is about to be deleted in a multisite
	 * environment.
	 * 
	 * @param int $blog_id
	 */
	public static function delete_blog( $blog_id, $drop = false ) {
		if ( is_multisite() ) {
			$active_sitewide_plugins = get_site_option( 'active_sitewide_plugins', array() );
			if ( key_exists( 'groups/groups.php', $active_sitewide_plugins ) ) {
				self::switch_to_blog( $blog_id );
				self::cleanup( $drop );
				self::restore_current_blog();
			}
		}
	}

	/**
	 * Initialize.
	 * Loads the plugin's translations.
	 * Invokes version check.
	 */
	public static function init() {

		unload_textdomain( 'groups' );

		// Load our current translations first ...
		$mofile = self::get_mofile();
		load_textdomain( 'groups', $mofile );

		// ... otherwise load_plugin_textdomain will simply get those in WP's languages
		// and we won't have our up-to-date translations.
		//load_plugin_textdomain( 'groups', null, 'groups/languages' );
		self::version_check();

		// load the notice class
		if ( is_admin() ) {
			if ( current_user_can( 'activate_plugins' ) ) { // important: after init hook
				require_once GROUPS_ADMIN_LIB . '/class-groups-admin-notice.php';
			}
		}
	}

	/**
	 * Builds the mofile string for our own translations.
	 * @return string mofile
	 */
	private static function get_mofile() {
		$locale = get_locale();
		if ( function_exists( 'get_user_locale' ) ) {
			$locale = get_user_locale();
		}
		$locale = apply_filters( 'plugin_locale', $locale, 'groups' );
		$mofile = GROUPS_CORE_DIR . '/languages/groups-' . $locale . '.mo';
		return $mofile;
	}

	/**
	 * Makes sure that our own translation file is loaded first.
	 * 
	 * @param string $mofile
	 * @param string $domain
	 * @return string mofile
	 */
	public static function load_textdomain_mofile( $mofile, $domain ) {
		$own_mofile = self::get_mofile();
		if ( $domain == 'groups' ) {
			if ( $own_mofile != $mofile ) {
				if ( !is_textdomain_loaded( $domain ) ) {
					if ( is_readable( $own_mofile ) ) {
						$mofile = $own_mofile;
					}
				}
			}
		}
		return $mofile;
	}

	/**
	 * Plugin activation.
	 * @param boolean $network_wide
	 */
	public static function activate( $network_wide = false ) {
		$sem_id = self::sem_get( self::get_sem_key() );
		if ( ( $sem_id === false ) || self::sem_acquire( $sem_id ) ) {
			if ( is_multisite() && $network_wide ) {
				$blog_ids = Groups_Utility::get_blogs();
				foreach ( $blog_ids as $blog_id ) {
					self::switch_to_blog( $blog_id );
					self::setup();
					self::restore_current_blog();
				}
			} else {
				self::setup();
				set_transient( 'groups_plugin_activated', true, 60 );
			}
			if ( $sem_id !== false ) {
				self::sem_release( $sem_id );
				self::sem_remove( $sem_id );
			}
		}
	}

	/**
	 * Plugin activation work.
	 */
	private static function setup() {
		global $wpdb, $wp_roles;

		// create WP capabilities
		Groups_Controller::set_default_capabilities();

		$charset_collate = '';
		if ( ! empty( $wpdb->charset ) ) {
			$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
		}
		if ( ! empty( $wpdb->collate ) ) {
			$charset_collate .= " COLLATE $wpdb->collate";
		}

		// create tables
		$group_table = _groups_get_tablename( 'group' );
		if ( $wpdb->get_var( "SHOW TABLES LIKE '$group_table'" ) != $group_table ) {
			$queries[] = "CREATE TABLE IF NOT EXISTS $group_table (
				group_id     BIGINT(20) UNSIGNED NOT NULL auto_increment,
				parent_id    BIGINT(20) DEFAULT NULL,
				creator_id   BIGINT(20) DEFAULT NULL,
				datetime     DATETIME DEFAULT NULL,
				name         VARCHAR(100) NOT NULL,
				description  LONGTEXT DEFAULT NULL,
				PRIMARY KEY  (group_id),
				UNIQUE INDEX group_n (name)
			) $charset_collate;";
		}
		$capability_table = _groups_get_tablename( 'capability' );
		if ( $wpdb->get_var( "SHOW TABLES LIKE '$capability_table'" ) != $capability_table ) {
			$queries[] = "CREATE TABLE IF NOT EXISTS $capability_table (
				capability_id BIGINT(20) UNSIGNED NOT NULL auto_increment,
				capability    VARCHAR(255) NOT NULL,
				class         VARCHAR(255) DEFAULT NULL,
				object        VARCHAR(255) DEFAULT NULL,
				name          VARCHAR(100) DEFAULT NULL,
				description   LONGTEXT DEFAULT NULL,
				PRIMARY KEY   (capability_id),
				UNIQUE INDEX  capability (capability(100)),
				INDEX         capability_kco (capability(20),class(20),object(20))
			) $charset_collate;";
		}
		$user_group_table = _groups_get_tablename( 'user_group' );
		if ( $wpdb->get_var( "SHOW TABLES LIKE '$user_group_table'" ) != $user_group_table ) {
			$queries[] = "CREATE TABLE IF NOT EXISTS $user_group_table (
				user_id     bigint(20) unsigned NOT NULL,
				group_id    bigint(20) unsigned NOT NULL,
				PRIMARY KEY (user_id, group_id),
				INDEX       user_group_gu (group_id,user_id)
			) $charset_collate;";
		}
		$user_capability_table = _groups_get_tablename( 'user_capability' );
		if ( $wpdb->get_var( "SHOW TABLES LIKE '$user_capability_table'" ) != $user_capability_table ) {
			$queries[] = "CREATE TABLE IF NOT EXISTS $user_capability_table (
				user_id	      bigint(20) unsigned NOT NULL,
				capability_id bigint(20) unsigned NOT NULL,
				PRIMARY KEY   (user_id, capability_id),
				INDEX         user_capability_cu (capability_id,user_id)
			) $charset_collate;";
		}
		$group_capability_table = _groups_get_tablename( 'group_capability' );
		if ( $wpdb->get_var( "SHOW TABLES LIKE '$group_capability_table'" ) != $group_capability_table ) {
			$queries[] = "CREATE TABLE IF NOT EXISTS $group_capability_table (
				group_id      bigint(20) unsigned NOT NULL,
				capability_id bigint(20) unsigned NOT NULL,
				PRIMARY KEY   (group_id, capability_id),
				INDEX         group_capability_cg (capability_id,group_id)
			) $charset_collate;";
		}
		if ( !empty( $queries ) ) {
			// For the record ... (and https://core.trac.wordpress.org/ticket/12773 should not be closed)
			// dbDelta() fails to handle queries "CREATE TABLE IF NOT EXISTS ..."
			// (a regex results in "IF" used as array index holding only last query to create table).
			//require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
			//dbDelta( $queries );
			foreach( $queries as $query ) {
				$wpdb->query( $query );
			}
		}
		// needs to be called to create its capabilities
		Groups_Post_Access::activate();
		// same thing to created groups for registered users
		Groups_Registered::activate();
		// add WordPress capabilities
		Groups_WordPress::activate();
		// ... end of plugin activation work.
	}

	/**
	 * Checks current version and triggers update if needed.
	 */
	public static function version_check() {
		global $groups_version, $groups_admin_messages;
		$previous_version = get_option( 'groups_plugin_version', null );
		$groups_version = GROUPS_CORE_VERSION;
		// auto-enable legacy support on upgrade from Groups previous to 2.0.0
		if ( $previous_version ) {
			if ( version_compare( $previous_version, self::GROUPS_200 ) < 0 ) {
				if ( Groups_Options::get_option( GROUPS_LEGACY_ENABLE ) === null ) {
					Groups_Options::update_option( GROUPS_LEGACY_ENABLE, true );
				}
				set_transient( 'groups_plugin_updated_legacy', true, 60 );
			}
		}

		// disable legacy support on new installations
		if ( Groups_Options::get_option( GROUPS_LEGACY_ENABLE ) === null ) {
			Groups_Options::update_option( GROUPS_LEGACY_ENABLE, false );
		}

		// run update procedure if newer version is installed
		if ( version_compare( $previous_version, $groups_version ) < 0 ) {
			if ( self::update( $previous_version ) ) {
				if ( update_option( 'groups_plugin_version', $groups_version ) ) {
					set_transient( 'groups_plugin_updated', true, 60 );
				}
			} else {
				$groups_admin_messages[] = '<div class="error">Updating Groups plugin core <em>failed</em>.</div>';
			}
		}
	}

	/**
	 * Update maintenance.
	 */
	public static function update( $previous_version ) {

		global $wpdb, $groups_admin_messages;

		$result = true;

		$sem_id = self::sem_get( self::get_sem_key() );
		if ( ( $sem_id === false ) || self::sem_acquire( $sem_id ) ) {
			$queries = array();
			switch ( $previous_version ) {
				case '1.0.0' :
					$capability_table = _groups_get_tablename( 'capability' );
					if ( $wpdb->get_var( "SHOW TABLES LIKE '$capability_table'" ) == $capability_table ) {
						// increase column sizes
						$queries[] = "ALTER TABLE $capability_table MODIFY capability VARCHAR(255) UNIQUE NOT NULL;";
						$queries[] = "ALTER TABLE $capability_table MODIFY class VARCHAR(255) DEFAULT NULL;";
						$queries[] = "ALTER TABLE $capability_table MODIFY object VARCHAR(255) DEFAULT NULL;";
						// correct capabilities
						$queries[] = "UPDATE $capability_table SET capability='delete_published_pages' WHERE capability='delete_published_pag';";
						$queries[] = "UPDATE $capability_table SET capability='delete_published_posts' WHERE capability='delete_published_pos';";
						// fix hideously big index
						$queries[] = "ALTER TABLE $capability_table DROP INDEX capability_kco;";
						$queries[] = "ALTER TABLE $capability_table ADD INDEX capability_kco (capability(20),class(20),object(20));";
					}
					break;
				case '1.0.0-beta-3d' :
					$capability_table = _groups_get_tablename( 'capability' );
					if ( $wpdb->get_var( "SHOW TABLES LIKE '$capability_table'" ) == $capability_table ) {
						// increase column sizes
						$queries[] = "ALTER TABLE $capability_table MODIFY capability VARCHAR(255) UNIQUE NOT NULL;";
						$queries[] = "ALTER TABLE $capability_table MODIFY class VARCHAR(255) DEFAULT NULL;";
						$queries[] = "ALTER TABLE $capability_table MODIFY object VARCHAR(255) DEFAULT NULL;";
						// correct capabilities
						$queries[] = "UPDATE $capability_table SET capability='delete_published_pages' WHERE capability='delete_published_pag';";
						$queries[] = "UPDATE $capability_table SET capability='delete_published_posts' WHERE capability='delete_published_pos';";
					}
					break;
				default :
					if ( !empty( $previous_version ) ) {
						if ( version_compare( $previous_version, '1.1.6' ) < 0 ) {
							Groups_Options::update_option( Groups_Post_Access::READ_POST_CAPABILITIES, array( Groups_Post_Access::READ_POST_CAPABILITY ) );
							$wpdb->query( $wpdb->prepare( "UPDATE $wpdb->postmeta SET meta_value = %s WHERE meta_key = %s", Groups_Post_Access::READ_POST_CAPABILITY, Groups_Post_Access::POSTMETA_PREFIX . Groups_Post_Access::READ_POST_CAPABILITY ) );
						}
						if ( version_compare( $previous_version, '1.5.1' ) < 0 ) {
							$capability_table = _groups_get_tablename( 'capability' );
							$queries[] = "ALTER TABLE $capability_table DROP INDEX capability, ADD UNIQUE INDEX capability(capability(100));";
						}
					}
			} // switch
			if ( !empty( $previous_version ) && version_compare( $previous_version, '2.0.0' ) < 0 ) {
				self::set_default_capabilities();
				Groups_WordPress::refresh_capabilities();
			}
			foreach ( $queries as $query ) {
				if ( $wpdb->query( $query ) === false ) {
					$result = false;
				}
			}
		}
		return $result;
	}

	/**
	* Drop tables and clear data if the plugin is deactivated.
	* This will happen only if the user chooses to delete data upon deactivation.
	* @param boolean $network_wide
	*/
	public static function deactivate( $network_wide = false ) {
		$sem_id = self::sem_get( self::get_sem_key() );
		if ( ( $sem_id === false ) || self::sem_acquire( $sem_id ) ) {
			if ( is_multisite() && $network_wide ) {
				if ( Groups_Options::get_option( 'groups_network_delete_data', false ) ) {
					$blog_ids = Groups_Utility::get_blogs();
					foreach ( $blog_ids as $blog_id ) {
						self::switch_to_blog( $blog_id );
						self::cleanup( true );
						self::restore_current_blog();
					}
				}
			} else {
				self::cleanup();
			}
			if ( $sem_id !== false ) {
				self::sem_release( $sem_id );
				self::sem_remove( $sem_id );
			}
		}
	}

	/**
	 * Plugin deactivation cleanup.
	 * @param $drop overrides the groups_delete_data option, default is false
	 */
	private static function cleanup( $drop = false ) {

		global $wpdb, $wp_roles;

		$delete_data = Groups_Options::get_option( 'groups_delete_data', false );
		if ( $delete_data || $drop ) {
			foreach ( $wp_roles->role_objects as $role ) {
				$role->remove_cap( GROUPS_ACCESS_GROUPS );
				$role->remove_cap( GROUPS_ADMINISTER_GROUPS );
				$role->remove_cap( GROUPS_ADMINISTER_OPTIONS );
				$role->remove_cap( GROUPS_RESTRICT_ACCESS );
			}
			$wpdb->query( 'DROP TABLE IF EXISTS ' . _groups_get_tablename( 'group' ) );
			$wpdb->query( 'DROP TABLE IF EXISTS ' . _groups_get_tablename( 'capability' ) );
			$wpdb->query( 'DROP TABLE IF EXISTS ' . _groups_get_tablename( 'user_group' ) );
			$wpdb->query( 'DROP TABLE IF EXISTS ' . _groups_get_tablename( 'user_capability' ) );
			$wpdb->query( 'DROP TABLE IF EXISTS ' . _groups_get_tablename( 'group_capability' ) );
			Groups_Options::flush_options();
			if ( class_exists( 'Groups_Admin_Notice' ) ) {
				delete_metadata( 'user', null, Groups_Admin_Notice::HIDE_REVIEW_NOTICE, null, true );
				delete_site_option( Groups_Admin_Notice::INIT_TIME );
			}
			delete_option( GROUPS_ADMINISTRATOR_ACCESS_OVERRIDE ); // keep this to delete the deprecated option @since 2.1.1
			delete_option( 'groups_plugin_version' );
			delete_option( 'groups_delete_data' );
		}
	}

	/**
	 * Determines the default capabilities for the administrator role.
	 * In lack of an administrator role, these capabilities are assigned
	 * to any role that can manage_options.
	 * This is also used to assure a minimum set of capabilities is
	 * assigned to an appropriate role, so that it's not possible
	 * to lock yourself out (although deactivating and then activating
	 * the plugin would have the same effect but with the danger of
	 * deleting all plugin data).
	 * @param boolean $activate defaults to true, when this function is called upon plugin activation
	 * @access private
	 */
	public static function set_default_capabilities() {
		global $wp_roles;
		// The administrator role should be there, if it's not, assign privileges to
		// any role that can manage_options:
		if ( $administrator_role = $wp_roles->get_role( 'administrator' ) ) {
			$administrator_role->add_cap( GROUPS_ACCESS_GROUPS );
			$administrator_role->add_cap( GROUPS_ADMINISTER_GROUPS );
			$administrator_role->add_cap( GROUPS_ADMINISTER_OPTIONS );
			$administrator_role->add_cap( GROUPS_RESTRICT_ACCESS );
		} else {
			foreach ( $wp_roles->role_objects as $role ) {
				if ($role->has_cap( 'manage_options' ) ) {
					$role->add_cap( GROUPS_ACCESS_GROUPS );
					$role->add_cap( GROUPS_ADMINISTER_GROUPS );
					$role->add_cap( GROUPS_ADMINISTER_OPTIONS );
					$role->add_cap( GROUPS_RESTRICT_ACCESS );
				}
			}
		}
	}

	/**
	 * There must be at least one role with the minimum set of capabilities
	 * to access and manage the Groups plugin's options.
	 * If this condition is not met, the minimum set of capabilities is
	 * reestablished.
	 */
	public static function assure_capabilities() {
		global $wp_roles;
		$complies = false;
		$roles = $wp_roles->role_objects;
		foreach( $roles as $role ) {
			if ( $role->has_cap( GROUPS_ACCESS_GROUPS ) && ( $role->has_cap( GROUPS_ADMINISTER_OPTIONS ) ) ) {
				$complies = true;
				break;
			}
		}
		if ( !$complies ) {
			self::set_default_capabilities();
		}
	}

	/**
	 * Guarded sem_get() wrapper.
	 *
	 * @see sem_get()
	 *
	 * @param int $key
	 * @param number $max_acquire
	 * @param number $perm
	 * @param number $auto_release
	 * @return boolean|resource
	 */
	private static function sem_get( $key, $max_acquire = 1, $perm = 0666, $auto_release = 1 ) {
		$result = false;
		if ( function_exists( 'sem_get' ) ) {
			$result = sem_get( $key, $max_acquire, $perm, $auto_release );
		}
		return $result;
	}

	/**
	 * Guarded sem_acquire() wrapper.
	 *
	 * To maintain backwards-compatibility with servers running PHP < 5.6 where
	 * the second parameter to sem_acquire() is not supported, we use sem_remove()
	 * and have any calls waiting on sem_acquire() fail silently (achieving that
	 * the activation, update or deactivation routines are not run for those
	 * processes that have been waiting and which would have duplicated execution
	 * unnecessarily).
	 *
	 * @see sem_acquire()
	 *
	 * @param resource $sem_identifier
	 * @param string $nowait (only taken into account and effective on PHP >= 5.6.1)
	 * @return boolean
	 */
	private static function sem_acquire( $sem_identifier, $nowait = false ) {
		$result = false;
		if ( function_exists( 'sem_acquire' ) ) {
			if ( version_compare( phpversion(), '5.6.1' ) >= 0 ) {
				$result = @sem_acquire( $sem_identifier, $nowait );
			} else {
				$result = @sem_acquire( $sem_identifier );
			}
		}
		return $result;
	}

	/**
	 * Guarded sem_release() wrapper.
	 *
	 * @see sem_release()
	 *
	 * @param resource $sem_identifier
	 * @return boolean
	 */
	private static function sem_release( $sem_identifier ) {
		$result = false;
		if ( function_exists( 'sem_release' ) ) {
			$result = @sem_release( $sem_identifier );
		}
		return $result;
	}

	/**
	 * Guarded sem_remove() wrapper.
	 *
	 * @see sem_remove()
	 *
	 * @param unknown $sem_identifier
	 * @return boolean
	 */
	private static function sem_remove( $sem_identifier ) {
		$result = false;
		if ( function_exists( 'sem_remove' ) ) {
			$result = @sem_remove( $sem_identifier );
		}
		return $result;
	}

	/**
	 * Produces a file-based key for use with sem_get().
	 *
	 * @return number
	 */
	private static function get_sem_key() {
		$key = -1;
		if ( function_exists( 'ftok' ) ) {
			$key = ftok( __FILE__, 'g' );
		}
		if ( $key == -1 ) {
			$key = fileinode( __FILE__ );
		}
		return $key;
	}
}
Groups_Controller::boot();
