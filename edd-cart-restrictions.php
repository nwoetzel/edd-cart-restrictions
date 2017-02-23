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
     * For a given download_id (key) a list (array) of conflicting downloads is stored.
     *
     * @var         array
     * @see         getDownloadCartConflicts
     * @since       1.0.0
     */
   private $downloadCartConflicts = array();

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
            self::$instance->registerMeta();
            self::$instance->hooks();
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
        add_action( 'download_category_edit_form_fields', array( $this, 'editDownloadCategory'), 10, 2 );
        add_action( 'download_tag_edit_form_fields', array( $this, 'editDownloadCategory'), 10, 2 );
        add_action( 'download_category_edit_form_fields', array( $this, 'editDownloadTag'), 10, 2 );
        add_action( 'download_tag_edit_form_fields', array( $this, 'editDownloadTag'), 10, 2 );

        // save meta for edited download category or new download tag
        add_action( 'edited_download_category', array($this, 'saveTermFields'), 10, 2 );
        add_action( 'edited_download_tag', array($this, 'saveTermFields'), 10, 2 );

        // display excluded download category or download tag in term table
        add_filter('manage_edit-download_tag_columns', array($this,'addTermColumnHeader'),10,1);
        add_filter('manage_edit-download_category_columns', array($this,'addTermColumnHeader'),10,1);
        add_action('manage_download_category_custom_column', array($this,'addTermColumn'),10,3);
        add_action('manage_download_tag_custom_column', array($this,'addTermColumn'),10,3);

        // hide link when it might conflict with the current cart
        add_action('edd_purchase_link_top',array($this,'purchaseLinkTop'), 33, 2);
        add_action('edd_purchase_link_end',array($this,'purchaseLinkEnd'), 34, 2);

        // before adding item to cart
        add_action('edd_pre_add_to_cart', array($this,'preAddToCart'), 10, 2 );
        add_action('edd_post_add_to_cart', array($this,'clearDownloadCartConflicts'), 10, 3 );
