<?php
/**
 * Author: https://gist.github.com/hlashbrooke
 * Modified by: Grzegorz Pisarski
 */

namespace FPWD\Bulk_Orders_Remover;

/**
 * Class Admin_Options
 *
 * @package FPWD\Bulk_Orders_Remover
 */
class Admin_Options {
	/** @var string */
	public $base;

	/** @var string */
	private $dir;

	/** @var string */
	private $file;

	/** @var string */
	private $assets_dir;

	/** @var string */
	private $assets_url;

	/** @var array */
	private $settings;

	/**
	 * Admin_Options constructor.
	 *
	 * @param string $file
	 */
	public function __construct( $file ) {
		$this->file       = $file;
		$this->dir        = dirname( $this->file );
		$this->assets_dir = trailingslashit( $this->dir ) . 'assets';
		$this->assets_url = esc_url( trailingslashit( plugins_url( '/assets/', $this->file ) ) );
		$this->base       = 'borfw_';

		// Initialise settings
		add_action( 'admin_init', [
			$this,
			'init',
		] );

		// Register plugin settings
		add_action( 'admin_init', [
			$this,
			'register_settings',
		] );

		// Add settings page to menu
		add_action( 'admin_menu', [
			$this,
			'add_menu_item',
		], 999 );

		// Add settings link to plugins page
		add_filter( 'plugin_action_links_' . plugin_basename( $this->file ), [
			$this,
			'add_settings_link',
		] );
	}

	/**
	 * Initialise settings
	 */
	public function init() {
		$this->settings = $this->settings_fields();
	}

	/**
	 * Add settings page to admin menu
	 */
	public function add_menu_item() {
		$page = add_submenu_page('woocommerce', __( 'Bulk Orders Remover Settings', 'bulk-orders-remover' ), __( 'Bulk  Orders Remover', 'bulk-orders-remover' ), 'manage_options', 'bulk-orders-remover-settings', [
			$this,
			'settings_page',
		] );
		add_action( 'admin_print_styles-' . $page, array(
			$this,
			'settings_assets',
		) );
	}

	/**
	 * Load settings JS & CSS
	 */
	public function settings_assets() {
		// We're including the farbtastic script & styles here because they're needed for the colour picker
		// If you're not including a colour picker field then you can leave these calls out as well as the farbtastic dependency for the wpt-admin-js script below
		wp_enqueue_style( 'farbtastic' );
		wp_enqueue_script( 'farbtastic' );
		// We're including the WP media scripts here because they're needed for the image upload field
		// If you're not including an image upload then you can leave this function call out
		wp_enqueue_media();

		wp_register_script( 'wpt-admin-js', $this->assets_url . 'js/settings.js', array(
			'farbtastic',
			'jquery',
		), '1.0.0' );

		wp_enqueue_script( 'wpt-admin-js' );
	}

	/**
	 * Add settings link to plugin list table
	 *
	 * @param array $links Existing links
	 *
	 * @return array        Modified links
	 */
	public function add_settings_link( $links ) {
		$settings_link = '<a href="admin.php?page=bulk-orders-remover-settings">' . __( 'Settings', 'bulk-orders-remover' ) . '</a>';
		array_unshift( $links, $settings_link );

		return $links;
	}

	/**
	 * Build settings fields
	 *
	 * @return array Fields to be displayed on settings page
	 */
	private function settings_fields() {
		$settings['filters']   = array(
			'title'       => __( 'Orders filter', 'bulk-orders-remover' ),
			'description' => __( 'Choose  date filter for orders, all orders older than selected value will be automatically removed.', 'bulk-orders-remover' ),
			'fields'      => array(
				array(
					'id'          => 'date_count',
					'label'       => __( 'Clean orders older than', 'bulk-orders-remover' ),
					'description' => __( '', 'bulk-orders-remover' ),
					'type'        => 'text',
					'default'     => '',
					'placeholder' => __( '90', 'bulk-orders-remover' ),
				),
				array(
					'id'          => 'date_treshold',
					'label'       => __( 'Select period', 'bulk-orders-remover' ),
					'description' => __( '', 'bulk-orders-remover' ),
					'type'        => 'select',
					'options'     => array(
						'days'   => __( 'Day(s)', 'bulk-orders-remover' ),
						'months' => __( 'Month(s)', 'bulk-orders-remover' ),
						'years'  => __( 'Year(s)', 'bulk-orders-remover' ),
					),
					'default'     => 'days',
				),
			),

		);
		$settings['frequency'] = array(
			'title'       => __( 'Clean Frequency', 'bulk-orders-remover' ),
			'description' => __( 'How often would you like to automatically remove old orders', 'bulk-orders-remover' ),
			'fields'      => array(
				array(
					'id'          => 'clean_frequency',
					'label'       => __( 'Frequency', 'bulk-orders-remover' ),
					'description' => __( '', 'bulk-orders-remover' ),
					'type'        => 'select',
					'options'     => array(
						'daily'   => __( 'Once a day', 'bulk-orders-remover' ),
						'weekly'  => __( 'Once a week', 'bulk-orders-remover' ),
						'monthly' => __( 'Once a month', 'bulk-orders-remover' ),
						'yearly'  => __( 'Once a year', 'bulk-orders-remover' ),
					),
					'default'     => 'days',
				),
			),
		);
		$settings              = apply_filters( 'borfw_settings_fields', $settings );

		return $settings;
	}

