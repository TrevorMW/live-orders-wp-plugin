<?php

require_once $plugin_name . '/vendor/autoload.php';

class Location
{
    public $ID;
    public $name;
    public $post;
    public $locationID;
    public $lastUpdatedAt;
    public $merchantID;
    public $timezone;

    /**
     * __construct function.
     *
     * @access public
     * @param mixed $action (default: null)
     * @return void
     */
    public function __construct($id = false)
    {
        $locPost = null;
        // Can load a location by post ID or location ID as a meta field.
        if ($id) {
            $locPost = get_post($id);             
        }

        if($locPost instanceof WP_Post){
            $this->ID = $locPost->ID;
            $this->name = $locPost->post_title;
            $this->post = $locPost;
            $this->loadLocationMeta();
        }  
    }

    public static function createNewLocation($name)
    {
        $location = array(
            'post_title' => wp_strip_all_tags($name),
            'post_type' => 'location',
            'post_status' => 'publish'
        );

        $id = wp_insert_post($location);

        // Insert the post into the database
        return new Location($id);
    }

    public function loadLocationMeta()
    {
        $id = $this->ID;

        $this->locationID = get_field('location_id', $id);
        $this->merchantID = get_field('merchant_id', $id);
        $this->timezone   = get_field('timezone', $id);
        $this->lastUpdatedAt = get_field('last_updated_at', $id);
    }

    public function saveLocationMeta()
    {
        $id = $this->ID;

        update_field('location_id', $this->locationID, $id);
        update_field('merchant_id', $this->merchantID, $id);
        update_field('timezone', $this->timezone, $id);
        update_field('last_updated_at', $this->lastUpdatedAt, $id);
    }

    public function assignLocationToUser($ID, $userID)
    {
        $curr = get_field('authorized_locations', 'user_' . $userID, false);

        $curr[] = $ID;

        return update_field('authorized_locations', $curr, 'user_' . $userID);
    }

    //////////////////////////////////////////////////////////////////
    ////////////////////// AJAX ENDPOINTS ////////////////////////////
    //////////////////////////////////////////////////////////////////


}
