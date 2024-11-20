<?php

$plugin_name = dirname( __FILE__, 2 ) ;

require_once $plugin_name . '/vendor/autoload.php';
require_once $plugin_name . '/includes/class-oauthcrypt.php';

use Square\SquareClient;

class OAuth
{
    /**
     * __construct function.
     *
     * @access public
     * @param mixed $action (default: null)
     * @return void
     */
    public function __construct()
    {
    
    }

	public static function getSquareOauthAuthorizeLink($id = false, $data = false){
		$link = null;

        $dotenv = Dotenv\Dotenv::createImmutable(ABSPATH);
        $dotenv->load();

        // Specify the permissions and url encode the spaced separated list.
        $permissions = urlencode("ORDERS_READ VENDOR_READ MERCHANT_PROFILE_READ");

        // $keyContents = file_get_contents('/usr/local/encryptKey.txt');
        // $key = \Defuse\Crypto\Key::loadFromAsciiSafeString($keyContents);
        $crypt = new OauthCrypt();

        // Set the Auth_State cookie with a random md5 string to protect against cross-site request forgery.
        // Auth_State will expire in 60 seconds (1 mins) after the page is loaded.
        $application_id = $crypt->decrypt($_ENV['SQ_APPLICATION_ID']);    
        $environment =  $_ENV['SQ_ENVIRONMENT'];

        $base_url = $environment == "sandbox" ? "https://connect.squareupsandbox.com" : "https://connect.squareup.com";

        $link = $base_url . '/oauth2/authorize?client_id=' . $application_id . '&scope=' . $permissions . '&state=';

        session_start();

        if (empty($_SESSION['auth_state'])) {
            $_SESSION['auth_state'] = bin2hex(random_bytes(32));
        }

        $hashedData = array(
            'nonce' => $_SESSION['auth_state'],
        );

        if($data){
            $hashedData = array_merge($hashedData, $data);
        }

        $link .= base64_encode(
            json_encode($hashedData)
        );
    
		return $link;
	}

    public static function getSquareClient($systemID)
    {
        $client = null;

        if($systemID){
            $token = System::getAccessToken($systemID);

            $client = new SquareClient([
                'accessToken' => $token,
                'environment' => 'production',
            ]);
        }

        return $client;
    }

	public static function testOauthConnection($id){
        
		$status = array(
			'connected' => false,
			'scopes' => [],
			'expiration' => null,
			'errors' => []
		);

		if($id){
			$token = System::getAccessToken($id);

			if($token){
				$client = new SquareClient([
					'accessToken' => $token,
					'environment' => 'production',
				]);
				
				try{
					$dotenv = Dotenv\Dotenv::createImmutable(ABSPATH);
					$dotenv->load();

					$authorization = 'bearer ' . $token;
					$oAuthApi = $client->getOAuthApi();
					$apiResponse = $oAuthApi->retrieveTokenStatus($authorization);

					if ($apiResponse->isSuccess()) {
						$resp = $apiResponse->getResult();

						$status['connected'] = true;
						$status['scopes'] = $resp->getScopes();
						$status['expiration'] = get_field('token_expires_at', $id);
					} else {
						$status['errors'] = $apiResponse->getErrors();
					}
				} catch(Exception $e){
                    var_dump($e);
                }
			}
		}

		return $status;
	}

    //////////////////////////////////////////////////////////////////
    ////////////////////// AJAX ENDPOINTS ////////////////////////////
    //////////////////////////////////////////////////////////////////

}
