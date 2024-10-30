<?php
/**
 * Plugin Name: Bulk Orders Remover for WooCommerce
 * Description: Clean WooCommerce orders automatically and periodically
 * Version: 1.0
 * Author: From Poland With Dev
 * Author URI: https://frompolandwithdev.com
 * License: GPL3
 * Text Domain: bulk-orders-remover
 */

namespace FPWD\Bulk_Orders_Remover;

require_once( "vendor/autoload.php" );

use DateTimeImmutable;
use DateTimeZone;
use Exception;
use FPWD\Bulk_Orders_Remover\Db;
use FPWD\Bulk_Orders_Remover\Admin_Options;

/**
 * Class FPWD_Bulk_Orders_Remover
 *
 * @package FPWD\Bulk_Orders_Remover
 */
class FPWD_Bulk_Orders_Remover {

	/** @var Admin_Options */
	private $settings;

	/** @var Db */
	private $db;

	/**
	 * @var int If filter return more orders than this value to remove warning will be displayed in Admin Panel
	 */
	private $order_count_warn_treshold = 5000;

	/**
	 * @var Current WordPress installation timezone
	 */
	public $timezone;

	/**
	 * FPWD_Bulk_Orders_Remover constructor.
	 */
	public function __construct() {
		$this->settings = new Admin_Options( __FILE__ );
		$this->db       = new Db();
		$this->timezone = get_option( 'timezone_string' );
		if ( empty( $this->timezone ) ) {
			$this->timezone = 'Europe/London';
		}
	}

	/**
	 *
	 */
	public function add_hooks() {

		add_action( 'plugins_loaded', [
			$this,
			'load_textdomain',
		] );

		add_filter( 'cron_schedules', [
			$this,
			'add_cron_cutom_intervals',
		] );

		add_action( 'updated_option', [
			$this,
			'on_settings_update',
		], 99, 3 );

		add_action( 'borfw_set_orders_to_remove_cron', [
			$this,
			'set_orders_to_remove',
		] );

		add_action( 'borfw_delete_order_item_meta_cron', [
			$this,
			'delete_order_item_meta',
		] );

		add_action( 'borfw_delete_order_items_cron', [
			$this,
			'delete_order_items',
		] );

		add_action( 'borfw_delete_order_note_meta_cron', [
			$this,
			'delete_order_note_meta',
		] );

		add_action( 'borfw_delete_order_notes_cron', [
			$this,
			'delete_order_notes',
		] );

		add_action( 'borfw_delete_order_meta_cron', [
			$this,
			'delete_order_meta',
		] );

		add_action( 'borfw_delete_orders_cron', [
			$this,
			'delete_orders',
		] );

		//Notices
		add_action( 'admin_notices', [
			$this,
			'admin_notices',
		] );

		register_activation_hook( __FILE__, [
			$this,
			'activation',
		] );


		register_deactivation_hook( __FILE__, [
			$this,
			'deactivation',
		] );

		register_uninstall_hook( __FILE__, [
			'FPWD\Bulk_Orders_Remover\FPWD_Bulk_Orders_Remover',
			'uninstall',
		] );

	}

