<?php

namespace Monetivo\Api;

use Monetivo\Exceptions\MonetivoException;
use Monetivo\Interfaces\ApiInterface;
use Monetivo\MerchantApi;

/**
 * Class Pos
 * @author Jakub Jasiulewicz <jjasiulewicz@monetivo.com>
 * @package Monetivo\Api
 */
class Pos implements ApiInterface
{
    /**
     * @var MerchantApi
     */
    private $merchantApi;

    public function __construct(MerchantApi $merchantApi)
    {
        $this->merchantApi = $merchantApi;
    }

    /** Lists POS
     * @return array
     * @throws \Monetivo\Exceptions\MonetivoException
     */
    public function list()
    {
        return $this->merchantApi->call('get', 'pos/')->toArray();
    }

    /** Creates a POS
     * @param array $pos
     * @return array
     * @throws \Monetivo\Exceptions\MonetivoException
     */
    public function create(array $pos)
    {
        return $this->merchantApi->call('post', 'pos/', [
            'form_params' => $pos
        ])->toArray();
    }

    /** Updates as POS
     * @param array $pos
     * @return array
     * @throws MonetivoException
     */
    public function update(array $pos)
    {
        if(!isset($pos['id'])) {
            throw new MonetivoException('$pos["id"] is required');
        }

        return $this->merchantApi->call('put', 'pos/' . $pos['id'], [
            'form_params' => $pos
        ])->toArray();
    }

    /** Gets details about specific POS
     * @param $pos_id
     * @return array
     * @throws \Monetivo\Exceptions\MonetivoException
     */
    public function details($pos_id)
    {
        return $this->merchantApi->call('get', 'pos/' . $pos_id)->toArray();
    }

    /** Gets accounts bound to the specified POS
     * @param $pos_id
     * @return array
     * @throws \Monetivo\Exceptions\MonetivoException
     */
    public function accounts($pos_id)
    {
        return $this->merchantApi->call('get', 'pos/' . $pos_id . '/accounts')->toArray();
    }

    /** Binds account to the specified POS
     * @param $pos_id
     * @param $account_id
     * @return array
     * @throws \Monetivo\Exceptions\MonetivoException
     */
    public function bindAccount($pos_id, $account_id)
    {
        return $this->merchantApi->call('post', 'pos/' . $pos_id . '/accounts/' . $account_id)->toArray();
    }

    /** Unbinds account from POS
     * @param $pos_id
     * @param $account_id
     * @return array
     * @throws \Monetivo\Exceptions\MonetivoException
     */
    public function unbindAccount($pos_id, $account_id)
    {
        return $this->merchantApi->call('delete', 'pos/' . $pos_id . '/accounts/' . $account_id)->toArray();
    }
}
