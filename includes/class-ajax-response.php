<?php

class Ajax_Response
{
  public $status;
  public $message;
  public $data;
  public $action;
  public $url;
  public $redirectURL;


  /**
   * __construct function.
   *
   * Builds out a simple response object for sending data back from wp_ajax calls.
   * Use "status" as a boolean for true/false operations within callbacks after the ajax request has been completed, "messages" for any custom messages/user feedback
   * And use "data" as an array to send back html or custom data that can be acted on with javascript.
   *
   * @access public
   * @param mixed $action (default: null)
   * @return void
   */
  public function __construct($action = false)
  {
    $this->status  = false;
    $this->message = null;
    $this->data    = null;
    $this->redirectURL = null;

    if ($action != null)
      $this->action = $action;
  }

  /**
   * encode_response function.
   *
   * Encodes PHP array as valid JSON to be sent back to callback javascript scope.
   *
   * @access public
   * @return void
   */
  public function encodeResponse()
  {
    return json_encode(array(
      'status'    => $this->status,
      'message'   => $this->message,
      'data'      => $this->data,
      'action'    => $this->action,
      'url'       => $this->url,
      'redirectURL' => $this->redirectURL
    ));
  }
}
