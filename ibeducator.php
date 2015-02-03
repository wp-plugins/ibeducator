<?php
/**
 * @package ibeducator
 */
/*
Plugin Name: Educator WP
Plugin URI: http://incrediblebytes.com/plugins/educator-wp/
Description: Offer courses to students online.
Author: dmytro.d (IncredibleBytes)
Version: 1.3.3
Author URI: http://incrediblebytes.com
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Text Domain: ibeducator
*/

/*
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'IBEDUCATOR_VERSION', '1.3.3' );
define( 'IBEDUCATOR_DB_VERSION', '1.3.0' );
define( 'IBEDUCATOR_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'IBEDUCATOR_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

register_activation_hook( __FILE__, array( 'IB_Educator_Main', 'plugin_activation' ) );
register_deactivation_hook( __FILE__, array( 'IB_Educator_Main', 'plugin_deactivation' ) );

require_once IBEDUCATOR_PLUGIN_DIR . 'includes/objects/ib-educator-payment.php';
require_once IBEDUCATOR_PLUGIN_DIR . 'includes/objects/ib-educator-entry.php';
require_once IBEDUCATOR_PLUGIN_DIR . 'includes/objects/ib-educator-question.php';
require_once IBEDUCATOR_PLUGIN_DIR . 'includes/ib-educator-post-types.php';
require_once IBEDUCATOR_PLUGIN_DIR . 'includes/ib-educator.php';
require_once IBEDUCATOR_PLUGIN_DIR . 'includes/ib-educator-view.php';
require_once IBEDUCATOR_PLUGIN_DIR . 'includes/functions.php';
require_once IBEDUCATOR_PLUGIN_DIR . 'includes/ib-educator-memberships.php';
require_once IBEDUCATOR_PLUGIN_DIR . 'includes/ib-educator-main.php';
require_once IBEDUCATOR_PLUGIN_DIR . 'includes/ib-educator-request.php';
require_once IBEDUCATOR_PLUGIN_DIR . 'includes/shortcodes.php';

// Setup the post types and taxonomies.
IB_Educator_Post_Types::init();

// Setup Educator.
IB_Educator_Main::init();

// Parse incoming requests (e.g. PayPal IPN).
IB_Educator_Request::init();

if ( is_admin() ) {
	// Setup educator admin.
	require_once IBEDUCATOR_PLUGIN_DIR . 'admin/ib-educator-admin.php';
	IB_Educator_Admin::init();

	// Update.
	function ib_edu_update_check() {
		if ( get_option( 'ib_educator_version' ) != IBEDUCATOR_VERSION ) {
			require_once 'includes/ib-educator-install.php';
			$install = new IB_Educator_Install();
			$install->activate( false, false );
		}
	}
	add_action( 'init', 'ib_edu_update_check', 9 );
}