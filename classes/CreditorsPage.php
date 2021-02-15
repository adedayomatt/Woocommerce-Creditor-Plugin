<?php 

require __DIR__.'/CreditorsTable.php';

class CreditorsPage {
	static $instance;
    public $table;

	// class constructor
	public function __construct() {
        add_action( 'admin_menu', [ $this, 'plugin_menu' ] );
	}

	public function plugin_menu() {

		$hook = add_menu_page(
			'Creditor',
			'Creditors',
			'manage_options',
			'creditor_list',
            [ $this, 'render_users' ],
            'dashicons-admin-users',
            70
        );
        
        add_submenu_page(
            'creditor_list', 
            'All Credit Users',
            'All Credit Users',
            'manage_options',
            'creditor_list',
            [$this, 'render_users']
        );

        add_submenu_page(
            'creditor_list', 
            'Approved Credit Users',
            'Approved Creditors',
            'manage_options',
            'approved_creditors',
            [$this, 'render_users']
        );
        
        add_submenu_page(
            'creditor_list', 
            'Unapproved Credit Users',
            'Unapproved Creditors',
            'manage_options',
            'unapproved_creditors',
            [$this, 'render_users']
        );
        add_submenu_page(
            'creditor_list', 
            'Credit users configuration',
            'Configuration',
            'manage_options',
            'credit_user_config',
            [$this, 'render_configurations']
        );
        $this->table = new CreditorsTable($_REQUEST['page']);
	}
    

    function render_user( $id ) { 
        
        $this->table->process_single_action();
        
        $user = get_user_by('ID', $id);
        if($user){
            $phone = get_user_meta($user->ID, 'phone', true );
            $credit_worthy = get_user_meta($user->ID, 'credit_worthy', true );
            if(user_can($user->ID, 'creditor')){        
                ?>
                    <h2>Credit User</h2>
                    <div style="background-color: #fff; padding: 10px; margin-top: 10px">
                        <table class="form-table">
                            <tr>
                                <td>
                                    <h1 style="margin: 10px 0"><?php _e("{$user->first_name} {$user->last_name}")  ?></h1>
                                    <div style="color: gray"><?php _e($phone); ?> | <?php _e($user->user_email); ?></div>
                                </td>
                                <td>
                                    <form action="" method="post">
                                        <h4><?php _e("Installment Eligibilty"); ?></h4>
                                        <input type="hidden" name="creditor_id" value="<?php echo $user->ID ?>">
                                        <label for="eligibility">
                                            <input type="checkbox" name="credit_worthy" id="eligibility" value="1" <?php echo $credit_worthy ? 'checked' : ''  ?> />
                                            Allow user to pay on installment
                                        </label><br />
                                        <span class="description"><?php _e("Let user be able to pay installmentally"); ?></span>
                                        
                                        <div style="margin: 10px 0">
                                            <button type="button" id="cred-lookup" class="button button-primary" data-phone="<?php echo $phone ?>">Look up on CredEquity</button><br>
                                            <span class="description"><?php _e("Look up {$user->first_name} with the phone number: {$phone}"); ?> on CredEquity</span>
                                            <div id="cred-lookup-result" style="margin-top: 20px; max-height: 200px; overflow: auto; background-color: #fff; border-radius: 5px;"></div>
                                        </div>
                                        <label for="send_cred_approval_mail" >
                                            <input type="checkbox" name="send_cred_approval_mail" id="send_cred_approval_mail" value="1" />
                                            Send notification mail
                                        </label>
                                        <div style="margin-top: 5px">
                                            <textarea name="cred_approval_mail" id="cred_approval_mail" rows="5" cols="50" Placeholder="Approval mail">Dear <?php _e("{$user->first_name} {$user->last_name}") ?>, This is to inform you that your creditor status has been updated.
                                            </textarea>
                                        </div>
                                        <button type="submit" class="button button-primary" name="update_single_creditor">Update creditor</button>
                                    </form>
                                </td>
                            </tr>
                        </table>
                    </div>
                
                <?php 
            }else{
                ?>
                    <h4>User is not a credit user</4>
                <?php
            }
        }
        else{
            ?>
            <h4>User not found</h4>
            <?php
        }
    }
    
    public function render_users(){
        if(isset($_GET['single_creditor'])){
            $this->render_user($_GET['single_creditor']);
        }else{
            $this->table->prepare_items();
            ?>
            <div class="wrap">
                <h2><?php _e($this->table->showing['title'])  ?></h2>
                <div id="poststuff">
                    <div id="post-body" class="metabox-holder">
                        <div id="post-body-content">
                            <div class="meta-box-sortables ui-sortable">
                                <form method="get">
                                    <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
                                    <?php
                                        $this->table->search_box( __( 'Find', 'cred' ), 'user-find');
                                    ?>
                                </form>
                                <form method="post">
                                    <?php
                                        $this->table->display(); 
                                    ?>
                                </form>
                            </div>
                        </div>
                    </div>
                    <br class="clear">
                </div>
            </div>
            <?php
        }
    }

    public function render_configurations(){
        if(isset($_POST['credit_user_config'])){
            update_option('cred_contact_form', $_POST['cred_contact_form']);
            update_option('credequity_access_key', $_POST['credequity_access_key']);
            update_option('cred_min_credequity_score', $_POST['cred_min_credequity_score']);
            update_option('cred_max_credit', $_POST['cred_max_credit']);
        }
        ?>
        <h1>Credit User Configuration</h1>
        <form method="post">
            <div>
                <table class="form-table">
                    <tr>
                        <th><label for="contact-form"><?php _e("Contact Form ID"); ?></label></th>
                        <td>
                            <input type="text" name="cred_contact_form" id="contact-form" value="<?php _e(get_option('cred_contact_form')) ?>" class="regular-text" /><br />
                            <span class="description"><?php _e("ID of the contact from for credit user registration"); ?></span>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="contact-form"><?php _e("CredEquity Access Key"); ?></label></th>
                        <td>
                            <input type="text" name="credequity_access_key" id="credquity-access-key" value="<?php _e(get_option('credequity_access_key')) ?>" class="regular-text" /><br />
                            <span class="description"><?php _e("Access key to use CredEquity API"); ?></span>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="min-score"><?php _e("CredEquity Minimum Score"); ?></label></th>
                        <td>
                            <input type="text" name="cred_min_credequity_score" id="min-score" value="<?php _e(get_option('cred_min_credequity_score')) ?>" class="regular-text" /><br />
                            <span class="description"><?php _e("Minimum score to automatically approve new registered creditors"); ?></span>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="min-score"><?php _e("Maximum Credit"); ?></label></th>
                        <td>
                            <input type="text" name="cred_max_credit" id="max-credit" value="<?php _e(get_option('cred_max_credit')) ?>" class="regular-text" /><br />
                            <span class="description"><?php _e("Maximum amount of credit user can take"); ?></span>
                        </td>
                    </tr>

                </table>
                <button type="submit" class="button button-primary" name="credit_user_config">Save configuration</button>
            </div>
        </form>
	<?php
    }

	/** Singleton instance */
	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

}
