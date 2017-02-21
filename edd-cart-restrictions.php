<?php
// based on https://github.com/easydigitaldownloads/EDD-Extension-Boilerplate
/**
 * Plugin Name: Easy Digital Downloads Cart restrictions
 * Plugin URI:  https://github.com/nwoetzel/edd-cart-restrictions
 * Description: This plugin extends easy-digital-downloads to restrict the items that can be added to the cart.
 * Version:     1.0.0
 * Author:      Nils Woetzel
 * Author URI:  https://github.com/nwoetzel
 * Text Domain: edd-cart-restrictions
 */

// Exit if accessed directly
if( !defined( 'ABSPATH' ) ) exit;

if( !class_exists( 'EDD_Cart_Restrictions' ) ) {

/**
 * Main EDD_Cart_Restrictions class
 *
 * @since 1.0.0
 */
class EDD_Cart_Restrictions {

    /**
     * @var EDD_Cart_Restrictions $instance The one true EDD_Cart_Restrictions
     * @since       1.0.0
     */
    private static $instance;

    /**
     * Get active instance
     *
     * @access      public
     * @since       1.0.0
     * @return      object self::$instance The one true EDD_Cart_Restrictions
     */
    public static function instance() {
        if( !self::$instance ) {
            self::$instance = new EDD_Cart_Restrictions();
            self::$instance->setup_constants();
            self::$instance->includes();
//            self::$instance->load_textdomain();
            self::$instance->hooks();
            self::pllRegisterStrings();
        }
        return self::$instance;
    }

    /**
     * Setup plugin constants
     *
     * @access      private
     * @since       1.0.0
     * @return      void
     */
    private function setup_constants() {
        // Plugin version
        define( 'EDD_CART_RESTRICTIONS_VER', '1.0.0' );
        // Plugin path
        define( 'EDD_CART_RESTRICTIONS_DIR', plugin_dir_path( __FILE__ ) );
        // Plugin URL
        define( 'EDD_CART_RESTRICTIONS_URL', plugin_dir_url( __FILE__ ) );
    }

    /**
     * Include necessary files
     *
     * @access      private
     * @since       1.0.0
     * @return      void
     */
    private function includes() {
        // Include scripts
//        require_once EDD_CART_RESTRICTIONS_DIR . 'includes/scripts.php';
//        require_once EDD_CART_RESTRICTIONS_DIR . 'includes/functions.php';
        /**
         * @todo        The following files are not included in the boilerplate, but
         *              the referenced locations are listed for the purpose of ensuring
         *              path standardization in EDD extensions. Uncomment any that are
         *              relevant to your extension, and remove the rest.
         */
//        require_once EDD_CART_RESTRICTIONS_DIR . 'includes/shortcodes.php';
//        require_once EDD_CART_RESTRICTIONS_DIR . 'includes/widgets.php';
    }

    /**
     * Run action and filter hooks
     *
     * @access      private
     * @since       1.0.0
     * @return      void
     */
    private function hooks() {
        // Register settings
        add_filter('edd_settings_extensions', array( $this, 'settings' ), 1 );
        add_filter('edd_settings_sections_extensions', array( $this, 'settingsSection') );

        // display form fields for new download category or download tag
        add_action( 'download_category_add_form_fields', array($this, 'addDownloadCategory' ) );
        add_action( 'download_category_add_form_fields', array($this, 'addDownloadTag' ) );
        add_action( 'download_tag_add_form_fields', array($this, 'addDownloadCategory' ) );
        add_action( 'download_tag_add_form_fields', array($this, 'addDownloadTag' ) );

        // save meta for new download category or new download tag
        add_action( 'create_download_category', array($this, 'saveTermFields'), 10, 2 );
        add_action( 'create_download_tag', array($this, 'saveTermFields'), 10, 2 );

        //  display form fields for editing download category or download tag
        add_action( 'download_category_edit_form_fields', array( $this, 'editDownloadCategory') );
        add_action( 'download_tag_edit_form_fields', array( $this, 'editDownloadCategory') );
        add_action( 'download_category_edit_form_fields', array( $this, 'editDownloadTag') );
        add_action( 'download_tag_edit_form_fields', array( $this, 'editDownloadTag') );

        // save meta for edited download category or new download tag
        add_action( 'edited_download_category', array($this, 'saveTermFields'), 10, 2 );
        add_action( 'edited_download_tag', array($this, 'saveTermFields'), 10, 2 );

        // display excluded download category or download tag in term table
        add_filter('manage_edit-download_tag_columns', array($this,'addTermColumnHeader'),10,1);
        add_filter('manage_edit-download_category_columns', array($this,'addTermColumnHeader'),10,1);
        add_action('manage_download_category_custom_column', array($this,'addTermColumn'),10,3);
        add_action('manage_download_tag_custom_column', array($this,'addTermColumn'),10,3);

        // Handle licensing
//        if( class_exists( 'EDD_License' ) ) {
//            $license = new EDD_License( __FILE__, 'VC Integration', EDD_VC_INTEGRATION_VER, 'Nils Woetzel' );
//        }
    }

    /**
     * Add settings
     *
     * @access      public
     * @since       1.0.0
     * @param       array $settings The existing EDD settings array
     * @return      array The modified EDD settings array
     */
    public function settings( $settings ) {
        $plugin_settings_general = array(
            array(
                'id'    => 'edd_cart_restrictions_settings_general',
                'name'  => '<strong>' . __( 'Cart Restrictions General', 'edd-cart-restrictions' ) . '</strong>',
                'desc'  => __( 'Configure general cart restriction settings', 'edd-cart-restrictions' ),
                'type'  => 'header',
            ),
            array(
                'id'    => 'edd_cart_restrictions_settings_categories_tags_enabled',
                'name'  => 'Restrictions on categories and tags enabled',
                'desc'  => 'Enable category and tag based restrictions',
                'type'  => 'checkbox',
                'settings' => array('edd_cart_restrictions_settings_categories_tags'),
            ),
        );

	// If EDD is at version 2.5 or later...
	if ( version_compare( EDD_VERSION, 2.5, '>=' ) ) {
            // Use the previously noted array key as an array key again and next your settings
            $plugin_settings_general = array( 'cart-restrictions-settings-general' => $plugin_settings_general );
        }

        return array_merge( $settings, $plugin_settings_general );
    }

    public function settingsSection( $sections ) {
        $sections['cart-restrictions-settings-general'] = 'Cart Restrictions General';
        return $sections;
    }

    public function __construct() {
        self::pllRegisterStrings();
    }

    protected static function pllRegisterStrings() {
       if (!function_exists('pll_register_string')) {
           return;
       }
//       pll_register_string( $field.' '.$key, $values[$key], 'HITS EDD');
    }

    protected static function trans($string) {
        if (!function_exists('pll__')) return $string;
        return pll__($string);
    }

    /**
     * define the meta key for excluded categories
     * @since 1.0.0
     * @var string
     */
    CONST CATEGORIES_META_KEY = '_edd_cart_restrictions_categories';

    /**
     * define the meta key for excluded tags
     * @since 1.0.0
     * @var string
     */
    CONST TAGS_META_KEY = '_edd_cart_restrictions_tags';


    public function registerMeta() {
        register_meta( 'term', self::CATEGORIES_META_KEY,
            array(
                'type' => array(),
                'description' => 'Cannot add a download to the cart, if a download with any of the categories is already in the cart.',
                'single' => true,
                'show_in_rest' => true,
        ) );
        register_meta( 'term', self::TAGS_META_KEY,
            array(
                'type' => array(),
                'description' => 'Cannot add a download to the cart, if a download with any of the tags is already in the cart.',
                'single' => true,
                'show_in_rest' => true,
        ) );
    }

    public function addDownloadCategory( ) {
        $field  = '<div class="form-field">';
        $field .= '    <label>Excluded Categories in Cart</label>';
        $field .= self::termChecklist($term, 'download_category', self::CATEGORIES_META_KEY);
        $field .= '    <p class="description">Select all categories of which downloads cannot be in the cart at the same time.</p>';
        $field .= '</div>';

        echo $field;
    }

    /**
     * Add a excluded category field to the edit form of a term (category or tag)
     */
    public function editDownloadCategory( $term ) {
        $field  = '<tr class="form-field">';
        $field .= '<th scope="row">';
        $field .= '    <label>Excluded Categories in Cart</label>';
        $field .= '</th>';
        $field .= '    <td>';
        $field .= self::termChecklist($term, 'download_category', self::CATEGORIES_META_KEY);
        $field .= '    <p class="description">Select all categories of which downloads cannot be in the cart at the same time.</p>';
        $field .= '    </td>';
        $field .= '</tr>';

        echo $field;
    }

    public function addDownloadTag( ) {
        $field  = '<div class="form-field">';
        $field .= '    <label>Excluded Tags in Cart</label>';
        $field .= self::termChecklist($term, 'download_tag', self::CATEGORIES_META_KEY);
        $field .= '    <p class="description">Select all tags of which downloads cannot be in the cart at the same time.</p>';
        $field .= '</div>';

        echo $field;
    }

    /**
     * Add a excluded tag field to the edit form of a term (category or tag)
     */
    public function editDownloadTag( $term ) {
        $field  = '<tr class="form-field">';
        $field .= '<th scope="row">';
        $field .= '    <label>Excluded Tags in Cart</label>';
        $field .= '</th>';
        $field .= '    <td>';
        $field .= self::termChecklist($term, 'download_tag', self::TAGS_META_KEY);
        $field .= '    <p class="description">Select all tags of which downloads cannot be in the cart at the same time.</p>';
        $field .= '    </td>';
        $field .= '</tr>';

        echo $field;
    }

    public function saveTermFields($term_id, $taxonomy) {
        if ( isset( $_POST['tax_input'] ) && isset( $_POST['tax_input']['download_category'] ) ) {
            update_term_meta($term_id,self::CATEGORIES_META_KEY, $_POST['tax_input']['download_category']);
        }

        if ( isset( $_POST['tax_input'] ) && isset( $_POST['tax_input']['download_tag'] ) ) {
            update_term_meta($term_id,self::TAGS_META_KEY, $_POST['tax_input']['download_tag']);
        }
    }

    protected static function termChecklist( $term, $taxonomy, $meta_key ) {
        $term_id = $term->term_id;
        $terms = get_term_meta( $term_id, $meta_key, true );

        $field  = '';
        $field .= '    <ul>';
        $field .= wp_terms_checklist(0,array('taxonomy' => $taxonomy,'selected_cats'=>$terms,'echo'=>false,));
        $field .= '    </ul>';

        return $field;
    }

    public function addTermColumnHeader($columns) {
        return array_merge($columns, array('cart_restrictions'=>'Cart Excluded'));
    }

    public function addTermColumn($content, $column_name, $term_id) {
        if ( $column_name != 'cart_restrictions' ) {
            return $content;
        }
        $categories_ids = get_term_meta( $term_id, self::CATEGORIES_META_KEY, true );
        $tags_ids = get_term_meta( $term_id, self::TAGS_META_KEY, true );

        $categories_names = self::termNames('download_category',$categories_ids);
        $tags_names = self::termNames('download_tag',$tags_ids);

        echo '<strong>Categories:</strong><br>'.join(', ',$categories_names).'<br>';
        echo '<strong>Tags:</strong><br>'.join(', ',$tags_names);

        return $content;
    }

    protected static function termNames($taxonomy,$include) {
        return get_terms( array(
            'taxonomy' => $taxonomy,
            'include' => $include,
            'fields' => 'names',
        ));
    }

}

} // End if class_exists check