//        add_action('wp_ajax_edd_add_to_cart',array($this,'ajaxAddToCart'), 9, 0 );
//        add_action('wp_ajax_nopriv_edd_add_to_cart',array($this,'ajaxAddToCart'), 9, 0 );

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

    /**
     * Add a settings section.
     * uses edd filter: edd_settings_sections_extensions
     *
     * @access      public
     * @since       1.0.0
     * @param       array $settings The existing EDD settings array
     * @return      array The modified EDD settings array
     */
    public function settingsSection( $sections ) {
        $sections['cart-restrictions-settings-general'] = 'Cart Restrictions General';
        return $sections;
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

    /**
     * Register meta keys for terms, which are used for the download_category and download_tag.
     * @see https://developer.wordpress.org/reference/functions/register_meta/ 
     *
     * @access      protected
     * @since       1.0.0
     * @return      void
     */
    protected function registerMeta() {
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

    /**
     * Echo a field to choose download_category to a new term add form.
     * uses wp action: download_category_add_form_field and download_tag_add_form_field
     * @see https://developer.wordpress.org/reference/hooks/taxonomy_add_form_fields/ 
     *
     * @access      public
     * @since       1.0.0
     * @param       $taxonomy string The taxonomy slug
     * @return      void
     */
    public function addDownloadCategory($taxonomy) {
        $field  = '<div class="form-field">';
        $field .= '    <label>Excluded Categories in Cart</label>';
        $field .= self::termChecklist($term, 'download_category', self::CATEGORIES_META_KEY);
        $field .= '    <p class="description">Select all categories of which downloads cannot be in the cart at the same time.</p>';
        $field .= '</div>';

        echo $field;
    }

    /**
     * Echo a field to choose download_category to an existing term edit form.
     * uses wp action: download_category_edit_form_fields and download_tag_edit_form_fields
     * @see https://developer.wordpress.org/reference/hooks/taxonomy_edit_form_fields/ 
     *
     * @access      public
     * @since       1.0.0
     * @param       $term object current taxonomy term object
     * @param       $taxonomy string The taxonomy slug
     * @return      void
     */
    public function editDownloadCategory( $term, $taxonomy ) {
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

    /**
     * Echo a field to choose download_tag to a new term add form.
     * uses wp action: download_category_add_form_field and download_tag_add_form_field
     * @see https://developer.wordpress.org/reference/hooks/taxonomy_add_form_fields/ 
     *
     * @access      public
     * @since       1.0.0
     * @param       $taxonomy string The taxonomy slug
     * @return      void
     */
    public function addDownloadTag($taxonomy) {
        $field  = '<div class="form-field">';
        $field .= '    <label>Excluded Tags in Cart</label>';
        $field .= self::termChecklist($term, 'download_tag', self::CATEGORIES_META_KEY);
        $field .= '    <p class="description">Select all tags of which downloads cannot be in the cart at the same time.</p>';
        $field .= '</div>';

        echo $field;
    }

    /**
     * Echo a field to choose download_tag to an existing term edit form.
     * uses wp action: download_category_edit_form_fields and download_tag_edit_form_fields
     * @see https://developer.wordpress.org/reference/hooks/taxonomy_edit_form_fields/ 
     *
     * @access      public
     * @since       1.0.0
     * @param       $term object current taxonomy term object
     * @param       $taxonomy string The taxonomy slug
     * @return      void
     */
    public function editDownloadTag( $term, $taxonomy ) {
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

    /**
     * save taxonomy input for download_category and download_tag that was chosen for a new or edited term.
     * uses wp action: edited_download_category and edited_download_tag
     * @see https://developer.wordpress.org/reference/hooks/edited_taxonomy/ 
     *
     * @access      public
     * @since       1.0.0
     * @param       $term_id int term id
     * @param       $tt_id int term taxonomy id
     * @return      void
     */
    public function saveTermFields($term_id, $tt_id) {
        $excluded_categories = array();
        $excluded_tags = array();
        if ( isset( $_POST['tax_input'] ) ) {
            if ( isset( $_POST['tax_input']['download_category'] ) ) {
                $excluded_categories = $_POST['tax_input']['download_category'];
            }
            if ( isset( $_POST['tax_input']['download_tag'] ) ) {
                $excluded_tags = $_POST['tax_input']['download_tag'];
            }
        }

        update_term_meta($term_id,self::CATEGORIES_META_KEY, $excluded_categories);
        update_term_meta($term_id,self::TAGS_META_KEY, $excluded_tags);
    }

    /**
     * Display a list of checkboxes of terms for the taxonomy, selecting the ones that are saved in the term's meta.
     * @see  addDownloadCategory, addDownloadTag, editDownloadCategory, editDownloadTag
     *
     * @access      protected
     * @since       1.0.0
     * @param       $term object current taxonomy term object
     * @param       $taxonomy string taxonomy slug
     * @param       $meta_key string the meta_key to retrive the currently checked fields
     * @return      string the html with checkboxes with each term of the taxonomy with checkboxes checked, when they are stored in the term's meta
     */
    protected static function termChecklist( $term, $taxonomy, $meta_key ) {
        $term_id = $term->term_id;
        $terms = get_term_meta( $term_id, $meta_key, true );

        $field  = '';
        $field .= '    <ul>';
        $field .= wp_terms_checklist(0,array('taxonomy' => $taxonomy,'selected_cats'=>$terms,'echo'=>false,));
        $field .= '    </ul>';

        return $field;
    }

    /**
     * Add a custom column heading with key and description for taxonomy tables.
     * uses wp action: manage_edit-download_category_columns and manage_edit-download_tag_columns
     * @see  https://codex.wordpress.org/Plugin_API/Filter_Reference/manage_$taxonomy_id_columns
     *
     * @access      public
     * @since       1.0.0
     * @param       $columns array column_name => Heading
     * @return      array $columns extended by the cart_restriction
     */
    public function addTermColumnHeader($columns) {
        return array_merge($columns, array('cart_restrictions'=>'Cart Excluded'));
    }

    /**
     * Add content to the custom column 'cart_restrictions'.
     * uses wp filter: manage_download_category_custom_column and manage_download_tag_custom_column 
     * @see https://developer.wordpress.org/reference/hooks/manage_this-screen-taxonomy_custom_column/
     *
     * @access      public
     * @since       1.0.0
     * @param       $content string the content of the cell
     * @param       $column_name string the column name manipulated
     * @param       $term_id int the current term (i.e. row)
     * @return      string the modified content
     */
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

    /**
     * Get term names for a given taxonomy and a list of term ids.
     * @see https://developer.wordpress.org/reference/functions/get_terms/
     *
     * @access      protected 
     * @since       1.0.0
     * @param       $taxonomy string the taxonomy slug
     * @param       $include int[] term ids
     * @return      string[] the names of the terms in $include for that taxonomy
     */
    protected static function termNames($taxonomy,$include) {
        if(empty($include)) {
            return array();
        }

        return get_terms( array(
            'taxonomy' => $taxonomy,
            'include' => $include,
            'fields' => 'names',
        ));
    }

    /**
     * Echo HTML that comes before the purchase link/button. Will hide the link, if the download conflicts with downloads in the cart.
     * uses edd action: edd_purchase_link_top
     * @see http://docs.easydigitaldownloads.com/article/510-edd-purchase-link-top
     * @see purchaseLinkEnd
     *
     * @access      public
     * @since       1.0.0 
     * @param       $download_id int the id of the download for the purchase link
     * @param       $args array information about the purchase linke
     * @return      void
     */
    public function purchaseLinkTop($download_id, $args) {
        $conflicts = $this->getDownloadCartConflicts($download_id);
        if (empty($conflicts)) {
            return;
        }
        echo '<a class="button red edd-submit">This download cannot be added due to '.(count($conflicts)).' conflicting downloads in the cart</a>';
        echo $this->conflictsTable($conflicts);
        echo '<div class="edd-cart-restrictions" style="display: none;">';
    }

    protected function conflictsTable($conflicts) {
        if ( empty( $conflicts)) {
            return '';
        }

        $table  = '<table>';
        $table .= '  <thead>';
        $table .= '    <tr>';
        $table .= '      <th>Conflicting download in cart</th>';
        $table .= '    </tr>';
        $table .= '  </thead>';
        $downloads = get_posts(array('post_type'=>'download','include'=>$conflicts,));
        foreach ($downloads as $download) {
            $table .= '    <tr>';
            $table .= '      <td><a href="'.esc_url( get_permalink($download)).'">'.$download->post_title.'</a></td>';
            $table .= '    </tr>';
        }
        $table .= '  <tbody>';
        $table .= '  </tbody>';
        $table .= '</table>';

        return $table;
    }

    /**
     * Echo HTML that comes after the purchase link/button. Will close the html tag if necessary, if the download conflicts with downloads in the cart.
     * uses edd action: edd_purchase_link_end
     * @see http://docs.easydigitaldownloads.com/article/509-edd-purchase-link-end
     * @see purchaseLinkTop
     *
     * @access      public
     * @since       1.0.0 
     * @param       $download_id int the id of the download for the purchase link
     * @param       $args array information about the purchase linke
     * @return      void
     */
    public function purchaseLinkEnd($download_id, $args) {
        $conflicts = $this->getDownloadCartConflicts($download_id);
        if (empty($conflicts)) {
            return;
        }
        echo '</div>';
    }

    /**
     * Check if a download can be added to the cart by checking if there are any conflicts with items in the cart.
     * If there is a conflict, calls edd_die(), otherwise returns.
     * use edd action: edd_pre_add_to_cart
     * @see https://github.com/easydigitaldownloads/easy-digital-downloads/blob/2.6.17/includes/cart/functions.php#L172
     *
     * @access      public
     * @since       1.0.0 
     * @param       $download_id int the id of the download for the purchase link
     * @param       $options the options for the download to add
     * @return      void
     */
    public function preAddToCart($download_id, $options) {
        $conflicts = $this->getDownloadCartConflicts($download_id);

        if ( !empty($conflicts)) {
            edd_die();
        }

        return;
    }

    /**
     * for an ajax call, terminate if the $_POST['download_id'] conflicts with downloads in the cart.
     * Used in edd action: wp_ajax_nopriv_edd_add_to_cart and wp_ajax_edd_add_to_cart
     * @see         preAddToCart
     *
     * @access      public
     * @since       1.0.0
     * @return      void
     */
    public function ajaxAddToCart() {
        if ( !isset( $_POST['download_id'] ) ) {
            return;
        }

        $download_id = $_POST['download_id'];

        $this->preAddToCart($download_id,array());
    }

    /**
     * clear the conflicts when the cart has changed
     * use edd action: edd_post_add_to_cart
     * @see https://github.com/easydigitaldownloads/easy-digital-downloads/blob/2.6.17/includes/cart/functions.php#L273
     * @see getDownloadCartConflicts
     *
     * @access      public
     * @since       1.0.0
     * @param       $download_id int post_id of the download that was added
     * @param       $options array options for the download added
     * @param       $items array items added to the cart
     * @return      void
     */
    public function clearDownloadCartConflicts($download_id, $options, $items) {
      $this->downloadCartConflicts = array();
    }

    /**
     * For a $download_id check if there are any downloads in the cart, that might conflict through the excluded tags or categories.
     * Caches the information for a download, if it is called multiple times.
     *
     * @access      protected
     * @since       1.0.0
     * @param       $download_id int the download post id that might conflict with items in the cart
     * @return      int[] conflicting donwload ids in the cart
     */
    protected function getDownloadCartConflicts($download_id) {
        $download_id = intval($download_id);
        if ( array_key_exists($download_id,$this->downloadCartConflicts)) {
            return $this->downloadCartConflicts[$download_id];
        }

        $conflicts = array();

        // get the categories and tags for the current download
        list($download_category_ids,$download_tag_ids) = self::downloadCategoryAndTagIds($download_id);

        // if the current download has neither categories nor tags, there is no conflict
        if ( empty($download_category_ids) && empty($download_tag_ids) ) {
            return $this->downloadCartConflicts[$download_id] = $conflicts;
        }

        // http://docs.easydigitaldownloads.com/article/1414-edd-get-cart-contents
        $cart_contents = edd_get_cart_contents();

        // if there is nothing in the cart, there is no conflict
        if (empty($cart_contents)) {
            return $this->downloadCartConflicts[$download_id] = $conflicts;
        }

        // all excluded categories and tags for the current download
        list($download_excluded_category_ids, $download_excluded_tag_ids) = self::excludedCategoryAndTagIds($download_category_ids,$download_tag_ids);

        // test against each individual download in the cart
        foreach ($cart_contents as $item) {
            list($category_ids,$tag_ids) = self::downloadCategoryAndTagIds(intval($item['id']));

            // check if the present categories and tags do conflict with the download
            list($conflicting_category_ids,$conflicting_tag_ids) = self::conflictingCategoriesAndTags($category_ids,$tag_ids,$download_excluded_category_ids, $download_excluded_tag_ids);

            if ( !empty($conflicting_category_ids) || !empty($conflicting_tag_ids)) {
                $conflicts[] = intval($item['id']);
            }

            list($excluded_category_ids, $excluded_tag_ids) = self::excludedCategoryAndTagIds($category_ids,$tag_ids);
            list($conflicting_category_ids,$conflicting_tag_ids) = self::conflictingCategoriesAndTags($download_category_ids,$download_tag_ids,$excluded_category_ids, $excluded_tag_ids);

            if ( !empty($conflicting_category_ids) || !empty($conflicting_tag_ids)) {
                $conflicts[] = intval($item['id']);
            }
        }

        return $this->downloadCartConflicts[$download_id] = $conflicts;
    }

    /**
     * Get all assigned download_category and download_tag ids to a download post.
     *
     * @access      protected
     * @since       1.0.0 
     *
     * @param       $download_id int the download in question
     * @return      array of array of download_category ids and download_tag ids
     */
    protected static function downloadCategoryAndTagIds($download_id) {
        $download_category_ids = wp_get_post_terms($download_id, 'download_category',array(
                'taxonomy' => 'download',
                'fields' => 'ids',
        ));
        $download_tag_ids = wp_get_post_terms($download_id, 'download_tag',array(
                'taxonomy' => 'download',
                'fields' => 'ids',
        ));

        return array($download_category_ids,$download_tag_ids);
    }

    /**
     * Comulate all excluded download_category and download_tag ids for a given list of download_category and download_tag ids using the meta data for excluded terms.
     *
     * @access      protected
     * @since       1.0.0 
     *
     * @param       $download_category_ids int[] all download_category ids for a download
     * @param       $download_tag_ids int[] all download_tag ids for a download
     * @return      array of array of excluded download_category ids and download_tag ids
     */
    protected static function excludedCategoryAndTagIds($download_category_ids,$download_tag_ids) {
        $download_excluded_category_ids = array();
        $download_excluded_tag_ids = array();

        foreach (array_merge($download_category_ids,$download_tag_ids) as $id) {
            $excluded_category_ids = get_term_meta( $id, self::CATEGORIES_META_KEY, true );
            $excluded_tag_ids = get_term_meta( $id, self::TAGS_META_KEY, true );

            if( !empty($excluded_category_ids)) {
                $download_excluded_category_ids = array_unique(array_merge($download_excluded_category_ids,$excluded_category_ids));
            }

            if( !empty($excluded_tag_ids)) {
                $download_excluded_tag_ids = array_unique(array_merge($download_excluded_tag_ids,$excluded_tag_ids));
            }
        }

        return array($download_excluded_category_ids,$download_excluded_tag_ids);
    }

    /**
     * Given a list of category ids (new) and category ids (excluded) derived the intersect. Similarly for tag ids. If the resulting sets are empty, there is no conflict
     *
     * @access      protected
     * @since       1.0.0 
     * @param       $categories_ids_new int[] ids for download_category of a download that should be added to the cart
     * @param       $tags_ids_new int[] ids for download_tag of a download that should be added to the cart
     * @param       $categories_ids_excluded int[] ids for download_category that are derived from the meta_data of terms of a download that is already in the cart
     * @param       $tag_ids_excluded int[] ids for download_tag that are derived from the meta_data of terms of a download that is already in the cart
     * @return      array of arrays of the intersect between ids_new and ids_exluded (for category and tag each)
     */ 
    protected function conflictingCategoriesAndTags($categories_ids_new, $tags_ids_new, $categories_ids_excluded, $tags_ids_excluded) {
        return array(
            array_intersect($categories_ids_new,$categories_ids_excluded),
            array_intersect($tags_ids_new,$tags_ids_excluded),
        );
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
