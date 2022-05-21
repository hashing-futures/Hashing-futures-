<?php

namespace FedExVendor\FedEx\ShipService\SimpleType;

use FedExVendor\FedEx\AbstractSimpleType;
/**
 * PaymentType
 *
 * @author      Jeremy Dunn <jeremy@jsdunn.info>
 * @package     PHP FedEx API wrapper
 * @subpackage  Ship Service
 */
class PaymentType extends \FedExVendor\FedEx\AbstractSimpleType
{
    const _ACCOUNT = 'ACCOUNT';
    const _CASH = 'CASH';
    const _COLLECT = 'COLLECT';
    const _CREDIT_CARD = 'CREDIT_CARD';
    const _RECIPIENT = 'RECIPIENT';
    const _SENDER = 'SENDER';
    const _THIRD_PARTY = 'THIRD_PARTY';
}
