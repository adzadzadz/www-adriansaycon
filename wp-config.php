<?php
define( 'WP_CACHE', true );    // Added by WP Rocket.
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the
 * installation. You don't have to use the web site, you can
 * copy this file to "wp-config.php" and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * MySQL settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://codex.wordpress.org/Editing_wp-config.php
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'local' );

/** MySQL database username */
define( 'DB_USER', 'root' );

/** MySQL database password */
define( 'DB_PASSWORD', 'root' );

/** MySQL hostname */
define( 'DB_HOST', 'localhost' );

/** Database Charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8' );

/** The Database Collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define('AUTH_KEY',         'dA9Ue/iPs1JSH7PwWWKNe6nWiYPYy6poomRctgaFANQi8kGZ4z0cz3AEe08zIxvCdIH3bamtcw619dHzGNJi1Q==');
define('SECURE_AUTH_KEY',  'OYQ3rD51NchE0qlv0d/5hii/vjM7/Y/IIdSMYOaA5pMosib/QF0nK9UA+oj9MXU/+jln3LVh2+XTeNWnL5xYHw==');
define('LOGGED_IN_KEY',    'Naui0FW6dRm7JpXiRRg1FXDdH+akZU5JmMZzl008dgJaHLxKCTU9rwbdkcCeaEmV5oKxo9GOs+uvZQIrgl4PxQ==');
define('NONCE_KEY',        'RI1V/Pvt7FP/U0RPyeowy7NIv3kARaKrmsiKpaZPhW+fbSdR/q0YxOiKwGkUSg66zO25QJLmGegvlVFGS4k8aQ==');
define('AUTH_SALT',        'g+HgdXpPppZc6u60rPp4jp3miZ+9c3DAbX9yd7vbTTjR9eKV1Oc+jgtbLArO1gt6uqnpJmy74DiATuTUSRYSSA==');
define('SECURE_AUTH_SALT', 'H3AmdW/31nmDhWDeVl3iB7Fe83/p9UsFcz7wqLxD6VgkSbzpv6+meA9lQ2n+OoK7uL3wgjll3Y8ONFu+zsiaRA==');
define('LOGGED_IN_SALT',   '1iH6XfhXHrUvyCnh0R4TmGgc7firG0REUTFzrq67gAfjLjCxjZJLGvkO3/g05Gnkh5cyaWwiHY6BsCaOqv4b1g==');
define('NONCE_SALT',       'JQ307YKqc/V3RQEG0XY2A1kufBh+MRlRPfusLu5F60PZwYHLrTgF2vAxjXeHHYNJfL3zDMf9bn1yoHV0AJAaiQ==');

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';




/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __FILE__ ) . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
