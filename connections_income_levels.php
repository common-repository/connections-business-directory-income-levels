<?php
/**
 * An extension for the Connections plugin which adds a metabox for income levels.
 *
 * @package   Connections Business Directory Extension - Income Level
 * @category  Extension
 * @author    Steven A. Zahm
 * @license   GPL-2.0+
 * @link      https://connections-pro.com
 * @copyright 2021 Steven A. Zahm
 *
 * @wordpress-plugin
 * Plugin Name:       Connections Business Directory Extension - Income Level
 * Plugin URI:        https://connections-pro.com/add-on/income-level/
 * Description:       An extension for the Connections plugin which adds a metabox for income levels.
 * Version:           2.0.1
 * Author:            Steven A. Zahm
 * Author URI:        https://connections-pro.com
 * License:           GPL-2.0+
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       connections_income_levels
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! class_exists('Connections_Income_Levels') ) {

	class Connections_Income_Levels {

		const VERSION = '2.0.1';

		/**
		 * @var Connections_Income_Levels Stores the instance of this class.
		 *
		 * @access private
		 * @since 1.1
		 */
		private static $instance;

		/**
		 * @var string The absolute path this this file.
		 *
		 * @access private
		 * @since 1.1
		 */
		private static $file = '';

		/**
		 * @var string The URL to the plugin's folder.
		 *
		 * @access private
		 * @since 1.1
		 */
		private static $url = '';

		/**
		 * @var string The absolute path to this plugin's folder.
		 *
		 * @access private
		 * @since 1.1
		 */
		private static $path = '';

		/**
		 * @var string The basename of the plugin.
		 *
		 * @access private
		 * @since 1.0
		 */
		private static $basename = '';

		public function __construct() { /* Do nothing here */ }

		/**
		 * @access public
		 * @since  1.1
		 *
		 * @return Connections_Income_Levels
		 */
		public static function instance() {

			if ( ! isset( self::$instance ) && ! ( self::$instance instanceof Connections_Income_Levels ) ) {

				self::$instance = $self = new self;

				self::$file       = __FILE__;
				self::$url        = plugin_dir_url( self::$file );
				self::$path       = plugin_dir_path( self::$file );
				self::$basename   = plugin_basename( self::$file );

				self::loadDependencies();
				self::hooks();

				/**
				 * This should run on the `plugins_loaded` action hook. Since the extension loads on the
				 * `plugins_loaded` action hook, load immediately.
				 */
				cnText_Domain::register(
					'connections_income_levels',
					self::$basename,
					'load'
				);

				// register_activation_hook( CNIL_BASE_NAME . '/connections_income_levels.php', array( __CLASS__, 'activate' ) );
				// register_deactivation_hook( CNIL_BASE_NAME . '/connections_income_levels.php', array( __CLASS__, 'deactivate' ) );
			}

			return self::$instance;
		}

		/**
		 * Gets the basename of a plugin.
		 *
		 * @access public
		 * @since  1.1
		 *
		 * @return string
		 */
		public function pluginBasename() {

			return self::$basename;
		}

		/**
		 * Get the absolute directory path (with trailing slash) for the plugin.
		 *
		 * @access public
		 * @since  1.1
		 *
		 * @return string
		 */
		public function pluginPath() {

			return self::$path;
		}

		/**
		 * Get the URL directory path (with trailing slash) for the plugin.
		 *
		 * @access public
		 * @since  1.1
		 *
		 * @return string
		 */
		public function pluginURL() {

			return self::$url;
		}

		/**
		 * Register all the hooks that makes this thing run.
		 *
		 * @access private
		 * @since  1.1
		 */
		private static function hooks() {

			// Register the metabox and fields.
			add_action( 'cn_metabox', array( __CLASS__, 'registerMetabox') );

			// Register the custom fields CSV Export attributes and processing callback.
			add_filter( 'cn_csv_export_fields_config', array( __CLASS__, 'registerCustomFieldCSVExportConfig' ) );
			add_filter( 'cn_export_header-income_level', array( __CLASS__, 'registerCSVExportFieldHeader' ), 10, 3 );
			add_filter( 'cn_export_field-income_level', array( __CLASS__, 'registerCustomFieldExportAction' ), 10, 4 );

			// Register the custom fields CSV Import mapping options and processing callback.
			add_filter( 'cncsv_map_import_fields', array( __CLASS__, 'registerCSVImportFieldHeader' ) );
			add_action( 'cncsv_import_fields', array( __CLASS__, 'registerCustomFieldImportAction' ), 10, 3 );

			// Add the income level option to the admin settings page.
			// This is also required so it'll be rendered by $entry->getContentBlock( 'income_level' ).
			add_filter( 'cn_content_blocks', array( __CLASS__, 'settingsOption') );

			// Add the action that'll be run when calling $entry->getContentBlock( 'income_level' ) from within a template.
			add_action( 'cn_output_meta_field-income_level', array( __CLASS__, 'block' ), 10, 4 );

			// Register the widget.
			add_action( 'widgets_init', array( 'CN_Income_Levels_Widget', 'register' ) );
		}

		/**
		 * The widget.
		 *
		 * @access private
		 * @since  1.0
		 * @static
		 * @return void
		 */
		private static function loadDependencies() {

			require_once( Connections_Income_Levels()->pluginPath() . 'includes/class.widgets.php' );
		}

		public static function activate() {}

		public static function deactivate() {}

		/**
		 * Defines the income level options.
		 *
		 * @access private
		 * @since  1.0
		 * @static
		 * @uses   apply_filters()
		 * @return array An indexed array containing the income levels.
		 */
		private static function levels() {

			$options = array(
				'-1'  => __( 'Choose...', 'connections_income_levels'),
				'1'   => __( 'Under $5,000', 'connections_income_levels'),
				'5'   => __( '$5,000 to $9,999', 'connections_income_levels'),
				'10'  => __( '$10,000 to $14,999', 'connections_income_levels'),
				'15'  => __( '$15,000 to $19,999', 'connections_income_levels'),
				'20'  => __( '$20,000 to $24,999', 'connections_income_levels'),
				'25'  => __( '$25,000 to $29,999', 'connections_income_levels'),
				'30'  => __( '$30,000 to $34,999', 'connections_income_levels'),
				'35'  => __( '$35,000 to $39,999', 'connections_income_levels'),
				'40'  => __( '$40,000 to $44,999', 'connections_income_levels'),
				'45'  => __( '$45,000 to $49,999', 'connections_income_levels'),
				'50'  => __( '$50,000 to $54,999', 'connections_income_levels'),
				'55'  => __( '$55,000 to $59,999', 'connections_income_levels'),
				'60'  => __( '$60,000 to $64,999', 'connections_income_levels'),
				'65'  => __( '$65,000 to $69,999', 'connections_income_levels'),
				'70'  => __( '$70,000 to $74,999', 'connections_income_levels'),
				'75'  => __( '$75,000 to $79,999', 'connections_income_levels'),
				'80'  => __( '$80,000 to $84,999', 'connections_income_levels'),
				'85'  => __( '$85,000 to $89,999', 'connections_income_levels'),
				'90'  => __( '$90,000 to $94,999', 'connections_income_levels'),
				'95'  => __( '$95,000 to $99,999', 'connections_income_levels'),
				'100' => __( '$100,000 to $104,999', 'connections_income_levels'),
				'105' => __( '$105,000 to $109,999', 'connections_income_levels'),
				'110' => __( '$110,000 to $114,999', 'connections_income_levels'),
				'115' => __( '$115,000 to $119,999', 'connections_income_levels'),
				'120' => __( '$120,000 to $124,999', 'connections_income_levels'),
				'125' => __( '$125,000 to $129,999', 'connections_income_levels'),
				'130' => __( '$130,000 to $134,999', 'connections_income_levels'),
				'135' => __( '$135,000 to $139,999', 'connections_income_levels'),
				'140' => __( '$140,000 to $144,999', 'connections_income_levels'),
				'145' => __( '$145,000 to $149,999', 'connections_income_levels'),
				'150' => __( '$150,000 to $154,999', 'connections_income_levels'),
				'155' => __( '$155,000 to $159,999', 'connections_income_levels'),
				'160' => __( '$160,000 to $164,999', 'connections_income_levels'),
				'165' => __( '$165,000 to $169,999', 'connections_income_levels'),
				'170' => __( '$170,000 to $174,999', 'connections_income_levels'),
				'175' => __( '$175,000 to $179,999', 'connections_income_levels'),
				'180' => __( '$180,000 to $184,999', 'connections_income_levels'),
				'185' => __( '$185,000 to $189,999', 'connections_income_levels'),
				'190' => __( '$190,000 to $194,999', 'connections_income_levels'),
				'195' => __( '$195,000 to $199,999', 'connections_income_levels'),
				'200' => __( '$200,000 to $249,999', 'connections_income_levels'),
				'250' => __( '$250,000 and over', 'connections_income_levels'),
			);

			return apply_filters( 'cn_income_level_options', $options );
		}

		/**
		 * Return the income level based on the supplied key.
		 *
		 * @access private
		 * @since  1.0
		 * @static
		 * @uses   levels()
		 * @param  string $level The key of the income level to return.
		 * @return mixed         bool | string	The incomes level if found, if not, FALSE.
		 */
		private static function income( $level = '' ) {

			if ( ! is_string( $level ) || empty( $level ) || $level === '-1' ) {

				return FALSE;
			}

			$levels = self::levels();
			$income = isset( $levels[ $level ] ) ? $levels[ $level ] : FALSE;

			return $income;
		}

		/**
		 * Callback for the `cn_csv_export_fields_config` filter.
		 *
		 * @access private
		 * @since  2.0
		 *
		 * @param array $fields
		 *
		 * @return array
		 */
		public static function registerCustomFieldCSVExportConfig( $fields ) {

			$fields[] = array(
				'field'  => 'income_level',
				'type'   => 'income_level',
				'fields' => '',
				'table'  => CN_ENTRY_TABLE_META,
				'types'  => NULL,
			);

			return $fields;
		}

		/**
		 * Callback for the `cn_export_header-income_level` action.
		 *
		 * @access private
		 *
		 * @param string                 $header
		 * @param array                  $field
		 * @param cnCSV_Batch_Export_All $export
		 *
		 * @return string
		 * @since  2.0
		 *
		 */
		public static function registerCSVExportFieldHeader( $header, $field, $export ) {

			$header = __( 'Income Level', 'connections_income_levels' );

			return $header;
		}

		/**
		 * Callback for the `cn_export_field-income_level` filter.
		 *
		 * @access private
		 * @since  2.0
		 *
		 * @param string                 $value
		 * @param object                 $entry
		 * @param array                  $field The field config array.
		 * @param cnCSV_Batch_Export_All $export
		 *
		 * @return string
		 */
		public static function registerCustomFieldExportAction( $value, $entry, $field, $export ) {

			if ( 'income_level' !== $field['field'] ) return $value;

			$value = '';
			$meta  = cnMeta::get( 'entry', $entry->id, $field['field'], TRUE );

			if ( ! empty( $meta ) ) {

				$data  = cnFormatting::maybeJSONencode( $meta );
				$level = self::income( $data );

				if ( FALSE !== $level ) {

					$value = $export->escapeAndQuote( $level );
				}
			}

			return $value;
		}

		/**
		 * Callback for the `cncsv_map_import_fields` filter.
		 *
		 * @access private
		 * @since  2.0
		 *
		 * @param array $fields
		 *
		 * @return array
		 */
		public static function registerCSVImportFieldHeader( $fields ) {

			$fields['income_level'] = __( 'Income Level', 'connections_income_levels' );

			return $fields;
		}

		/**
		 * Callback for the `cncsv_import_fields` action.
		 *
		 * @access private
		 * @since  2.0
		 *
		 * @param int         $id
		 * @param array       $row
		 * @param cnCSV_Entry $entry
		 */
		public static function registerCustomFieldImportAction( $id, $row, $entry ) {

			$meta  = array();
			$level = $entry->arrayPull( $row, 'income_level' );

			if ( ! is_null( $level ) ) {

				$result = array_search( $level, self::levels() );

				if ( FALSE !== $result ) {

					$meta[] = array(
						'key'   => 'income_level',
						'value' => $result,
					);

					cnEntry_Action::meta( 'update', $id, $meta );
				}
			}
		}

		/**
		 * Registered the custom metabox.
		 *
		 * @access private
		 * @since  1.0
		 * @static
		 * @uses   levels()
		 * @uses   cnMetaboxAPI::add()
		 * @return void
		 */
		public static function registerMetabox() {

			$atts = array(
				'name'     => 'Income Level',
				'id'       => 'income-level',
				'title'    => __( 'Income Level', 'connections_income_levels' ),
				'context'  => 'side',
				'priority' => 'core',
				'fields'   => array(
					array(
						'id'      => 'income_level',
						'type'    => 'select',
						'options' => self::levels(),
						'default' => '-1',
						),
					),
				);

			cnMetaboxAPI::add( $atts );
		}

		/**
		 * Add the custom meta as an option in the content block settings in the admin.
		 * This is required for the output to be rendered by $entry->getContentBlock().
		 *
		 * @access private
		 * @since  1.0
		 * @param  array  $blocks An associtive array containing the registered content block settings options.
		 * @return array
		 */
		public static function settingsOption( $blocks ) {

			$blocks['income_level'] =  __( 'Income Level', 'connections_income_levels' );

			return $blocks;
		}

		/**
		 * Callback for the `cn_output_meta_field-income_level` action.
		 *
		 * Renders the Income Levels content block.
		 *
		 * @internal
		 * @since 1.0
		 *
		 * @param string $id           The field id.
		 * @param array  $value        The income level ID.
		 * @param cnEntry_HTML $object Instance of the cnEntry object.
		 * @param array  $atts         The shortcode atts array passed from the calling action.
		 */
		public static function block( $id, $value, $object, $atts ) {

			if ( $income = self::income( $value ) ) {

				printf( '<div class="cn-income-level">%1$s</div>', esc_attr( $income ) );
			}
		}

	}

	/**
	 * Start up the extension.
	 *
	 * @access public
	 * @since 1.0
	 *
	 * @return Connections_Income_Levels|false
	 */
	function Connections_Income_Levels() {

		if ( class_exists( 'connectionsLoad' ) ) {

			return Connections_Income_Levels::instance();

		} else {

			add_action(
				'admin_notices',
				function() {
					echo '<div id="message" class="error"><p><strong>ERROR:</strong> Connections must be installed and active in order use Connections Income Level.</p></div>';
				}
			);

			return false;
		}
	}

	/**
	 * Since Connections loads at default priority 10, and this extension is dependent on Connections,
	 * we'll load with priority 11 so we know Connections will be loaded and ready first.
	 */
	add_action( 'plugins_loaded', 'Connections_Income_Levels', 11 );

}
