<?php

namespace Monetivo\Api;

use ArrayAccess;
use JsonSerializable;
use Monetivo\Exceptions\MonetivoException;

/**
 * Class ApiResponse
 * @author Jakub Jasiulewicz <jjasiulewicz@monetivo.com>
 * @package Monetivo\Api
 */
class ApiResponse implements JsonSerializable, ArrayAccess
{
    /** Parsed body
     * @var
     */
    private $body = array();

    /** Parsed headers
     * @var array
     */
    private $headers = array();

    /** HTTP status code
     * @var
     */
    private $httpCode;

    public function __construct($headers, $body, $httpCode)
    {
        $this->parseHeaders($headers);
        $this->parseBody($body, $httpCode);
        $this->httpCode = $httpCode;
    }

    /** Parses headers to an array
     * @param $headers
     */
    private function parseHeaders($headers)
    {
        $headers_temp = array();

        $requests = explode("\r\n\r\n", $headers);
        // follow eventual redirections
        for ($index = 0; $index < count($requests) - 1; $index++) {

            foreach (explode("\r\n", $requests[$index]) as $i => $line) {
                if ($i === 0)
                    continue;
                list($key, $value) = explode(': ', $line);
                $headers_temp[$index][$key] = $value;
            }
        }
        // gets always the latest response
        $this->headers = end($headers_temp) !== false ? end($headers_temp) : [];
    }

    /** Parses the response to an array
     * @param $response
     * @param $httpCode
     * @throws MonetivoException
     */
    private function parseBody($response, $httpCode)
    {
        if (!is_string($response)) {
            return;
        }

        $parsed = json_decode($response, 1);
        if (json_last_error() !== 0) {
            throw new MonetivoException('API response is malformed: ' . json_last_error_msg(), 0, $httpCode, $response);
        }

        $this->body = $parsed;
    }

    /** Checks if the response is successful (2xx)
     * @return bool
     */
    public function isOK()
    {
        return 0 === strpos((string)$this->httpCode, '2');
    }

    /**
     * Specify data which should be serialized to JSON
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     */
    public function jsonSerialize()
    {
        return $this->__toString();
    }

    /** Serialize response to JSON string
     * @return string
     */
    public function __toString()
    {
        return json_encode($this->toArray());
    }

    /** Casts response to an array
     * @return array
     */
    public function toArray()
    {
        return array_merge($this->body, ['httpCode' => $this->getHttpCode()]);
    }

    /** Returns HTTP status code
     * @return mixed
     */
    public function getHttpCode()
    {
        return $this->httpCode;
    }

    /** Returns response headers
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * Offset to unset
     * @link http://php.net/manual/en/arrayaccess.offsetunset.php
     */
    public function offsetUnset($offset)
    {
        unset($this->body[$offset]);
    }

    /** __get magic method implementation
     * @param $offset
     * @return mixed|null
     */
    public function __get($offset)
    {
        return $this->offsetGet($offset);
    }

    /** __set magic method implementation
     * @param $offset
     * @param $value
     */
    public function __set($offset, $value)
    {
        $this->offsetSet($offset, $value);
    }

    /**
     * Offset to retrieve
     * @link http://php.net/manual/en/arrayaccess.offsetget.php
     */
    public function offsetGet($offset)
    {
        return isset($this->body[$offset]) ? $this->body[$offset] : null;
    }

    /**
     * Offset to set
     * @link http://php.net/manual/en/arrayaccess.offsetset.php
     */
    public function offsetSet($offset, $value)
    {
        if ($offset === null) {
            $this->body[] = $value;
        } else {
            $this->body[$offset] = $value;
        }
    }

    /** __isset magic method implementation
     * @param $name
     * @return bool
     */
    public function __isset($name)
    {
        return $this->offsetExists($name);
    }

    /**
     * Whether a offset exists
     * @link http://php.net/manual/en/arrayaccess.offsetexists.php
     */
    public function offsetExists($offset)
    {
        return isset($this->body[$offset]);
    }


}