	/**
	 * Register plugin settings
	 */
	public function register_settings() {
		if ( is_array( $this->settings ) ) {
			foreach ( $this->settings as $section => $data ) {
				// Add section to page
				add_settings_section( $section, $data['title'], array(
					$this,
					'settings_section',
				), 'borfw_settings' );
				foreach ( $data['fields'] as $field ) {
					// Validation callback for field
					$validation = '';
					if ( isset( $field['callback'] ) ) {
						$validation = $field['callback'];
					}
					// Register field
					$option_name = $this->base . $field['id'];
					register_setting( 'borfw_settings', $option_name, $validation );
					// Add field to page
					add_settings_field( $field['id'], $field['label'], array(
						$this,
						'display_field',
					), 'borfw_settings', $section, array( 'field' => $field ) );
				}
			}
		}
	}

	/**
	 * @param array $section
	 */
	public function settings_section( $section ) {
		$html = '<p> ' . $this->settings[ $section['id'] ]['description'] . '</p>' . "\n";
		echo $html;
	}

	/**
	 * Generate HTML for displaying fields
	 *
	 * @param array $args Field data
	 */
	public function display_field( $args ) {
		$field       = $args['field'];
		$html        = '';
		$option_name = $this->base . $field['id'];
		$option      = get_option( $option_name );
		$data        = '';
		if ( isset( $field['default'] ) ) {
			$data = $field['default'];
			if ( $option ) {
				$data = $option;
			}
		}
		switch ( $field['type'] ) {
			case 'text':
			case 'password':
			case 'number':
				$html .= '<input id="' . esc_attr( $field['id'] ) . '" type="' . $field['type'] . '" name="' . esc_attr( $option_name ) . '" placeholder="' . esc_attr( $field['placeholder'] ) . '" value="' . $data . '"/>' . "\n";
				break;
			case 'text_secret':
				$html .= '<input id="' . esc_attr( $field['id'] ) . '" type="text" name="' . esc_attr( $option_name ) . '" placeholder="' . esc_attr( $field['placeholder'] ) . '" value=""/>' . "\n";
				break;
			case 'textarea':
				$html .= '<textarea id="' . esc_attr( $field['id'] ) . '" rows="5" cols="50" name="' . esc_attr( $option_name ) . '" placeholder="' . esc_attr( $field['placeholder'] ) . '">' . $data . '</textarea><br/>' . "\n";
				break;
			case 'checkbox':
				$checked = '';
				if ( $option && 'on' == $option ) {
					$checked = 'checked="checked"';
				}
				$html .= '<input id="' . esc_attr( $field['id'] ) . '" type="' . $field['type'] . '" name="' . esc_attr( $option_name ) . '" ' . $checked . '/>' . "\n";
				break;
			case 'checkbox_multi':
				foreach ( $field['options'] as $k => $v ) {
					$checked = false;
					if ( in_array( $k, $data ) ) {
						$checked = true;
					}
					$html .= '<label for="' . esc_attr( $field['id'] . '_' . $k ) . '"><input type="checkbox" ' . checked( $checked, true, false ) . ' name="' . esc_attr( $option_name ) . '[]" value="' . esc_attr( $k ) . '" id="' . esc_attr( $field['id'] . '_' . $k ) . '" /> ' . $v . '</label> ';
				}
				break;
			case 'radio':
				foreach ( $field['options'] as $k => $v ) {
					$checked = false;
					if ( $k == $data ) {
						$checked = true;
					}
					$html .= '<label for="' . esc_attr( $field['id'] . '_' . $k ) . '"><input type="radio" ' . checked( $checked, true, false ) . ' name="' . esc_attr( $option_name ) . '" value="' . esc_attr( $k ) . '" id="' . esc_attr( $field['id'] . '_' . $k ) . '" /> ' . $v . '</label> ';
				}
				break;
			case 'select':
				$html .= '<select name="' . esc_attr( $option_name ) . '" id="' . esc_attr( $field['id'] ) . '">';
				foreach ( $field['options'] as $k => $v ) {
					$selected = false;
					if ( $k == $data ) {
						$selected = true;
					}
					$html .= '<option ' . selected( $selected, true, false ) . ' value="' . esc_attr( $k ) . '">' . $v . '</option>';
				}
				$html .= '</select> ';
				break;
			case 'select_multi':
				$html .= '<select name="' . esc_attr( $option_name ) . '[]" id="' . esc_attr( $field['id'] ) . '" multiple="multiple">';
				foreach ( $field['options'] as $k => $v ) {
					$selected = false;
					if ( in_array( $k, $data ) ) {
						$selected = true;
					}
					$html .= '<option ' . selected( $selected, true, false ) . ' value="' . esc_attr( $k ) . '" />' . $v . '</label> ';
				}
				$html .= '</select> ';
				break;
			case 'image':
				$image_thumb = '';
				if ( $data ) {
					$image_thumb = wp_get_attachment_thumb_url( $data );
				}
				$html .= '<img id="' . $option_name . '_preview" class="image_preview" src="' . $image_thumb . '" /><br/>' . "\n";
				$html .= '<input id="' . $option_name . '_button" type="button" data-uploader_title="' . __( 'Upload an image', 'bulk-orders-remover' ) . '" data-uploader_button_text="' . __( 'Use image', 'bulk-orders-remover' ) . '" class="image_upload_button button" value="' . __( 'Upload new image', 'bulk-orders-remover' ) . '" />' . "\n";
				$html .= '<input id="' . $option_name . '_delete" type="button" class="image_delete_button button" value="' . __( 'Remove image', 'bulk-orders-remover' ) . '" />' . "\n";
				$html .= '<input id="' . $option_name . '" class="image_data_field" type="hidden" name="' . $option_name . '" value="' . $data . '"/><br/>' . "\n";
				break;
			case 'color':
				?>
				<div class="color-picker" style="position:relative;">
					<input type="text" name="<?php esc_attr_e( $option_name ); ?>" class="color"
						   value="<?php esc_attr_e( $data ); ?>"/>
					<div style="position:absolute;background:#FFF;z-index:99;border-radius:100%;"
						 class="colorpicker"></div>
				</div>
				<?php
				break;
		}
		switch ( $field['type'] ) {
			case 'checkbox_multi':
			case 'radio':
			case 'select_multi':
				$html .= '<br/><span class="description">' . $field['description'] . '</span>';
				break;
			default:
				$html .= '<label for="' . esc_attr( $field['id'] ) . '"><span class="description">' . $field['description'] . '</span></label>' . "\n";
				break;
		}
		echo $html;
	}

