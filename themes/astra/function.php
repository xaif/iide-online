<?php
/**
 * Astra Child Theme functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package Astra Child
 * @since 2.4.4
 */

/**
 * Define Constants
 */
define( 'CHILD_THEME_ASTRA_CHILD_VERSION', '2.4.4' );

/**
 * Enqueue styles
 */
function child_enqueue_styles() {

	wp_enqueue_style( 'astra-child-theme-css', get_stylesheet_directory_uri() . '/style.css', array('astra-theme-css'), CHILD_THEME_ASTRA_CHILD_VERSION, 'all' );

}

add_action( 'wp_enqueue_scripts', 'child_enqueue_styles', 15 );

/**
 * Automatically apply coupons for users that have referred friends
 */
add_action( 'woocommerce_before_calculate_totals', 'wpgens_auto_apply_coupons_checkout', 10, 1 );
function wpgens_auto_apply_coupons_checkout( $cart ) {
	if ( is_admin() && ! defined( 'DOING_AJAX' ) )
        return;
    if ( did_action( 'woocommerce_before_calculate_totals' ) >= 2 )
        return;
    if(!is_user_logged_in())
    	return;
    $user_info = get_userdata(get_current_user_id());
    $user_email = $user_info->user_email;
    $date_format = get_option( 'date_format' );
    $args = array(
        'posts_per_page'   => 10,
        'post_type'        => 'shop_coupon',
        'post_status'      => 'publish',
        'meta_query' => array (
        'relation' => 'AND',
            array (
              'key' => 'customer_email',
              'value' => $user_email,
              'compare' => 'LIKE'
            ),
            array (
              'key' => 'usage_count',
              'value' => '0',
              'compare' => '='
            )
        ),
    );
    $raf_coupons = array();
    $coupons = get_posts( $args );
    if($coupons) {
        $i = 0;
        foreach ( $coupons as $coupon ) {
			if(!$cart->has_discount( $coupon->post_title ) ){
	        	$cart->add_discount( $coupon->post_title );
	        }
        }
    }   
}

/**
 * Login Link in Menu
 */

add_filter( 'wp_nav_menu_items', 'wti_loginout_menu_link', 10, 2 );

function wti_loginout_menu_link( $items, $args ) {
   if ($args->theme_location == 'primary') {
      if (is_user_logged_in()) {
         $items .= '<li class="logout"><a href="'. wp_logout_url('index.php') .'">'. __("Logout") .'</a></li>';
      } else {
         $items .= '<li class="loginpopup">'. __("Login") .'</a></li>';
      }
   }
   return $items;
}

/**
 * Gravatar
 */

function shortcode_user_avatar() {
	if(is_user_logged_in()) { // check if user is logged in
		global $current_user; // get current user's information
		get_currentuserinfo();
		return get_avatar( $current_user -> ID, 150 ); // display the logged in user's avatar
	}
	else {
	  // if not logged in, show default avatar. change URL to show your own default avatar
		return get_avatar( 'http://1.gravatar.com/avatar/ad524503a11cd5ca435acc9bb6523536?s=64', 150 );
	}
}

add_shortcode('display-user-avatar','shortcode_user_avatar');
/*
 * Add My Courses Link to My Account Menu
 */
add_filter ( 'woocommerce_account_menu_items', 'my_courses_link', 40 );
function my_courses_link( $menu_links ){
	$menu_links = array_slice( $menu_links, 0, 5, true ) 
	+ array( 'my-courses' => 'My Courses' )
	+ array_slice( $menu_links, 5, NULL, true );
	return $menu_links;
}

add_action( 'init', 'add_endpoint' );
function add_endpoint() {
 	add_rewrite_endpoint( 'my-courses', EP_PAGES );
}

add_action( 'woocommerce_account_my-courses_endpoint', 'my_account_endpoint_content' );
function my_account_endpoint_content() {
 	echo do_shortcode ("[elementor-template id='30663']");
}
// Go to Settings > Permalinks and just push "Save Changes" button.


/*
 * Add Referral Program Link to My Account Menu
 */
add_filter ( 'woocommerce_account_menu_items', 'referral_program_link', 40 );
function referral_program_link( $menu_links ){
	$menu_links = array_slice( $menu_links, 0, 5, true ) 
	+ array( 'referral-program' => 'Referral Program' )
	+ array_slice( $menu_links, 5, NULL, true );
	return $menu_links;
}

add_action( 'init', 'add_referral_program_endpoint' );
function add_referral_program_endpoint() {
 	add_rewrite_endpoint( 'referral-program', EP_PAGES );
}

add_action( 'woocommerce_account_referral-program_endpoint', 'referral_program_endpoint_content' );
function referral_program_endpoint_content() {
 	echo do_shortcode ("[elementor-template id='30448']");
}
// Go to Settings > Permalinks and just push "Save Changes" button.

