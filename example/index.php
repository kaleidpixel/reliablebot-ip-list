<?php
require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'src'.DIRECTORY_SEPARATOR.'ReliableBotIPList.php';

use kaleidpixel\ReliableBotIPList;

/**
 * Add Jetpack IPs to an Allowlist
 * https://jetpack.com/support/how-to-add-jetpack-ips-allowlist/
 */
$add_ip_list = [
	'jetpack' => [
		'122.248.245.244/32',
		'54.217.201.243/32',
		'54.232.116.4/32',
		'195.234.108.0/22',
		'192.0.64.0/18',
		'192.0.64.0/24',
		'192.0.65.0/24',
		'192.0.66.0/24',
		'192.0.67.0/24',
		'192.0.68.0/24',
		'192.0.69.0/24',
		'192.0.70.0/24',
		'192.0.71.0/24',
		'192.0.72.0/24',
		'192.0.73.0/24',
		'192.0.74.0/24',
		'192.0.75.0/24',
		'192.0.76.0/24',
		'192.0.77.0/24',
		'192.0.78.0/24',
		'192.0.79.0/24',
		'192.0.80.0/24',
		'192.0.81.0/24',
		'192.0.82.0/24',
		'192.0.83.0/24',
		'192.0.84.0/24',
		'192.0.85.0/24',
		'192.0.86.0/24',
		'192.0.87.0/24',
		'192.0.88.0/24',
		'192.0.89.0/24',
		'192.0.90.0/24',
		'192.0.91.0/24',
		'192.0.92.0/24',
		'192.0.93.0/24',
		'192.0.94.0/24',
		'192.0.95.0/24',
		'192.0.96.0/24',
		'192.0.97.0/24',
		'192.0.98.0/24',
		'192.0.99.0/24',
		'192.0.100.0/24',
		'192.0.101.0/24',
		'192.0.102.0/24',
		'192.0.103.0/24',
		'192.0.104.0/24',
		'192.0.105.0/24',
		'192.0.106.0/24',
		'192.0.107.0/24',
		'192.0.108.0/24',
		'192.0.109.0/24',
		'192.0.110.0/24',
		'192.0.111.0/24',
		'192.0.112.0/24',
		'192.0.113.0/24',
		'192.0.114.0/24',
		'192.0.115.0/24',
		'192.0.116.0/24',
		'192.0.117.0/24',
		'192.0.118.0/24',
		'192.0.119.0/24',
		'192.0.120.0/24',
		'192.0.121.0/24',
		'192.0.122.0/24',
		'192.0.123.0/24',
		'192.0.124.0/24',
		'192.0.125.0/24',
		'192.0.126.0/24',
		'192.0.127.0/24',
	],
];
$ip          = new ReliableBotIPList(
	[
		'output_path' => __DIR__.DIRECTORY_SEPARATOR.'bot-ips.csv',
		'add_comment' => true,
		'add_ip_list' => $add_ip_list,
	]
);

$ip->read(true);