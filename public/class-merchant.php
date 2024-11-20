<?php

$plugin_name = dirname(__FILE__, 2);

require_once $plugin_name . '/includes/class-oauthcrypt.php';
require_once $plugin_name . '/includes/class-utilities.php';
require_once $plugin_name . '/public/class-oauth.php';
require_once $plugin_name . '/vendor/autoload.php';

use Square\SquareClient;
use Square\Environment;
use Square\Models\SubscriptionStatus;

$dotenv = Dotenv\Dotenv::createImmutable(ABSPATH);
$dotenv->load();
class Merchant
{
    public $ID;
    public $name;
    public $merchantID;
    public $location;
    public $merchantRestaurants;
    public $posType;
    public $tokenExpiresAt;
    public $isConnected = false;
    public $subscriptionID;
    public $planID;
    public $customerID;
    public $mainLocationID;

    /**
     * __construct function.
     *
     * @access public
     * @param mixed $action (default: null)
     * @return void
     */
    public function __construct($id = false)
    {
        // if we pass an ID, then we load all the current data.
        if ($id) {
            $post = get_post($id);

            if ($post instanceof WP_Post) {
                $this->ID = $id;
                $this->name = $post->post_title;
                $this->loadMerchantMeta();

                $token = Merchant::getAccessToken($this->ID);

                if ($token) {
                    $this->isConnected = true;
                }
            }
        }
    }

    public static function createNewMerchant($name)
    {
        $merchant = array(
            'post_title' => wp_strip_all_tags($name),
            'post_type' => 'merchant',
            'post_status' => 'publish'
        );

        // Insert the post into the database
        return wp_insert_post($merchant);
    }

    public function loadMerchantMeta()
    {
        $id = $this->ID;

        $this->merchantID = get_field('merchant_id', $id);
        $this->location = get_field('location_id', $id);
        $this->merchantRestaurants = get_field('merchant_restaurants', $id);
        $this->posType = get_field('pos_type', $id);
        $this->tokenExpiresAt = get_field('square_api_token_expiration_date', $id);
        $this->subscriptionID = get_field('subscription_id', $id);
        $this->planID = get_field('subscription_plan_id', $id);
        $this->customerID = get_field('customer_id', $id);
        $this->mainLocationID = get_field('main_location_id', $id);
    }

    public function saveMerchantMeta()
    {
        $id = $this->ID;

        update_field('merchant_id', $this->merchantID, $this->ID);
        update_field('location_id', $this->location, $id);
        update_field('merchant_restaurants', $this->merchantRestaurants, $id);
        update_field('pos_type', $this->posType, $id);
        update_field('square_api_token_expiration_date', $this->tokenExpiresAt, $id);
        update_field('subscription_id', $this->subscriptionID, $this->ID);
        update_field('subscription_plan_id', $this->planID, $this->ID);
        update_field('customer_id', $this->customerID, $this->ID);
        update_field('main_location_id', $this->mainLocationID, $this->ID);
    }

    public function assignMerchantToUser($ID, $userID)
    {
        $curr = get_field('connected_merchant_accounts', 'user_' . $userID, false);

        if(!$curr){
            $curr = [];
        }

        $curr[] = $ID;

        return update_field('connected_merchant_accounts', $curr, 'user_' . $userID);
    }

    public function assignRestaurantToMerchant($id, $merchantID)
    {
        $curr = get_field('merchant_restaurants', $merchantID);

        if(!$curr){
            $curr = [];
        }
        
        $curr[] = $id;

        return update_field('merchant_restaurants', $curr, $merchantID);
    }

