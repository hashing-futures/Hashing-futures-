<?php

namespace FedExVendor\FedEx\OpenShipService\SimpleType;

use FedExVendor\FedEx\AbstractSimpleType;
/**
 * Identifies special or custom features to be applied to a shipment.
 *
 * @author      Jeremy Dunn <jeremy@jsdunn.info>
 * @package     PHP FedEx API wrapper
 * @subpackage  OpenShip Service
 */
class ShipmentProcessingOptionType extends \FedExVendor\FedEx\AbstractSimpleType
{
    const _PACKAGE_LEVEL_COMMODITIES = 'PACKAGE_LEVEL_COMMODITIES';
    const _PRE_ETD_ENHANCEMENTS = 'PRE_ETD_ENHANCEMENTS';
}
