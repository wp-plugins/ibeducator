<?php
/**
 * @package ibeducator
 */
/*
Plugin Name: Educator WP
Plugin URI: http://incrediblebytes.com/plugins/educator-wp/
Description: Offer courses to students online.
Author: dmytro.d (IncredibleBytes)
Version: 0.9.0
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

define( 'IBEDUCATOR_VERSION', '0.9.0' );
define( 'IBEDUCATOR_DB_VERSION', '0.9.0' );
define( 'IBEDUCATOR_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'IBEDUCATOR_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

register_activation_hook( __FILE__, array( 'IBEdu_Main', 'plugin_activation' ) );
register_deactivation_hook( __FILE__, array( 'IBEdu_Main', 'plugin_deactivation' ) );

require_once IBEDUCATOR_PLUGIN_DIR . 'includes/objects/class.ibedu-payment.php';
require_once IBEDUCATOR_PLUGIN_DIR . 'includes/objects/class.ibedu-entry.php';
require_once IBEDUCATOR_PLUGIN_DIR . 'includes/objects/class.ibedu-question.php';
require_once IBEDUCATOR_PLUGIN_DIR . 'includes/class.ibedu-api.php';
require_once IBEDUCATOR_PLUGIN_DIR . 'includes/class.ibedu-view.php';
require_once IBEDUCATOR_PLUGIN_DIR . 'includes/functions.php';

// Setup Educator.
require_once IBEDUCATOR_PLUGIN_DIR . 'includes/class.ibedu-main.php';
IBEdu_Main::init();

// Parse incoming requests (e.g. PayPal IPN).
require_once IBEDUCATOR_PLUGIN_DIR . 'includes/class.ibedu-request.php';
IBEdu_Request::init();

if ( is_admin() ) {
	// Setup educator admin.
	require_once IBEDUCATOR_PLUGIN_DIR . 'admin/class.ibedu-admin.php';
	IBEdu_Admin::init();

	// Setup educator quiz admin.
	require_once IBEDUCATOR_PLUGIN_DIR . 'admin/class.ibedu-quiz-admin.php';
	IBEdu_Quiz_Admin::init();
}

// Shortcodes.
require_once IBEDUCATOR_PLUGIN_DIR . 'includes/shortcodes.php';