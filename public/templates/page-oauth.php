<?php
/**
 * Template Name: Template - Generic
 * Description: Generic Sub Page Template
 *
 * @package WordPress
 * @subpackage themename
 */

require WP_PLUGIN_DIR . '/service-charge-app/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(ABSPATH);
$dotenv->load();

$keyContents = file_get_contents('/usr/local/encryptKey.txt');
$key = \Defuse\Crypto\Key::loadFromAsciiSafeString($keyContents);

// Encrypt the keys for .env file
$application_id = \Defuse\Crypto\Crypto::encrypt($_ENV['SQ_APPLICATION_ID'], $key);    
$secret         = \Defuse\Crypto\Crypto::encrypt($_ENV['SQ_APPLICATION_SECRET'], $key);

var_dump($application_id);
var_dump($secret);

die(0);