    public function getSubscriptionDetails($client = false, $id = false)
    {
        if (!$client) {
            $client = Oauth::getSquareClient();
        }

        $details = array();
        $subscription = null;

        if ($client) {
            $api_response = $client->getSubscriptionsApi()->retrieveSubscription($id);

            if ($api_response->isSuccess()) {
                $subscription = $api_response->getResult()->getSubscription();
            }
        }

        if ($subscription !== null) {
            $details = array(
                'id' => $subscription->getID(),
                'plan' => $this->getSubscriptionPlanDetails($client, $subscription->getPlanVariationId()),
                'customerId' => $subscription->getCustomerId(),
                'status' => $subscription->getStatus(),
                'billingDate' => $subscription->getMonthlyBillingAnchorDate(),
                'invoiceData' => $this->getInvoiceData($client, $subscription->getInvoiceIds())
            );
        }

        return $details;
    }

    public function getSubscriptionPlanDetails($client, $planId)
    {
        if (!$client) {
            $client = Oauth::getSquareClient();
        }

        $data = array();

        if ($planId) {
            $api_response = $client->getCatalogApi()->retrieveCatalogObject($planId, true);

            if ($api_response->isSuccess()) {
                $planData = $api_response->getResult()->getObject()->getSubscriptionPlanVariationData();

                if ($planData) {
                    $data = array(
                        'name' => $planData->getName(),
                        'price' => ''
                    );
                }
            }
        }

        return $data;
    }

    public function getInvoiceData($client, $ids)
    {
        if ($ids === null || count($ids) <= 0) {
            return [];
        } else {
            $data = [];
            $invoices = [];

            if (!$client) {
                $client = Oauth::getSquareClient();
            }

            $ids = array_slice($ids, 0, 3);

            for ($i = 0; $i <= count($ids); $i++) {
                $invoiceID = $ids[$i];
                $resp = $client->getInvoicesApi()->getInvoice($invoiceID);

                if ($resp->isSuccess()) {
                    $invoice = $resp->getResult();
                }

                if ($invoice) {
                    $invoices[] = array(
                        '' => '',
                        '' => '',
                    );
                }
            }

            return $data;
        }
    }

    //////////////////////////////////////////////////////////////////////////
    ////////////////////// STATIC TOKEN FUNCTIONS ////////////////////////////
    //////////////////////////////////////////////////////////////////////////

    public static function getAccessToken($id)
    {
        $crypt = new OauthCrypt();
        $encryptedToken = get_field('square_api_access_token', $id);
        return $encryptedToken ? $crypt->decrypt($encryptedToken) : null;
    }

    public static function setAccessToken($token, $id)
    {
        $crypt = new OauthCrypt();
        return update_field('square_api_access_token', $crypt->encrypt($token), $id);
    }

    public static function removeAccessToken($id)
    {
        return update_field('square_api_access_token', '', $id);
    }

    public static function getRefreshToken($id)
    {
        $crypt = new OauthCrypt();
        $encryptedToken = get_field('square_api_refresh_token', $id);
        return $encryptedToken ? $crypt->decrypt($encryptedToken) : null;
    }

    public static function setRefreshToken($token, $id)
    {
        $crypt = new OauthCrypt();
        return update_field('square_api_refresh_token', $crypt->encrypt($token), $id);
    }

    //////////////////////////////////////////////////////////////////
    ////////////////////// AJAX ENDPOINTS ////////////////////////////
    //////////////////////////////////////////////////////////////////

