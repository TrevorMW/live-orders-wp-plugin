<?php session_start(); ?>
<link rel="stylesheet" href="public/style.css" type="text/css">
<?php

$plugin_name = dirname(__FILE__, 3);

require $plugin_name . '/vendor/autoload.php';
require $plugin_name . '/messages.php';

use Square\Exceptions\ApiException;
use Square\SquareClient;
use Square\Environment;
use Square\Models\ObtainTokenRequest;

$dotenv = Dotenv\Dotenv::createImmutable(ABSPATH);
$dotenv->load();

// The obtainOAuthToken function shows you how to obtain a OAuth access token
// with the OAuth API with the authorization code returned to OAuth callback.
function obtainOAuthToken($authorizationCode)
{
    $crypt = new OauthCrypt();

    // Initialize Square PHP SDK OAuth API client.
    $environment = $_ENV['SQ_ENVIRONMENT'] == "sandbox" ? Environment::SANDBOX : Environment::PRODUCTION;
    $apiClient = new SquareClient([
        'environment' => $environment,
        'userAgentDetail' => "ServiceChargeAppClient" // Remove or replace this detail when building your own app
    ]);
    $oauthApi = $apiClient->getOAuthApi();
    // Initialize the request parameters for the obtainToken request.
    $body_grantType = 'authorization_code';

    $application_id = $crypt->decrypt($_ENV['SQ_APPLICATION_ID']);
    $body = new ObtainTokenRequest(
        $application_id,
        $body_grantType
    );
    $body->setCode($authorizationCode);
    $secret = $crypt->decrypt($_ENV['SQ_APPLICATION_SECRET']);
    $body->setClientSecret($secret);

    // // need to explicitly set a redirect here when using subscription flow
    $body->setRedirectUri(get_home_url() . '/callback.php');

    // // also need to set scopes when using subscription flow, cause it will not be passed through URL to oauth permissions screen normally
    $body->setScopes([OAuth::getOAuthScopesString()]);

    // Call obtainToken endpoint to get the OAuth tokens.
    try {
        $response = $oauthApi->obtainToken($body);
        
        if ($response->isError()) {
            $code = $response->getErrors()[0]->getCode();
            $category = $response->getErrors()[0]->getCategory();
            $detail = $response->getErrors()[0]->getDetail();

            //throw new Exception("Error Processing Request: obtainToken failed!\n" . $code . "\n" . $category . "\n" . $detail, 1);
            wp_redirect('/profile/' . buildRedirect(array(
                array(
                    'name' => 'status',
                    'value' => false
                ),
                array(
                    'name' => 'message',
                    'value' => $detail
                )
            )));

        } else {
            
            // Extract the tokens from the response.
            $accessToken = $response->getResult()->getAccessToken();
            $refreshToken = $response->getResult()->getRefreshToken();
            $expiresAt = $response->getResult()->getExpiresAt();
            $merchantId = $response->getResult()->getMerchantId();

            // Return the tokens along with the expiry date/time and merchant ID.
            return array($accessToken, $refreshToken, $expiresAt, $merchantId);
        }
    } catch (ApiException $e) {
        error_log($e->getMessage());
        error_log($e->getHttpResponse()->getRawBody());
        //throw new Exception("Error Processing Request: obtainToken failed!\n" . $e->getMessage() . "\n" . $e->getHttpResponse()->getRawBody(), 1);

        wp_redirect('/profile/' . buildRedirect(array(
            array(
                'name' => 'status',
                'value' => false
            ),
            array(
                'name' => 'message',
                'value' => $e->getMessage()
            )
        )));
    }
}

function buildRedirect($params = false)
{
    $paramStr = '';

    if (is_array($params) && count($params) >= 1) {
        foreach ($params as $k => $param) {
            $prefix = $k === 0 ? '?' : '&';
            $paramStr .= $prefix . $param['name'] . '=' . $param['value'];
        }
    }

    return $paramStr;
}

$userID = null;
$nonce = null;

// Handle the response.
try {
    $state = urldecode($_GET['state']);

    // FIRST WE gotta deconstruct the state variable for all the data in the request.
    $decodedData = base64_decode($_GET['state']);
    $data = json_decode($decodedData);

    $userID = $data->userID;
    $nonce = $data->nonce;

} catch (Exception $e) {
    write_log('Could not decode base 64 data in oAuth call. - ' . $e->getMessage());
    wp_redirect('/profile/' . buildRedirect(array(
        array(
            'name' => 'status',
            'value' => false
        ),
        array(
            'name' => 'message',
            'value' => '#6 - ' . $e->getMessage()
        )
    )));
    exit;
}

// Verify the state to protect against cross-site request forgery.
if ($_SESSION["auth_state"] !== $nonce) {
    write_log('oAuth nonce invalid for user: ' . $userID);

    wp_redirect('/profile/' . buildRedirect(array(
        array(
            'name' => 'status',
            'value' => false
        ),
        array(
            'name' => 'message',
            'value' => '#2 - ' . 'Could not verify state session token. Please try again.'
        )
    )));
    return;
}

