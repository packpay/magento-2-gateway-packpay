<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Packpay\Gateway\Model;
use Magento\Framework\Pricing\PriceCurrencyInterface;


/**
 * Pay In Store payment method model
 */
class Packpay extends \Magento\Payment\Model\Method\AbstractMethod
{
    /**
     * Payment code
     *
     * @var string
     */
    protected $_code = 'gateway';
}
