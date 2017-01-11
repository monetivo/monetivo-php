<?php

namespace Monetivo\Api;

use Monetivo\Exceptions\MonetivoException;
use Monetivo\Interfaces\ApiInterface;
use Monetivo\MerchantApi;

/**
 * Class AddressesTemplates
 * @author Jakub Jasiulewicz <jjasiulewicz@monetivo.com>
 * @package Monetivo\Api
 */
class AddressesTemplates implements ApiInterface
{
    /**
     * @var MerchantApi
     */
    private $merchantApi;

    public function __construct(MerchantApi $merchantApi)
    {
        $this->merchantApi = $merchantApi;
    }

    /** Lists address templates
     * @return array
     * @throws \Monetivo\Exceptions\MonetivoException
     */
    public function list()
    {
        return $this->merchantApi->call('get', 'addresses_templates/')->toArray();
    }

    /** Creates address template
     * @param array $contact_template
     * @return array
     * @throws \Monetivo\Exceptions\MonetivoException
     */
    public function create(array $contact_template)
    {
        return $this->merchantApi->call('post', 'addresses_templates/', [
            'form_params' => $contact_template
        ])->toArray();
    }

    /** Updates address template
     * @param array $address_template
     * @return array
     * @throws MonetivoException
     */
    public function update(array $address_template)
    {
        if(!isset($address_template['id'])) {
            throw new MonetivoException('$address_template["id"] is required');
        }

        return $this->merchantApi->call('put', 'addresses_templates/' . $address_template['id'], [
            'form_params' => $address_template
        ])->toArray();
    }

    /** Deletes address template
     * @param $address_template_id
     * @return array
     * @throws \Monetivo\Exceptions\MonetivoException
     */
    public function delete($address_template_id)
    {
        return $this->merchantApi->call('delete', 'addresses_templates/' . $address_template_id)->toArray();
    }

    /** Gets details about address template
     * @param $address_template_id
     * @return array
     * @throws \Monetivo\Exceptions\MonetivoException
     */
    public function details($address_template_id)
    {
        return $this->merchantApi->call('get', 'addresses_templates/' . $address_template_id)->toArray();
    }
}
