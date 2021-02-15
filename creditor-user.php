<?php
/**
 * Plugin Name: Creditor User
 * Plugin URI: 
 * Description: Create creditor user and check if user is eligible to pay on credit using CredEquity Api
 * Version: 1.0
 * Author: Adedayo Matthews
 * Author URI: http://adedayomatt.com
 */

// auto load dependencies
require __DIR__.'/classes/CredConfig.php';
require __DIR__.'/classes/CredEquity.php';
require __DIR__.'/classes/CreditorsPage.php';


add_action( 'plugins_loaded', 'init_creditors_pages', 11 );

function init_creditors_pages(){
	CreditorsPage::get_instance();
	
}

add_action( 'admin_enqueue_scripts', 'load_cred_scripts' );
    function load_cred_scripts() {
      wp_register_script( 'cred_script', plugins_url( 'js/script.js', __FILE__ ), array( 'jquery') );
    wp_localize_script( 'cred_script', 'params', [
        'cred_api' => CredConfig::CRED_API_URL(),
        'cred_access_key' => CredConfig::CRED_ACCESS_KEY()
    ]);
    wp_enqueue_script( 'cred_script' );
}

register_activation_hook( __FILE__, 'cred_plugin_activate' );

function cred_plugin_activate() {

    if(!get_option('credit_user_role_created', false))
    {
        $customer_capabilities = get_role('customer')->capabilities ?? [];
        
        add_role(CredConfig::$ROLE, 'Creditor', array_merge($customer_capabilities, ['can_take_credit']));
        update_option( 'credit_user_role_created', true );
    }
    
}

register_deactivation_hook( __FILE__, 'cred_plugin_deactivate' );
function cred_plugin_deactivate() {
    update_option( 'credit_user_role_created', false );
}


add_action( 'wpcf7_before_send_mail', 'cred_create_credit_user' );
function cred_create_credit_user($form)
{
    $formID = CredConfig::FORMID();
    
    if($form->id == $formID){
        $user_id = wp_insert_user( array(
            'user_login' => $_POST['username'],
            'user_pass' => $_POST['password'],
            'user_email' => $_POST['email'],
            'first_name' => $_POST['first_name'],
            'last_name' => $_POST['last_name'],
            'display_name' => $_POST['username'],
            'role' => CredConfig::$ROLE,
        ));
        $cred = new CredEquity;
        $result = json_decode($cred->lookUp($_POST['phone']));
        if($result->message == 'Succesfull' && $result->data){
            $credit_worthy = $result->data->score && $result->data->score >= CredConfig::CRED_SCORE() ? true : false;
        }else{
            $credit_worthy = false;
        }

        update_user_meta($user_id, 'phone' , $_POST['phone']);
        update_user_meta($user_id, 'credit_worthy' , $newvalue = $credit_worthy);

        wp_signon([
                'user_login' => $_POST['username'],
                'user_password' => $_POST['password'],
                'remember' => true,
            ]
        );
    }
}

add_filter( 'wpcf7_validate_email*', 'cred_validate_email', 10, 2  );
function cred_validate_email(  $result, $tag )
{
    $type = $tag['type'];
    $name = $tag['name'];
    $value = $_POST[$name] ;
    $user =  get_user_by( 'email', $value);
    if($user){
        $result->invalidate( $tag, wpcf7_get_message( 'email_taken' ) );
    }
    return $result;
}

add_filter( 'wpcf7_validate_text*', 'cred_validate_username', 10, 2  );
function cred_validate_username(  $result, $tag )
{
    $type = $tag['type'];
    $name = $tag['name'];
    $value = $_POST[$name];
    if($name == 'username'){
        $user = get_user_by('login', $value);
        if($user){
            $result->invalidate( $tag, wpcf7_get_message( 'username_taken' ) );
        }
    }
   
    return $result;
}

add_filter( 'wpcf7_messages', 'cred_validation_messages' );
function cred_validation_messages( $messages ) {
    return array_merge( $messages, array(
        'email_taken' => array(
            'description' => __( "Email is already taken by another user.", 'contact-form-7' ),
            'default' => __( 'Email is already taken by another user.', 'contact-form-7' )
        ),
        'username_taken' => array(
            'description' => __( "Username is already taken by another user.", 'contact-form-7' ),
            'default' => __( 'Username is already taken by another user.', 'contact-form-7' )
        )
    ));

}

add_action( 'show_user_profile', 'cred_user_profile_fields' );
add_action( 'edit_user_profile', 'cred_user_profile_fields' );

function cred_user_profile_fields( $user ) { 
    $phone = get_user_meta($user->id, 'phone', true );
    $credit_worthy = get_user_meta($user->id, 'credit_worthy', true );

    if(is_admin() && user_can($user->id, 'creditor')){        
        ?>
            <table class="form-table">
                <tr>
                    <th><label for="phone"><?php _e("Phone"); ?></label></th>
                    <td>
                        <input type="tel" name="phone" id="phone" value="<?php echo $phone; ?>" class="regular-text" /><br />
                        <span class="description"><?php _e("Active phone number"); ?></span>
                    </td>
                </tr>
            </table>
            
        <?php 
    }
}

add_action( 'personal_options_update', 'cred_extra_user_profile_fields' );
add_action( 'edit_user_profile_update', 'cred_extra_user_profile_fields' );

function cred_extra_user_profile_fields( $user_id ) {
    
    if(current_user_can( 'edit_user', $user_id )){
        update_user_meta( $user_id, 'phone', $_POST['phone'] );
    }
  
}