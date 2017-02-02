<?php

namespace Monetivo\Api;

use Monetivo\Exceptions\MonetivoException;
use Monetivo\Interfaces\ApiInterface;
use Monetivo\MerchantApi;

/**
 * Class Transactions
 * @author Jakub Jasiulewicz <jjasiulewicz@monetivo.com>
 * @package Monetivo\Api
 */
class Transactions implements ApiInterface
{
    /**
     *  Transaction statuses (self explanatory)
     */
    const TRAN_STATUS_NEW = 1;
    const TRAN_STATUS_PAID = 2;
    const TRAN_STATUS_ACCEPTED = 4;
    const TRAN_STATUS_REFUNDED = 16;
    const TRAN_STATUS_DECLINED = 32;
    /**
     * @var MerchantApi
     */
    private $merchantApi;

    public function __construct(MerchantApi $merchantApi)
    {
        $this->merchantApi = $merchantApi;
    }

    /** Handles Monetivo ping notification
     * identifier of the transaction comes from the request sent by the Monetivo notifications system
     * if you omit identifier parameter, this method will try to obtain an identifier from the $_POST superglobal
     * @example:
     * $api = new Monetivo\MerchantApi();
     * $api->auth('your_login', 'your_password');
     * $transaction = $api->handleCallback('identifier_of_your_transaction');
     * if($transaction)
     * {
     *      // your code goes here
     * }
     * @see https://docs.monetivo.com/ For transaction response reference
     * @param null $identifier Identifier of the transaction (optional)
     * @return array|bool
     */
    public function handleCallback($identifier = null)
    {
        // get identifier of the transaction from $_POST superglobal if not defined
        if($identifier === null) {
            $identifier = $_POST['identifier'];
        }

        try {
            $transaction = $this->details($identifier);
            if ($transaction['status'] == self::TRAN_STATUS_ACCEPTED) {
                return $transaction;
            }
        } catch (\Exception $e) {
        }

        return false;

    }

    /**
     * Creates transaction
     * @param array $params
     * @return array
     * @throws MonetivoException
     */
    public function create(array $params = [])
    {
        if(!empty($params['currency'])) {
            $params['currency'] = strtoupper(trim($params['currency']));
        }

        $response = $this->merchantApi->call('post', 'transactions', [
            'form_params' => $params
        ]);
        // ensure that response contains necessary elements - identifier and sign of the newly created transaction
        if (empty($response['identifier']) || empty($response['sign'])) {
            throw new MonetivoException('Invalid ' . __FUNCTION__ . ' response: '.(string)$response, 0, $response->getHttpCode(), (string)$response);
        }

        return $response->toArray();

    }

    /** Lists the transactions
     *
     * @param array $pagination_settings
     * @return array
     * @throws \Monetivo\Exceptions\MonetivoException
     */
    public function listing(array $pagination_settings = [])
    {
        $pagination_settings = (count($pagination_settings) > 0) ?  '?'.http_build_query($pagination_settings) : '';
        return $this->merchantApi->call('get', 'transactions/'.$pagination_settings)->toArray();
    }

    /**
     * Get transaction details
     * @param string $identifier
     * @return array
     * @throws MonetivoException
     */
    public function details($identifier)
    {
        return $this->merchantApi->call('get', 'transactions/' . $identifier)->toArray();
    }

    /** Accepts the transaction
     * Accceptance is usually not necessary because Auto-acceptance feature is enabled by default. See your account configuration for possible options.
     * @param $identifier
     * @return array
     * @throws \Monetivo\Exceptions\MonetivoException
     */
    public function accept($identifier)
    {
        return $this->merchantApi->call('put', 'transactions/' . $identifier . '/accept')->toArray();
    }

    /** Declines the transaction
     * Only paid transaction can be declined
     * @param $identifier
     * @return array
     * @throws \Monetivo\Exceptions\MonetivoException
     */
    public function decline($identifier)
    {
        return $this->merchantApi->call('put', 'transactions/' . $identifier . '/decline')->toArray();
    }

    /** Refunds the transaction
     * @param array $transaction
     * @return array
     * @throws \Monetivo\Exceptions\MonetivoException
     */
    public function refund(array $transaction)
    {
        // transaction identifier must be set
        if(!isset($transaction['identifier']))
        {
            throw new MonetivoException('$transaction["identifier"] is not set');
        }
        // other parameters like 'refund_amount' and 'desc' are optional

        return $this->merchantApi->call('post', 'transactions/' . $transaction['identifier'] . '/refund', $transaction)->toArray();
    }



}
