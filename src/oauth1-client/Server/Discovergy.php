<?php
/**
 * @copyright Copyright (c) 2018, Andreas Goetz
 * @author Andreas Goetz <cpuidle@gmx.de>
 * @license https://opensource.org/licenses/MIT
 */

namespace League\OAuth1\Client\Server;

use League\OAuth1\Client\Credentials\ConsumerCredentials;
use League\OAuth1\Client\Credentials\ClientCredentials;
use League\OAuth1\Client\Credentials\TokenCredentials;
use League\OAuth1\Client\Credentials\CredentialsException;
// use GuzzleHttp\Client as GuzzleHttpClient;

/**
 * Discovergy OAuth1 server class
 */
class Discovergy extends Server
{
    const DISCOVERGY_API_ENDPOINT = 'https://api.discovergy.com/public';

    public function urlConsumerToken()
    {
        return self::DISCOVERGY_API_ENDPOINT . '/v1/oauth1/consumer_token';
    }

    /**
     * Generate consumer token bound to client from api
     *
     * @param $clientid client identifier
     *
     * @return ConsumerCredentials
     */
    public function getConsumerToken($clientid)
    {
        $uri = $this->urlConsumerToken();

        $client = $this->createHttpClient();

        $headers = $this->buildHttpClientHeaders([
            'Content-Type' => 'application/x-www-form-urlencoded'
        ]);

        try {
            $response = $client->post($uri, [
                'headers' => $headers,
                'body' => http_build_query([
                    'client' => $clientid
                ])
            ]);
        } catch (BadResponseException $e) {
            return $this->handleConsumerCredentialsBadResponse($e);
        }

        return $this->createConsumerCredentials((string) $response->getBody());
    }

    /**
     * Handle a bad response coming back when getting temporary credentials.
     *
     * @param BadResponseException $e
     *
     * @throws CredentialsException
     */
    protected function handleConsumerCredentialsBadResponse(BadResponseException $e)
    {
        $response = $e->getResponse();
        $body = $response->getBody();
        $statusCode = $response->getStatusCode();

        throw new CredentialsException(
            "Received HTTP status code [$statusCode] with message \"$body\" when getting consumer credentials."
        );
    }

    /**
     * Creates token credentials from the body response.
     *
     * @param string $body
     *
     * @return TokenCredentials
     */
    protected function createConsumerCredentials($body)
    {
        $data = json_decode($body, true);

        if (!$data) {
            throw new CredentialsException('Unable to parse token credentials response.');
        }

        if (isset($data['error'])) {
            throw new CredentialsException("Error [{$data['error']}] in retrieving token credentials.");
        }

        $consumerCredentials = new ConsumerCredentials();
        $consumerCredentials->setIdentifier($data['key']);
        $consumerCredentials->setSecret($data['secret']);
        $consumerCredentials->setClient($data['owner']);
        $consumerCredentials->setCallbackUri('oob');

        return $consumerCredentials;
    }

    /**
     * Redirect the client to the authorization URL.
     *
     * @param TemporaryCredentials|string $temporaryIdentifier
     */
    public function authorizeOutOfBand($temporaryIdentifier)
    {
        $url = $this->getAuthorizationUrl($temporaryIdentifier);

        $client = $this->createHttpClient();

        try {
            $response = $client->get($url, [
                'query' => [
                    'oauth_token' => $temporaryIdentifier->getIdentifier(),
                    'email' => $this->clientCredentials->getIdentifier(),
                    'password' => $this->clientCredentials->getSecret(),
                ]
            ]);
        } catch (BadResponseException $e) {
            return $this->handleTemporaryCredentialsBadResponse($e);
        }

        return $this->createVerifier((string) $response->getBody());
    }

    /**
     * Extract oauth_verifier from authorization response
     *
     * @param $body response body
     */
    protected function createVerifier($body)
    {
        $parameters = null;
        parse_str($body, $parameters);

        if (!isset($parameters['oauth_verifier'])) {
            throw new CredentialsException('Unable to parse verification response.');
        }

        return $parameters['oauth_verifier'];
    }

    /**
     * Call api endpoint with given token credentials
     */
    public function call(TokenCredentials $tokenCredentials, $api, $queryParams = [], $options = [])
    {
        $url = self::DISCOVERGY_API_ENDPOINT . '/v1/' . $api;
        if (count($queryParams)) {
            $url .= '?' . http_build_query($queryParams);
        }

        $client = $this->createHttpClient();

        $headers = $this->getHeaders($tokenCredentials, 'GET', $url);

        try {
            $response = $client->get($url, [
                'headers' => $headers,
            ]);
        } catch (BadResponseException $e) {
            $response = $e->getResponse();
            $body = $response->getBody();
            $statusCode = $response->getStatusCode();

            throw new \Exception(
                "Received error [$body] with status code [$statusCode] when retrieving token credentials."
            );
        }

        $body = (string) $response->getBody();
        $result = isset($options['plain']) ? $body : json_decode($body, true);

        return $result;
    }

    /**
    * {@inheritDoc}
    */
    public function urlTemporaryCredentials()
    {
        return self::DISCOVERGY_API_ENDPOINT . '/v1/oauth1/request_token';
    }

    /**
    * {@inheritDoc}
    */
    public function urlAuthorization()
    {
        return self::DISCOVERGY_API_ENDPOINT . '/v1/oauth1/authorize';
    }
    /**
    * {@inheritDoc}
    */
    public function urlTokenCredentials()
    {
        return self::DISCOVERGY_API_ENDPOINT . '/v1/oauth1/access_token';
    }

    /**
     * {@inheritDoc}
     */
    public function urlUserDetails()
    {
        throw new \Exception('Not supported');
    }

    /**
     * {@inheritDoc}
     */
    public function userDetails($data, TokenCredentials $tokenCredentials)
    {
        throw new \Exception('Not supported');
    }

    /**
     * {@inheritDoc}
     */
    public function userUid($data, TokenCredentials $tokenCredentials)
    {
        throw new \Exception('Not supported');
    }

    /**
     * {@inheritDoc}
     */
    public function userEmail($data, TokenCredentials $tokenCredentials)
    {
        throw new \Exception('Not supported');
    }

    /**
     * {@inheritDoc}
     */
    public function userScreenName($data, TokenCredentials $tokenCredentials)
    {
        throw new \Exception('Not supported');
    }
}
