<?php
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class CreditorsTable extends WP_List_Table {

    public $showing = [];
	/** Class constructor */
	public function __construct($target) {

		parent::__construct( [
			'singular' => __( 'Creditor', 'cred' ), //singular name of the listed records
			'plural'   => __( 'Creditors', 'cred' ), //plural name of the listed records
			'ajax'     => false //does this table support ajax?
        ] );
        
        $this->showing['page'] = $target;
        switch ($target) {
            case 'approved_creditors':
                $this->showing['title'] = 'Approved creditors';
            break;
            case 'unapproved_creditors':
                $this->showing['title'] = 'Unapproved creditors';
            break;
            default:
                $this->showing['title'] = 'All creditors';
                break;

        }
       
	}


	/**
	 * Retrieve customers data from the database
	 *
	 * @param int $per_page
	 * @param int $page_number
	 *
	 * @return mixed
	 */
	public function creditors_query( $per_page = 5, $page_number = 5 ) {
		$query =  [];
		$query['role'] = 'creditor';
		$query['fields'] = 'all';
		$query['orderby'] = isset($_REQUEST['orderby']) ?  $_REQUEST['orderby'] : 'user_name';
		$query['order'] = isset($_REQUEST['order'] ) ? $_REQUEST['order']  : 'ASC';
		$query['number'] = $per_page;
		$query['offset'] = $page_number > 1 ? $page_number*$per_page : 0;
		$query['count_total'] = true;
		$query['meta_query'] = [];

        if($this->showing['page'] == 'approved_creditors'){
			$query['meta_query'][] = [
				'relation' => 'OR',
					[
						'key'     => 'credit_worthy',
						'value'   => true,
						'compare' => '='
					],
					[
						'key'     => 'credit_worthy',
						'value'   => 1,
						'compare' => '='
					]
				];	
        }

        else if($this->showing['page'] == 'unapproved_creditors'){
			$query['meta_query'][] = [
				'relation' => 'OR',
					[
						'key'     => 'credit_worthy',
						'value'   => true,
						'compare' => '!='
					],
                 	[
						'key'     => 'credit_worthy',
						'value'   => 1,
						'compare' => '!='
					],
					
				];	
			
		}
		
        //if there is search query
		if(isset( $_REQUEST['s'] )){
			$searchKey = wp_unslash( trim( $_REQUEST['s'] ) );

			$query['meta_query'][] = [
				'relation' => 'OR',
					[
						'key'     => 'first_name',
						'value'   => $searchKey,
						'compare' => 'LIKE'
					],
					[
						'key'     => 'last_name',
						'value'   => $searchKey,
						'compare' => 'LIKE'
					],
					[
						'key'     => 'phone',
						'value'   => $searchKey,
						'compare' => 'LIKE'
					],
				];
		}
	
		return new WP_User_Query( $query );
	}


	/**
	 * Delete a customer record.
	 *
	 * @param int $id customer ID
	 */
	public static function delete_user( $id ) {
		global $wpdb;

		$wpdb->delete(
			"{$wpdb->prefix}users",
			[ 'ID' => $id ],
			[ '%d' ]
		);
    }

    public static function approve_creditor( $id ) {
        update_user_meta( $id, 'credit_worthy', 1 );
	}

    public static function unapprove_creditor( $id ) {
        update_user_meta( $id, 'credit_worthy', 0 );
	}


	/** Text displayed when no customer data is available */
	public function no_items() {
		_e( 'No creditor avaliable.', 'cred' );
	}


	/**
	 * Render a column when no column specific method exist.
	 *
	 * @param array $item
	 * @param string $column_name
	 *
	 * @return mixed
	 */
	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'username':
				return $item->user_nicename;
			case 'email':
				return $item->user_email;
			case 'registered':
				return $item->user_registered;
			default:
				return print_r( $item, true ); 
				
		}
	}

	/**
	 * Render the bulk edit checkbox
	 *
	 * @param array $item
	 *
	 * @return string
	 */
	public function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="creditors[]" value="%s" />', $item->ID
		);
	}

	/**
	 * Method for name column
	 *
	 * @param array $item an array of DB data
	 *
	 * @return string
	 */
    public function column_name( $item ) 
    {
		$title = '<strong>' .$item->first_name.' '.$item->last_name.'</strong>';

        $actions = [];
        
        $actions['view'] = sprintf('<a href="?page=%s&single_creditor=%s">View</a>', esc_attr( $_REQUEST['page'] ), absint( $item->ID ));
        // if(get_user_meta($item->ID, 'credit_worthy', true )){
        //     $actions['unapprove_creditor'] = sprintf('<a href="?page=%s&action=%s&creditor=%s&_wpnonce=%s">Unapprove</a>', esc_attr( $_REQUEST['page'] ), 'unapprove_creditor', absint( $item->ID ), wp_create_nonce( 'cred_user_unapprove' ));
        // }else{
        //     $actions['approve_creditor'] = sprintf('<a href="?page=%s&action=%s&creditor=%s&_wpnonce=%s">Approve</a>', esc_attr( $_REQUEST['page'] ), 'approve_creditor', absint( $item->ID ), wp_create_nonce( 'cred_user_approve' ));
        // }
        $actions['delete'] = sprintf('<a href="?page=%s&action=%s&creditor=%s&_wpnonce=%s">Delete</a>', esc_attr( $_REQUEST['page'] ), 'delete', absint( $item->ID ), wp_create_nonce( 'cred_user_delete' ));

		return $title . $this->row_actions( $actions );
	}
	
	public function column_phone($item){
	    return  get_user_meta($item->ID, 'phone', true );
	}

    public function column_status( $item ) {
        $credit_worthy = get_user_meta($item->ID, 'credit_worthy', true );
        return ($credit_worthy ? 'Approved' : 'Not Approved');
    }


	/**
	 *  Associative array of columns
	 *
	 * @return array
	 */
	public function get_columns() {
		$columns = [
			'cb'      => '<input type="checkbox" />',
			'name'    => __( 'Name', 'cred' ),
			'username'    => __( 'Username', 'cred' ),
			'email' => __( 'Email', 'cred' ),
			'phone' => __( 'Phone', 'cred' ),
			'status'    => __( 'Status', 'cred' ),
			'registered' => __('Registered', 'cred')
		];

		return $columns;
	}


	/**
	 * Columns to make sortable.
	 *
	 * @return array
	 */
	public function get_sortable_columns() {
		$sortable_columns = array(
			'email' => array( 'user_email', true ),
			'username' => array( 'user_nicename', true ),
			'registered' => array( 'user_registered', true ),
		);

		return $sortable_columns;
	}

	/**
	 * Returns an associative array containing the bulk action
	 *
	 * @return array
	 */
	public function get_bulk_actions() {
        $actions = array();

        if($this->showing['page'] == 'approved_creditors'){
            $actions['bulk-unapprove'] = 'Unapprove';
        }

        if($this->showing['page'] == 'unapproved_creditors'){
            $actions['bulk-approve'] = 'Approve';

        }
        
        $actions['bulk-delete'] = 'Delete';

		return $actions;
	}


	/**
	 * Handles data query and filter, sorting, and pagination.
	 */
	public function prepare_items() {
		$per_page = 10;
		$query = $this->creditors_query($per_page, $this->get_pagenum());

		$this->_column_headers = [ 
            $this->get_columns(), 
            [], //hidden columns if applicable 
            $this->get_sortable_columns()
        ];
        
		/** Process bulk action */
		$this->process_bulk_action();

		$this->set_pagination_args( [
			'total_items' => $query->get_total(),
			'per_page'    => $per_page
		] );

		$this->items = $query->get_results();
	}

	public function process_bulk_action() {

        // Single actions
		if ( 'delete' === $this->current_action() ) {

			// In our file that handles the request, verify the nonce.
			$nonce = esc_attr( $_REQUEST['_wpnonce'] );

			if ( ! wp_verify_nonce( $nonce, 'cred_user_delete' ) ) {
				die( 'Ooops!' );
			}
			else {
				self::delete_user( absint( $_GET['creditor'] ) );
                wp_redirect( admin_url('admin.php?page='.$_REQUEST['page']) );
				exit;
			}

		}

        if ( 'approve_creditor' === $this->current_action() ) {

			// In our file that handles the request, verify the nonce.
			$nonce = esc_attr( $_REQUEST['_wpnonce'] );

			if ( ! wp_verify_nonce( $nonce, 'cred_user_approve' ) ) {
				die( 'Ooops!' );
			}
			else {
				self::approve_creditor( absint( $_GET['creditor'] ) );
                wp_redirect( admin_url('admin.php?page='.$_REQUEST['page']) );
				exit;
			}

        }
        
        if ( 'unapprove_creditor' === $this->current_action() ) {

			// In our file that handles the request, verify the nonce.
			$nonce = esc_attr( $_REQUEST['_wpnonce'] );

			if ( ! wp_verify_nonce( $nonce, 'cred_user_unapprove' ) ) {
				die( 'Ooops!' );
			}
			else {
				self::unapprove_creditor( absint( $_GET['creditor'] ) );
                wp_redirect( admin_url('admin.php?page='.$_REQUEST['page']) );
				exit;
			}

        }
        

		// Bulk actions
		if (isset( $_POST['action'] ) || isset( $_POST['action2'] )) {
			$bulkAction = isset( $_POST['action'] ) ? $_POST['action'] :  (isset($_POST['action2']) ? $_POST['action2'] : '');
			
            $ids = esc_sql( $_POST['creditors'] );
            
			// loop over the array of record IDs and perform bulk action
			foreach ( $ids as $id ) {
                switch ($bulkAction) {
                    case 'bulk-delete':
                        self::delete_user( $id );
                        break;
                    case 'bulk-approve':
                        self::approve_creditor( $id );
                        break;
                    case 'bulk-unapprove':
                        self::unapprove_creditor( $id );
                        break;
                }
			}
		    wp_redirect( esc_url_raw(add_query_arg()) );
			exit;
        }
        
        
	}
	
	public function process_single_action(){
	     
        // Single user action
        if(isset($_POST['update_single_creditor']) )
        {
            $user = get_user_by('ID', $_POST['creditor_id']);
            update_user_meta( $user->ID, 'credit_worthy', isset($_POST['credit_worthy']) ?  $_POST['credit_worthy'] : 0);
            
            if(isset($_POST['send_cred_approval_mail']) && $_POST['send_cred_approval_mail'] && !empty($_POST['cred_approval_mail']))
            {
               wp_mail($user->user_email, 'Installment Payment Update', $_POST['cred_approval_mail']);
            }

           wp_redirect( admin_url('admin.php?page='.$_REQUEST['page']) );
		   exit;
        }
        
	}
	

}