<?php

namespace Monetivo\Api;

use Monetivo\Interfaces\ApiInterface;
use Monetivo\MerchantApi;

/**
 * Class Contacts
 * @author Jakub Jasiulewicz <jjasiulewicz@monetivo.com>
 * @package Monetivo\Api
 */
class Contacts implements ApiInterface
{

    const TYPE_MAIN = 'main';
    const TYPE_TECHNICAL = 'technical';
    const TYPE_ACCOUNTING = 'accounting';
    /**
     * @var MerchantApi
     */
    private $merchantApi;

    public function __construct(MerchantApi $merchantApi)
    {
        $this->merchantApi = $merchantApi;
    }

    /** Lists contacts according to type
     * @param $type
     * @return array
     * @throws \Monetivo\Exceptions\MonetivoException
     */
    public function listing($type)
    {
        return $this->merchantApi->call('get', 'contacts/' . $type)->toArray();
    }

    /** Update contact
     * @param $type
     * @param array $contact
     * @return array
     * @throws \Monetivo\Exceptions\MonetivoException
     */
    public function update($type, array $contact)
    {
        return $this->merchantApi->call('put', 'contacts/' . $type, [
            'form_params' => $contact
        ])->toArray();
    }
}
