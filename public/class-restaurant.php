<?php

$plugin_name = dirname(__FILE__, 2);

require_once $plugin_name . '/includes/class-oauthcrypt.php';
require_once $plugin_name . '/includes/class-utilities.php';
require_once $plugin_name . '/public/class-money.php';
require_once $plugin_name . '/public/class-oauth.php';
require_once $plugin_name . '/vendor/autoload.php';

use Square\SquareClient;
use Square\Environment;

$dotenv = Dotenv\Dotenv::createImmutable(ABSPATH);
$dotenv->load();
class Restaurant
{
    public $ID;
    public $name;
    public $location;
    public $posType;
    public $timezone;
    public $isConnected = false;
    public $parentMerchantID;


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
                $this->ID = $post->ID;
                $this->name = $post->post_title;
                $this->loadRestaurantMeta();

                $token = Merchant::getAccessToken($this->parentMerchantID);

                if ($token) {
                    $this->isConnected = true;
                }
            }
        }
    }

    public static function createNewRestaurant($name)
    {
        $restaurant = array(
            'post_title' => wp_strip_all_tags($name),
            'post_type' => 'restaurant',
            'post_status' => 'publish'
        );

        // Insert the post into the database
        return wp_insert_post($restaurant);
    }

    public function loadRestaurantMeta()
    {
        $id = $this->ID;

        $this->location = get_field('location_id', $id);
        $this->posType = get_field('pos_system_type', $id);
        $this->timezone = get_field('timezone', $id);
        $this->parentMerchantID = get_field('parent_merchant_id', $this->ID);
    }

    public function saveRestaurantMeta()
    {
        $id = $this->ID;

        update_field('location_id', $this->location, $id);
        update_field('pos_system_type', $this->posType, $id);
        update_field('timezone', $this->timezone, $id);
        update_field('parent_merchant_id', $this->parentMerchantID, $this->ID);
    }

    public function assignRestaurantToUser($ID, $userID)
    {
        $curr = get_field('connected_restaurants', 'user_' . $userID, false);

        if(!$curr){
            $curr = [];
        }

        $curr[] = $ID;

        return update_field('connected_restaurants', $curr, 'user_' . $userID);
    }

    //////////////////////////////////////////////////////////////////
    ////////////////////// AJAX ENDPOINTS ////////////////////////////
    //////////////////////////////////////////////////////////////////


    public function loadRestaurantData()
    {
        $id = (int) $_POST['id'];

        $data = array(
            'restaurant' => []
        );

        try {
            // if we dont have an ID, get all reports data.
            if ($id) {
                // OTHERWISE, get the report by it's ID.
                $post = get_post($id);

                if ($post instanceof WP_Post) {

                    $data['restaurant']['ID'] = $post->ID;
                    $data['restaurant']['slug'] = $post->post_name;
                    $data['restaurant']['name'] = html_entity_decode($post->post_title);
                    
                    $user = wp_get_current_user();

                    $rest = new Restaurant($id);
                    $merchant = new Merchant($rest->parentMerchantID);
                    $connectionDetails = OAuth::testOauthConnection($merchant->ID);

                    $data['restaurant']['oauth'] = array(
                        'isConnected' => $connectionDetails['connected'],
                        'link' => OAuth::getSquareOauthAuthorizeLink(null, array('userID' => $user->data->ID)),
                        'expiration' => Utilities::convertDateToUserFriendly($connectionDetails['expiration']),
                        'scopes' => $connectionDetails['scopes']
                    );

                    $data['restaurant']['timezone'] = $rest->timezone;
                    $data['restaurant']['merchantID'] = $rest->parentMerchantID;
                }
            }

        } catch (Exception $e) {
            var_dump($e);
        }

        echo json_encode($data);

        die(0);
    }

    public function saveRestaurantSettings()
    {
        $resp = array(
            'message' => '',
            'success' => true,
        );

        $id = $_POST['id'];
        $rest = new Restaurant($id);

        if ($rest instanceof Restaurant) {
            $jobTypes = $_POST['eligible_job_types'];

            if (is_array($jobTypes)) {

                // grab a copy of the old data we had, so we can keep track of any base points data and other stuff that was already set.
                $oldEmployeeData = $rest->getEmployeeData();

                // set the new job types
                $rest->jobTypes = $jobTypes;

                $newEmployeeList = $rest->updateEmployeeDataBasedOnJobTypes($jobTypes, $oldEmployeeData);

                // update the types at the end of it, but only if we have something, so we leave data there in case this fails
                if (is_array($newEmployeeList) && count($newEmployeeList) > 0) {
                    $rest->employeeData = $newEmployeeList;
                }

                $newPoints = $_POST['employee_job_base_points'];
                $mainJobs = $_POST['employee_job_main_job'];

                foreach ($rest->employeeData as $e => $employee) {
                    foreach ($employee['jobTitles'] as $j => $job) {
                        $pts = $newPoints[$e][$job['title']]['points'];
                        if (!$pts) {
                            $pts = 0;
                        }
                        $rest->employeeData[$e]['jobTitles'][$j]['basePoints'] = $pts;
                    }
                }

                foreach ($rest->employeeData as $e => $employee) {
                    foreach ($employee['jobTitles'] as $j => $job) {
                        $isMainJob = $mainJobs[$e][$job['title']]['isMainJob'];
                        $rest->employeeData[$e]['jobTitles'][$j]['isMainJobTitle'] = $isMainJob;
                    }
                }

                // if these two dont match in serialized form, then there have been job types selected
                if ($rest->employeeData !== $rest->originalEmployeeData) {
                    $rest->jobTypesChosen = true;
                }
            }

            // update all fields
            $rest->saveRestaurantMeta();

            $resp['message'] = 'Details saved successfully.';
        } else {
            $resp['message'] = 'Could not save details. Please try again.';
        }

        echo json_encode($resp);

        die(0);
    }

    public function deleteRestaurant()
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