    public function disconnectMerchantOauth()
    {
        $id = $_POST['id'];
        $resp = new Ajax_Response($_POST['action']);

        if ($id) {
            $rest = new Restaurant($id);

            if ($rest instanceof Restaurant) {
                $result = $rest->removeAccessToken($id);

                $resp->status = true;
                $resp->message = $result ? 'Oauth successfully disconnected!' : 'Could not disconnect Oauth. Try again.';
            } else {
                $resp->status = false;
                $resp->message = 'Could not load POS system.';
            }
        } else {
            $resp->status = false;
            $resp->message = 'Could not disconnect Oauth. Try again.';
        }

        echo json_encode($resp);

        die(0);
    }
    public function reauthorizeOauth()
    {
        $id = $_POST['id'];
        $resp = new Ajax_Response($_POST['action']);
        $rest = null;

        if (!$id) {
            $resp->status = false;
            $resp->message = 'Could not disconnect Oauth. Try again.';
        } else {
            $rest = new Restaurant($id);
        }

        if ($rest instanceof Restaurant) {

            try {
                $token = Restaurant::getAccessToken($id);
                $refresh = Restaurant::getRefreshToken($id);
                $crypt = new OauthCrypt();

                $clientID = $crypt->decrypt($_ENV['SQ_APPLICATION_ID']);
                $secret = $crypt->decrypt($_ENV['SQ_APPLICATION_SECRET']);

                if ($refresh && $secret && $clientID) {
                    $scopes = Oauth::getOAuthScopesString();

                    $body = new \Square\Models\ObtainTokenRequest($clientID, 'refresh_token');
                    $body->setClientSecret($secret);
                    $body->setRefreshToken($refresh);
                    $body->setScopes([$scopes]);

                    $client = new SquareClient([
                        'accessToken' => $refresh,
                        'environment' => $_ENV['SQ_ENVIRONMENT'] == "sandbox" ? Environment::SANDBOX : Environment::PRODUCTION,
                    ]);

                    $api_response = $client->getOAuthApi()->obtainToken($body);

                    if ($api_response->isSuccess()) {
                        $result = $api_response->getResult();

                        $newToken = $result->getAccessToken();
                        $newRefresh = $result->getRefreshToken();

                        Restaurant::setAccessToken($newToken, $id);
                        Restaurant::setRefreshToken($newRefresh, $id);

                        $rest->tokenExpiresAt = $result->getExpiresAt();

                        $rest->saveRestaurantMeta();

                        $resp->status = true;
                        $resp->message = 'Oauth token successfully re-authorized.';
                    } else {
                        $errors = $api_response->getErrors();
                        $resp->status = false;
                        $resp->message = $errors[0];
                        $resp->redirectURL = Oauth::getSquareOauthAuthorizeLink(null, array(
                            'restaurant' => $rest->ID
                        ));
                    }
                }
            } catch (Exception $e) {
                $resp->status = false;
                $resp->message = $e->getMessage();
                $resp->redirectURL = Oauth::getSquareOauthAuthorizeLink(null, array(
                    'restaurant' => $rest->ID
                ));
            }
        } else {
            $resp->status = false;
            $resp->message = 'Could not load POS system.';
        }

        echo json_encode($resp);

        die(0);
    }
    public function loadMerchantData()
    {
        $id = (int) $_POST['id'];

        $resp = new Ajax_Response($_POST['action']);

        $resp->data = array(
            'merchant' => []
        );

        // if we dont have an ID, get all reports data.
        if ($id) {
            $merch = new Merchant(id: $id);

            $connectionDetails = OAuth::testOauthConnection($merch->ID);

            $resp->data['merchant']['ID'] = $merch->ID;
            $resp->data['merchant']['slug'] = $merch->name;
            $resp->data['merchant']['merchantID'] = $merch->merchantID;
            $resp->data['merchant']['name'] = html_entity_decode($merch->name);
            $resp->data['merchant']['type'] = array(
                'value' => 'square'
            );

            $user = wp_get_current_user();

            $resp->data['merchant']['oauth'] = array(
                'isConnected' => $connectionDetails['connected'],
                'link' => OAuth::getSquareOauthAuthorizeLink(null, array('userID' => $user->data->ID)),
                'expiration' => Utilities::convertDateToUserFriendly($connectionDetails['expiration']),
                'scopes' => $connectionDetails['scopes']
            );

            //$resp->data['merchant']['subscription'] = $merch->getSubscriptionDetails(Oauth::getSquareClient(), $merch->subscriptionID);
            $resp->data['locations'] = [];

            if ($merch->merchantRestaurants) {
                foreach ($merch->merchantRestaurants as $k => $loc) {
                    $rest = new Restaurant($loc->ID);

                    $resp->data['locations'][] = array(
                        'id' => $rest->ID,
                        'name' => $rest->name,
                        'postype' => $rest->posType,
                        'timezone' => $rest->timezone,
                        'locationID' => $rest->location
                    );
                }
            }

            $resp->status = true;
        } else {
            $resp->status = false;
            $resp->message = 'Could not load Merchant Data.';
        }

        echo $resp->encodeResponse();

        die(0);
    }

