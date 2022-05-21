<?php

namespace FedExVendor\FedEx\ValidationAvailabilityAndCommitmentService\SimpleType;

use FedExVendor\FedEx\AbstractSimpleType;
/**
 * Identification of a FedEx operating company (transportation).
 *
 * @author      Jeremy Dunn <jeremy@jsdunn.info>
 * @package     PHP FedEx API wrapper
 * @subpackage  Validation Availability And Commitment Service Service
 */
class CarrierCodeType extends \FedExVendor\FedEx\AbstractSimpleType
{
    const _FDXE = 'FDXE';
    const _FDXG = 'FDXG';
    const _FXSP = 'FXSP';
}
