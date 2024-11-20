<?php

$plugin_name = dirname(__FILE__, 2);

require_once $plugin_name . '/vendor/autoload.php';

class POS_System
{
    public $ID;
    public $name;
    public $enabled;
    public $active;
    public $icon;

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
                $this->loadSystemMeta();
            }
        }
    }

    public function loadSystemMeta()
    {
        $id = $this->ID;

        $this->enabled = get_field('enabled', $id);
        $this->active = get_field('active', $id);
        $this->icon = get_field('icon', $this->ID);
    }

    // public function saveRestaurantMeta()
    // {
    //     $id = $this->ID;

    //     update_field('enabled', $this->enabled, $id);
    //     update_field('active', $this->active, $id);
    //     update_field('icon', $this->icon, $this->ID);
    // }
}
