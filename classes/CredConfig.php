<?php 
class CredConfig {

    public static $ROLE = 'creditor';

    public static function FORMID(){
        return get_option('cred_contact_form', 7);
    }

    public static function CRED_SCORE(){
        return get_option('cred_min_credequity_score', 100);
    }

    public static function CRED_API_URL(){
        return 'http://102.164.38.38/CreditBureau/api/v1/CECREDPro';
    }

    public static function CRED_ACCESS_KEY(){
        return get_option('credequity_access_key', '');
    }
}