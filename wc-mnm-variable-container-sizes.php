<?php
/**
 * Plugin Name: WooCommerce Mix and Match -  Variable Container Sizes
 * Plugin URI: http://www.woocommerce.com/products/woocommerce-mix-and-match-products/
 * Description: Validate container by chosen container size quantity
 * Version: 1.0.0-beta
 * Author: Kathy Darling
 * Author URI: http://kathyisawesome.com/
 * Developer: Kathy Darling
 * Developer URI: http://kathyisawesome.com/
 * Text Domain: wc-mnm-variable-container-sizes
 * Domain Path: /languages
 *
 * Copyright: © 2020 Kathy Darling
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */



/**
 * The Main WC_MNM_Variable_Container_Sizes class
 **/
if ( ! class_exists( 'WC_MNM_Variable_Container_Sizes' ) ) :

class WC_MNM_Variable_Container_Sizes {

	/**
	 * @constants
	 */
	CONST VERSION = '1.0.0';
	CONST REQUIRED_WOO = '3.3.0';

	/**
	 * @var
	 */
	private static $size = '';

	/**
	 * WC_MNM_Variable_Container_Sizes Constructor
	 *
	 * @access 	public
     * @return 	WC_MNM_Variable_Container_Sizes
	 */
	public static function init() {

		// Load translation files.
		add_action( 'init', array( __CLASS__, 'load_plugin_textdomain' ) );

		// Display the box size input on the front end.
		add_action( 'woocommerce_before_mnm_items', array( __CLASS__, 'add_quantity_dropdown' ), -10 );

		// Store min/max sizes on product.
		add_filter( 'woocommerce_add_cart_item_data', array( __CLASS__, 'add_cart_item_data' ), 10, 2 );
		add_filter( 'woocommerce_get_cart_item_from_session', array( __CLASS__, 'get_cart_item_from_session' ), 10, 2 );
		add_filter( 'woocommerce_add_cart_item', array( __CLASS__, 'add_cart_item_filter' ) );

		// Modify min/max sizes for add to cart validation.
		add_action( 'woocommerce_add_to_cart_validation', array( __CLASS__, 'add_filter' ), 1, 6 );
		add_filter( 'woocommerce_add_to_cart_validation', array( __CLASS__, 'remove_filter' ), 99, 6 );

    }


	/*-----------------------------------------------------------------------------------*/
	/* Localization */
	/*-----------------------------------------------------------------------------------*/


	/**
	 * Make the plugin translation ready
	 *
	 * @return void
	 */
	public static function load_plugin_textdomain() {
		load_plugin_textdomain( 'wc-mnm-variable-container-sizes' , false , dirname( plugin_basename( __FILE__ ) ) .  '/languages/' );
	}

	/*-----------------------------------------------------------------------------------*/
	/* Front End Display */
	/*-----------------------------------------------------------------------------------*/

	/**
	 * Display size options
	 * @param obj $parent_product - the container product
	 */
	public static function add_quantity_dropdown( $parent_product ) { 

		$options = array( 4, 8, 12 );

		$default = isset( $_REQUEST['_mnm_container_size' ] ) ? $_REQUEST['_mnm_container_size' ] : ''; ?>

		<p>
			<label for="_mnm_container_size"><?php _e( 'Box size', 'wc-mnm-variable-container-sizes' ); ?></label>
			<select name="_mnm_container_size" class="container_size">

				<?php
				foreach( $options as $option ) {
					printf( '<option value="%d" %s>%d</option>', $option, selected( $option, $default, false ), $option );
				}
				?>
			</select>
			<span class="description"><?php __( 'Select the size of your box', 'wc-mnm-variable-container-sizes' ); ?></span>

		</p>
	
		<?php
		add_action( 'wp_print_footer_scripts', array( __CLASS__, 'footer_scripts' ) );

	}

	/*-----------------------------------------------------------------------------------*/
	/* Scripts */
	/*-----------------------------------------------------------------------------------*/

	/**
	 * Print scripts in the footer
	 */
	public static function footer_scripts() { ?>

		<script type="text/javascript">

			( function( $ ) {
				
				$('body').on( 'wc-mnm-initializing', function( event, container ) {

					container.$mnm_form.on( 'change', '.container_size', function(e) {
						var qty = $(this).val();

						// Set container size to chosen value;
						container.min_container_size = qty;
						container.max_container_size = qty;

						container.$mnm_items.each( function(i) {
							// Change the max input to the container size. This doesn't work well if you're filtering the max for some other reason.
							var $input = $(this).find('.qty').attr('max', qty);
							// If the child products' quantity is higher than the current max, downgrade it.
							if( $input.val() > qty ) {
								$input.val(qty).change();
							}
						});

						container.update_container();
						
					});

					$('.container_size').trigger( 'change' );

				});

			} ) ( jQuery );
		</script>

		<?php
	}

