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
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\ResponseException;

/**
 * Discovergy OAuth1 server class
 */
class Discovergy extends Server
{
    const DISCOVERGY_API_ENDPOINT = 'https://api.discovergy.com/public';

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
        if (isset($options['headers'])) {
            $headers = array_merge($headers, $options['headers']);
        }

        try {
            $response = $client->get($url, [
                'headers' => $headers,
            ]);
        } catch (BadResponseException $e) {
            $response = $e->getResponse();
            $statusCode = $response->getStatusCode();

            if ($options['httpunauthorized'] && $statusCode == 401) {
                throw $e;
            }

            $body = $response->getBody();

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
        throw new \Exception('Not supported');
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
