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
use Monetivo\Api\PaymentChannels;
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
    const CLIENT_VERSION = '1.0.12';

    /**
     * Name of request headers
     */
    const APP_TOKEN_HEADER = 'X-API-Token';
    const AUTH_TOKEN_HEADER = 'X-Auth-Token';
    const LANG_HEADER = 'X-API-Language';
    const TIMEZONE_HEADER = 'X-API-Timezone';

    const USER_AGENT = 'monetivo/monetivo-php';

    const APP_TOKEN_VALIDATION_REGEX = '/^(test_|prod_)?[a-f0-9]{8}-[a-f0-9]{4}-4{1}[a-f0-9]{3}-[89ab]{1}[a-f0-9]{3}-[a-f0-9]{12}$/';

    const DEFAULT_TIMEZONE = 'Europe/Warsaw';

    /**
     * Monetivo Merchant API endpoint URL
     */
    const API_PRODUCTION_ENDPOINT = 'https://api.monetivo.com/';
    /**
     * Monetivo Merchant Sandbox API endpoint URL
     */
    const API_SANDBOX_ENDPOINT = 'https://api.monetivo.io/';

    /**
     * @var string
     */
    private $current_api_endpoint;

    /**
     * @var array \Closure
     */
    private $callbacks = [];

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
     * @param string $app_token required
     * @param string $language
     * @param string $timezone See https://secure.php.net/manual/en/timezones.php
     * @throws MonetivoException
     */
    public function __construct($app_token = '', $language = 'pl', $timezone = self::DEFAULT_TIMEZONE)
    {
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

    /** Overrides URL to the API (optionally)
     * Endpoint address should start with https://
     * @param $url
     * @throws \Monetivo\Exceptions\MonetivoException
     */
    public function setBaseAPIEndpoint($url)
    {
        $url = strtolower($url);
        if(substr($url, 0, strlen('https://')) !== 'https://') {
            throw new MonetivoException('Endpoint address should start with https://');
        }

        $this->current_api_endpoint = rtrim($url, '/').'/';
        if($this->api_client !== null)
            $this->api_client->setBaseUri($this->getBaseAPIEndpoint());
    }

    /**
     * Sets sandbox mode explicitly (optionally)
     * Environment is determined by app token
     * @throws \Monetivo\Exceptions\MonetivoException
     */
    public function setSandboxMode()
    {
        $this->setBaseAPIEndpoint(self::API_SANDBOX_ENDPOINT);
    }

    /** Sets target API version (optionally)
     * @param string $version
     */
    public function setAPIversion($version = '1')
    {
        preg_match('/\d+/', trim($version), $matches);

        if (count($matches) === 1) {
            $this->api_version = $matches[0];
        }
    }

    /** Sets name of your software platform used in custom integrations (optionally).
     * This name will be send as a request's header along other headers.
     * It is used mainly in plugins.
     * @param $platform
     */
    public function setPlatform($platform)
    {
        $this->api_client->setPlatform($platform);
    }

    /** Sets environment (production or sandbox) based on application token
     * each token is prefixed with "test_" or "prod_" prefixes
     * @param $app_token
     * @throws \Monetivo\Exceptions\MonetivoException
     */
    private function setEnvironment($app_token)
    {
        // by default, send all requests to the production API
        $this->setBaseAPIEndpoint(self::API_PRODUCTION_ENDPOINT);

        if(strpos(strtolower($app_token), 'test') !== false) {
            $this->setBaseAPIEndpoint(self::API_SANDBOX_ENDPOINT);
        }
    }

    /** Sets application token and environment
     * @param string $app_token
     * @throws MonetivoException
     */
    private function setAppToken($app_token)
    {
        if (!preg_match(self::APP_TOKEN_VALIDATION_REGEX, $app_token)) {
            throw new MonetivoException('App token format is invalid');
        }
        $this->app_token = $app_token;

        // determine proper environment
        $this->setEnvironment($app_token);
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
     * Initialize API client with custom params and custom handler, both empty by default
     * @param array $custom_params See ApiRequest->setOptions()
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

    /** Authenticates user
     * After successful authentication authorization token is set automatically. Further calls are no longer required.
     * @url https://merchant.monetivo.com visit to obtain credentials
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

        // check if token is present
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

        // call API
        /** @var ApiResponse $response */
        $response = $this->api_client->$method(ltrim($route, '/'), $params);

        // try to automatically renew a token
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
     * Used to inject token directly without auth (optionally).
     * If you call auth() then token is set automatically after successful authentication.
     * If you store your token somehow (e.g. in cache) then you can programmatically set the token with this method thus calling auth() is unnecesary (unless token is expired).
     * REMEMBER that token is valid only for some time. Store your token securely for a limited time.
     * @param string $token
     * @throws MonetivoException
     */
    public function setAuthToken($token)
    {
        if (empty($token)) {
            throw new MonetivoException('Auth token format is invalid');
        }

        $this->auth_token = $token;

        // invoke callback
        if(!empty($this->callbacks[__FUNCTION__]) && is_callable($this->callbacks[__FUNCTION__])) {
            $this->callbacks[__FUNCTION__]($token);
        }

    }

    /**
     * Use to set custom callback function on setAuthToken execution.
     * Useful to capture token auto-renew eg. for storing it in session.
     * @param \Closure $func
     */
    public function setAuthTokenCallback(\Closure $func)
    {
        $this->callbacks['setAuthToken'] = $func;
    }

    /** Enables logging communication with API to file
     * USE WITH CAUTION! Some sensitive data will be included in log file. Make sure that the file is protected or delete it after debugging session is finished.
     * Data about subsequent requests will be appended to the end of the file
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

    /** Payment channels
     * @return PaymentChannels
     */
    public function paymentChannels()
    {
        return new PaymentChannels($this);
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