<?php

namespace FedExVendor\FedEx\RateService\SimpleType;

use FedExVendor\FedEx\AbstractSimpleType;
/**
 * Relative to normal orientation for the printer.
 *
 * @author      Jeremy Dunn <jeremy@jsdunn.info>
 * @package     PHP FedEx API wrapper
 * @subpackage  Rate Service
 */
class LabelRotationType extends \FedExVendor\FedEx\AbstractSimpleType
{
    const _LEFT = 'LEFT';
    const _NONE = 'NONE';
    const _RIGHT = 'RIGHT';
    const _UPSIDE_DOWN = 'UPSIDE_DOWN';
}
