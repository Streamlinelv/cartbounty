<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * Defines how Abandoned cart Table should be displayed
 *
 * @package    CartBounty - Save and recover abandoned carts for WooCommerce
 * @subpackage CartBounty - Save and recover abandoned carts for WooCommerce/admin
 * @author     Streamline.lv
 */
 
if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}
 
class CartBounty_Table extends WP_List_Table{

   /**
    * Constructor, we override the parent to pass our own arguments
    * We usually focus on three parameters: singular and plural labels, as well as whether the class supports AJAX.
    *
    * @since    1.0
    */
    function __construct(){
        global $status, $page;

        parent::__construct(array(
            'singular' => 'id',
            'plural' => 'ids',
        ));
    }
	
	/**
     * This method return columns to display in table
     *
     * @since    1.0
     * @return   array
     */
	function get_columns(){
        return $columns = array(
            'cb'            => 		'<input type="checkbox" />',
            'id'            =>		__('ID', CARTBOUNTY_TEXT_DOMAIN),
            'name'          =>		__('Name, Surname', CARTBOUNTY_TEXT_DOMAIN),
            'email'         =>		__('Email', CARTBOUNTY_TEXT_DOMAIN),
            'phone'			=>		__('Phone', CARTBOUNTY_TEXT_DOMAIN),
            'location'      =>      __('Location', CARTBOUNTY_TEXT_DOMAIN),
            'cart_contents' =>		__('Cart contents', CARTBOUNTY_TEXT_DOMAIN),
            'cart_total'    =>		__('Cart total', CARTBOUNTY_TEXT_DOMAIN),
            'time'          =>		__('Time', CARTBOUNTY_TEXT_DOMAIN)
        );
	}
	
	/**
     * This method return columns that may be used to sort table
     * all strings in array - is column names
     * True on name column means that its default sort
     *
     * @since    1.0
     * @return   array
     */
	public function get_sortable_columns(){
		return $sortable = array(
			'id'                =>      array('id', true),
            'name'              =>      array('name', true),
            'email'             =>      array('email', true),
            'phone'             =>      array('phone', true),
            'cart_total'        =>      array('cart_total', true),
            'time'              =>      array('time', true)
		);
	}
	
	/**
     * This is a default column renderer
     *
     * @since    1.0
     * @return   HTML
     * @param    $item - row (key, value array)
     * @param    $column_name - string (key)
     */
    function column_default( $item, $column_name ){
        return $item[$column_name];
    }
	
	/**
     * Rendering Name column
     *
     * @since    1.0
     * @return   HTML
     * @param    $item - row (key, value array)
     */
    function column_name( $item ){
        $cart_status = 'all';
        if (isset($_GET['cart-status'])){
            $cart_status = $_GET['cart-status'];
        }

        $actions = array(
            'delete' => sprintf('<a href="?page=%s&action=delete&id=%s&cart-status='. $cart_status .'">%s</a>', esc_html($_REQUEST['page']), esc_html($item['id']), __('Delete', 'custom_table_example')),
        );

        return sprintf('<svg class="cartbounty-customer-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 450 506"><path d="M225,0A123,123,0,1,0,348,123,123.14,123.14,0,0,0,225,0Z"/><path d="M393,352.2C356,314.67,307,294,255,294H195c-52,0-101,20.67-138,58.2A196.75,196.75,0,0,0,0,491a15,15,0,0,0,15,15H435a15,15,0,0,0,15-15A196.75,196.75,0,0,0,393,352.2Z"/></svg>%s %s %s',
            esc_html($item['name']),
            esc_html($item['surname']),
            $this->row_actions($actions)
        );
    }
	
	/**
     * Rendering Email column
     *
     * @since    1.0
     * @return   HTML
     * @param    $item - row (key, value array)
     */
    function column_email( $item ){
        return sprintf('<a href="mailto:%1$s" title="">%1$s</a>',
            esc_html($item['email'])
        );
    }

    /**
     * Rendering Location column
     *
     * @since    4.6
     * @return   HTML
     * @param    $item - row (key, value array)
     */
    function column_location( $item ){
        if(is_serialized($item['location'])){ //Since version 4.6
            $location_data = unserialize($item['location']);
            $country = $location_data['country'];
            $city = $location_data['city'];
            $postcode = $location_data['postcode'];

        }else{ //Prior version 4.6. Will be removed in future releases
            $parts = explode(',', $item['location']); //Splits the Location field into parts where there are commas
            if (count($parts) > 1) {
                $country = $parts[0];
                $city = trim($parts[1]); //Trim removes white space before and after the string
            }
            else{
                $country = $parts[0];
                $city = '';
            }

            $postcode = '';
            if(is_serialized($item['other_fields'])){
                $other_fields = @unserialize($item['other_fields']);
                if(isset($other_fields['cartbounty_billing_postcode'])){
                    $postcode = $other_fields['cartbounty_billing_postcode'];
                }
            }
        }

        $location = $country;
        if(!empty($city)){
             $location .= ', ' . $city;
        }
        if(!empty($postcode)){
             $location .= ', ' . $postcode;
        }

        return sprintf('%s',
            esc_html($location)
        );
    }
	