// When the response_type is "code", the seller clicked Allow
// and the authorization page returned the auth tokens.
if ("code" === $_GET["response_type"]) {
    // Get the authorization code and use it to call the obtainOAuthToken wrapper function.
    $authorizationCode = $_GET['code'];

    list($accessToken, $refreshToken, $expiresAt, $merchantId) = obtainOAuthToken($authorizationCode);
    
    // Lets boot up an instance of square first, grab all locations associated with this account.
    $client = new SquareClient([
        'accessToken' => $accessToken,
        'environment' => $_ENV['SQ_ENVIRONMENT'] == "sandbox" ? Environment::SANDBOX : Environment::PRODUCTION,
    ]);

    // TODO: convert this to merchant now that weve moved tokens over.
    if($data->restaurant){
        $rest = new Restaurant($data->restaurant);

        if($rest instanceof Restaurant){
            // set these two as static methods, so we dont expose them in restaurant object
            Restaurant::setAccessToken($accessToken, $restID);
            Restaurant::setRefreshToken($refreshToken, $restID);

            wp_redirect('/profile/' . buildRedirect( array(
                array(
                    'name' => 'status',
                    'value' => true
                ),
                array(
                    'name' => 'message',
                    'value' => $rest->name . ' was successfully reauthorized!'
                )
            )));
            exit; 
        }
    }

    ///////////////////////////////////////////////////////////////////////////////////
    /////////////// MERCHANT AND LOCATIONS SETUP PHASE OF CONNECTION //////////////////
    ///////////////////////////////////////////////////////////////////////////////////
    $merchantPostID = null;
    $merchant_api_response = $client->getMerchantsApi()->retrieveMerchant($merchantId);

    if($merchant_api_response->isSuccess()){
        // get the merchant record
        $merchantRecord = $merchant_api_response->getResult()->getMerchant();

        // use it create a new merchant post
        $newMerchantPost = Merchant::createNewMerchant($merchantRecord->getBusinessName());
        
        if(!$newMerchantPost instanceof WP_Error){
            $merchant = new Merchant($newMerchantPost);
            $merchantPostID = $newMerchantPost;
            $merchant->merchantID = $merchantId;
            $merchant->posType = 'square';

            // first encode and store acces and refresh token
            Merchant::setAccessToken($accessToken, $merchant->ID);
            Merchant::setRefreshToken($refreshToken, $merchant->ID);

            // then add the expires at as well
            $merchant->tokenExpiresAt = $expiresAt;

            $subscriptionID = $_GET['subscription_id']; // get the ID from the URL
            if($subscriptionID){
                $merchant->subscriptionID = $subscriptionID;

                $subDetails = $merchant->getSubscriptionDetails($client, $subscriptionID);

                if($subDetails){
                    $merchant->planID = $subDetails['plan'][0]['name'];
                    $merchant->customerID = $subDetails['customerId'];
                }
            }
            
            $merchant->assignMerchantToUser($merchant->ID, $userID);
            $merchant->saveMerchantMeta();
        }

        $api_response = $client->getLocationsApi()->listLocations();

        if ($api_response->isSuccess() && $merchantPostID !== null) {
            $result = $api_response->getResult();
    
            if ($result) {
                // get the first location in list
                $locations = $result->getLocations();
                $restaurantID = null;
    
                if ($locations) {
                    foreach ($locations as $location) {
                        // first, create the new restaurant post in backend. 
                        $restaurantID = Restaurant::createNewRestaurant($location->getName());
    
                        // then, fill in the details!
                        $rest = new Restaurant($restaurantID);
    
                        if ($rest instanceof Restaurant) {                        
                            // all other data can be updated as a chunk, as it exists on restaurant object
                            $rest->location = $location->getID();
                            $rest->posType = 'square';
                            $rest->timezone = $location->getTimezone();
                            $rest->parentMerchantID = $merchantPostID;
                        }
                            
                        $merchant->merchantRestaurants[] = $restaurantID;

                        //save all data updates.
                        $rest->assignRestaurantToUser($restaurantID, $userID);
                        $rest->saveRestaurantMeta();
                    }
                }
            }

            $merchant->saveMerchantMeta();
            
            wp_redirect('/profile/' . buildRedirect(array(
                array(
                    'name' => 'status',
                    'value' => true
                ),
                array(
                    'name' => 'message',
                    'value' => 'You have successfully connected ' . count($locations) . ' locations!'
                )
            )));
        
            exit;
        } else {
            $errors = $api_response->getErrors();
            write_log('Could not get any location data for merchant with ID: ' . $merchantId . ' - ' . $errors);
        }
    } else {
        $errors = $merchant_api_response->getErrors();
        write_log('Could not get any merchant data for merchant with ID: ' . $merchantId . ' - ' . $errors);
    }
    
} elseif ($_GET['error']) {
    // Check to see if the seller clicked the Deny button and handle it as a special case.
    if (("access_denied" === $_GET["error"]) && ("user_denied" === $_GET["error_description"])) {
        write_log('Access Denied for oAuth for merchant: ' . $merchantId );

        wp_redirect('/profile/' . buildRedirect(array(
            array(
                'name' => 'status',
                'value' => false
            ),
            array(
                'name' => 'message',
                'value' => '#4 - ' . 'You have chosen to deny access to the app.'
            )
        )));
    }
    // Display the error and description for all other errors.
    else {
        write_log('Generic oAuth error: ' . $_GET["error"] . ' - ' . $_GET["error_description"] );

        wp_redirect('/profile/' . buildRedirect(array(
            array(
                'name' => 'status',
                'value' => false
            ),
            array(
                'name' => 'message',
                'value' => '#5 - ' . $_GET["error_description"]
            )
        )));
    }
    exit;

} else {
    write_log('Generic oAuth error.' );

    wp_redirect('/profile/' . buildRedirect(array(
        array(
            'name' => 'status',
            'value' => false
        ),
        array(
            'name' => 'message',
            'value' => '#5 - ' . 'Unknown parameters", Expected parameters were not returned'
        )
    )));
    exit;
}

?>