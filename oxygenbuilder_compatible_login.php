<?php 
if ( ! defined( 'WPINC' )) 
    wp_die();

 /* add_action('login_form_logout', 'redirect_to_custom_logout'); */

/**
* Redirect the user to the custom login page instead of wp-login.php.
*/
function redirect_to_custom_login() {
    /* exit if not called using GET  */
    if ("GET" != $_SERVER['REQUEST_METHOD'] ) 
        return;

    $redirect_to = isset( $_REQUEST['redirect_to'] ) ? $_REQUEST['redirect_to'] : null;

    if ( is_user_logged_in() ) {
        redirect_logged_in_user( $redirect_to );
        exit;
    }

    // The rest are redirected to the login page
    $login_url = home_url( 'wachtwoord' );
    if ( ! empty( $redirect_to ) ) {
        $login_url = add_query_arg( 'redirect_to', $redirect_to, $login_url );
    }

    wp_redirect( $login_url );
    exit;
}
add_action( 'login_form_login',  'redirect_to_custom_login' );

/**
* Redirects the user to the custom registration page instead
* of wp-login.php?action=register.
*/
function redirect_to_custom_register() {
    /* exit if called using GET  */
    if ("GET" != $_SERVER['REQUEST_METHOD'] ) 
      exit;

    if ( is_user_logged_in() ){
        redirect_logged_in_user();
        exit;
    }

    wp_redirect( home_url( 'inschrijven' ) );
    exit;
}
add_action( 'login_form_register', 'redirect_to_custom_register' );

/**
* Redirects the user to the correct page depending on whether he / she
* is an admin or not.
*
* @param string $redirect_to   An optional redirect_to URL for admin users
*/
function redirect_logged_in_user( $redirect_to = null ) {
      
    $is_admin = (user_can( wp_get_current_user() , 'manage_options'));
    
    if (! $is_admin){    
        $redirect_url = $redirect_to ?: home_url( 'mijn-sndt' ); 
        wp_safe_redirect( $redirect_url );
        exit;
    }
    
    $redirect_url = $redirect_to ?: admin_url(); 
    wp_safe_redirect( $redirect_url );
    exit;
}

/*
function redirect_to_custom_logout (){
$redirect_url = home_url( 'wachtwoord?action=logout' );
wp_safe_redirect( $redirect_url );
exit;		
}*/


/**
* Redirect to custom login page after the user has been logged out.
*/
function redirect_after_logout() {
    $redirect_url = home_url( 'wachtwoord?action=loggedout' );
    wp_safe_redirect( $redirect_url );
    exit;
}
add_action( 'wp_logout', 'redirect_after_logout' );


/**
* Returns the URL to which the user should be redirected after the (successful) login.
*
* @param string           $redirect_to           The redirect destination URL.
* @param string           $requested_redirect_to The requested redirect destination URL passed as a parameter.
* @param WP_User|WP_Error $user                  WP_User object if login was successful, WP_Error object otherwise.
*
* @return string Redirect URL
*/
function redirect_after_login( $redirect_to, $requested_redirect_to, $user ) {

    if ( ! isset( $user->ID ) ) 
        return add_query_arg( 'login', $user, home_url( 'wachtwoord' ) );

    if ( user_can($user, 'manage_options') ) {
        $url = $redirect_to ?: admin_url();
        return wp_validate_redirect( $url, home_url() ); 
    }

    $url = $redirect_to ?: home_url( 'mijn-sndt/?tab=1' ); 
    return wp_validate_redirect( $url, home_url() );    
}
add_filter( 'login_redirect', 'redirect_after_login' , 10, 3 ); 

/**
* Redirects the user to the custom "Forgot your password?" page instead of
* wp-login.php?action=lostpassword.
*/
function redirect_to_custom_lostpassword() {
    if ('GET' != $_SERVER['REQUEST_METHOD'] ) 
        exit;

    if ( is_user_logged_in() ) {
        redirect_logged_in_user();
        exit;
    }
    $redirect_url = add_query_arg( 'action', 'lostpassword', home_url( 'wachtwoord' ) );
    wp_redirect( $redirect_url );
    exit;
}
add_action( 'login_form_lostpassword', 'redirect_to_custom_lostpassword');

