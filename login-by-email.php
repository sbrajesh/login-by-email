<?php
/**
 * Plugin Name: Login By Email
 * Plugin URI: http://buddydev.com/plugins/login-by-email/
 * Version: 1.0
 * Author: Brajesh Singh
 * Author URI: http://buddydev.com
 * Description: Allows site admins to allow users login by email or user name or site admin can force users to login by email only.
 * License: GPL
 */
/**
 * @todo Things I want to do
 *  - give site admins option in backend to choose between forced email login or optional email login
 *  - translate plugin
 *  - release on Buddydev/WP.org
 */
class LoginByEmail{
    private static $instance;
    
    private function __construct() {
        add_filter( 'authenticate', array( $this, 'authenticate_by_email' ), 20, 3 );
        //filter username field to say username/email
        add_filter( 'gettext', array( $this, 'filter_username_label' ), 10, 3 );
        add_filter( 'wp_login_errors', array( $this, 'filter_errors' ), 10, 2 );

    }
    /**
     * 
     * @return LoginByEmail
     */
    public static function get_instance(){
        if( !isset( self::$instance ) )
            self::$instance = new self();
        return self::$instance;
    }
    
    public function authenticate_by_email( $user, $username, $password ) {
        //if it is email only system, let us check for the username
           if( $this->force_email_login() && $username && !is_email( $username ) ) {
               $user = new WP_Error('invalid_username', __('Please enter a valid email!', 'loginbyemail' ) );
               return $user;
           }
           //if we are here, the username is  is given
            //check if the given username is email?
           if( $username && is_email( $username) ) {
                //let us find the actual username
                $user = get_user_by( 'email', $username );
                //if user not found and it is force by email login we need to throw an erro
                if(  !$user )
                    return new WP_Error( 'invalid_username', __( 'There does not exist an account with this email!', 'loginbyemail' ) );
              
                
                if(  $user && isset( $user->user_login) ) 
                    $username = $user->user_login;
                
            }  
        // now let wp authenticate it
        return wp_authenticate_username_password( NULL, $username, $password );
    }
    
    /**
     * Should we only allow email login?
     * @return boolean
     */
    public function force_email_login(){
        
        return true;
    }


    public function filter_username_label( $translation, $text, $domain ){
        global $pagenow;

        //we are filtering username label only on wp-login.php, don't want to mess other places
        if( $text=== 'Username' && $pagenow == 'wp-login.php' ){
         
            if( $this->force_email_login() )
                $translation = __( 'Email', 'loginbyemail' );
            else
            $translation = __( 'Username or Email', 'loginbyemail' );
            
        }

        return $translation;

    }
    /**
     * Filters login errors
     * @param type $errors
     * @param type $redirect
     * @return type
     */
    function filter_errors( $errors, $redirect ){


     //if there is an error   
     if( !empty( $errors ) ) {
        
         
          //if the email login is forced, we need to update the message for empty username
        if( $this->force_email_login() && $errors->get_error_message( 'empty_username' ) ){

             unset( $errors->errors['empty_username'] );
             $errors->add( 'incorrect_password', __( '<strong>ERROR</strong>: The email field is empty', 'loginbyemail' ) );

        }

        //let us check if it was caused by email
        $email = $_POST['log'];
        if( !is_email( $email ) )
            return $errors;

        //if we are here, user is using email to login
        //we only need to correct the invalid password/empty username issue
        if( $errors->get_error_message( 'incorrect_password' ) ){
            unset( $errors->errors['incorrect_password'] );
            $errors->add('incorrect_password', sprintf( __( '<strong>ERROR</strong>: The password you entered for the email <strong>%1$s</strong> is incorrect. <a href="%2$s" title="Password Lost and Found">Lost your password</a>?', 'loginbyemail' ), $email, wp_lostpassword_url() ));

        }
         
        

     }

     return $errors;
    }    
}

LoginByEmail::get_instance();

