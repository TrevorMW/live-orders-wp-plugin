<?php

require_once $plugin_name . '/vendor/autoload.php';

class Order
{
    public $locationID;
    public $merchantID;
    public $merchant;
    public $location;
    public $lastUpdated;

    public $orderData = array(
        'proposed' => [],
        'reserved' => [],
        'prepared' => [],
    );

    /**
     * __construct function.
     *
     * @access public
     * @param mixed $action (default: null)
     * @return void
     */
    public function __construct($locationID = false, $merchantID = false)
    {
        // if we pass an ID, then we load all the current data.
        if ($locationID && $merchantID) {
            $this->merchant = new Merchant($merchantID);
            $this->location = new Location($locationID);
            $this->locationID = $locationID;
        }
    }

    public function buildOrderData($order)
    {
        $json = array();

        if ($order) {
            $json['id'] = $order->getID();
            $json['createdAt'] = Utilities::convertDateToUserFriendly($order->getCreatedAt(), 'America/New_York');
            $json['updatedAt'] = Utilities::convertDateToUserFriendly($order->getUpdatedAt(), 'America/New_York');
            $json['state'] = $order->getState();
            $json['changing'] = false;
            $json['updating'] = false;

            $json['version'] = $order->getVersion();
            $json['source'] = $order->getSource()->getName();

            $json['isDelivery'] = $this->isDelivery($order);
            $json['isPickup'] = $this->isPickup($order);

            $json['isNew'] = $this->isNew($order);
            $json['isOpen'] = $this->isOpen($order);
            $json['isCompleted'] = $this->isCompleted($order);
            $json['isInProgress'] = $this->isInProgress($order);
            $json['isUpcoming'] = $this->isUpcoming($order);
            $json['timeTillReady'] = null;
            $json['user'] = array();

            $fulfillments = $order->getFulfillments();

            if ($fulfillments && count($fulfillments) > 0) {
                $name = '';
                $fulfillment = $fulfillments[0];

                $json['fulfillmentType'] = $fulfillment->getType();

                if ($fulfillment->getType() === 'PICKUP') {
                    $pickupDetails = $fulfillment->getPickupDetails();
                    $recipient = $pickupDetails->getRecipient();
                    $name = $recipient->getDisplayName();

                    if (!$name) {
                        $name = $recipient->getPhoneNumber();
                    }

                    $json['user']['name'] = $name;
                    $json['user']['email'] = $recipient->getEmailAddress();
                    $json['user']['phone'] = Utilities::formatPhoneNumber($recipient->getPhoneNumber());
                    $json['user']['email'] = $recipient->getEmailAddress();

                    $json['pickupAt'] = Utilities::convertDateToUserFriendly($pickupDetails->getPickupAt(), 'America/New_York');
                    $json['placedAt'] = Utilities::convertDateToUserFriendly($pickupDetails->getPlacedAt(), 'America/New_York');
                    $json['readyAt'] = Utilities::convertDateToUserFriendly($pickupDetails->getReadyAt(), 'America/New_York');

                    if($pickupDetails && $pickupDetails->getReadyAt()){
                        $json['timeTillReady'] = Utilities::timeDifferenceInMinutes($pickupDetails->getReadyAt());
                    } else {
                        $json['timeTillReady'] = Utilities::timeDifferenceInMinutes($pickupDetails->getPickupAt());
                    }
                }

                if ($fulfillment->getType() === 'DELIVERY') {
                    $deliveryDetails = $fulfillment->getDeliveryDetails();
                    $recipient = $deliveryDetails->getRecipient();

                    $name = $recipient->getDisplayName();

                    if (!$name) {
                        $name = Utilities::formatPhoneNumber($recipient->getPhoneNumber());
                    }

                    $json['user']['name'] = $name;
                    $json['user']['email'] = $recipient->getEmailAddress();
                    $json['user']['phone'] = Utilities::formatPhoneNumber($recipient->getPhoneNumber());
                    $json['user']['email'] = $recipient->getEmailAddress();
                    
                    $json['deliverAt'] = Utilities::convertDateToUserFriendly($deliveryDetails->getDeliverAt(), 'America/New_York');
                    $json['placedAt'] = Utilities::convertDateToUserFriendly($deliveryDetails->getPlacedAt(), 'America/New_York');
                    $json['readyAt'] = Utilities::convertDateToUserFriendly($deliveryDetails->getReadyAt(), 'America/New_York');
                    
                    if($deliveryDetails && $deliveryDetails->getReadyAt()){
                        $json['timeTillReady'] = Utilities::timeDifferenceInMinutes($pickupDetails->getReadyAt());
                    } else {
                        $json['timeTillReady'] = Utilities::timeDifferenceInMinutes($pickupDetails->getDeliveryAt());
                    }
                }

                $json['ticketName'] = $name;
            }
        }

        return $json;
    }

    //////////////////////////////////////////////////////////////////
    ////////////////////// HELPER FUNCTIONS //////////////////////////
    //////////////////////////////////////////////////////////////////