	/**
	 * Validate individual settings field
	 *
	 * @param string $data Inputted value
	 *
	 * @return string       Validated value
	 */
	public function validate_field( $data ) {
		if ( $data && strlen( $data ) > 0 && $data != '' ) {
			$data = urlencode( strtolower( str_replace( ' ', '-', $data ) ) );
		}

		return $data;
	}

	/**
	 * Load settings page content
	 *
	 * @return void
	 */
	public function settings_page() {
		// Build page HTML
		$html = '<div class="wrap" id="borfw_settings">' . "\n";
		$html .= '<h1>' . __( 'Plugin Settings', 'bulk-orders-remover' ) . '</h1>' . "\n";
		$html .= '<form method="post" action="options.php" enctype="multipart/form-data">' . "\n";
		// Setup navigation
		$html .= '<ul id="settings-sections" class="subsubsub hide-if-no-js">' . "\n";
		$html .= '<li><a class="tab all current" href="#all">' . __( 'All', 'bulk-orders-remover' ) . '</a></li>' . "\n";
		foreach ( $this->settings as $section => $data ) {
			$html .= '<li>| <a class="tab" href="#' . $section . '">' . $data['title'] . '</a></li>' . "\n";
		}
		$html .= '</ul>' . "\n";
		$html .= '<div class="clear"></div>' . "\n";
		// Get settings fields
		ob_start();
		settings_fields( 'borfw_settings' );
		do_settings_sections( 'borfw_settings' );
		$html .= ob_get_clean();
		$html .= '<p class="submit">' . "\n";
		$html .= '<input name="Submit" type="submit" class="button-primary" value="' . esc_attr( __( 'Save Settings', 'bulk-orders-remover' ) ) . '" />' . "\n";
		$html .= '</p>' . "\n";
		$html .= '</form>' . "\n";
		$html .= '</div>' . "\n";
		echo $html;
	}
}