	/**
     * Rendering Cart Contents column
     *
     * @since    1.0
     * @return   HTML
     * @param    $item - row (key, value array)
     */
    function column_cart_contents( $item ){
        if(!is_serialized($item['cart_contents'])){
            return;
        }

        $product_array = unserialize($item['cart_contents']); //Retrieving array from database column cart_contents
        $output = '';
        
        if($product_array){
            if(get_option('cartbounty_hide_images')){ //Outputing Cart contents as a list
                $output = '<ul class="wlcfc-product-list">';
                foreach($product_array as $product){
                    if(is_array($product)){
                        if(isset($product['product_title'])){
                            $product_title = esc_html($product['product_title']);
                            $quantity = " (". $product['quantity'] .")"; //Enclose product quantity in brackets
                            $edit_product_link = get_edit_post_link( $product['product_id'], '&' ); //Get product link by product ID
                            if($edit_product_link){ //If link exists (meaning the product hasn't been deleted)
                                $output .= '<li><a href="'. $edit_product_link .'" title="'. $product_title .'" target="_blank">'. $product_title . $quantity .'</a></li>';
                            }else{
                                $output .= '<li>'. $product_title . $quantity .'</li>';
                            }
                        }
                    }
                }
                $output .= '</ul>';

            }else{ //Displaying cart contents with thumbnails
                foreach($product_array as $product){
                    if(is_array($product)){
                        if(isset($product['product_title'])){
                            //Checking product image
                            if(!empty($product['product_variation_id'])){ //In case of a variable product
                                $image = get_the_post_thumbnail_url($product['product_variation_id'], 'thumbnail');
                                if(empty($image)){ //If variation didn't have an image set
                                    $image = get_the_post_thumbnail_url($product['product_id'], 'thumbnail');
                                }
                            }else{ //In case of a simple product
                                 $image = get_the_post_thumbnail_url($product['product_id'], 'thumbnail');
                            }

                            if(empty($image)){//In case product has no image, output default WooCommerce image
                                $image = wc_placeholder_img_src('thumbnail');
                            }

                            $product_title = esc_html($product['product_title']);
                            $quantity = " (". $product['quantity'] .")"; //Enclose product quantity in brackets
                            $edit_product_link = get_edit_post_link( $product['product_id'], '&' ); //Get product link by product ID
                            if($edit_product_link){ //If link exists (meaning the product hasn't been deleted)
                                $output .= '<div class="cartbounty-abandoned-product"><span class="tooltiptext">'. $product_title . $quantity .'</span><a href="'. $edit_product_link .'" title="'. $product_title . $quantity .'" target="_blank"><img src="'. $image .'" title="'. $product_title . $quantity .'" alt ="'. $product_title . $quantity .'" /></a></div>';
                            }else{
                                $output .= '<div class="cartbounty-tooltip"><span class="tooltiptext">'. $product_title . $quantity .'</span><img src="'. $image .'" title="'. $product_title . $quantity .'" alt ="'. $product_title . $quantity .'" /></div>';
                            }
                        }
                    }
                }
            }
        }
        return sprintf('%s', $output );
    }
	
	/**
     * Rendering Cart Total column
     *
     * @since    1.0
     * @return   HTML
     * @param    $item - row (key, value array)
     */
    function column_cart_total( $item ){
        return sprintf('%0.2f %s',
            esc_html($item['cart_total']),
            esc_html($item['currency'])
        );
    }
	
