<?php

namespace FedExVendor\FedEx\RateService\ComplexType;

use FedExVendor\FedEx\AbstractComplexType;
/**
 * Payment
 *
 * @author      Jeremy Dunn <jeremy@jsdunn.info>
 * @package     PHP FedEx API wrapper
 * @subpackage  Rate Service
 *
 * @property \FedEx\RateService\SimpleType\PaymentType|string $PaymentType
 * @property Payor $Payor
 */
class Payment extends \FedExVendor\FedEx\AbstractComplexType
{
    /**
     * Name of this complex type
     *
     * @var string
     */
    protected $name = 'Payment';
    /**
     * Set PaymentType
     *
     * @param \FedEx\RateService\SimpleType\PaymentType|string $paymentType
     * @return $this
     */
    public function setPaymentType($paymentType)
    {
        $this->values['PaymentType'] = $paymentType;
        return $this;
    }
    /**
     * Set Payor
     *
     * @param Payor $payor
     * @return $this
     */
    public function setPayor(\FedExVendor\FedEx\RateService\ComplexType\Payor $payor)
    {
        $this->values['Payor'] = $payor;
        return $this;
    }
}