    public function isNew($order)
    {
        $isNew = false;

        if ($order && $order->getState() === 'OPEN') {
            $ful = $order->getFulfillments()[0];

            if ($ful) {
                $state = $ful->getState();

                $isNew = $state === 'PROPOSED' ? true : false;
            }
        }

        return $isNew;
    }

    public function isOpen($order)
    {
        $isOpen = false;

        if ($order && $order->getState() === 'OPEN') {
            $isOpen = true;
        }

        return $isOpen;
    }

    public function isCompleted($order)
    {
        $isCompleted = false;

        if ($order && $order->getState() === 'COMPLETED') {
            $isCompleted = true;
        }

        return $isCompleted;
    }

    public function isUpcoming($order)
    {
        $isUpcoming = false;

        if ($order && !$order->getState()) {
            $isUpcoming = true;
        }

        return $isUpcoming;
    }

    public function isReady($order)
    {
        $isReady = false;

        if ($order && $order->getState() === 'OPEN') {
            $ful = $order->getFulfillments()[0];

            if ($ful) {
                $state = $ful->getState();

                $isReady = $state === 'PREPARED' ? true : false;
            }
        }

        return $isReady;
    }

    public function isInProgress($order)
    {
        $inProgress = false;

        if ($order && $order->getState() === 'OPEN') {
            $ful = $order->getFulfillments()[0];

            if ($ful) {
                $state = $ful->getState();

                $inProgress = $state === 'RESERVED' ? true : false;
            }
        }

        return $inProgress;
    }

    public function isPickup($order)
    {
        $isPickup = false;

        if ($order && $order->getState() === 'OPEN') {
            $ful = $order->getFulfillments()[0];

            if ($ful) {
                $state = $ful->getType();
                $isPickup = $state === 'PICKUP' ? true : false;
            }
        }

        return $isPickup;
    }

    public function isDelivery($order)
    {
        $isDelivery = false;

        if ($order && $order->getState() === 'OPEN') {
            $ful = $order->getFulfillments()[0];

            if ($ful) {
                $state = $ful->getType();
                $isDelivery = $state === 'DELIVERY' ? true : false;
            }
        }

        return $isDelivery;
    }

    public function getOrderStatus($order)
    {
        $status = null;

        if($order){

            $fulfillments = $order->getFulfillments();

            // sort the orders out into their appropriate columns using fulfillment status
            if ($fulfillments && count($fulfillments) > 0) {
                foreach ($fulfillments as $fulfill) {
                    // we only take proposed orders that do not have an amount due, meaning they are paid for and will be undertaken.
                    if (
                        $fulfill->getState() === 'PROPOSED' &&
                        $order->getNetAmountDueMoney()->getAmount() <= 0
                    ) {
                        $status = 'proposed';

                        // IF we have a proposed
                        if($fulfill->getType() === 'PICKUP'){
                            $deets = $fulfill->getPickupDetails();
                            // TODO: add checks for acceptedAt

                            //var_dump($deets->scheduleType(), $deets);

                            // if($deets->getScheduleType() === 'SCHEDULED' || strtotime('now') <= strtotime($deets->getAcceptedAt())){
                            //     $status = 'reserved';
                            // }                            
                        }
                    }

                    // we check for actual reserved orders, but above we also check the acceptedAt value. 
                    // Cause once a proprosed order has been accepted, it is in progress, which would equate to "RESERVED"
                    if ($fulfill->getState() === 'RESERVED') { 
                        $status = 'reserved';
                    }

                    if ($fulfill->getState() === 'PREPARED') {
                        $status = 'prepared';
                    }

                    if ($fulfill->getState() === 'COMPLETED') {
                        $status = 'completed';
                    }
                }
            }
        }

        return $status;
    }

    //////////////////////////////////////////////////////////////////
    ////////////////////// AJAX ENDPOINTS ////////////////////////////
    //////////////////////////////////////////////////////////////////