	/**
     * Render Time column 
     *
     * @since    1.0
     * @return   HTML
     * @param    $item - row (key, value array)
     */
	function column_time( $item ){
		$time = new DateTime($item['time']);
		$date_iso = $time->format('c');
        $date_title = $time->format('M d, Y H:i:s');
        $utc_time = $time->format('U');

        if($utc_time > strtotime( '-1 day', current_time( 'timestamp' ))){ //In case the abandoned cart is newly captued
             $friendly_time = sprintf( 
                /* translators: %1$s - Time, e.g. 1 minute, 5 hours */
                __( '%1$s ago', CARTBOUNTY_TEXT_DOMAIN ),
                human_time_diff( $utc_time,
                current_time( 'timestamp' ))
            );
        }else{ //In case the abandoned cart is older tahn 24 hours
            $friendly_time = $time->format('M d, Y');
        }

		return sprintf( '<svg class="cartbounty-time-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 31.18 31.18"><path d="M15.59,31.18A15.59,15.59,0,1,1,31.18,15.59,15.6,15.6,0,0,1,15.59,31.18Zm0-27.34A11.75,11.75,0,1,0,27.34,15.59,11.76,11.76,0,0,0,15.59,3.84Z"/><path d="M20.39,20.06c-1.16-.55-6-3-6.36-3.19s-.46-.76-.46-1.18V7.79a1.75,1.75,0,1,1,3.5,0v6.88s4,2.06,4.8,2.52a1.6,1.6,0,0,1,.69,2.16A1.63,1.63,0,0,1,20.39,20.06Z"/></svg><time datetime="%s" title="%s">%s</time>', esc_html($date_iso), esc_html($date_title), esc_html($friendly_time));
	}

	/**
     * Rendering checkbox column
     *
     * @since    1.0
     * @return   HTML
     * @param    $item - row (key, value array)
     */
	function column_cb( $item ){
		return sprintf(
			'<input type="checkbox" name="id[]" value="%s" />',
			esc_html($item['id'])
		);
	}
	
	/**
     * Return array of bulk actions if we have any
     *
     * @since    1.0
     * @return   array
     */
	 function get_bulk_actions(){
        $actions = array(
            'delete' => __('Delete', CARTBOUNTY_TEXT_DOMAIN)
        );
        return $actions;
    }

    /**
     * This method processes bulk actions
     *
     * @since    1.0
     */
    function process_bulk_action(){
        global $wpdb;
        $table_name = $wpdb->prefix . CARTBOUNTY_TABLE_NAME; // do not forget about tables prefix

        if ('delete' === $this->current_action()) {
            $ids = isset($_REQUEST['id']) ? $_REQUEST['id'] : array();

            if (!empty($ids)){
                if(is_array($ids)){ //Bulk abandoned cart deletion
                    foreach ($ids as $key => $id){
                        $wpdb->query(
                            $wpdb->prepare(
                                "DELETE FROM $table_name
                                WHERE id = %d",
                                intval($id)
                            )
                        );
                    }
                }else{ //Single abandoned cart deletion
                    $id = $ids;
                    $wpdb->query(
                        $wpdb->prepare(
                            "DELETE FROM $table_name
                            WHERE id = %d",
                            intval($id)
                        )
                    );
                }
            }
        }
    }

	/**
     * Method that renders the table
     *
     * @since    1.0
     */
	function prepare_items(){
        global $wpdb;
        $table_name = $wpdb->prefix . CARTBOUNTY_TABLE_NAME; // do not forget about tables prefix

        $cart_status = 'all';
        if (isset($_GET['cart-status'])){
            $cart_status = $_GET['cart-status'];
        }

        $per_page = 10;
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = array($columns, $hidden, $sortable); // here we configure table headers, defined in our methods
        $this->process_bulk_action(); // process bulk action if any
        $admin = new CartBounty_Admin(CARTBOUNTY_PLUGIN_NAME_SLUG, CARTBOUNTY_VERSION_NUMBER);
        $total_items = $admin->get_cart_count($cart_status);

        // prepare query params, as usual current page, order by and order direction
        $paged = isset($_REQUEST['paged']) ? max(0, intval($_REQUEST['paged']) - 1) : 0;
        $orderby = (isset($_REQUEST['orderby']) && in_array($_REQUEST['orderby'], array_keys($this->get_sortable_columns()))) ? $_REQUEST['orderby'] : 'time';
        $order = (isset($_REQUEST['order']) && in_array($_REQUEST['order'], array('asc', 'desc'))) ? $_REQUEST['order'] : 'desc';

        // configure pagination
        $this->set_pagination_args(array(
            'total_items' => $total_items, // total items defined above
            'per_page' => $per_page, // per page constant defined at top of method
            'total_pages' => ceil($total_items / $per_page) // calculate pages count
        ));

        $where_sentence = $admin->get_where_sentence($cart_status);
        $this->items = $wpdb->get_results($wpdb->prepare("
            SELECT * FROM $table_name
            WHERE cart_contents != ''
            $where_sentence
            ORDER BY $orderby $order
            LIMIT %d OFFSET %d",
        $per_page, $paged * $per_page), ARRAY_A);
    }
}