	/**
	 * Do some actions on plugin activation
	 */
	public function activation() {
		update_option( 'borfw_clean_frequency', '' );
		if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) && current_user_can( 'activate_plugins' ) ) {
			// Stop activation redirect and show error
			wp_die( 'Sorry, but this plugin requires the WooCommerce Plugin to be installed and active. <br><a href="' . admin_url( 'plugins.php' ) . '">&laquo; Return to Plugins</a>' );
		}
	}

	/**
	 * Do some actions on plugin deactivation
	 */
	public function deactivation() {
		wp_clear_scheduled_hook( 'borfw_set_orders_to_remove_cron' );
		update_option( 'borfw_clean_frequency', '' );
	}

	/**
	 *  Clean db after plugin uninstall
	 */
	public static function uninstall() {
		wp_clear_scheduled_hook( 'borfw_set_orders_to_remove_cron' );
		wp_clear_scheduled_hook( 'borfw_delete_order_item_meta_cron' );
		wp_clear_scheduled_hook( 'borfw_delete_order_items_cron' );
		wp_clear_scheduled_hook( 'borfw_delete_order_note_meta_cron' );
		wp_clear_scheduled_hook( 'borfw_delete_order_notes_cron' );
		wp_clear_scheduled_hook( 'borfw_delete_order_meta_cron' );
		wp_clear_scheduled_hook( 'borfw_delete_orders_cron' );
		delete_option( 'borfw_clean_frequency' );
		delete_option( 'borfw_date_treshold' );
		delete_option( 'borfw_date_count' );
	}

	/**
	 * Load plugin textdomain
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'bulk-orders-remover', false, basename( dirname( __FILE__ ) ) . '/languages' );
	}

	/**
	 * @param array $schedules
	 *
	 * @return array
	 */
	public function add_cron_cutom_intervals( $schedules ) {
		$schedules['weekly'] = [
			'interval' => WEEK_IN_SECONDS,
			'display'  => esc_html__( 'Once Weekly', 'bulk-orders-remover' ),
		];
		$schedules['yearly'] = [
			'interval' => YEAR_IN_SECONDS,
			'display'  => esc_html__( 'Once Yearly', 'bulk-orders-remover' ),
		];

		return $schedules;
	}

	/**
	 * Setup cron after settings save or update.
	 *
	 * @param string $option Option Name
	 * @param mixed $old_value Option old value
	 * @param mixed $new_value Option new value
	 */
	public function on_settings_update( $option, $old_value, $new_value ) {

		if ( 'borfw_clean_frequency' === $option ) {
			$this->remove_all_schedules();
			$schedule_time = new \DateTime( 'tomorrow 02:00:00', new \DateTimeZone( $this->timezone ) );
			wp_schedule_event( $schedule_time->getTimestamp(), $new_value, 'borfw_set_orders_to_remove_cron' );
		}

		if ( 'borfw_date_count' === $option || 'borfw_date_treshold' === $option ) {
			$treshold_date = $this->get_treshold_date();
			$orders_count  = $this->db->count_order_to_delete( $treshold_date->format( 'Y-m-d 00:00:00' ) );

			if ( $orders_count >= $this->order_count_warn_treshold ) {
				update_user_meta( get_current_user_id(), 'notice', [
					'status'  => 'error',
					'message' => sprintf( __( 'Due to the high volume of orders to be removed (over %d), please mind the website might be slightly slower for the time beeing.', 'bulk-orders-remover' ), $this->order_count_warn_treshold ),
				] );
			}
		}

	}

	/**
	 * @throws Exception
	 */
	public function set_orders_to_remove() {
		$treshold_date = $this->get_treshold_date();

		if ( ! $treshold_date ) {
			return;
		}

		$this->db->set_trashed_orders( $treshold_date->format( 'Y-m-d 00:00:00' ) );

		if ( ! wp_next_scheduled( 'borfw_delete_order_item_meta_cron' ) ) {
			wp_schedule_single_event( time() + 3, 'borfw_delete_order_item_meta_cron' );
		}
	}

	/**
	 *
	 */
	public function delete_order_item_meta() {
		$this->run_db_action( 'borfw_delete_order_item_meta_cron', 'borfw_delete_order_items_cron', 'delete_order_item_meta' );
	}

	/**
	 *
	 */
	public function delete_order_items() {
		$this->run_db_action( 'borfw_delete_order_items_cron', 'borfw_delete_order_note_meta_cron', 'delete_order_items' );
	}

	/**
	 *
	 */
	public function delete_order_note_meta() {
		$this->run_db_action( 'borfw_delete_order_note_meta_cron', 'borfw_delete_order_notes_cron', 'delete_order_note_meta' );
	}

	/**
	 *
	 */
	public function delete_order_notes() {
		$this->run_db_action( 'borfw_delete_order_notes_cron', 'borfw_delete_order_meta_cron', 'delete_order_notes' );
	}

	/**
	 *
	 */
	public function delete_order_meta() {
		$this->run_db_action( 'borfw_delete_order_meta_cron', 'borfw_delete_orders_cron', 'delete_order_meta' );
	}

	/**
	 *
	 */
	public function delete_orders() {
		$this->db->delete_orders();
	}

	/**
	 * @param string $current_cron
	 * @param string $next_cron
	 * @param string $method
	 * @param null $args
	 */
	public function run_db_action( $current_cron, $next_cron, $method, $args = null ) {
		wp_schedule_single_event( time() + 60 * 15, $current_cron );

		if ( method_exists( $this->db, $method ) ) {
			if ( ! empty( $args ) ) {
				call_user_func( [
					$this->db,
					$method,
				], $args );
			} else {
				call_user_func( [
					$this->db,
					$method,
				] );
			}

		}

		wp_clear_scheduled_hook( $current_cron );

		if ( ! wp_next_scheduled( $next_cron ) ) {
			wp_schedule_single_event( time() + 10, $next_cron );
		}
	}

	/**
	 *
	 */
	public function admin_notices() {
		if ( $notice = get_user_meta( get_current_user_id(), 'notice', 1 ) ) {
			delete_user_meta( get_current_user_id(), 'notice' );

			if ( isset( $notice['status'], $notice['message'] ) ) {
				echo '<div class="notice notice-' . esc_attr( $notice['status'] ) . '"><p>' . $notice['message'] . '</p></div>';
			}
		}

		if ( ! wp_next_scheduled( 'borfw_set_orders_to_remove_cron' ) ) {
			$notice      = sprintf( __( 'Bulk Order Remover needs some attention. Please click <a href="%s">here</a> and confirm all settings.', 'bulk-orders-remover' ), 'admin.php?page=bulk-orders-remover-settings' );
			$notice_type = 'warning';
			echo '<div class="notice notice-' . esc_attr( $notice_type ) . '"><p>' . $notice . '</p></div>';

		}
	}

	/**
	 * Remove all schedules set up for orders removal.
	 */
	private function remove_all_schedules() {
		wp_clear_scheduled_hook( 'borfw_set_orders_to_remove_cron' );
		wp_clear_scheduled_hook( 'borfw_delete_order_item_meta_cron' );
		wp_clear_scheduled_hook( 'borfw_delete_order_items_cron' );
		wp_clear_scheduled_hook( 'borfw_delete_order_note_meta_cron' );
		wp_clear_scheduled_hook( 'borfw_delete_order_notes_cron' );
		wp_clear_scheduled_hook( 'borfw_delete_order_meta_cron' );
		wp_clear_scheduled_hook( 'borfw_delete_orders_cron' );
	}

	/**
	 * Get date for filter to remove orders.
	 *
	 * @return bool|DateTimeImmutable
	 */
	private function get_treshold_date() {
		//@TODO Convert all dates to GMT

		$treshold_count = (int) get_option( $this->settings->base . 'date_count', 90 );
		$treshold_type  = get_option( $this->settings->base . 'date_treshold', 'days' );

		switch ( $treshold_type ) {
			case 'months':
				$days_offset = $treshold_count * 30;
				break;
			case 'years':
				$days_offset = $treshold_count * 365;
				break;
			default:
				$days_offset = $treshold_count;
		}

		try {
			return new DateTimeImmutable( 'today -' . $days_offset . ' days' );
		} catch ( Exception $e ) {
			trigger_error( $e->getMessage() );
		}

		return false;
	}
}

( new FPWD_Bulk_Orders_Remover() )->add_hooks();
