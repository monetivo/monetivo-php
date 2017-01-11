<?php

namespace Monetivo\Api;

use Monetivo\Interfaces\ApiInterface;
use Monetivo\Exceptions\MonetivoException;
use Monetivo\MerchantApi;

/**
 * Class Accounts
 * @author Jakub Jasiulewicz <jjasiulewicz@monetivo.com>
 * @package Monetivo\Api
 */
class Accounts implements ApiInterface
{
    /**
     * Report types
     */
    const REPORT_PAYOUTS = 1;
    const REPORT_TRANSACTIONS = 2;
    const REPORT_REFUNDS = 4;
    const REPORT_CHARGES = 32;

    /**
     * @var MerchantApi
     */
    private $merchantApi;

    public function __construct(MerchantApi $merchantApi)
    {
        $this->merchantApi = $merchantApi;
    }

    /** Lists accounts
     * @return array
     * @throws \Monetivo\Exceptions\MonetivoException
     */
    public function list()
    {
        return $this->merchantApi->call('get', 'accounts/')->toArray();
    }

    /** Creates account
     * @param array $account
     * @return array
     * @throws \Monetivo\Exceptions\MonetivoException
     */
    public function create(array $account)
    {
        return $this->merchantApi->call('post', 'accounts/', [
            'form_params' => $account
        ])->toArray();
    }

    /** Updates account
     * @param array $account
     * @return array
     * @throws MonetivoException
     */
    public function update(array $account)
    {
        if(!isset($account['account_id'])) {
            throw new MonetivoException('$account["account_id"] is required');
        }

        $id = $account['account_id'];
        unset($account['account_id']);
        $account['id'] = $id;

        return $this->merchantApi->call('put', 'accounts/' . $id, [
            'form_params' => $account
        ])->toArray();
    }

    /** Gets details about account
     * @param $account_id
     * @return array
     * @throws \Monetivo\Exceptions\MonetivoException
     */
    public function details($account_id)
    {
        return $this->merchantApi->call('get', 'accounts/' . $account_id)->toArray();
    }

    /** Generates a report for a specific account
     * Look at defined constants to see possible report types. Further info can be found in docs.
     * @param $account_id
     * @param $report_type
     * @param array $parameters
     * @return array
     * @throws \Monetivo\Exceptions\MonetivoException
     */
    public function report($account_id, $report_type, array $parameters = [])
    {
        $parameters = array_merge(['type' => $report_type], $parameters);
        return $this->merchantApi->call('get', 'accounts/' . $account_id . '/report?'.http_build_query($parameters))->toArray();
    }
}