/**
* Redirects to the custom password reset page, or the login page
* if there are errors.
*/
function redirect_to_custom_password_reset() {
    if ( 'GET' != $_SERVER['REQUEST_METHOD'] ) 
        return;
 
    // Verify key / login combo
    $user = custom_check_password_reset_key( $_REQUEST['key'], $_REQUEST['login'], $_REQUEST['email'] );
    
    //if no user was found, exit
    if(! $user){
        wp_redirect( home_url( 'wachtwoord?action=retry&login=invalidkey' ) );
        exit;
    }
   
    //is user is a wp_error object, attempt to handle and exit. 
    if( is_wp_error( $user ) ){   
        if($user->get_error_code() === 'expired_key')
            wp_redirect( home_url( 'wachtwoord?action=retry&login=expiredkey' ) );
        else
            wp_redirect( home_url( 'wachtwoord?action=retry&login=invalidkey' ) );

        exit;    
    }

    //all is good 
    $redirect_url = add_query_arg( 'action', 'reset', home_url( 'wachtwoord' ) );
    $redirect_url = add_query_arg( 'login', esc_attr( $_REQUEST['login'] ), $redirect_url );
    $redirect_url = add_query_arg( 'email', esc_attr( $_REQUEST['email'] ), $redirect_url );
    $redirect_url = add_query_arg( 'key', esc_attr( $_REQUEST['key'] ), $redirect_url );

    wp_redirect( $redirect_url );
    exit;
    
}
add_action( 'login_form_rp',        'redirect_to_custom_password_reset' );
add_action( 'login_form_resetpass', 'redirect_to_custom_password_reset' );

/**
* Initiates password reset.
*/
function do_password_lost() {
    if ( 'POST' != $_SERVER['REQUEST_METHOD'] )
        return;

    $errors = retrieve_password();
    if ( is_wp_error( $errors ) ) {
        // Errors found
        $redirect_url = home_url( 'wachtwoord' );
        $redirect_url = add_query_arg( array(
            'action' => 'lostpassword',
            'errors' => join( ',', $errors->get_error_codes() ),
            ), 
            $redirect_url );
    } else {
        // Email sent
        $redirect_url = home_url( 'wachtwoord' );
        $redirect_url = add_query_arg( 'action', 'checkemail', $redirect_url );
    }

    wp_redirect( $redirect_url );
    exit;
}
add_action( 'login_form_lostpassword', 'do_password_lost' );

/**
* Resets the user's password if the password reset form was submitted.
*/
function do_password_reset() {
    if ( 'POST' != $_SERVER['REQUEST_METHOD'] ) 
        exit;

    $rp_key = $_REQUEST['rp_key'];
    $rp_login = $_REQUEST['rp_login'];
    $rp_email = $_REQUEST['rp_email'];

    $user = custom_check_password_reset_key( $rp_key, $rp_login, $rp_email );
    //$user = check_password_reset_key( $rp_key, $rp_login );
    
    if ( ! $user || is_wp_error( $user ) ) {
        if ( $user && $user->get_error_code() === 'expired_key' ) {
            wp_redirect( home_url( 'wachtwoord?action=retry&login=expiredkey' ) );
        } else {
            wp_redirect( home_url( 'wachtwoord?action=retry&login=invalidkey' ) );
        }
        exit;
    }

    if ( isset( $_POST['pass1'] ) ) {
        if ( $_POST['pass1'] != $_POST['pass2'] ) {
            // Passwords don't match
            $redirect_url = add_query_arg( 'action', 'reset', home_url( 'wachtwoord' ) );

            $redirect_url = add_query_arg( 'key', $rp_key, $redirect_url );
            $redirect_url = add_query_arg( 'login', $rp_login, $redirect_url );
            $redirect_url = add_query_arg( 'error', 'password_reset_mismatch', $redirect_url );

            wp_redirect( $redirect_url );
            exit;
        }

        if ( empty( $_POST['pass1'] ) ) {
            // Password is empty
            $redirect_url = add_query_arg( 'action', 'reset', home_url( 'wachtwoord' ) );

            $redirect_url = add_query_arg( 'key', $rp_key, $redirect_url );
            $redirect_url = add_query_arg( 'login', $rp_login, $redirect_url );
            $redirect_url = add_query_arg( 'error', 'password_reset_empty', $redirect_url );

            wp_redirect( $redirect_url );
            exit;
        }

        // Parameter checks OK, reset password
        reset_password( $user, $_POST['pass1'] );
        wp_redirect( home_url( 'wachtwoord?action=done&sndtemail=' . $rp_email ) );
    } else {
        echo "Invalid request.";
    }

    exit;
    
}
add_action( 'login_form_rp',        'do_password_reset' );
add_action( 'login_form_resetpass', 'do_password_reset' );

