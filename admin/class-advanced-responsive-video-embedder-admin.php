<?php
/**
 * Plugin Name.
 *
 * @package   Advanced_Responsive_Video_Embedder_Admin
 * @author    Your Name <email@example.com>
 * @license   GPL-3.0+
 * @link      http://example.com
 * @copyright 2013 Your Name or Company Name
 */

/*****************************************************************************

Copyright (c) 2013 Nicolas Jonas

This file is part of Advanced Responsive Video Embedder.

Advanced Responsive Video Embedder is free software: you can redistribute it
and/or modify it under the terms of the GNU General Public License as
published by the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

Advanced Responsive Video Embedder is distributed in the hope that it will be
useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General
Public License for more details.

You should have received a copy of the GNU General Public License along with
Advanced Responsive Video Embedder.  If not, see
<http://www.gnu.org/licenses/>.

_  _ ____ _  _ ___ ____ ____ _  _ ___ _  _ ____ _  _ ____ ____  ____ ____ _  _ 
|\ | |___  \/   |  | __ |___ |\ |  |  |__| |___ |\/| |___ [__   |    |  | |\/| 
| \| |___ _/\_  |  |__] |___ | \|  |  |  | |___ |  | |___ ___] .|___ |__| |  | 

*******************************************************************************/

class Advanced_Responsive_Video_Embedder_Admin {

	/**
	 * Instance of this class.
	 *
	 * @since    2.6.0
	 *
	 * @var      object
	 */
	protected static $instance = null;

	/**
	 * Slug of the plugin screen.
	 *
	 * @since    1.0.0
	 *
	 * @var      string
	 */
	protected $plugin_screen_hook_suffix = null;

	/**
	 * Initialize the plugin by loading admin scripts & styles and adding a
	 * settings page and menu.
	 *
	 * @since     1.0.0
	 */
	private function __construct() {

		/*
		 * Call $plugin_slug from public plugin class.
		 *
		 *
		 */
		$plugin = Advanced_Responsive_Video_Embedder::get_instance();
		$this->plugin_slug = $plugin->get_plugin_slug();

		// Load admin style sheet and JavaScript.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
		
		// Add the options page and menu item.
		add_action( 'admin_menu', array( $this, 'add_plugin_admin_menu' ) );
		
		// Add an action link pointing to the options page.
		$plugin_basename = plugin_basename( plugin_dir_path( __DIR__ ) . $this->plugin_slug . '.php' );
		add_filter( 'plugin_action_links_' . $plugin_basename, array( $this, 'add_action_links' ) );
		
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_init', array( $this, 'init_mce_plugin' ) );

		add_action( 'wp_ajax_get_arve_form', array( $this, 'get_mce_form' ) );
	}

