<?php

namespace FedExVendor\FedEx\PickupService\SimpleType;

use FedExVendor\FedEx\AbstractSimpleType;
/**
 * TrackingIdType
 *
 * @author      Jeremy Dunn <jeremy@jsdunn.info>
 * @package     PHP FedEx API wrapper
 * @subpackage  Pickup Service
 */
class TrackingIdType extends \FedExVendor\FedEx\AbstractSimpleType
{
    const _EXPRESS = 'EXPRESS';
    const _FEDEX = 'FEDEX';
    const _FREIGHT = 'FREIGHT';
    const _GROUND = 'GROUND';
    const _USPS = 'USPS';
}
