<?php

namespace FedExVendor\FedEx\ShipService\ComplexType;

use FedExVendor\FedEx\AbstractComplexType;
/**
 * NaftaProducer
 *
 * @author      Jeremy Dunn <jeremy@jsdunn.info>
 * @package     PHP FedEx API wrapper
 * @subpackage  Ship Service
 *
 * @property string $Id
 * @property Party $Producer
 */
class NaftaProducer extends \FedExVendor\FedEx\AbstractComplexType
{
    /**
     * Name of this complex type
     *
     * @var string
     */
    protected $name = 'NaftaProducer';
    /**
     * Set Id
     *
     * @param string $id
     * @return $this
     */
    public function setId($id)
    {
        $this->values['Id'] = $id;
        return $this;
    }
    /**
     * Set Producer
     *
     * @param Party $producer
     * @return $this
     */
    public function setProducer(\FedExVendor\FedEx\ShipService\ComplexType\Party $producer)
    {
        $this->values['Producer'] = $producer;
        return $this;
    }
}
