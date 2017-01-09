<?php

namespace Monetivo\Api;

use Monetivo\Interfaces\ApiInterface;
use Monetivo\MerchantApi;

/**
 * Class Addresses
 * @author Jakub Jasiulewicz <jjasiulewicz@monetivo.com>
 * @package Monetivo\Api
 */
class Addresses implements ApiInterface
{

    /**
     * Types of addresses
     */
    const TYPE_REGISTRATION = 'registration';
    const TYPE_CORRESPONDENCE = 'correspondence';

    /**
     * @var MerchantApi
     */
    private $merchantApi;

    public function __construct(MerchantApi $merchantApi)
    {
        $this->merchantApi = $merchantApi;
    }

    /** Lists addresses according to type
     * @param $type
     * @return array
     * @throws \Monetivo\Exceptions\MonetivoException
     */
    public function list($type)
    {
        return $this->merchantApi->call('get', 'addresses/' . $type)->toArray();
    }

    /** Update address
     * @param $type
     * @param array $address
     * @return array
     * @throws \Monetivo\Exceptions\MonetivoException
     */
    public function update($type, array $address)
    {
        return $this->merchantApi->call('put', 'addresses/' . $type, [
            'form_params' => $address
        ])->toArray();
    }






}