<?php

namespace Monetivo;

use Monetivo\Api\Accounts;
use Monetivo\Api\Addresses;
use Monetivo\Api\AddressesTemplates;
use Monetivo\Api\ApiRequest;
use Monetivo\Api\ApiResponse;
use Monetivo\Api\BankAccounts;
use Monetivo\Api\Channels;
use Monetivo\Api\Contacts;
use Monetivo\Api\ContactsTemplates;
use Monetivo\Api\Offer;
use Monetivo\Api\Payouts;
use Monetivo\Api\Pos;
use Monetivo\Api\Transactions;
use Monetivo\Exceptions\MonetivoException;

/**
 * Monetivo Merchant API client
 * @author Grzegorz Agaciński <gagacinski@monetivo.com>
 * @author Jakub Jasiulewicz <jjasiulewicz@monetivo.com>
 * @see  https://docs.monetivo.com/
 * @package Monetivo
 */
class MerchantApi
{

    /**
     * Merchant API client version
     */
    const CLIENT_VERSION = '1.0.0';

    /**
     * Name of request headers
     */
    const APP_TOKEN_HEADER = 'X-API-Token';
    const AUTH_TOKEN_HEADER = 'X-Auth-Token';
    const LANG_HEADER = 'X-API-Language';

    const USER_AGENT = 'MonetivoMerchantApi/v';

    const APP_TOKEN_VALIDATION_REGEX = '/^[a-f0-9]{8}-[a-f0-9]{4}-4{1}[a-f0-9]{3}-[89ab]{1}[a-f0-9]{3}-[a-f0-9]{12}$/';

    /**
     * Monetivo Merchant API endpoint URL
     */
    const API_ENDPOINT = 'https://api.monetivo.com/';
    /**
     * Monetivo Merchant Sandbox API endpoint URL
     */
    const SANDBOX_API_ENDPOINT = '';

    /**
     * @var string
     */
    private $current_api_endpoint;

    /**
     * @var array languages supported by the API
     */
    private static $supported_langs = ['pl', 'en'];

    /**
     * @var string current API version
     */
    private $api_version = '1';

    /**
     * @var string Merchant App token
     */
    private $app_token = '';
    /**
     * @var string Auth token (used after login)
     */
    private $auth_token = '';
    /**
     * @var string API messages language
     */
    private $language = 'en';
    /**
     * @var ApiRequest $api_client Guzzle HTTP client handler
     */
    private $api_client;

    /**
     * MerchantApi constructor
     * @param string $app_token
     * @param string $language
     * @param null $custom_handler
     * @throws MonetivoException
     */
    public function __construct($app_token = '', $language = 'pl', $custom_handler = null)
    {
        $this->setAppToken($app_token);
        $this->setLanguage($language);
        $this->initClient($custom_handler);
    }

    /** Returns full URL to the API
     * @return string
     */
    public function getBaseAPIEndpoint()
    {
        return self::API_ENDPOINT . 'v' . $this->api_version.'/';
    }

    /** Sets target API version
     * @param string $version
     */
    public function setAPIversion($version = '1')
    {
        preg_match('/\d+/', trim($version), $matches);

        if (count($matches) === 1) {
            $this->api_version = $matches[0];
        }
    }

    /**
     * @param string $app_token
     * @throws MonetivoException
     */
    private function setAppToken($app_token)
    {
        if (!$this->validateToken($app_token)) {
            throw new MonetivoException('App token format is invalid');
        }
        $this->app_token = $app_token;
    }

    /**
     * Local validation of Merchant App token
     * @param string $token
     * @return int
     */
    private function validateToken($token)
    {
        return preg_match(self::APP_TOKEN_VALIDATION_REGEX, $token);
    }

    /** Sets desired language; Defaults to 'en'
     * @param string $language
     * @throws MonetivoException
     */
    public function setLanguage($language)
    {
        if (!is_string($language) || strlen($language) !== 2) {
            throw new MonetivoException('Language format is invalid');
        }
        $language = strtolower($language);

        $this->language = in_array($language, self::$supported_langs) ? $language : 'en';
    }

    /**
     * Initialize API client with default params
     * @param null $custom_handler
     */
    public function initClient($custom_handler = null)
    {
        $config = [
            'base_uri' => $this->getBaseAPIEndpoint(),
            'headers' => [
                'Accept' => 'application/json',
                'User-Agent' => self::USER_AGENT . self::CLIENT_VERSION,
                self::APP_TOKEN_HEADER => $this->app_token,
                self::LANG_HEADER => $this->language
            ]
        ];

        $this->api_client = new ApiRequest($config);
    }