    public function deleteSubscriptionViaApi($client, $subscriptionID)
    {
        return $client->getSubscriptionsApi()->cancelSubscription($subscriptionID);
    }

    public function deleteSubscription()
    {
        $id = $_POST['subscriptionID'];
        $resp = new Ajax_Response($_POST['action']);

        if ($id) {
            $client = Oauth::getSquareClient();

            $api_response = $this->deleteSubscriptionViaApi($client, $id);

            if ($api_response->isSuccess()) {
                $result = $api_response->getResult();

                $sub = $result->getSubscription();

                if ($sub->getStatus() === SubscriptionStatus::CANCELED) {
                    $cancelled = Utilities::convertDateToUserFriendly($sub->getCanceledDate());
                    $resp->status = true;
                    $resp->message = "Your subscription has been cancelled as of $cancelled. ";
                } else {
                    $resp->message = 'Unable to cancel your subscription, please try again.';
                    $resp->status = false;
                }
            } else {
                //$errors = $api_response->getErrors();
                $resp->message = 'Unable to cancel your subscription, please try again.';
                $resp->status = false;
            }
        }

        $resp->encodeResponse();

        die(0);
    }
    public function revokeToken()
    {
        $id = $_POST['merchantID'];
        $resp = new Ajax_Response($_POST['action']);

        if ($id) {
            try {
                $crypt = new OauthCrypt();

                $clientID = $crypt->decrypt($_ENV['SQ_APPLICATION_ID']);
                $secret = $crypt->decrypt($_ENV['SQ_APPLICATION_SECRET']);
                
                $merchant = new Merchant($id);

                $client = Oauth::getSquareClient($id);
                $body = new \Square\Models\RevokeTokenRequest();
                $body->setClientId($clientID);

                $token = Merchant::getAccessToken($id);
                $body->setAccessToken($token);

                // first, lets cancel the subscription, so the person doesnt have to go through the cancellation part of the subscription re-auth
                // $this->deleteSubscriptionViaApi($client, $merchant->subscriptionID);

                // Next, lets revoke the token
                $api_response = $client->getOAuthApi()->revokeToken($body, "Client $secret");

                if ($api_response->isSuccess()) {
                    // if we successfully revoked the token, we can remove the tokens from the merchant record
                    Merchant::setAccessToken('', $id);
                    Merchant::setRefreshToken('', $id);

                    $resp->message = 'You have successfully revoked your oAuth token.';
                    $resp->status = true;
                } else {
                    $resp->message = 'Your Oauth token has been revoked successfully.';
                    $resp->status = false;
                }
            } catch(Exception $e){
                write_log('Error trying to revoke token for merchant (' . $merchant->merchantID . ') - ' . $e->getMessage());
                $resp->message = $e->getMessage();
                $resp->status = false;
            }
        }

        $resp->encodeResponse();

        die(0);
    }
    public function deleteMerchant()
    {
        $id = $_POST['restaurant_id'];
        $resp = new Ajax_Response($_POST['action']);

        if ($id) {
            $post = get_post($id);
            $title = html_entity_decode($post->post_title);
            $msg = $title . ' was successfully deleted.';
            $error = $title . ' could not be deleted. Please try again.';

            $result = wp_delete_post($id, true);

            if ($result instanceof WP_Post) {
                $resp->status = true;
                $resp->message = $msg;
            } else {
                $resp->status = false;
                $resp->message = $error;
            }
        } else {
            $resp->status = false;
            $resp->message = $error;
        }

        echo json_encode($resp);

        die(0);
    }
}
