<?php 
class IC_LMS_WC_Helper{
    function __construct(){
        add_action('init',[$this,'initialize']);
        add_action('woocommerce_account_demo_endpoint',[$this,'render_demo_page']);
        add_action('woocommerce_account_xyz_endpoint',[$this,'render_xyz_page']);
        add_filter('woocommerce_account_menu_items',[$this,'sidebar_menu']);
    }

    function sidebar_menu($items){
        $items['demo'] = "Demo Page";
        $items['xyz'] = "XYZ Page";
        return $items;
    }

    function render_demo_page(){
        echo '<h2>Woo Demo Dashboard Item</h2>';
        echo '<p>This is a demo item added to the WooCommerce user dashboard.</p>';
        $customer = WC()->customer;
        echo "<p>".$customer->get_billing_country()."</p>";
        echo "<p>".$customer->get_billing_address()."</p>";
        $user_id = get_current_user_id();
        $did_i_purchase = wc_customer_bought_product('',$user_id, 126);
        if($did_i_purchase){
            echo "<p>Yes I have purchased the product</p>";
        }else{
            echo "<p>I didnt purchase</p>";
        }
        // $customer->id
    }
    
    function render_xyz_page(){
        echo '<h2>XYZ Page</h2>';
        echo '<p>XYZ - This is a demo item added to the WooCommerce user dashboard.</p>';
    }

    function initialize(){
        add_rewrite_endpoint('demo',EP_PAGES);
        add_rewrite_endpoint('xyz',EP_PAGES);
        // flush_rewrite_rules();
    }
}