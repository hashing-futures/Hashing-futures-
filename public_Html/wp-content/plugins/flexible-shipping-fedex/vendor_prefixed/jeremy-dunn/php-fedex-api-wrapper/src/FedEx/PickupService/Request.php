<?php

namespace FedExVendor\FedEx\PickupService;

use FedExVendor\FedEx\AbstractRequest;
/**
 * Request sends the SOAP call to the FedEx servers and returns the response
 *
 * @author      Jeremy Dunn <jeremy@jsdunn.info>
 * @package     PHP FedEx API wrapper
 * @subpackage  Pickup Service
 */
class Request extends \FedExVendor\FedEx\AbstractRequest
{
    const PRODUCTION_URL = 'https://ws.fedex.com:443/web-services/pickup';
    const TESTING_URL = 'https://wsbeta.fedex.com:443/web-services/pickup';
    protected static $wsdlFileName = 'PickupService_v22.wsdl';
    /**
     * Sends the PickupAvailabilityRequest and returns the response
     *
     * @param ComplexType\PickupAvailabilityRequest $pickupAvailabilityRequest
     * @param bool $returnStdClass Return the $stdClass response directly from \SoapClient
     * @return ComplexType\PickupAvailabilityReply|stdClass
     */
    public function getGetPickupAvailabilityReply(\FedExVendor\FedEx\PickupService\ComplexType\PickupAvailabilityRequest $pickupAvailabilityRequest, $returnStdClass = \false)
    {
        $response = $this->getSoapClient()->getPickupAvailability($pickupAvailabilityRequest->toArray());
        if ($returnStdClass) {
            return $response;
        }
        $pickupAvailabilityReply = new \FedExVendor\FedEx\PickupService\ComplexType\PickupAvailabilityReply();
        $pickupAvailabilityReply->populateFromStdClass($response);
        return $pickupAvailabilityReply;
    }
    /**
     * Sends the CreatePickupRequest and returns the response
     *
     * @param ComplexType\CreatePickupRequest $createPickupRequest
     * @param bool $returnStdClass Return the $stdClass response directly from \SoapClient
     * @return ComplexType\CreatePickupReply|stdClass
     */
    public function getCreatePickupReply(\FedExVendor\FedEx\PickupService\ComplexType\CreatePickupRequest $createPickupRequest, $returnStdClass = \false)
    {
        $response = $this->getSoapClient()->createPickup($createPickupRequest->toArray());
        if ($returnStdClass) {
            return $response;
        }
        $createPickupReply = new \FedExVendor\FedEx\PickupService\ComplexType\CreatePickupReply();
        $createPickupReply->populateFromStdClass($response);
        return $createPickupReply;
    }
    /**
     * Sends the CancelPickupRequest and returns the response
     *
     * @param ComplexType\CancelPickupRequest $cancelPickupRequest
     * @param bool $returnStdClass Return the $stdClass response directly from \SoapClient
     * @return ComplexType\CancelPickupReply|stdClass
     */
    public function getCancelPickupReply(\FedExVendor\FedEx\PickupService\ComplexType\CancelPickupRequest $cancelPickupRequest, $returnStdClass = \false)
    {
        $response = $this->getSoapClient()->cancelPickup($cancelPickupRequest->toArray());
        if ($returnStdClass) {
            return $response;
        }
        $cancelPickupReply = new \FedExVendor\FedEx\PickupService\ComplexType\CancelPickupReply();
        $cancelPickupReply->populateFromStdClass($response);
        return $cancelPickupReply;
    }
}
