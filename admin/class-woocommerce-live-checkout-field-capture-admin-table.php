<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * Defines how Table should be outputed
 *
 * @package    Woocommerce Live Checkout Field Capture
 * @subpackage Woocommerce Live Checkout Field Capture/admin
 * @author     Streamline.lv
 */
 
if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}
 
class Woocommerce_Live_Checkout_Field_Capture_Table extends WP_List_Table {

   /**
    * Constructor, we override the parent to pass our own arguments
    * We usually focus on three parameters: singular and plural labels, as well as whether the class supports AJAX.
    */
    function __construct()
    {
        global $status, $page;

        parent::__construct(array(
            'singular' => 'id',
            'plural' => 'ids',
        ));
    }
	
	
	/**
     * [REQUIRED] This method return columns to display in table
     * you can skip columns that you do not want to show
     * like content, or description
     *
     * @return array
     */
	function get_columns() {
	   return $columns= array(
		  'cb'				=> 		'<input type="checkbox" />',
		  'id'				=>		__('ID'),
		  'nameSurname'		=>		__('Name, Surname'),
		  'email'			=>		__('Email'),
		  'phone'			=>		__('Phone'),
          'location'        =>      __('Location'),
		  'cart_contents'	=>		__('Cart contents'),
		  'cart_total'		=>		__('Cart total'),
		  'time'			=>		__('Time')
	   );
	}
	
	
	
	/**
     * [OPTIONAL] This method return columns that may be used to sort table
     * all strings in array - is column names
     * notice that true on name column means that its default sort
     *
     * @return array
     */
	public function get_sortable_columns() {
		return $sortable = array(
			'id'				=>		array('id', true),
            'nameSurname'       =>      array('name', true),
			'location'	     	=>		array('location', true),
			'cart_total'		=>		array('cart_total', true),
			'time'				=>		array('time', true)
		);
	}
	
	
	/**
     * [REQUIRED] this is a default column renderer
     *
     * @param $item - row (key, value array)
     * @param $column_name - string (key)
     * @return HTML
     */
    function column_default($item, $column_name)
    {
        return $item[$column_name];
    }
	
	
	/**
     * This is example, how to render column with actions,
     * when you hover row "Edit | Delete" links showed
     *
     * @param $item - row (key, value array)
     * @return HTML
     */
    function column_nameSurname($item)
    {
        // links going to /admin.php?page=[your_plugin_page][&other_params]
        // notice how we used $_REQUEST['page'], so action will be done on curren page
        // also notice how we use $this->_args['singular'] so in this example it will
        // be something like &person=2
        $actions = array(
            'delete' => sprintf('<a href="?page=%s&action=delete&id=%s">%s</a>', esc_html($_REQUEST['page']), esc_html($item['id']), __('Delete', 'custom_table_example')),
        );

        return sprintf('<span class="dashicons dashicons-admin-users"></span> %s %s %s',
            esc_html($item['name']),
            esc_html($item['surname']),
            $this->row_actions($actions)
        );
    }
	
	
	/**
     * Rendering Email field
     *
     * @param $item - row (key, value array)
     * @return HTML
     */
    function column_email($item)
    {
        return sprintf('<a href="mailto:%1$s" title="">%1$s</a>',
            esc_html($item['email'])
        );
    }
	
	
	/**
     * Rendering Cart Contents field
     *
     * @param $item - row (key, value array)
     * @return HTML
     */
    function column_cart_contents($item)
    {
		//Retrieving array from database column cart_contents
		$product_array = @unserialize($item['cart_contents']);
		
		if ($product_array){
			//Creating Cart content output in a list
			$output = '<ul class="wlcfc-product-list">';
			foreach($product_array as $product){
                $product_title = $product[0];
                $edit_product_link = get_edit_post_link( $product[2], '&' ); //Get product link by product ID
                $quantity = " (". $product[1] .")"; //Enclose product quantity in brackets
				$output .= '<li><a href="'. $edit_product_link .'" title="'. $product_title .'" target="_blank">'. $product_title . $quantity .'</a></li>';
			}
			$output .= '</ul>';
			
			return sprintf('%s',
				$output
			);
		}
		else{
			return false;
		}
    }
	
	
	/**
     * Rendering Cart Total field
     *
     * @param $item - row (key, value array)
     * @return HTML
     */
    function column_cart_total($item)
    {
        return sprintf('%0.2f %s',
            esc_html($item['cart_total']),
            esc_html($item['currency'])
        );
    }
	