    /** Returns instance of the API Client
     * @return ApiRequest
     */
    public function getApiClient()
    {
        return $this->api_client;
    }

    /**
     * @param $login
     * @param $password
     * @return mixed
     * @throws MonetivoException
     */
    public function auth($login, $password)
    {
        $response = $this->call('post', 'auth/login', [
            'form_params' => [
                'login' => $login,
                'password' => $password
            ]
        ]);

        if (!isset($response['token'])) {
            if (isset($response['errors']) || isset($response['code'])) {
                $error_message = isset($response['code']) ? $response['message'] : $response['errors'][0]['message'];
                throw new MonetivoException('API error: ' . $error_message, 0, $response->getHttpCode(), (string)$response);
            } else {
                throw new MonetivoException('API ' . __FUNCTION__ . ' response is invalid: ' . (string)$response, 0, $response->getHttpCode(), (string)$response);
            }
        }

        // put the auth_token
        $this->setAuthToken($response['token']);

        // return for further usage
        return $response['token'];
    }

    /** Issues cutomized requests to API (low-level)
     * @param string $method HTTP request method post/get/put/delete etc.
     * @param string $route Relative to endpoint url
     * @param array $params Request options
     * @return ApiResponse
     * @throws MonetivoException
     */
    public function call($method, $route, $params = [])
    {
        if (!$this->api_client instanceof ApiRequest) {
            throw new MonetivoException('Client not initialized');
        }

        if (!is_callable('ApiRequest', $method)) {
            throw new MonetivoException('Method not implemented in the client');
        }

        // if we have auth_token, then include it in the request
        if (!empty($this->auth_token)) {
            $params = array_merge($params, [
                'headers' => [
                    self::AUTH_TOKEN_HEADER => $this->auth_token
                ]
            ]);
        }

        /** @var ApiResponse $response */
        $response = $this->api_client->$method(ltrim($route, '/'), $params);

        // throw an exception if response was not 2xx
        if($response->isOK() === false) {
            throw new MonetivoException('API error: ' . (string)$response, 0, $response->getHttpCode(), (string)$response);
        }

        return $response;

    }

    /**
     * Used to inject token directly without auth
     * @param string $token
     * @throws MonetivoException
     */
    public function setAuthToken($token)
    {
        if (empty($token)) {
            throw new MonetivoException('Auth token format is invalid');
        }

        $this->auth_token = $token;

    }

    /** Enables logging communication with API to file
     * USE WITH CAUTION! Some sensitive infos will be included in log file. Make sure that the file is protected or delete it after debugging session is finished.
     * infos about subsequent requests will be appended to the end of the file
     * @param $log_file
     */
    public function enableLogging($log_file)
    {
        if($this->api_client !== null)
        {
            $this->api_client->setLogFile($log_file);
        }
    }

    /**
     * Disables logging
     */
    public function disableLogging()
    {
        if($this->api_client !== null)
        {
            $this->api_client->setLogFile(false);
        }
    }

    /** Handles Monetivo ping notification
     * @see Transactions::handleCallback()
     * @param null $identifier
     * @return array|bool
     */
    public function handleCallback($identifier = null)
    {
        return (new Transactions($this))->handleCallback($identifier);
    }

    /** Transactions
     * @return Transactions
     */
    public function transactions()
    {
        return new Transactions($this);
    }

    /** Addresses
     * @return Addresses
     */
    public function addresses()
    {
        return new Addresses($this);
    }

    /** Addresses templates
     * @return AddressesTemplates
     */
    public function addressesTemplates()
    {
        return new AddressesTemplates($this);
    }

    /** Contacts
     * @return Contacts
     */
    public function contacts()
    {
        return new Contacts($this);
    }

    /** Contacts templates
     * @return ContactsTemplates
     */
    public function contactsTemplates()
    {
        return new ContactsTemplates($this);
    }

    /** Points of Sales
     * @return Pos
     */
    public function pos()
    {
        return new Pos($this);
    }

    /** Merchant's accounts
     * @return Accounts
     */
    public function accounts()
    {
        return new Accounts($this);
    }

    /** Bank accounts
     * @return BankAccounts
     */
    public function bankAccounts()
    {
        return new BankAccounts($this);
    }

    /** Payouts
     * @return Payouts
     */
    public function payouts()
    {
        return new Payouts($this);
    }

    /** Offer
     * @return Offer
     */
    public function offer()
    {
        return new Offer($this);
    }
}