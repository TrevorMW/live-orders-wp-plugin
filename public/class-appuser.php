<?php

require_once $plugin_name . '/includes/class-ajax-response.php';

class AppUser
{    
    /**
     * __construct function.
     *
     * @access public
     * @param mixed $action (default: null)
     * @return void
     */
    public function __construct($data = false)
    {

    }

    //////////////////////////////////////////////////////////////////
    ////////////////////// AJAX ENDPOINTS ////////////////////////////
    //////////////////////////////////////////////////////////////////

    public function logUserIn()
	{
		$resp = new Ajax_Response();
		$username = $_POST['email'];
		$pass = $_POST['password'];

		$creds = array(
			'user_login' => $username,
			'user_password' => $pass,
			'remember' => true
		);

		$user = wp_signon($creds, false);

		if (is_wp_error($user)) {
			$resp->message = 'This username/password combination is not valid. Please try again.';
		} else {
			$resp->status = true;
			$resp->message = 'Logging you in...';
			$resp->redirectURL = site_url();
		}

		echo $resp->encodeResponse();
		die(0);
	}

	public function logUserOut()
	{
		wp_logout();

		echo json_encode(array('success' => true));

		die(0);
	}

    public function registerUser()
	{
		$resp = new Ajax_Response();
        $firstName   = $_POST['firstName'];
        $lastName    = $_POST['lastName'];
        $email       = $_POST['email'];
        $password    = $_POST['password'];
		$confirmPass = $_POST['confirmPass'];

        // If passwords dont match, then 
        if($password !== $confirmPass){
		    $resp->message = 'Passwords do not match. Please try again.';
           
            echo $resp->encodeResponse();
		    die(0);
        }

        // Lets make sure we have a valid email
        if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
		    $resp->message = 'Not a valid email address. Please add a valid one.';
           
            echo $resp->encodeResponse();
		    die(0);
        }
        
        // Lets make sure we have a valid email
        if(preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/', $password) <= 0){
		    $resp->message = 'Password does not match the requirements.';
           
            echo $resp->encodeResponse();
		    die(0);
        }

        $userdata = array(
            'user_pass' => $password,
            'user_login' => $email,
            'user_nicename' => $firstName . ' ' . $lastName,
            'user_email' => $email,
            'display_name' => $firstName,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'show_admin_bar_front' => false, 
            'role' => 'appclient',
            'locale' => 'US',
        );

        $userID = wp_insert_user( $userdata ) ;

        if($userID instanceof WP_Error){
		    $resp->message = $userID->get_error_messages()[0];//'There was a problem registering you. Please try again.';
        } else {
            wp_signon(array(
            	'user_login' => $email,
            	'user_password' => $password,
            	'remember' => true
            ), false);  

            $resp->status = true;
            $resp->data = array( 'id' => $userID );
            $resp->message = 'Logging you in...';
        }

		echo $resp->encodeResponse();
		die(0);
	}
    
    public function resetPassword()
    {
        $resp = new Ajax_Response();
        $username = $_POST['username'];

        // FOR TESTING:  Oauth::refreshUserTokens();

        $crypt = new OauthCrypt();

        $str = $crypt->encrypt('');
        var_dump($str);

        // If passwords dont match, then 
        // if(!$username){
        //     $resp->status = false;
        //     $resp->message = 'A password reset email could not be generated, please try again.';
           
        //     echo $resp->encodeResponse();
		//     die(0);
        // }

        // $result = retrieve_password( $username ) ;

        // if(!$result){
        //     $resp->status = false;
        //     $resp->message = 'A password reset email could not be generated, please try again.';
        // } else {
        //     $resp->status = true;
        //     $resp->message = 'An password reset email has been sent to "' . $username . '"';
        // }

		echo $resp->encodeResponse();
        
		die(0);
    }
}
