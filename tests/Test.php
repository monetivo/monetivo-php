<?php

use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Handler\MockHandler;
use Monetivo\MerchantApi;

class MerchantApiTest extends PHPUnit_Framework_TestCase
{
    const TEST_APP_TOKEN = '10b96ea8-b8bb-4303-822c-2370f1fed5cc';
    const TEST_AUTH_TOKEN = '';
    const TEST_LOGIN = '';
    const TEST_PASS = '';

    public function testSettingUpAPIVersion()
    {
        $api = new MerchantApi(self::TEST_APP_TOKEN);

        $api->setAPIversion('version2');
        $this->assertEquals('https://api.monetivo.com/v2/', $api->getBaseAPIEndpoint());

        $api->setAPIversion(4);
        $this->assertEquals('https://api.monetivo.com/v4/', $api->getBaseAPIEndpoint());

        $api->setAPIversion(-6);
        $this->assertEquals('https://api.monetivo.com/v6/', $api->getBaseAPIEndpoint());
    }

    /**
     * @expectedException ErrorException
     * @expectedExceptionMessage App token format is invalid
     */
    public function testInitWithInvalidTokenFormat()
    {
        $api = new MerchantApi('invalidtoken');
    }

    /**
     * @expectedException ErrorException
     * @expectedExceptionMessage Language format is invalid
     */
    public function testInitWithInvalidLanguage()
    {
        $api = new MerchantApi(self::TEST_APP_TOKEN, null);
    }

    /**
     * @expectedException ErrorException
     * @expectedExceptionMessage API error: API token invalid
     */
    public function testAuthWithInvalidAppToken()
    {
        $mockHandler = new MockHandler([
            new Response(401, [], '{"code":"401","message":"API token invalid"}')
        ]);

        $api = new MerchantApi(self::TEST_APP_TOKEN, 'pl', $mockHandler);
        $api->Auth('dummyuser', 'dummypass');
    }

    /**
     * @expectedException ErrorException
     * @expectedExceptionMessage API error: Unauthorized access
     */
    public function testAuthWithInvalidCredentials()
    {
        $mockHandler = new MockHandler([
            new Response(401, [], '{"code":"401","message":"Unauthorized access"}')
        ]);

        $api = new MerchantApi(self::TEST_APP_TOKEN, 'pl', $mockHandler);
        $api->Auth('dummyuser', 'dummypass');
    }

    /**
     * @expectedException ErrorException
     * @expectedExceptionMessage Invalid CreateTransaction params: pos_id
     */
    public function testCreateTransactionWithInvalidParams()
    {
        $mockHandler = new MockHandler([
            new Response(200, [], '{"user":null,"token":"validdummytoken"}')
        ]);

        $api = new MerchantApi(self::TEST_APP_TOKEN, 'pl', $mockHandler);
        $api->Auth('dummyuser', 'dummypass');
        $api->CreateTransaction();
    }
}