/*
* We need to customize this one to allow 
* for reset keys using email adresses.
*/
function custom_check_password_reset_key( $key, $login, $email ) {
    global $wpdb, $wp_hasher;

    $key = preg_replace( '/[^a-z0-9]/i', '', $key );

    if ( empty( $key ) || ! is_string( $key ) ) {
        return new WP_Error( 'invalid_key', __( 'Invalid key.' ) );
    }

    if ( empty( $login ) || ! is_string( $login ) ) {
        return new WP_Error( 'invalid_key', __( 'Invalid key.' ) );
    }
    
    /* decode login string replacing raw url codes 
    *  with the appropriate special characters, the @ symbol in particular.
    */
    if($email){
        $email = rawurldecode($email);
        $user = get_user_by( 'email', trim( wp_unslash( $email ) ) );
    } else {
        $user = get_user_by( 'login', trim( wp_unslash( $login ) ) );
    }
    /* 
    * if login string contains @ sign treat it as an email adress
    * and get user by email rather than login.
    * 
    */
    
    if ( ! $user ) {
        return new WP_Error( 'invalid_user', __( 'Invalid user.' ) );
    }

    if ( empty( $wp_hasher ) ) {
        require_once ABSPATH . WPINC . '/class-phpass.php';
        $wp_hasher = new PasswordHash( 8, true );
    }

    /**
     * Filters the expiration time of password reset keys.
     *
     * @since 4.3.0
     *
     * @param int $expiration The expiration time in seconds.
     */
    $expiration_duration = apply_filters( 'password_reset_expiration', DAY_IN_SECONDS );

    if ( false !== strpos( $user->user_activation_key, ':' ) ) {
        list( $pass_request_time, $pass_key ) = explode( ':', $user->user_activation_key, 2 );
        $expiration_time                      = $pass_request_time + $expiration_duration;
    } else {
        $pass_key        = $user->user_activation_key;
        $expiration_time = false;
    }

    if ( ! $pass_key ) {
        return new WP_Error( 'invalid_key', __( 'Invalid key.' ) );
    }

    $hash_is_correct = $wp_hasher->CheckPassword( $key, $pass_key );

    if ( $hash_is_correct && $expiration_time && time() < $expiration_time ) {
        return $user;
    } elseif ( $hash_is_correct && $expiration_time ) {
        // Key has an expiration time that's passed.
        return new WP_Error( 'expired_key', __( 'Invalid key.' ) );
    }

    if ( hash_equals( $user->user_activation_key, $key ) || ( $hash_is_correct && ! $expiration_time ) ) {
        $return  = new WP_Error( 'expired_key', __( 'Invalid key.' ) );
        $user_id = $user->ID;

    /**
     * Filters the return value of check_password_reset_key() when an
     * old-style key is used.
     *
     * @since 3.7.0 Previously plain-text keys were stored in the database.
     * @since 4.3.0 Previously key hashes were stored without an expiration time.
     *
     * @param WP_Error $return  A WP_Error object denoting an expired key.
     *                          Return a WP_User object to validate the key.
     * @param int      $user_id The matched user ID.
     */
        return apply_filters( 'password_reset_key_expired', $return, $user_id );
    }

    return new WP_Error( 'invalid_key', __( 'Invalid key.' ) );
}
add_filter( 'check_password_reset_key' , 'custom_check_password_reset_key', 10, 4 ); 

/* for more convenient link making in oxygen  */ 

function sndt_get_login_url(){
    return	esc_url( wp_login_url( get_permalink() ) );
}

function sndt_get_logout_url(){
    return esc_url( wp_logout_url( get_permalink() ) );
}
/**
* Returns the message body for the password reset mail.
* Called through the retrieve_password_message filter.
*
* @param string  $message    Default mail message.
* @param string  $key        The activation key.
* @param string  $user_login The username for the user.
* @param WP_User $user_data  WP_User object.
*
* @return string   The mail message to send.
*/
function replace_retrieve_password_title( $title, $user_login, $user_data){

    $first_name = get_user_meta( $user_data->ID, 'first_name', true );
    
    $groet = ($first_name)? "Ha $first_name! ": '';
    
    return $groet . "Hier is de link om je wachtwoord in te stellen bij Schrijven naar de Toekomst";
}
add_filter( 'retrieve_password_title', 'replace_retrieve_password_title' , 10, 4 );

