<?php

$plugin_name = dirname(__FILE__, 2);

require_once $plugin_name . '/vendor/autoload.php';
require_once $plugin_name . '/includes/class-oauthcrypt.php';

use Square\SquareClient;
use Square\Environment;

class OAuth
{
    const OAUTH_BASE_URL = '/oauth2/authorize';
    const SUBSCRIPTION_BASE_URL = '/app-subscriptions/plans';

    const SCOPES = array(
        'ORDERS_READ',
        'EMPLOYEES_READ',
        'TIMECARDS_READ',
        'TIMECARDS_SETTINGS_READ',
        'MERCHANT_PROFILE_READ',
        'SUBSCRIPTIONS_READ'
    );

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

    public function handleCronOauthTokenRefreshes()
    {
        var_dump("CRON EXECUTED");

    }

    public static function getSquareClient($id = false)
    {
        $client = null;

        // if we give a merchant ID, then lets grab the merchant's token. 
        if($id){
            $token = Merchant::getAccessToken($id);
            $client = new SquareClient([
                'accessToken' => $token,
                'environment' => $_ENV['SQ_ENVIRONMENT'] == "sandbox" ? Environment::SANDBOX : Environment::PRODUCTION,
            ]);
        } 
        // BUT, if we dont pass it an ID, then we get the base token for the APP. 
        // Needed to get subscription data or any data tied to the main account that grants tokens
        else {
            $client = new SquareClient([
                'accessToken' => 'EAAAlj37S_fifDFaRYJvv52H8YWtWdnkVPDjK5XnyStu-SAZiPObBfEkIFDu1Jsv',
                'environment' => $_ENV['SQ_ENVIRONMENT'] == "sandbox" ? Environment::SANDBOX : Environment::PRODUCTION,
            ]);
        }

        return $client;
    }

    public static function refreshUserTokens()
    {
        $loop = new WP_Query(array(
            'post_type' => 'merchant',
            'posts_per_page' => '-1'
        ));

        if($loop->have_posts()){
            while($loop->have_posts()) : $loop->the_post() ;
                $post = $loop->post;

                $merchant = new Merchant($post->ID);

                if($merchant->tokenExpiresAt){
                    $isWithinSevenDaysOfExpiration = Utilities::dateWithinSevenDays($merchant->tokenExpiresAt);
    
                    if($merchant instanceof Merchant && $isWithinSevenDaysOfExpiration){
                        $refreshToken = Merchant::getRefreshToken($merchant->ID);
                        self::refreshMerchantToken($merchant->ID, $refreshToken);
                    }
                }
            endwhile;
        }
    }

    public static function refreshMerchantToken($merchantID, $refresh)
    {
        if(!$merchantID || !$refresh){
            return false;
        } else {
            // Lets attempt to use refresh token to update each of the user's tokens
            try{
                $crypt = new OauthCrypt();

                $clientID = $crypt->decrypt($_ENV['SQ_APPLICATION_ID']);
                $secret   = $crypt->decrypt($_ENV['SQ_APPLICATION_SECRET']);

                if ($refresh && $secret && $clientID) {
                    $body = new \Square\Models\ObtainTokenRequest($clientID, 'refresh_token');
                    $body->setClientSecret($secret);
                    $body->setRefreshToken($refresh);
                    $body->setScopes(self::SCOPES);

                    $client = new SquareClient([
                        'accessToken' => $refresh,
                        'environment' => $_ENV['SQ_ENVIRONMENT'] == "sandbox" ? Environment::SANDBOX : Environment::PRODUCTION,
                    ]);

                    $api_response = $client->getOAuthApi()->obtainToken($body);

                    if ($api_response->isSuccess()) {
                        $result = $api_response->getResult();

                        $newToken = $result->getAccessToken();
                        $newRefresh = $result->getRefreshToken();

                        Merchant::setAccessToken($newToken, $merchantID);
                        Merchant::setRefreshToken($newRefresh, $merchantID);

                        $merchant = new Merchant($merchantID);
                        $merchant->tokenExpiresAt = $result->getExpiresAt();
                        $merchant->saveMerchantMeta();
                    } 
                }
            } catch(Exception $e){
                // TODO: log failure to rotate tokens with refresh
                write_log("Failure to refresh oauth token for user: $merchantID - " . $api_response->getErrors() . ' || ' . $e->getMessage());
            }
        }
    }

    public static function getOAuthScopesString(){
        return urlencode(implode(' ', self::SCOPES));
    }

    public static function getSquareOauthAuthorizeLink($id = false, $data = false)
    {
        $link = null;
        
        $dotenv = Dotenv\Dotenv::createImmutable(ABSPATH);
        $dotenv->load();

        // Specify the permissions and url encode the spaced separated list.
        $permissions = self::getOAuthScopesString();

        $crypt = new OauthCrypt();

        // Set the Auth_State cookie with a random md5 string to protect against cross-site request forgery.
        // Auth_State will expire in 60 seconds (1 mins) after the page is loaded.
        $application_id = $crypt->decrypt($_ENV['SQ_APPLICATION_ID']);
        $environment = $_ENV['SQ_ENVIRONMENT'];

        $base_url = $environment == "sandbox" ? "https://connect.squareupsandbox.com" : "https://squareup.com";

        $link = $base_url . self::OAUTH_BASE_URL . '?client_id=' . $application_id . '&scope=' . $permissions . '&state=';

        session_start();

        // Always set a new state nonce, no matter what, so the base64 encoding on the state param becomes unguessable
        $_SESSION['auth_state'] = bin2hex(random_bytes(32));

        $hashedData = array(
            'nonce' => $_SESSION['auth_state'],
        );

        if ($data) {
            $hashedData = array_merge($hashedData, $data);
        }

        $link .= base64_encode(
            json_encode($hashedData)
        );

        return $link;
    }

    public static function testOauthConnection($id)
    {
        $status = array(
            'connected' => false,
            'scopes' => [],
            'expiration' => null,
            'errors' => []
        );

        if ($id) {
            $token = Merchant::getAccessToken($id);

            if ($token) {
                $client = new SquareClient([
                    'accessToken' => $token,
                    'environment' => 'production',
                ]);

                try {
                    $dotenv = Dotenv\Dotenv::createImmutable(ABSPATH);
                    $dotenv->load();

                    $authorization = 'bearer ' . $token;
                    $oAuthApi = $client->getOAuthApi();
                    $apiResponse = $oAuthApi->retrieveTokenStatus($authorization);

                    if ($apiResponse->isSuccess()) {
                        $resp = $apiResponse->getResult();
                        
                        $status['connected'] = true;
                        $status['scopes'] = $resp->getScopes();
                        $status['expiration'] = get_field('square_api_token_expiration_date', $id);
                    } else {
                        $status['errors'] = $apiResponse->getErrors();
                    }
                } catch (Exception $e) {
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