	/**
	 * Return an instance of this class.
	 *
	 * @since     2.6.0
	 *
	 * @return    object    A single instance of this class.
	 */
	public static function get_instance() {

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Register and enqueue admin-specific style sheet.
	 *
	 *
	 * @since     1.0.0
	 *
	 * @return    null    Return early if no settings page is registered.
	 */
	public function enqueue_admin_styles() {

		if ( ! isset( $this->plugin_screen_hook_suffix ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( $this->plugin_screen_hook_suffix == $screen->id ) {
			wp_enqueue_style( $this->plugin_slug .'-admin-styles', plugins_url( 'assets/css/admin.css', __FILE__ ), array(), Advanced_Responsive_Video_Embedder::VERSION );
		}

	}

	/**
	 * Register and enqueue admin-specific JavaScript.
	 *
	 *
	 * @since     1.0.0
	 *
	 * @return    null    Return early if no settings page is registered.
	 */
	public function enqueue_admin_scripts() {

		if ( ! isset( $this->plugin_screen_hook_suffix ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( $this->plugin_screen_hook_suffix == $screen->id ) {
			return;
		}

		#wp_enqueue_script( $this->plugin_slug . '-admin-script', plugins_url( 'assets/js/admin.js', __FILE__ ), array( 'jquery' ), Advanced_Responsive_Video_Embedder::VERSION );

		$plugin = Advanced_Responsive_Video_Embedder::get_instance();
		$regex_list = $plugin->get_regex_list();

		foreach ( $regex_list as $provider => $regex ) {
			$regex_list[$provider] = str_replace(
				array( 'https?://(?:www\.)?', 'http://', '/'  ),
				array( ''                   , ''       , '\/' ),
				$regex
			);
		}

		wp_localize_script( 'jquery', 'arve_regex_list', $regex_list );

	}

	/**
	 * Register the administration menu for this plugin into the WordPress Dashboard menu.
	 *
	 * @since    1.0.0
	 */
	public function add_plugin_admin_menu() {

		/*
		 * Add a settings page for this plugin to the Settings menu.
		 *
		 * NOTE:  Alternative menu locations are available via WordPress administration menu functions.
		 *
		 *        Administration Menus: http://codex.wordpress.org/Administration_Menus
		 *
		 * TODO:
		 *
		 * - Change 'Page Title' to the title of your plugin admin page
		 * - Change 'Menu Text' to the text for menu item for the plugin settings page
		 * - Change 'manage_options' to the capability you see fit
		 *   For reference: http://codex.wordpress.org/Roles_and_Capabilities
		 */
		$this->plugin_screen_hook_suffix = add_options_page(
			__( 'Advanced Responsive Video Embedder Settings', $this->plugin_slug ),
			__( 'A.R. Video Embedder Settings', $this->plugin_slug ),
			'manage_options',
			$this->plugin_slug,
			array( $this, 'display_plugin_admin_page' )
		);

	}

	/**
	 * Render the settings page for this plugin.
	 *
	 * @since    1.0.0
	 */
	public function display_plugin_admin_page() {
		include_once( 'views/admin.php' );
	}

	/**
	 * Add settings action link to the plugins page.
	 *
	 * @since    1.0.0
	 */
	public function add_action_links( $links ) {

		$link = array( 'settings' => sprintf( 
			'<a href="%s">%s</a>',
			admin_url( 'options-general.php?page=' . $this->plugin_slug ), 
			__( 'Settings', $this->plugin_slug ) )
		);

		return array_merge( $link, $links );

	}

	/**
	 * Register and enqueue admin-specific style sheet.
	 *
	 * @since     2.6.0
	 */
	public function init_mce_plugin() {
		// only hook up these filters if we're in the admin panel, and the current user has permission
		// to edit posts and pages
		if (
			current_user_can( 'publish_posts' )
			|| current_user_can( 'edit_posts' )
			|| current_user_can( 'edit_private_posts' )
			|| current_user_can( 'edit_published_posts' )
			|| current_user_can( 'publish_pages' )
			|| current_user_can( 'edit_pages' )
			|| current_user_can( 'edit_private_pages' )
			|| current_user_can( 'edit_published_pages' )
			|| current_user_can( 'edit_other_pages' )
		) {
			add_filter( 'mce_buttons', array( $this, 'filter_mce_button' ) );
			add_filter( 'mce_external_plugins', array( $this, 'filter_mce_plugin' ) );
		}
	}

	/**
	 * R
	 *
	 * @since    2.6.0
	 */
	public function register_settings() {
	        register_setting( 'arve_plugin_options', 'arve_options', array( $this, 'validate_options' ) );
	}

	/**
	 *
	 * @since     2.6.0
	 */	
	public function filter_mce_button( $buttons ) {
		// add a separation before our button, here our button's id is "arve_button"
		array_push( $buttons, '|', 'arve_button' );
		return $buttons;
	}
	
	/**
	 *
	 * @since     2.6.0
	 */	
	public function filter_mce_plugin( $plugins ) {
		// this plugin file will work the magic of our button
		$plugins['arve'] = plugin_dir_url( __FILE__ ) . 'assets/js/mce-plugin.js';
		return $plugins;
	}
	
	/**
	 *
	 * @since     2.6.0
	 */
	public function get_mce_form() {
		?>
		<div id="arve-form">
			<table id="arve-table" class="form-table">
				<colgroup style="width: 45%;"></colgroup>
				<colgroup style="width: 55%;"></colgroup>
				<tr>
					<th>
						<label for="arve-url">URL</label><br>
						<small class="description">
							<?php _e('For Blip.tv, Videojug, Movieweb, Gametrailers, Yahoo!, Spike and Comedycentral paste the embed code, for all others paste the URL!', 'ngt-arve'); ?><br>
							<a href="#" id="arve-open-url-info"><?php _e('More info', 'ngt-arve'); ?></a>
						</small>

						<div id="arve-url-info" style="display: none; padding: 0 15px;">
							<p>
								<?php _e('Ustream: If your Address bar URL not contains a number. Click Share->URL-icon and paste the URL you get there here.', 'ngt-arve'); ?>
							</p>
							<p>
								<?php _e("For Youtube, Archiveorg, Metacafe and Viddler embed codes and URL's should work.", 'ngt-arve'); ?>
							</p>
						</div>
					</th>
					<td>
						<textarea id="arve-url" rows="4" value="" style="width: 100%;"></textarea><br>
					</td>
				</tr>
				<tr>
					<th>
						<label for="arve-mode"><?php _e('Mode', 'ngt-arve'); ?></label><br>
						<small class="description"><?php _e('Optional override setting for single videos.', 'ngt-arve');?></small>
					</th>
					<td>
						<select id="arve-mode">
							<option value=""></option>
							<option value="normal"><?php _e('Normal', 'ngt-arve'); ?></option>
							<option value="thumbnail"><?php _e('Thumbnail', 'ngt-arve'); ?></option>
						</select>
					</td>				
				</tr>
				<tr>
					<th>
						<label for="arve-align"><?php _e('Align', 'ngt-arve'); ?></label><br>
						<small class="description"><?php _e('');?></small>
					</th>
					<td>
						<select id="arve-align">
							<option value=""></option>
							<option value="left"><?php _e('left', 'ngt-arve'); ?></option>
							<option value="right"><?php _e('right', 'ngt-arve'); ?></option>
							<option value="center"><?php _e('center', 'ngt-arve'); ?></option>
						</select>
					</td>
				</tr>
				<tr>
					<th></th>
					<td>
						<input type="button" id="arve-show-more" class="button-secondary" value="Show More Options" name="arve-show-more" />
					</td>
				</tr>
				<tr style="display: none;" class="arve-hidden">
					<th>
						<label for="arve-autoplay"><?php _e('Autoplay this video', 'ngt-arve'); ?></label><br>
						<small class="description"><?php _e('Optional override setting for single videos.', 'ngt-arve'); ?></small>
					</th>
					<td>
						<select id="arve-autoplay">
							<option value=""></option>
							<option value="yes"><?php _e('yes', 'ngt-arve'); ?></option>
							<option value="no"><?php _e('no', 'ngt-arve'); ?></option>
						</select>
					</td>
				</tr>
				<tr style="display: none;" class="arve-hidden">
					<th>
						<label for="arve-maxwidth"><?php _e('Maximal width', 'ngt-arve'); ?></label><br>
						<small class="description"><?php _e('Optional override setting for single videos.', 'ngt-arve'); ?></small>
					</th>
					<td>
						<input type="text" id="arve-maxwidth" value="" />	
					</td>
				</tr>
				<!-- always hidden -->
				<tr style="display: none;" class="arve-hidden">
					<th>
						<label for="arve-provider"><?php _e('Provider', 'ngt-arve'); ?></label>
					</th>
					<td>
						<select id="arve-provider">
							<option value=""></option>
							<?php
							$options = get_option('arve_options');
							foreach( $options['shortcodes'] as $key => $val )
								printf( '<option value="%s">%s</option>', esc_attr( $val ), esc_html( $key ) );
							?>
						</select>
					</td>
				</tr>
				<tr style="display: none;" class="arve-hidden">
					<th>
						<label for="arve-id"><?php _e('Video ID', 'ngt-arve'); ?></label><br>
						<small class="description"><?php _e('If not filled in automatically after pasting the url above you have to insert the video ID in here.', 'ngt-arve'); ?></small>
					</th>
					<td>
						<input type="text" id="arve-id" value="" />
					</td>
				</tr>
				<!-- end always hidden-->
				<tr>
					<td colspan="2" style="padding: 15px; font-size: 17px; text-align: center;" id="arve-shortcode">
						-
					</td>
				</tr>	
				<tr>
					<th>
						<label for="arve-submit"><?php _e('Ready?', 'ngt-arve'); ?></label>
					</th>
					<td>
						<input type="button" id="arve-submit" class="button-primary" value="Insert Shortcode" name="submit" />
					</td>
				</tr>
			</table>
		</div>
		<?php
		
		exit;
	}

}