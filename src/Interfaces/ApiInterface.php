<?php

namespace Monetivo\Interfaces;

use Monetivo\MerchantApi;

/**
 * Interface ApiInterface
 * @package Monetivo\Interfaces
 */
interface ApiInterface
{
    public function __construct(MerchantApi $merchantApi);
}
