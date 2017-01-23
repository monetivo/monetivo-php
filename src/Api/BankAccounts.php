<?php

namespace Monetivo\Api;

use Monetivo\Interfaces\ApiInterface;
use Monetivo\MerchantApi;

/**
 * Class Accounts
 * @author Jakub Jasiulewicz <jjasiulewicz@monetivo.com>
 * @package Monetivo\Api
 */
class BankAccounts implements ApiInterface
{
    /**
     * @var MerchantApi
     */
    private $merchantApi;

    public function __construct(MerchantApi $merchantApi)
    {
        $this->merchantApi = $merchantApi;
    }

    /** Lists bank accounts
     * @return array
     * @throws \Monetivo\Exceptions\MonetivoException
     */
    public function listing()
    {
        return $this->merchantApi->call('get', 'bank_accounts/')->toArray();
    }

    /** Gets details about bank account
     * @param $bank_account_id
     * @return array
     * @throws \Monetivo\Exceptions\MonetivoException
     */
    public function details($bank_account_id)
    {
        return $this->merchantApi->call('get', 'bank_accounts/' . $bank_account_id)->toArray();
    }
}