	/**
     * Render date column 
     *
     * @param $item - row (key, value array)
     * @return HTML
     */
	function column_time($item)
	{
		$database_time = $item['time'];
		$date_time = new DateTime($database_time);
		$date = $date_time->format('d.m.Y');
		$time = $date_time->format('H:i:s');
		
		return sprintf(
			'<span class="dashicons dashicons-clock"></span> %s %s',
			esc_html($time),
			esc_html($date)
		);
	}	
	
	/**
     * [REQUIRED] this is how checkbox column renders
     *
     * @param $item - row (key, value array)
     * @return HTML
     */
	function column_cb($item)
	{
		return sprintf(
			'<input type="checkbox" name="id[]" value="%s" />',
			esc_html($item['id'])
		);
	}

	
	
	/**
     * [OPTIONAL] Return array of bult actions if has any
     *
     * @return array
     */
	 function get_bulk_actions()
    {
        $actions = array(
            'delete' => 'Delete'
        );
        return $actions;
    }

    /**
     * [OPTIONAL] This method processes bulk actions
     * it can be outside of class
     * it can not use wp_redirect cause there is output already
     * in this example we are processing delete action
     * message about successful deletion will be shown on page in next part
     */
    function process_bulk_action()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . WCLCFC_TABLE_NAME; // do not forget about tables prefix

        if ('delete' === $this->current_action()) {
            $ids = isset($_REQUEST['id']) ? $_REQUEST['id'] : array();
            if (is_array($ids)) $ids = implode(',', $ids);

            if (!empty($ids)) {
                $wpdb->query("DELETE FROM $table_name WHERE id IN($ids)");
            }
        }
    }
	
	
	
	/**
     * [REQUIRED] This is the most important method
     *
     * It will get rows from database and prepare them to be showed in table
     */
	function prepare_items(){
        global $wpdb;
        $table_name = $wpdb->prefix . WCLCFC_TABLE_NAME; // do not forget about tables prefix

        $per_page = 10; // constant, how much records will be shown per page

        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();

        // here we configure table headers, defined in our methods
        $this->_column_headers = array($columns, $hidden, $sortable);

        // [OPTIONAL] process bulk action if any
        $this->process_bulk_action();

        // will be used in pagination settings
        $total_items = $wpdb->get_var("SELECT COUNT(id) FROM $table_name");

        // prepare query params, as usual current page, order by and order direction
        $paged = isset($_REQUEST['paged']) ? max(0, intval($_REQUEST['paged']) - 1) : 0;
        $orderby = (isset($_REQUEST['orderby']) && in_array($_REQUEST['orderby'], array_keys($this->get_sortable_columns()))) ? $_REQUEST['orderby'] : 'id';
        $order = (isset($_REQUEST['order']) && in_array($_REQUEST['order'], array('asc', 'desc'))) ? $_REQUEST['order'] : 'desc';

        // [REQUIRED] configure pagination
        $this->set_pagination_args(array(
            'total_items' => $total_items, // total items defined above
            'per_page' => $per_page, // per page constant defined at top of method
            'total_pages' => ceil($total_items / $per_page) // calculate pages count
        ));
		
		// [REQUIRED] define $items array
        // notice that last argument is ARRAY_A, so we will retrieve array
        $this->items = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name ORDER BY $orderby $order LIMIT %d OFFSET %d", $per_page, $paged * $per_page), ARRAY_A);
    }
}