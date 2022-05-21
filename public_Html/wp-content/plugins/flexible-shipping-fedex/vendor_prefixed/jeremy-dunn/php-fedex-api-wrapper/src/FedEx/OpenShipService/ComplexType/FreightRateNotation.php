<?php

namespace FedExVendor\FedEx\OpenShipService\ComplexType;

use FedExVendor\FedEx\AbstractComplexType;
/**
 * Additional non-monetary data returned with Freight rates.
 *
 * @author      Jeremy Dunn <jeremy@jsdunn.info>
 * @package     PHP FedEx API wrapper
 * @subpackage  OpenShip Service
 *
 * @property string $Code
 * @property string $Description
 */
class FreightRateNotation extends \FedExVendor\FedEx\AbstractComplexType
{
    /**
     * Name of this complex type
     *
     * @var string
     */
    protected $name = 'FreightRateNotation';
    /**
     * Unique identifier for notation.
     *
     * @param string $code
     * @return $this
     */
    public function setCode($code)
    {
        $this->values['Code'] = $code;
        return $this;
    }
    /**
     * Human-readable explanation of notation.
     *
     * @param string $description
     * @return $this
     */
    public function setDescription($description)
    {
        $this->values['Description'] = $description;
        return $this;
    }
}
