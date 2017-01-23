<?php

namespace Monetivo;

use Monetivo\Api\Accounts;
use Monetivo\Api\Addresses;
use Monetivo\Api\AddressesTemplates;
use Monetivo\Api\ApiRequest;
use Monetivo\Api\ApiResponse;
use Monetivo\Api\BankAccounts;
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
    const CLIENT_VERSION = '1.0.5';

    /**
     * Name of request headers
     */
    const APP_TOKEN_HEADER = 'X-API-Token';
    const AUTH_TOKEN_HEADER = 'X-Auth-Token';
    const LANG_HEADER = 'X-API-Language';
    const TIMEZONE_HEADER = 'X-API-Timezone';

    const USER_AGENT = 'monetivo/monetivo-php';

    const APP_TOKEN_VALIDATION_REGEX = '/^[a-f0-9]{8}-[a-f0-9]{4}-4{1}[a-f0-9]{3}-[89ab]{1}[a-f0-9]{3}-[a-f0-9]{12}$/';

    const DEFAULT_TIMEZONE = 'Europe/Warsaw';

    /**
     * Monetivo Merchant API endpoint URL
     */
    const API_PRODUCTION_ENDPOINT = 'https://api.monetivo.com/';
    /**
     * Monetivo Merchant Sandbox API endpoint URL
     */
    const API_SANDBOX_ENDPOINT = '';

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
     * @var string API response timezone
     * @see https://secure.php.net/manual/en/timezones.php
     */
    private $timezone = self::DEFAULT_TIMEZONE;
    /**
     * @var ApiRequest $api_client HTTP client handler
     */
    private $api_client;

    /**
     * MerchantApi constructor
     * @param string $app_token
     * @param string $language
     * @param string $timezone See https://secure.php.net/manual/en/timezones.php
     * @throws MonetivoException
     */
    public function __construct($app_token = '', $language = 'pl', $timezone = self::DEFAULT_TIMEZONE)
    {
        // by default, send all requests to the production API
        $this->current_api_endpoint = self::API_PRODUCTION_ENDPOINT;
        $this->setAppToken($app_token);
        $this->setLanguage($language);
        $this->setTimezone($timezone);
        $this->initClient();
    }

    /** Returns full URL to the API
     * @return string
     */
    public function getBaseAPIEndpoint()
    {
        return $this->current_api_endpoint . 'v' . $this->api_version . '/';
    }

    /** Overrides URL to the API.
     * @param $url
     * @throws \Monetivo\Exceptions\MonetivoException
     */
    public function setBaseAPIEndpoint($url)
    {
        $url = strtolower($url);
        if(substr($url, 0, strlen('https://')) !== 'https://')
            throw new MonetivoException('Endpoint address should start with https://');

        $this->current_api_endpoint = rtrim($url, '/').'/';
        $this->initClient();
    }

    /**
     * Sets sandbox mode
     * @throws \Monetivo\Exceptions\MonetivoException
     */
    public function setSandboxMode()
    {
        $this->setBaseAPIEndpoint(self::API_SANDBOX_ENDPOINT);
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

    /** Returns customer IP address
     * @return mixed
     */
    private function getCustomerIP()
    {
        $ip = null;
        if(array_key_exists('X-Forwarded-For', $_SERVER)) {
            $ip = $_SERVER['X-Forwarded-For'];
        } elseif(array_key_exists('HTTP_X_FORWARDED_FOR', $_SERVER)) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif(array_key_exists('REMOTE_ADDR', $_SERVER)) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        return $ip;
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

    /** Sets desired timezone
     * @param string $timezone
     * @throws MonetivoException
     */
    public function setTimezone($timezone)
    {
        try {
            $test = new \DateTimeZone($timezone);
            $this->timezone = $timezone;
        } catch (\Exception $e) {
            throw new MonetivoException('Invalid timezone');
        }
    }

    /**
     * Initialize API client with custom params and custom handler, both null by default
     * @param array $custom_params See ApiRequest->setOptions()
     * Main usage - provide Monetivo with ecomm platform:
     *      $custom_params = [
     *          'headers' => [
     *              'Platform-Name' => '',
     *              'Platform-Version' => ''
     *          ]
     *      ];
     * @param null $custom_handler
     */
    public function initClient($custom_params = [], $custom_handler = null)
    {
        $config = [
            'base_uri' => $this->getBaseAPIEndpoint(),
            'headers'  => [
                'Accept'               => 'application/json',
                'User-Agent'           => self::USER_AGENT . ' ' . self::CLIENT_VERSION,
                self::APP_TOKEN_HEADER => $this->app_token,
                self::LANG_HEADER      => $this->language,
                self::TIMEZONE_HEADER  => $this->timezone,
                'X-Customer-IP'           => $this->getCustomerIP()
            ]
        ];

        $config = array_merge($config, $custom_params);

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
                'login'    => $login,
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
        if(!$this->api_client instanceof ApiRequest) {
            throw new MonetivoException('Client not initialized');
        }

        if(!is_callable('ApiRequest', $method)) {
            throw new MonetivoException('Method not implemented in the client');
        }

        // if we have auth_token, then include it in the request
        if(!empty($this->auth_token)) {
            $params = array_merge($params, [
                'headers' => [
                    self::AUTH_TOKEN_HEADER => $this->auth_token
                ]
            ]);
        }

        /** @var ApiResponse $response */
        $response = $this->api_client->$method(ltrim($route, '/'), $params);
        $this->autoRenewAuthToken($response);

        // throw an exception if response was not 2xx
        if($response->isOK() === false) {
            throw new MonetivoException('API error: ' . (string)$response, 0, $response->getHttpCode(), (string)$response);
        }

        return $response;

    }

    /** Sets freshly generated token if previous token expired
     * The new token comes from server's response - server automatically checks if the date of current token is approaching expiration
     * @param ApiResponse $response
     * @param string $header
     * @throws \Monetivo\Exceptions\MonetivoException
     */
    private function autoRenewAuthToken(ApiResponse $response, $header = 'X-Auth-Token')
    {
        $headers = $response->getHeaders();
        if(!empty($headers[$header])) {
            $this->setAuthToken($headers[$header]);
        }
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