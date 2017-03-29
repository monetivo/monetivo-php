<?php

namespace Monetivo\Api;

use Monetivo\Interfaces\ApiInterface;
use Monetivo\MerchantApi;

/**
 * Class Transactions
 * @author Jakub Jasiulewicz <jjasiulewicz@monetivo.com>
 * @package Monetivo\Api
 */
class PaymentChannels implements ApiInterface
{

    /**
     * eTransfer channel type
     */
    const TYPE_ETRANSFER = 1;
    const TYPE_BLIK = 2;
    /**
     * Channels for mobile apps
     */
    const TYPE_MO_APP = 4;
    /**
     * Channels for manual payments
     */
    const TYPE_MANUAL = 8;
    const TYPE_CARD = 16;
    const TYPE_OTHER = 32;

    /**
     * @var MerchantApi
     */
    private $merchantApi;

    public function __construct(MerchantApi $merchantApi)
    {
        $this->merchantApi = $merchantApi;
    }

    /** Lists currently available payment channels for a given currency
     * You can optionally filter channels by providing types of the channels as an array. See constants declared above.
     * @param $currency
     * @param array $types
     * @internal param array $type
     * @return array
     * @throws \Monetivo\Exceptions\MonetivoException
     */
    public function listing($currency, array $types = [])
    {
        $types = implode(',', $types);
        return $this->merchantApi->call('get', sprintf('paymentChannels?currency=%s&type=%s', strtoupper($currency), $types))->toArray();
    }
}