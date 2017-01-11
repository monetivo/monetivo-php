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
}