function replace_retrieve_password_message( $message, $key, $user_login, $user_data ) {
    // Create new message
    $user_email = $user_data->user_email;

    $first_name = get_user_meta( $user_data->ID, 'first_name', true );

    $msg = ($first_name)? "Hallo $first_name": 'Hallo,';
    $msg .= "\r\n\r\n";
    $msg .= "Je hebt op schrijven naar de toekomst gevraagd om een link waarmee je een nieuw wachtwoord aan kunt maken.\r\n\r\n";
    $msg .= "Je vroeg die aan voor het profiel dat verbonden is met het emailadres:  $user_email.\r\n\r\n";
    $msg .= "Die link geven we je natuurlijk met alle plezier. Alstublieft!" . "\r\n\r\n";
    $msg .=  site_url( "wp-login.php?action=rp&key=$key&email=" . rawurlencode( $user_email ) . "&login=$user_login", 'login' ) . "\r\n\r\n";
    $msg .= 'Mocht dit nou een vergissing geweest zijn, dan kun je dit bericht gewoon negeren.' . "\r\n\r\n";
    $msg .= 'Hartelijk dank!' . "\r\n";

    return $msg;
}
add_filter( 'retrieve_password_message', 'replace_retrieve_password_message' , 10, 4 );

/* ERROR HANDLING */

/**
* Redirect the user after authentication if there were any errors.
*
* @param Wp_User|Wp_Error  $user       The signed in user, or the errors that have occurred during login.
* @param string            $username   The user name used to log in.
* @param string            $password   The password used to log in.
*
* @return Wp_User|Wp_Error The logged in user, or error information if there were errors.
*/
function maybe_redirect_at_authenticate( $user, $username, $password ) {
    if("POST" != $_SERVER['REQUEST_METHOD'] )
        return $user;

    if ( is_wp_error( $user ) ) {
        $error_codes = join( ',', $user->get_error_codes() );

        $redirect_url = add_query_arg( 'login', $error_codes, home_url( 'wachtwoord' ) );

        wp_redirect( $redirect_url );
        exit;
    }

    //all is well. return the user.
    return $user;
}
add_filter( 'authenticate', 'maybe_redirect_at_authenticate' , 101, 3 ); 

/**
* Finds and returns a matching error message for the given error code.
*
* @param string $error_code    The error code to look up.
*
* @return string               An error message.
* 
* 
*/
function get_error_message( $error_code ) {
    
    switch ( $error_code ) {
        case 'empty_username':
            $error_string = 'Probeer het eens met je email adres?';
            break;

        case 'empty_password':
            $error_string = 'Je hebt een wachtwoord nodig om in te kunnen loggen.';
            break;

        case 'invalid_username':
            $error_string = "We don't have any users with that email address. Maybe you used a different one when signing up?";
            break;

        case 'incorrect_password':
            $err = "Dat wachtwoord klopte niet helemaal. <a href='%s'>Kan het zijn dat je je wachtwoord kwijt bent</a>?";
            $error_string = sprintf( $err, wp_lostpassword_url() );
            break;

        case 'empty_username':
            $error_string =  'Vul je email adres in om verder te gaan..';
            break;     

        case 'invalid_email':
        case 'invalidcombo':
            $error_string = 'We kunnen niemand vinden met dat emailadres. We weten ook niet alles.';
            break;

        case 'expiredkey':
        case 'invalidkey':
            $err = "De wachtwoord-ververs-link die je gebruikte is niet langer geldig. <a href='%s'>Vraag een hier nieuwe aan.</a>";
            $error_string = sprintf( $err, wp_lostpassword_url() );
            break;     

        case 'password_reset_mismatch':
            $error_string =  "De twee wachtwoorden die je invulde zijn niet gelijk.";
            break;       

        case 'password_reset_empty':
            $error_string = "Er is geen wachtwoord ingevuld.";            
            break;

        default:
            $error_string = 'Er is iets onverwachts voorgevallen. Wil je het later nog een keer proberen? Wij ruimen hier snel even de rommel op.';
    }   
    
    return $error_string;
}





?>
