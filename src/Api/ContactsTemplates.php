<?php

namespace Monetivo\Api;

use Monetivo\Exceptions\MonetivoException;
use Monetivo\Interfaces\ApiInterface;
use Monetivo\MerchantApi;

/**
 * Class ContactsTemplates
 * @author Jakub Jasiulewicz <jjasiulewicz@monetivo.com>
 * @package Monetivo\Api
 */
class ContactsTemplates implements ApiInterface
{
    /**
     * @var MerchantApi
     */
    private $merchantApi;

    public function __construct(MerchantApi $merchantApi)
    {
        $this->merchantApi = $merchantApi;
    }

    /** Lists contact templates
     * @return array
     * @throws \Monetivo\Exceptions\MonetivoException
     */
    public function list()
    {
        return $this->merchantApi->call('get', 'contacts_templates/')->toArray();
    }

    /** Creates contact template
     * @param array $contact_template
     * @return array
     * @throws \Monetivo\Exceptions\MonetivoException
     */
    public function create(array $contact_template)
    {
        return $this->merchantApi->call('post', 'contacts_templates/', [
            'form_params' => $contact_template
        ])->toArray();
    }

    /** Updates contact template
     * @param array $contact_template
     * @return array
     * @throws MonetivoException
     */
    public function update(array $contact_template)
    {
        if(!isset($contact_template['id'])) {
            throw new MonetivoException('$contact["type"] and $contact["id"] are required');
        }

        return $this->merchantApi->call('put', 'contacts_templates/' . $contact_template['id'], [
            'form_params' => $contact_template
        ])->toArray();
    }

    /** Deletes contact template
     * @param $contact_template_id
     * @return array
     * @throws \Monetivo\Exceptions\MonetivoException
     */
    public function delete($contact_template_id)
    {
        return $this->merchantApi->call('delete', 'contacts_templates/' . $contact_template_id)->toArray();
    }

    /** Gets details about contact template
     * @param $contact_template_id
     * @return array
     * @throws \Monetivo\Exceptions\MonetivoException
     */
    public function details($contact_template_id)
    {
        return $this->merchantApi->call('get', 'contacts_templates/' . $contact_template_id)->toArray();
    }
}