	/*-----------------------------------------------------------------------------------*/
	/* Cart Functions */
	/*-----------------------------------------------------------------------------------*/

	/**
	 * Add cart session data
	 * 
	 * @param array $cart_item_data extra cart item data we want to pass into the item.
	 * @param int   $product_id contains the id of the product to add to the cart.
	 * @return array
	 * @since 1.0
	 */
	public static function add_cart_item_data( $cart_item_data, $product_id ) {

		$product_type = WC_Product_Factory::get_product_type( $product_id );

		// Is this a MNM product?
		if ( 'mix-and-match' === $product_type && isset( $_REQUEST[ '_mnm_container_size' ] ) ) {
			$cart_item_data['mnm_container_size'] = intval( $_REQUEST[ '_mnm_container_size' ] );
		}

		return $cart_item_data;
	}

	/**
	 * Adjust the product based on cart session data
	 *
	 * @param  array $cart_item $cart_item['data'] is product object in session
	 * @param  array $values cart item array
	 * @since 1.0
	 */
	public static function get_cart_item_from_session( $cart_item, $values ) {

		// Stash size in cart data.
		if ( isset( $values['mnm_container_size'] ) ) {
			$cart_item['mnm_container_size'] = $values['mnm_container_size'];

			$cart_item = self::add_cart_item_filter( $cart_item );
		}

		return $cart_item;
	}

	/**
	 * Change the sizes of the min/max of the container in the cart.
	 *
	 * @param  array 	$cart_item
	 * @return array
	 */
	public static function add_cart_item_filter( $cart_item ) {

		$the_product = $cart_item['data'];

		// Is this a MNM product with a custom container size?
		if ( $the_product->is_type( 'mix-and-match' ) && isset( $cart_item[ 'mnm_container_size' ] ) ) {

			if( is_callable( array( $the_product, 'get_min_container_size' ) ) ) {
				$the_product->set_min_container_size( $cart_item['mnm_container_size'] );
			}

			if( is_callable( array( $the_product, 'get_max_container_size' ) ) ) {
				$the_product->set_max_container_size( $cart_item['mnm_container_size'] );
			}

		}
		return $cart_item;
	}


	/**
	 * Stash quantity in class variable, add filter for min/max props.
	 * 
	 * @param  bool $passed_validation
	 * @param  int 	$product_id
	 * @param  int 	$quantity
	 * @return  bool
	 */
	public static function add_filter( $passed_validation, $product_id, $quantity ) {

		$product_type = WC_Product_Factory::get_product_type( $product_id );

		if ( 'mix-and-match' === $product_type && isset( $_REQUEST[ '_mnm_container_size' ] ) ) {
			self::$size = intval( $_REQUEST['_mnm_container_size'] );
			add_filter( 'woocommerce_mnm_min_container_size', array( __CLASS__, 'set_container_size' ) );
			add_filter( 'woocommerce_mnm_max_container_size', array( __CLASS__, 'set_container_size' ) );
		}

		return $passed_validation;

	}

	/**
	 * Reset class variable, remove filter for min/max props.
	 * 
	 * @param  bool $passed_validation
	 * @param  int 	$product_id
	 * @param  int 	$quantity
	 * @param  int  $variation_id
	 * @param array $variation - selected attribues
	 * @param array $cart_item_data - data from session
	 */
	public static function remove_filter( $passed_validation, $product_id, $quantity ) {

		self::$size = '';
		remove_filter( 'woocommerce_mnm_min_container_size', array( __CLASS__, 'set_container_size' ) );
		remove_filter( 'woocommerce_mnm_max_container_size', array( __CLASS__, 'set_container_size' ) );
		
		return $passed_validation;

	}


	/**
	 * Modify min/max props from stashed class variable.
	 *
	 * @param  int 	$size
	 * @return int
	 */
	public static function set_container_size( $size ) {

		if( self::$size ) {
			$size = self::$size;
		}

		return $size;

	}

} //end class: do not remove or there will be no more guacamole for you

endif; // end class_exists check

// Launch the whole plugin.
add_action( 'woocommerce_mnm_loaded', array( 'WC_MNM_Variable_Container_Sizes', 'init' ) );