/*
 * Add Edit Avatar Link to My Account Menu
 */
add_filter ( 'woocommerce_account_menu_items', 'edit_avatar_link', 40 );
function edit_avatar_link( $menu_links ){
	$menu_links = array_slice( $menu_links, 0, 5, true ) 
	+ array( 'edit-avatar' => 'Change Avatar' )
	+ array_slice( $menu_links, 5, NULL, true );
	return $menu_links;
}

add_action( 'init', 'add_edit_avatar_endpoint' );
function add_edit_avatar_endpoint() {
 	add_rewrite_endpoint( 'edit-avatar', EP_PAGES );
}

add_action( 'woocommerce_account_edit-avatar_endpoint', 'edit_avatar_endpoint_content' );
function edit_avatar_endpoint_content() {
 	echo do_shortcode ("[elementor-template id='30226']");
}
// Go to Settings > Permalinks and just push "Save Changes" button.


/* Hide Menu Bar for Subscribers
 */
add_action('after_setup_theme', 'remove_admin_bar');
 
function remove_admin_bar() {
if (!current_user_can('administrator') && !is_admin()) {
  show_admin_bar(false);
}
}

// Disable re-purchase of product
function sv_wc_customer_purchased_product_in_cat( $product ) {

    // enter the category for which a single purchase is allowed
    $non_repeatable = 'Uncategorized';
    
    // bail if this product is in not in our target category
    if ( ! has_term( $non_repeatable, 'product_cat', $product->get_id() ) ) {
        return false;
    }
    
    // the product has our target category, so return whether the customer purchased
    return wc_customer_bought_product( wp_get_current_user()->user_email, get_current_user_id(), $product->get_id() );
}

function sv_wc_disable_repeat_purchase( $purchasable, $product ) {

    if ( sv_wc_customer_purchased_product_in_cat( $product ) ) {
        $purchasable = false;
    }
    
    // double-check for variations: if parent is not purchasable, then variation is not
    if ( $purchasable && $product->is_type( 'variation' ) ) {
        $purchasable = $product->parent->is_purchasable();
    }
    
    return $purchasable;
}
add_filter( 'woocommerce_variation_is_purchasable', 'sv_wc_disable_repeat_purchase', 10, 2 );
add_filter( 'woocommerce_is_purchasable', 'sv_wc_disable_repeat_purchase', 10, 2 );

function sv_wc_purchase_disabled_message() {

    // get the current product to check if purchasing should be disabled
    global $product;
    
    // now we know we're in the category, check if we've purchased already
    if ( sv_wc_customer_purchased_product_in_cat( $product ) ) {
        // Create your message for the customer here
        echo '<div class="woocommerce"><div class="woocommerce-info wc-nonpurchasable-message">
        You\'ve already purchased this product! It can only be purchased once.
        </div></div>';
    }
}
add_action( 'woocommerce_single_product_summary', 'sv_wc_purchase_disabled_message', 31 );

/**
 * Replace 'customer' role (WooCommerce default) to subscriber
**/
add_filter('woocommerce_new_customer_data', 'wc_assign_custom_role', 10, 1);

function wc_assign_custom_role($args) {
  $args['role'] = 'subscriber';
  
  return $args;
}
add_action( 'woocommerce_single_product_summary', 'sold_individually_custom_text', 25 );
function sold_individually_custom_text(){
    global $product;

    if ( $product->is_sold_individually( ) ) {
        echo '<p class="sold-individually">' . __("You can only buy one piece of this product", "woocommerce") . '</p>';
    }
}

//Read More Text to View Course
add_filter( 'woocommerce_product_add_to_cart_text', function( $text ) {
    if ( 'Read more' == $text ) {
        $text = __( 'View Course', 'woocommerce' );
    }

    return $text;
} );

//Add Product Author
add_action('init', 'function_to_add_author_woocommerce', 999 );

function function_to_add_author_woocommerce() {
  add_post_type_support( 'product', 'author' );
  }


// To change add to cart text on single product page
add_filter( 'woocommerce_product_single_add_to_cart_text', 'woocommerce_custom_single_add_to_cart_text' ); 
function woocommerce_custom_single_add_to_cart_text() {
    return __( 'Enroll Now', 'woocommerce' ); 
}

// To change add to cart text on product archives(Collection) page
add_filter( 'woocommerce_product_add_to_cart_text', 'woocommerce_custom_product_add_to_cart_text' );  
function woocommerce_custom_product_add_to_cart_text() {
    return __( 'Enroll Now', 'woocommerce' );
}

//Checkout Redirect
	
	add_action( 'template_redirect', 'custom_redirect_after_purchase' ); 
function custom_redirect_after_purchase() {
	global $wp;
	
	if ( is_checkout() && ! empty( $wp->query_vars['order-received'] ) ) {
		wp_redirect( 'https://online.iide.co/thank-you/' );
		exit;
	}
}
