<?php
/**
 * @copyright Copyright (c) 2018, Andreas Goetz
 * @author Andreas Goetz <cpuidle@gmx.de>
 * @license https://opensource.org/licenses/MIT
 */

namespace League\OAuth1\Client\Server;

use League\OAuth1\Client\Credentials\ClientCredentials;
use League\OAuth1\Client\Credentials\TokenCredentials;
use League\OAuth1\Client\Credentials\CredentialsException;
use GuzzleHttp\Exception\BadResponseException;

/**
 * Discovergy OAuth1 out of band authorization server
 */
class DiscovergyOutOfBand extends Server
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
    public function getClientCredentialsForClientId($clientid)
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
            return $this->handleClientCredentialsBadResponse($e);
        }

        ;
        if (!($json = json_decode((string) $response->getBody(), true))) {
            throw new CredentialsException('Unable to parse client credentials response.');
        }
        $json['identifier'] = $json['key'];

        return $this->createClientCredentials($json);
    }

    /**
     * Handle a bad response coming back when getting temporary credentials.
     *
     * @param BadResponseException $e
     *
     * @throws CredentialsException
     */
    protected function handleClientCredentialsBadResponse(BadResponseException $e)
    {
        $response = $e->getResponse();
        $body = $response->getBody();
        $statusCode = $response->getStatusCode();

        throw new CredentialsException(
            "Received HTTP status code [$statusCode] with message \"$body\" when getting consumer credentials."
        );
    }

    /**
     * {@inheritDoc}
     */
    public function urlAuthorization()
    {
        return self::DISCOVERGY_API_ENDPOINT . '/v1/oauth1/authorize';
    }

    /**
     * Redirect the client to the authorization URL.
     *
     * @param TemporaryCredentials $temporaryIdentifier
     */
    public function authorize($temporaryIdentifier)
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
     * {@inheritDoc}
     */
    public function urlTemporaryCredentials()
    {
        throw new \Exception('Not supported');
    }

    /**
     * {@inheritDoc}
     */
    public function urlTokenCredentials()
    {
        throw new \Exception('Not supported');
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
