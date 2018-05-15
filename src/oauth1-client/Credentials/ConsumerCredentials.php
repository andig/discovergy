<?php
/**
 * @copyright Copyright (c) 2018, Andreas Goetz
 * @author Andreas Goetz <cpuidle@gmx.de>
 * @license https://opensource.org/licenses/MIT
 */

namespace League\OAuth1\Client\Credentials;

class ConsumerCredentials extends ClientCredentials implements CredentialsInterface
{
    private $client;

    public function getClient()
    {
        return $this->client;
    }

    public function setClient($client)
    {

        $this->client = $client;
    }
}
