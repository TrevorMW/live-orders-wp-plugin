<?php

require_once $plugin_name . '/vendor/autoload.php';

class System
{
    public $ID;
    public $name;
    public $post;
    public $posType;
    public $tokenExpiresAt;

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
                $this->post = $post;
                $this->loadSystemMeta();
            }
        }
    }

    public static function createNewSystem($name)
    {
        $location = array(
            'post_title' => wp_strip_all_tags($name),
            'post_type' => 'system',
            'post_status' => 'publish'
        );

        $id = wp_insert_post($location);

        // Insert the post into the database
        return new System($id);
    }

    public function loadSystemMeta()
    {
        $id = $this->ID;

        $this->posType = get_field('pos_system_type', $id);
        $this->tokenExpiresAt = get_field('token_expires_at', $id);
    }

    public function saveSystemMeta()
    {
        $id = $this->ID;

        update_field('pos_system_type', $this->posType, $id);
        update_field('token_expires_at', $this->tokenExpiresAt, $id);
    }

    public function assignSystemToUser($systemID, $userID)
    {
        $curr = get_field('authorized_systems', 'user_' . $userID, false);

        if(is_array($curr)){
            $curr[] = (int) $systemID;
        } else {
            $curr = array($systemID);
        }        

        return update_field('authorized_systems', $curr, 'user_' . $userID);
    }



    public static function getAccessToken($id)
    {
        $crypt = new OauthCrypt();
        $encryptedToken = get_field('access_token', $id);
        return $encryptedToken ? $crypt->decrypt($encryptedToken) : null;
    }

    public static function setAccessToken($token, $id)
    {
        $crypt = new OauthCrypt();
        return update_field('access_token', $crypt->encrypt($token), $id);
    }

    public static function removeAccessToken($id)
    {
        return update_field('access_token', '', $id);
    }

    public static function getRefreshToken($id)
    {
        $crypt = new OauthCrypt();
        $encryptedToken = get_field('refresh_token', $id);
        return $encryptedToken ? $crypt->decrypt($encryptedToken) : null;
    }

    public static function setRefreshToken($token, $id)
    {
        $crypt = new OauthCrypt();
        return update_field('refresh_token', $crypt->encrypt($token), $id);
    }



    public function loadSystemData()
    {
        $resp = new Ajax_Response($_POST['action']);
        $userID = (int) $_POST['userID'];
        $systemID = (int) $_POST['systemID'];

        $systemData = [];

        if(!is_int($userID) || !is_int($systemID)){
            $resp->message = 'URL tampering is not allowed; This dashboard is not available to you!';
            $resp->data = array(
                'system' =>  $systemData,
            );

            $resp->encodeResponse();

            die(0);
        }

        $systems = get_field('authorized_systems', 'user_' . $userID, false);

        if (is_array($systems) && in_array($systemID, $systems)) {

            $sysPost = new System($systemID);

            if($sysPost instanceof System && $sysPost->post instanceof WP_Post){
                $connectionDetails = OAuth::testOauthConnection($sysPost->ID);

                $systemData = array(
                    'ID' => $sysPost->ID,
                    'slug' => $sysPost->post->post_name,
                    'title' => html_entity_decode($sysPost->post->post_title),
                    'posType' => $sysPost->posType, 
                    'oauth' => array(
                        'isConnected' => $connectionDetails['connected'],
                        'link' => OAuth::getSquareOauthAuthorizeLink($sysPost->ID),
                        'expiration' => $connectionDetails['expiration'],
                        'scopes' => $connectionDetails['scopes']
                    )
                );
            }
        }

        $resp->data = array(
            'system' => $systemData,
            'locations' => [],
        );

        echo $resp->encodeResponse();

        die(0);
    }


    public function loadSystemsData()
    {
        $resp = new Ajax_Response($_POST['action']);
        $userID = $_POST['userID'];

        $systemData = [];

        $systems = get_field('authorized_systems', 'user_' . $userID, false);

        if (is_array($systems)) {
            foreach ($systems as $sysID) {
                $sysPost = new System($sysID);

                if($sysPost instanceof System && $sysPost->post instanceof WP_Post){
                    $connectionDetails = OAuth::testOauthConnection($sysPost->ID);

                    $systemData[] = array(
                        'ID' => $sysPost->ID,
                        'slug' => $sysPost->post->post_name,
                        'title' => html_entity_decode($sysPost->post->post_title),
                        'posType' => $sysPost->posType, 
                        'oauth' => array(
                            'isConnected' => $connectionDetails['connected'],
                            'link' => OAuth::getSquareOauthAuthorizeLink($sysPost->ID),
                            'expiration' => $connectionDetails['expiration'],
                            'scopes' => $connectionDetails['scopes']
                        ),

                    );
                }
            }
        }

        $resp->data = array(
            'systems' => $systemData
        );

        echo $resp->encodeResponse();

        die(0);
    }

    /**
     * 
     * TODO, make sure this deletes the appropriate locations and references from the authorized user relationships
     * @return never
     */
    public function deleteSystem()
    {
        $resp = new Ajax_Response($_POST['action']);
        $sysID = $_POST['systemID'];

        if($sysID){
            $post = get_post($sysID);
            $name = $post->post_title;

            if($post instanceof WP_Post){
                wp_delete_post($post->ID);
                $resp->status = true;
                $resp->message = 'Successfully deleted "' . $name . '" ';

            } else {
                $resp->status = false;
                $resp->message = 'Could not find post to delete. Please try again';
            }
        } else {
            $resp->status = false;
            $resp->message = 'Could not find post to delete. Please try again';
        }

        echo $resp->encodeResponse();

        die(0);
    }
    
}