/**
 * The main function responsible for returning the one true EDD_Cart_Restrictions
 * instance to functions everywhere
 *
 * @since       1.0.0
 * @return      \EDD_Cart_Restrictions The one true EDD_Cart_Restrictions
 */
function edd_cart_restrictions_load() {
    if( ! class_exists( 'Easy_Digital_Downloads' ) ) {
        if( ! class_exists( 'EDD_Extension_Activation' ) ) {
            require_once 'includes/class.extension-activation.php';
        }
        $activation = new EDD_Extension_Activation( plugin_dir_path( __FILE__ ), basename( __FILE__ ) );
        $activation = $activation->run();
    } else {
        return EDD_Cart_Restrictions::instance();
    }
}
add_action( 'plugins_loaded', 'edd_cart_restrictions_load' );

/**
 * The activation hook is called outside of the singleton because WordPress doesn't
 * register the call from within the class, since we are preferring the plugins_loaded
 * hook for compatibility, we also can't reference a function inside the plugin class
 * for the activation function. If you need an activation function, put it here.
 *
 * @since       1.0.0
 * @return      void
 */
function edd_cart_restrictions_activation() {
    /* Activation functions here */
}
register_activation_hook( __FILE__, 'edd_cart_restrictions_activation' );

/**
 * A nice function name to retrieve the instance that's created on plugins loaded
 *
 * @since 1.0.0
 * @return \EDD_Cart_Restrictions
 */
function edd_cart_restriction() {
    return edd_cart_restrictions_load();
}
