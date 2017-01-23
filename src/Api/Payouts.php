<?php

namespace Monetivo\Api;

use Monetivo\Interfaces\ApiInterface;
use Monetivo\MerchantApi;

/**
 * Class Payouts
 * @author Jakub Jasiulewicz <jjasiulewicz@monetivo.com>
 * @package Monetivo\Api
 */
class Payouts implements ApiInterface
{
    /**
     * @var MerchantApi
     */
    private $merchantApi;

    public function __construct(MerchantApi $merchantApi)
    {
        $this->merchantApi = $merchantApi;
    }

    /** Creates the payout for a given account
     * @param $account_id
     * @return mixed
     * @throws \Monetivo\Exceptions\MonetivoException
     */
    public function create($account_id)
    {
        return $this->merchantApi->call('post', 'accounts/'.$account_id.'/payouts', [
            'form_params' => compact('account_id')
        ])->toArray();
    }

    /** Lists all payouts
     * @return mixed
     * @throws \Monetivo\Exceptions\MonetivoException
     */
    public function listing()
    {
        return $this->merchantApi->call('get', 'payouts')->toArray();
    }

    /** Gets details about payout
     * @param $identifier
     * @return mixed
     * @throws \Monetivo\Exceptions\MonetivoException
     */
    public function details($identifier)
    {
        return $this->merchantApi->call('get', 'payouts/'.$identifier)->toArray();
    }
}
