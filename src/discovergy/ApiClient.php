<?php
/**
 * @copyright Copyright (c) 2018, Andreas Goetz
 * @author Andreas Goetz <cpuidle@gmx.de>
 * @license https://opensource.org/licenses/MIT
 */

namespace Discovergy;

use League\OAuth1\Client\Server\Discovergy;
use League\OAuth1\Client\Credentials\ConsumerCredentials;
use League\OAuth1\Client\Credentials\TokenCredentials;
use League\OAuth1\Client\Credentials\CredentialsException;

class ApiClient
{
    const CREDENTIALS_FILE = __DIR__ . '/../../secret.json';

    /** @var array */
    protected $clientConfig;

    /** @var League\OAuth1\Client\Credentials\TokenCredentials */
    protected $token;

    /** @var League\OAuth1\Client\Server\Discovergy */
    protected $server;

    public function __construct($clientConfig)
    {
        $this->validateCredentials($clientConfig);
        $this->clientConfig = $clientConfig;
    }

    public function createApiServer()
    {
        if (($cached = $this->loadCachedCredentials()) != false) {
            list($consumerCredentials, $this->token) = $cached;

            $this->server = new Discovergy($consumerCredentials);
        }
        else {
            // Retrieve consumer credentials
            $clientServer = new Discovergy($this->clientConfig);
            $consumerCredentials = $clientServer->getConsumerToken('volkszaehler');
            // print_r($consumerCredentials);

            // Retrieve temporary credentials
            $this->server = new Discovergy($consumerCredentials);
            $temporaryCredentials = $this->server->getTemporaryCredentials();
            // print_r($temporaryCredentials);

            // Authorize
            $verifier = $clientServer->authorizeOutOfBand($temporaryCredentials);
            // printf("%s\n", $verifier);

            // Retrieve token
            $this->token = $this->server->getTokenCredentials($temporaryCredentials, $temporaryCredentials->getIdentifier(), $verifier);
            // print_r($token);

            $this->saveCredentialsToCache($consumerCredentials);
        }
    }

    private function loadCachedCredentials()
    {
        $credentials = json_decode(@file_get_contents(self::CREDENTIALS_FILE), true);
        if (!$credentials) {
            return false;
        }

        $this->validateCredentials($credentials['consumer']);
        $this->validateCredentials($credentials['token']);

        $consumerCredentials = new ConsumerCredentials();
        $consumerCredentials->setIdentifier($credentials['consumer']['identifier']);
        $consumerCredentials->setSecret($credentials['consumer']['secret']);

        $token = new TokenCredentials();
        $token->setIdentifier($credentials['token']['identifier']);
        $token->setSecret($credentials['token']['secret']);

        return [$consumerCredentials, $token];
    }

    private function saveCredentialsToCache($consumerCredentials)
    {
        file_put_contents(self::CREDENTIALS_FILE, json_encode([
            'consumer' => [
                'identifier' => $consumerCredentials->getIdentifier(),
                'secret' => $consumerCredentials->getSecret(),
            ],
            'token' => [
                'identifier' => $this->token->getIdentifier(),
                'secret' => $this->token->getSecret(),
            ],
        ]));
    }

    private function validateCredentials($credentials)
    {
        if (!isset($credentials['identifier']) || empty($credentials['identifier'])) {
            throw new CredentialsException("Missing client identifier");
        }
        if (!isset($credentials['secret']) || empty($credentials['secret'])) {
            throw new CredentialsException("Missing client secret");
        }
    }

    public function call($api, $queryParams = [], $options = [])
    {
        if (!isset($this->server)) {
            $this->createApiServer();
        }

        return $this->server->call($this->token, $api, $queryParams, $options);
    }
}