    public function loadOrdersData()
    {
        $resp = new Ajax_Response($_POST['action']);
        $userID = (int) $_POST['userID'];
        $merchantID = (int) $_POST['merchantID'];
        $locationID = $_POST['locationID'];

        // TODO check that user has access to specific merchant/location

        $order = new Order($locationID, $merchantID);

        if (!is_int($userID) || !is_int($merchantID)) {
            $resp->status = false;
            $resp->data = array(
                'orders' => $this->orderData,
                'disallowAccess' => true,
                'icon' => 'block',
                'message' => 'URL tampering detected; This dashboard is not available to you!'
            );

            $resp->encodeResponse();

            die(0);
        }

        $client = Oauth::getSquareClient($merchantID);

        if ($client) {
            // Lets set the lastUpdatedAt time everytime we call this function, that way we have and up to date timestamp
            $location = $order->location;
            $merchant = $order->merchant;

            $lastUpdate = new DateTime('now', new DateTimeZone($location->timezone));
            $location->lastUpdatedAt = $lastUpdate->format('c');
            $location->saveLocationMeta();

            $location_ids = [$location->locationID];
            $states = ['OPEN'];
            $state_filter = new \Square\Models\SearchOrdersStateFilter($states);

            $closed_at = new \Square\Models\TimeRange();

            $now = new DateTime('NOW 10am', new DateTimeZone('America/New_York'));
            $then = new DateTime('NOW 11pm', new DateTimeZone('America/New_York'));

            $closed_at->setStartAt($now->format("c"));
            $closed_at->setEndAt($then->format("c"));
            // $closed_at->setStartAt('2024-07-31T10:00:00-04:00');
            // $closed_at->setEndAt('2024-07-31T23:00:00-04:00');

            $date_time_filter = new \Square\Models\SearchOrdersDateTimeFilter();
            $date_time_filter->setCreatedAt($closed_at);

            $filter = new \Square\Models\SearchOrdersFilter();
            $filter->setStateFilter($state_filter);
            $filter->setDateTimeFilter($date_time_filter);

            $fulfillment_states = [
                'PROPOSED',
                'RESERVED',
                'PREPARED',
            ];
            $fulfillment_types = ['PICKUP', 'DELIVERY'];
            $fulfillment_filter = new \Square\Models\SearchOrdersFulfillmentFilter();
            $fulfillment_filter->setFulfillmentTypes($fulfillment_types);
            $fulfillment_filter->setFulfillmentStates($fulfillment_states);

            $filter = new \Square\Models\SearchOrdersFilter();
            $filter->setStateFilter($state_filter);
            $filter->setDateTimeFilter($date_time_filter);
            $filter->setFulfillmentFilter($fulfillment_filter);

            $sort = new \Square\Models\SearchOrdersSort('UPDATED_AT');
            $sort->setSortField('UPDATED_AT');
            $sort->setSortOrder('ASC');

            $query = new \Square\Models\SearchOrdersQuery();
            $query->setFilter($filter);
            $query->setSort($sort);

            $body = new \Square\Models\SearchOrdersRequest();
            $body->setLocationIds($location_ids);
            $body->setQuery($query);
            $body->setReturnEntries(false);

            $api_response = $client->getOrdersApi()->searchOrders($body);

            if ($api_response->isSuccess()) {
                $result = $api_response->getResult();
                $orders = $result->getOrders();

                if ($orders && count($orders) > 0) {
                    foreach ($orders as $order) {
                        // First we build data to send to the frontend for each order
                        $orderJSON = $this->buildOrderData($order);
                        $placement = $this->getOrderStatus($order);

                        $fulfillments = $order->getFulfillments();

                        // sort the orders out into their appropriate columns using fulfillment status
                        if ($fulfillments && count($fulfillments) > 0) {
                            foreach ($fulfillments as $fulfill) {

                                // we only take proposed orders that do not have an amount due, meaning they are paid for and will be undertaken.
                                if (
                                    $fulfill->getState() === 'PROPOSED' &&
                                    $order->getNetAmountDueMoney()->getAmount() <= 0
                                ) {
                                    $this->orderData['proposed'][] = $orderJSON;
                                }

                                if ($fulfill->getState() === 'RESERVED') {
                                    $this->orderData['reserved'][] = $orderJSON;
                                }

                                if ($fulfill->getState() === 'PREPARED') {
                                    $this->orderData['prepared'][] = $orderJSON;
                                }
                            }
                        }

                        //$this->orderData[$placement][] = $orderJSON;
                    }
                }
            } else {
                $errors = $api_response->getErrors();
            }
        }

        $resp->data = array(
            'orders' => $this->orderData,
            'lastUpdatedAt' => $lastUpdate->format('M d Y h:i:s A'),
            'location' => $location->ID,
            'disallowAccess' => false
        );

        echo $resp->encodeResponse();

        die(0);
    }

    public function loadOrderData()
    {
        $resp = new Ajax_Response($_POST['action']);
        $userID = (int) $_POST['userID'];
        $merchantID = (int) $_POST['merchantID'];
        $locationID = $_POST['locationID'];
        $orderID = $_POST['orderID'];

        // TODO check that user has access to specific merchant/location

        $order = new Order($locationID, $merchantID);

        if (!is_int($userID) || !is_int($merchantID)) {
            $resp->status = false;
        } else {
            $client = Oauth::getSquareClient($merchantID);

            if ($client) {
                $api_response = $client->getOrdersApi()->retrieveOrder($orderID);

                if ($api_response->isSuccess()) {
                    $order = $api_response->getResult()->getOrder();

                    $resp->data = array(
                        'order' => $this->buildOrderData($order),
                        'placement' => $this->getOrderStatus($order)
                    );

                    // for testing
                    if($order->getID() === 'VJBr154MmcmrtHk8mtrVod0LiDDZY'){
                        $resp->data['placement'] = 'prepared';
                    }
                }
            }
        }

        echo $resp->encodeResponse();

        die(0);
    }
}
