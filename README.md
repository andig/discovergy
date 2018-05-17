# Discovergy API client

PHP client library for the Discovergy API (https://api.discovergy.com/docs/)

## Setup

Put discovergy login credentials into `discovergy.json`. Set identifier/secret as email and password:

	{
		"clientid": "discovergy-example",
		"identifier": "",
		"secret": "",
	}

Running `example.php` will create `client.json` which contains the client API token and `token.json` which is an access token that can be regenerated with discovergy login credentials and client API token.

Possession of `discovergy.json` allows to create client API tokens.
Possession of `discovergy.json` together with `client.json` allows to create access tokens. Both client API token and access token are required to execute API calls.

## Usage

`Discovergy\ApiClient` will read token information from the respective files or (re)generate them when necessary.

As API user calling

	ApiClient->call('api');

is all that is required.
