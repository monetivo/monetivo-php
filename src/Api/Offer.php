<?php

namespace Monetivo\Api;

use Monetivo\Interfaces\ApiInterface;
use Monetivo\MerchantApi;

/**
 * Class Offer
 * @author Jakub Jasiulewicz <jjasiulewicz@monetivo.com>
 * @package Monetivo\Api
 */
class Offer implements ApiInterface
{
    const TYPE_SERVICES = 'services';
    const TYPE_PAYMENTS = 'payments';
    /**
     * @var MerchantApi
     */
    private $merchantApi;

    public function __construct(MerchantApi $merchantApi)
    {
        $this->merchantApi = $merchantApi;
    }

    /** Lists active offers depending on their type
     * @param $type
     * @return array
     * @throws \Monetivo\Exceptions\MonetivoException
     */
    public function list($type)
    {
        return $this->merchantApi->call('get', 'offer/'.$type)->toArray();
    }

    /** Gets details about an offer
     * @param string $type type of an offer
     * @param string $id id of an offer
     * @return array
     * @throws \Monetivo\Exceptions\MonetivoException
     */
    public function details($type, $id)
    {
        return $this->merchantApi->call('get', 'offer/'.$type.'/'.$id)->toArray();
    }
}
