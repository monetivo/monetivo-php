<?php

namespace Monetivo\Api;

use Monetivo\Exceptions\MonetivoException;

/**
 * Class ApiRequest
 * @author Jakub Jasiulewicz <jjasiulewicz@monetivo.com>
 * @package Monetivo\Api
 */
class ApiRequest {

    /** An array of headers to send along with requests
     * @var array
     **/
    protected $headers = array();

    /** An array of CURLOPT options to send along with requests
     * @var array
     **/
    public $options = array();


    /** The user agent to send along with requests
     * @var string
     **/
    public $user_agent ='Monetivo';

    /** API base uri
     * @var
     */
    protected $base_uri;


    /** Stores resource handle for the current CURL request
     * @var resource
     **/
    protected $request;

    /** Location of the log file
     * @var bool
     */
    private $log_file = false;

    public function __construct(array $options)
    {

        if(!function_exists('curl_version')) {
            throw new MonetivoException('cURL PHP extension is required');
        }
        $this->options = $options;
    }

    /** Returns default options for
     * @return array
     */
    protected function getDefaultOptions() {
        return [
            'followlocation' => true,
            'connecttimeout' => 20,
            'timeout' => 60,
            'header' => true,
            'returntransfer' => true,
        ];
    }

    /** Sets cURL options and headers for the current request
     * @param array $options
     */
    protected function setOptions(array $options)
    {
        // change case of the keys
        $defaults = array_change_key_case($this->getDefaultOptions(), CASE_UPPER);
        $options = array_change_key_case($options, CASE_UPPER);
        $options = array_merge($defaults, $options);

        // set base uri if defined
        if(array_key_exists('BASE_URI', $options)) {
            $this->base_uri = $options['BASE_URI'];
        }

        // set the headers
        if(array_key_exists('HEADERS', $options) && is_array($options['HEADERS'])) {
            $this->setHeaders($options['HEADERS']);
        }

        // set proper curl options
        foreach ($options as $key => $value) {
            $cst_name = 'CURLOPT_'.strtoupper(trim($key));
            $cst =  defined($cst_name) ? constant($cst_name) : null;
            if($cst !== null) {
                curl_setopt($this->request, $cst, $value);
            }
        }
    }

    /** Sends DELETE request
     * @param $url
     * @param array $vars
     * @return mixed|ApiResponse
     */
    public function delete($url, array $vars = []) {
        return $this->call('DELETE', $url, $vars);
    }

    /** Sends GET request
     * @param $url
     * @param array $vars
     * @return mixed|ApiResponse
     * @throws \Monetivo\Exceptions\MonetivoException
     */
    public function get($url, array $vars = []) {
        return $this->call('GET', $url, $vars);
    }

    /** Sends HEAD request
     * @param $url
     * @param array $vars
     * @return mixed|ApiResponse
     * @throws \Monetivo\Exceptions\MonetivoException
     */
    public function head($url, array $vars = []) {
        return $this->call('HEAD', $url, $vars);
    }

    /** Sends POST request
     * @param $url
     * @param array $vars
     * @return mixed|ApiResponse
     * @throws \Monetivo\Exceptions\MonetivoException
     */
    public function post($url, array $vars = []) {
        return $this->call('POST', $url, $vars);
    }

    /** Sends PUT request
     * @param $url
     * @param array $vars
     * @return mixed|ApiResponse
     * @throws \Monetivo\Exceptions\MonetivoException
     */
    public function put($url, array $vars = []) {
        return $this->call('PUT', $url, $vars);
    }

    protected function setMethod($method)
    {
        switch (strtoupper($method)) {
            case 'HEAD':
                curl_setopt($this->request, CURLOPT_NOBODY, true);
                break;
            case 'GET':
                curl_setopt($this->request, CURLOPT_HTTPGET, true);
                break;
            case 'POST':
                curl_setopt($this->request, CURLOPT_POST, true);
                break;
            default:
                curl_setopt($this->request, CURLOPT_CUSTOMREQUEST, $method);
        }
    }

    /**
     * Makes an HTTP request of the specified $method to a $url with an optional array or string of $vars
     *
     * @param string $method
     * @param string $url
     * @param array|string $vars
     * @return mixed|ApiResponse
     * @throws \Monetivo\Exceptions\MonetivoException
     **/
    public function call($method, $url, array $vars = array()) {

        // initiate request, pass options
        $this->request = curl_init();
        $this->setOptions($this->options);

        // enable logging to file
        if(!empty($this->log_file)) {
            $f = fopen($this->log_file, 'a');
            curl_setopt($this->request, CURLOPT_VERBOSE, true);
            curl_setopt($this->request, CURLOPT_STDERR, $f);
        }

        // if the base uri was included in options, treat url as a relative url
        if(!empty($this->base_uri)) {
            $url = $this->base_uri.$url;
        }
        curl_setopt($this->request, CURLOPT_URL, $url);

        // set additional headers
        if(array_key_exists('headers', $vars) && is_array($vars['headers'])) {
            $this->setHeaders($vars['headers']);
            unset($vars['headers']);
        }

        // set proper curl option depending on what is declared HTTP method
        $this->setMethod($method);

        // set params for POST request
        if(!empty($vars['form_params'])) {
            curl_setopt($this->request, CURLOPT_POSTFIELDS, http_build_query($vars['form_params'], '', '&'));
        }

        // send request, parse returned response
        $response = curl_exec($this->request);

        $httpCode = curl_getinfo($this->request, CURLINFO_HTTP_CODE);

        // split returned response string into headers and body
        list($headers, $body) = $this->parseResponse($response);

        if($response === false) {
            throw new MonetivoException('API error: '.curl_errno($this->request).' - '.curl_error($this->request), 0, $httpCode);
        }

        $response = new ApiResponse($headers, $body, $httpCode);

        // close resources
        curl_close($this->request);
        if(isset($f) && is_resource($f))
        {
            fclose($f);
        }

        return $response;
    }

    /** Parses the response
     * @param $response
     * @return array
     */
    protected function parseResponse($response)
    {
        $header_size = curl_getinfo($this->request, CURLINFO_HEADER_SIZE);
        $headers = substr($response, 0, $header_size); //todo parse headers
        $body = substr($response, $header_size);

        return [$headers, $body];
    }

    /** Adds headers to the current request
     * @param array $headers
     */
    protected function setHeaders(array $headers = array()) {
        if(count($headers) === 0) {
            return;
        }
        $this->headers = array_merge($this->headers, $headers);

        $curl_headers = [];

        $curl_version = curl_version();

        foreach ($this->headers as $key => $value) {
            $curl_headers[] = $key.': '.$value;
        }
        // add some helpful information about environment
        $curl_headers[] = 'Curl-Version: '. $curl_version['version'];
        $curl_headers[] = 'PHP-Version: '. phpversion();
        curl_setopt($this->request, CURLOPT_HTTPHEADER, $curl_headers);
    }

    /** Sets base uri; Subsequent requests will be sent to the URL relative to the base uri
     * @param mixed $base_uri
     */
    public function setBaseUri($base_uri)
    {
        $this->base_uri = $base_uri;
    }

    /** Enables logging to file by setting log file location
     * @param string $log_file
     */
    public function setLogFile($log_file)
    {
        $this->log_file = $log_file;
    }

}
