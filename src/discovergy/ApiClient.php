<?php
/**
 * @copyright Copyright (c) 2018, Andreas Goetz
 * @author Andreas Goetz <cpuidle@gmx.de>
 * @license https://opensource.org/licenses/MIT
 */

namespace Discovergy;

use League\OAuth1\Client\Server\Discovergy;
use League\OAuth1\Client\Server\DiscovergyOutOfBand;
use League\OAuth1\Client\Credentials\ClientCredentials;
use League\OAuth1\Client\Credentials\TokenCredentials;
use League\OAuth1\Client\Credentials\CredentialsException;
use GuzzleHttp\Exception\BadResponseException;

/**
 * ApiClient encapsulates a Discovergy OAuth1 server
 */
class ApiClient
{
    const DISCOVERGY_CREDENTIALS = __DIR__ . '/../../discovergy.json';
    const CLIENT_CREDENTIALS = __DIR__ . '/../../client.json';
    const TOKEN_CREDENTIALS = __DIR__ . '/../../token.json';

    const DISCOVERGY_ATTRIBUTES = ['clientid', 'identifier', 'secret'];
    const DEFAULT_ATTRIBUTES = ['identifier', 'secret'];

    /** @var League\OAuth1\Client\Credentials\TokenCredentials */
    protected $token;

    /** @var League\OAuth1\Client\Server\Discovergy */
    protected $server;

    /**
     * Create a new server instance
     * Uses either cached credentials or performs new login
     */
    public function createApiServer()
    {
        if (null == ($clientCredentials = $this->loadClientCredentials())) {
            // prepare oob server
            $discovergyCredentials = $this->loadDiscovergyCredentials();
            $oob = new DiscovergyOutOfBand($discovergyCredentials);

            // get consumer credentials (requires client id)
            $clientId = $discovergyCredentials['clientid'];
            $clientCredentials = $oob->getClientCredentialsForClientId($clientId);
            $this->saveClientCredentials($clientCredentials);
            // print_r($clientCredentials);
        }

        $this->server = new Discovergy($clientCredentials);

        if (null == ($this->token = $this->loadTokenCredentials())) {
            $this->createTokenCredentials();
        }
    }

    /**
     * Create token credentials
     */
    public function createTokenCredentials()
    {
        // prepare oob server
        $discovergyCredentials = $this->loadDiscovergyCredentials();
        $oob = new DiscovergyOutOfBand($discovergyCredentials);

        // get temporary credentials
        $temporaryCredentials = $this->server->getTemporaryCredentials();
        // print_r($temporaryCredentials);

        // authorize temporary credentials
        $verifier = $oob->authorize($temporaryCredentials);
        // printf("%s\n", $verifier);

        // Retrieve token
        $this->token = $this->server->getTokenCredentials($temporaryCredentials, $temporaryCredentials->getIdentifier(), $verifier);
        $this->saveTokenCredentials($this->token);
        // print_r($this->token);
    }

    /**
     * Load discovergy credentials from file
     */
    protected function loadDiscovergyCredentials()
    {
        $credentials = FileHelper::loadJsonFile(self::DISCOVERGY_CREDENTIALS);

        try {
            $this->validateCredentials($credentials, self::DISCOVERGY_ATTRIBUTES);
        }
        catch (CredentialsException $e) {
            throw new CredentialsException('Invalid discovergy credentials attribute: ', $e->getMessage());
        }

        return $credentials;
    }

    /**
     * Load client token from file
     */
    protected function loadClientCredentials()
    {
        if (null === ($credentials = FileHelper::loadJsonFile(self::CLIENT_CREDENTIALS, false))) {
            return null;
        }

        try {
            $this->validateCredentials($credentials, self::DEFAULT_ATTRIBUTES);
        }
        catch (CredentialsException $e) {
            throw new CredentialsException('Missing client credential attribute: ' . $e->getMessage());
        }

        $clientCredentials = new ClientCredentials();
        $clientCredentials->setIdentifier($credentials['identifier']);
        $clientCredentials->setSecret($credentials['secret']);
        // $clientCredentials->setCallbackUri('oob');

        return $clientCredentials;
    }

    /**
     * Save client token to file
     */
    protected function saveClientCredentials($credentials)
    {
        $this->saveCredentials(self::CLIENT_CREDENTIALS, $credentials);
    }

    /**
     * Load access token from file
     */
    protected function loadTokenCredentials()
    {
        if (null === ($credentials = FileHelper::loadJsonFile(self::TOKEN_CREDENTIALS, false))) {
            return null;
        }

        try {
            $this->validateCredentials($credentials, self::DEFAULT_ATTRIBUTES);
        }
        catch (CredentialsException $e) {
            throw new CredentialsException('Missing token credential attribue: ' . $e->getMessage());
        }

        $consumerCredentials = new TokenCredentials();
        $consumerCredentials->setIdentifier($credentials['identifier']);
        $consumerCredentials->setSecret($credentials['secret']);

        return $consumerCredentials;
    }

    /**
     * Save access token to file
     */
    protected function saveTokenCredentials($credentials)
    {
        return $this->saveCredentials(self::TOKEN_CREDENTIALS, $credentials);
    }

    /**
     * Save credentials to json file
     */
    protected function saveCredentials($file, $credentials)
    {
        $json = json_encode([
            'identifier' => $credentials->getIdentifier(),
            'secret' => $credentials->getSecret(),
        ], JSON_PRETTY_PRINT);

        if (false === @file_put_contents($file, $json)) {
            throw new \Exception('Could not save credentials to ' . $file);
        }
    }

    /**
     * Validate credentials loaded from cache
     */
    private function validateCredentials($credentials, $attributes)
    {
        foreach ($attributes as $key) {
            if (!isset($credentials[$key]) || empty($credentials[$key])) {
                throw new CredentialsException($key);
            }
        }
    }

    /**
     * Call api - creates a signed OAuth1 request
     */
    public function call($api, $queryParams = [], $options = [])
    {
        if (!isset($this->server)) {
            $this->createApiServer();
        }

        // add text/plain header
        $headers = $options['headers'] ?? [];
        $headers = array_merge($headers, [
            'Accept' => 'application/json, text/plain',
        ]);

        $options['headers'] = $headers;

        // raise HTTP 401 back to API client
        $options['httpunauthorized'] = 'true';

        try {
            return $this->executeCall($api, $queryParams, $options);
        }
        catch (BadResponseException $e) {
            $this->createTokenCredentials();
            return $this->executeCall($api, $queryParams, $options);
        }
    }

    private function executeCall($api, $queryParams, $options)
    {
        return $this->server->call($this->token, $api, $queryParams, $options);
    }
}
