#!/usr/bin/php
<?php
/**
 * @copyright Copyright (c) 2018, Andreas Goetz
 * @author Andreas Goetz <cpuidle@gmx.de>
 * @license https://opensource.org/licenses/MIT
 */

require_once(__DIR__ . '/vendor/autoload.php');

use Discovergy\ApiClient;

/*
 * discovergy.json
 *
 * {
 *   "clientid": "discovergy-example",
 *   "identifier": "",
 *   "secret": "",
 * }
 */
$api = new ApiClient();

$json = $api->call('meters');
print_r($json);
die;

$meter0 = sprintf('%s_%s', $json[0]['type'], $json[0]['serialNumber']);

$json = $api->call('devices', ['meterId' => $meter0]);
print_r($json);

$json = $api->call('field_names', ['meterId' => $meter0]);
print_r($json);

$json = $api->call('readings', [
    'meterId' => $meter0,
    'from' => (time() - 120) * 1e3
]);
print_r($json);

$json = $api->call('last_reading', ['meterId' => $meter0]);
print_r($json);

$json = $api->call('statistics', [
    'meterId' => $meter0,
    'from' => (time() - 120) * 1e3
]);
print_r($json);

// $json = $api->call('load_profile', ['meterId' => $meter0]);
// print_r($json);

// $json = $api->call('raw_load_profile', ['meterId' => $meter0]);
// print_r($json);

$json = $api->call('disaggregation', [
    'meterId' => $meter0,
    'from' => (time() - 24 * 3600) * 1e3
]);
print_r($json);

$json = $api->call('activities', [
    'meterId' => $meter0,
    'from' => (time() - 24 * 3600) * 1e3,
    'to' => time() * 1e3
]);
print_r($json);

$json = $api->call('website_access_code', [
    'email' => $config['identifier'],
], ['plain' => true]);
print_r($json);
