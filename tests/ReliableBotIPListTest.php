<?php
/**
 * PHP 7.4 or later
 *
 * @package    KALEIDPIXEL
 * @author     KAZUKI Otsuhata
 * @copyright  2024 (C) Kaleid Pixel
 * @license    MIT License
 * @version    1.0.0
 **/

namespace kaleidpixel\Tests;

use kaleidpixel\ReliableBotIPList;
use PHPUnit\Framework\TestCase;

class ReliableBotIPListTest extends TestCase
{
	private string $testOutputPath;

	protected function setUp(): void
	{
		parent::setUp();
		$this->testOutputPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'test_ip_list_' . uniqid() . '.csv';
	}

	protected function tearDown(): void
	{
		if (file_exists($this->testOutputPath)) {
			unlink($this->testOutputPath);
		}
		parent::tearDown();
	}

	public function testConstructorWithDefaultSettings(): void
	{
		$instance = new ReliableBotIPList();
		$this->assertInstanceOf(ReliableBotIPList::class, $instance);
	}

	public function testConstructorWithCustomSettings(): void
	{
		$settings = [
			'ipv' => 4,
			'add_comment' => true,
			'add_ip_list' => ['custom' => ['192.168.1.1']],
			'output_path' => $this->testOutputPath,
		];

		$instance = new ReliableBotIPList($settings);
		$this->assertInstanceOf(ReliableBotIPList::class, $instance);
	}

	public function testConstructorWithIpv6Setting(): void
	{
		$settings = [
			'ipv' => 6,
			'output_path' => $this->testOutputPath,
		];

		$instance = new ReliableBotIPList($settings);
		$this->assertInstanceOf(ReliableBotIPList::class, $instance);
	}

	public function testConstructorWithIpv46Setting(): void
	{
		$settings = [
			'ipv' => 46,
			'output_path' => $this->testOutputPath,
		];

		$instance = new ReliableBotIPList($settings);
		$this->assertInstanceOf(ReliableBotIPList::class, $instance);
	}

	public function testConstructorWithAddCommentEnabled(): void
	{
		$settings = [
			'add_comment' => true,
			'output_path' => $this->testOutputPath,
		];

		$instance = new ReliableBotIPList($settings);
		$this->assertInstanceOf(ReliableBotIPList::class, $instance);
	}

	public function testConstructorWithAddCommentAsInteger(): void
	{
		$settings = [
			'add_comment' => 1,
			'output_path' => $this->testOutputPath,
		];

		$instance = new ReliableBotIPList($settings);
		$this->assertInstanceOf(ReliableBotIPList::class, $instance);
	}

	public function testIpListEndPointsReturnsArray(): void
	{
		$endpoints = ReliableBotIPList::ipListEndPoints();

		$this->assertIsArray($endpoints);
		$this->assertNotEmpty($endpoints);
	}

	public function testIpListEndPointsContainsExpectedBots(): void
	{
		$endpoints = ReliableBotIPList::ipListEndPoints();

		$this->assertArrayHasKey('google', $endpoints);
		$this->assertArrayHasKey('googlebot', $endpoints);
		$this->assertArrayHasKey('bingbot', $endpoints);
		$this->assertArrayHasKey('duckduckgo', $endpoints);
		$this->assertArrayHasKey('duckassistbot', $endpoints);
	}

	public function testIpListEndPointsHasValidUrls(): void
	{
		$endpoints = ReliableBotIPList::ipListEndPoints();

		foreach ($endpoints as $key => $url) {
			$this->assertIsString($url);
			$this->assertStringStartsWith('https://', $url, "URL for {$key} should start with https://");
		}
	}

	public function testIsCliReturnsBool(): void
	{
		$result = ReliableBotIPList::is_cli();
		$this->assertIsBool($result);
	}

	public function testIsCliDetectsCli(): void
	{
		$result = ReliableBotIPList::is_cli();
		$this->assertTrue($result, 'Should detect CLI environment when running PHPUnit');
	}

	public function testCurlGetContentsWithSingleUrl(): void
	{
		$instance = new ReliableBotIPList(['output_path' => $this->testOutputPath]);
		$urls = ['https://httpbin.org/json'];

		$results = $instance->curl_get_contents($urls);

		$this->assertIsArray($results);
		$this->assertCount(1, $results);
		$this->assertArrayHasKey(0, $results);
		$this->assertArrayHasKey('body', $results[0]);
		$this->assertArrayHasKey('url', $results[0]);
		$this->assertArrayHasKey('http_code', $results[0]);
	}

	public function testCurlGetContentsWithMultipleUrls(): void
	{
		$instance = new ReliableBotIPList(['output_path' => $this->testOutputPath]);
		$urls = [
			'https://httpbin.org/json',
			'https://httpbin.org/user-agent',
		];

		$results = $instance->curl_get_contents($urls);

		$this->assertIsArray($results);
		$this->assertCount(2, $results);
		$this->assertArrayHasKey(0, $results);
		$this->assertArrayHasKey(1, $results);
	}

	public function testCurlGetContentsReturnsHttpCode(): void
	{
		$instance = new ReliableBotIPList(['output_path' => $this->testOutputPath]);
		$urls = ['https://httpbin.org/status/200'];

		$results = $instance->curl_get_contents($urls);

		$this->assertEquals(200, $results[0]['http_code']);
	}

	public function testCurlGetContentsHandles404(): void
	{
		$instance = new ReliableBotIPList(['output_path' => $this->testOutputPath]);
		$urls = ['https://httpbin.org/status/404'];

		$results = $instance->curl_get_contents($urls);

		$this->assertEquals(404, $results[0]['http_code']);
	}

	public function testCurlGetContentsWithCustomHeaders(): void
	{
		$instance = new ReliableBotIPList(['output_path' => $this->testOutputPath]);
		$urls = ['https://httpbin.org/headers'];
		$headers = [['X-Custom-Header: TestValue']];

		$results = $instance->curl_get_contents($urls, $headers);

		$this->assertIsArray($results);
		$this->assertArrayHasKey(0, $results);
		$this->assertStringContainsString('TestValue', $results[0]['body']);
	}

	public function testCurlGetContentsWithPostMethod(): void
	{
		$instance = new ReliableBotIPList(['output_path' => $this->testOutputPath]);
		$urls = ['https://httpbin.org/post'];
		$method = ['POST'];
		$data = [['key' => 'value']];

		$results = $instance->curl_get_contents($urls, [], $method, $data);

		$this->assertIsArray($results);
		$this->assertArrayHasKey(0, $results);
		$this->assertStringContainsString('key', $results[0]['body']);
	}

	public function testDownloadWithNonExistentFile(): void
	{
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('File not readable:');

		ReliableBotIPList::download('/path/to/nonexistent/file.txt');
	}

	public function testIpv4OnlyConfiguration(): void
	{
		$settings = [
			'ipv' => 4,
			'output_path' => $this->testOutputPath,
		];

		$instance = new ReliableBotIPList($settings);
		$this->assertInstanceOf(ReliableBotIPList::class, $instance);
	}

	public function testIpv6OnlyConfiguration(): void
	{
		$settings = [
			'ipv' => 6,
			'output_path' => $this->testOutputPath,
		];

		$instance = new ReliableBotIPList($settings);
		$this->assertInstanceOf(ReliableBotIPList::class, $instance);
	}

	public function testAddCustomIpList(): void
	{
		$customIps = [
			'custom-bot' => [
				'192.168.1.1/32',
				'10.0.0.1/32',
			],
		];

		$settings = [
			'add_ip_list' => $customIps,
			'output_path' => $this->testOutputPath,
		];

		$instance = new ReliableBotIPList($settings);
		$this->assertInstanceOf(ReliableBotIPList::class, $instance);
	}

	public function testEmptySettings(): void
	{
		$instance = new ReliableBotIPList([]);
		$this->assertInstanceOf(ReliableBotIPList::class, $instance);
	}

	public function testMultipleCustomBots(): void
	{
		$customIps = [
			'bot1' => ['192.168.1.1'],
			'bot2' => ['10.0.0.1'],
			'bot3' => ['172.16.0.1'],
		];

		$settings = [
			'add_ip_list' => $customIps,
			'add_comment' => true,
			'output_path' => $this->testOutputPath,
		];

		$instance = new ReliableBotIPList($settings);
		$this->assertInstanceOf(ReliableBotIPList::class, $instance);
	}

	public function testDefaultOutputPath(): void
	{
		$instance = new ReliableBotIPList();
		$this->assertInstanceOf(ReliableBotIPList::class, $instance);
	}

	public function testCurlGetContentsWithEmptyUrlArray(): void
	{
		$instance = new ReliableBotIPList(['output_path' => $this->testOutputPath]);
		$urls = [];

		$results = $instance->curl_get_contents($urls);

		$this->assertIsArray($results);
		$this->assertEmpty($results);
	}

	public function testIpListEndPointsConstantAccess(): void
	{
		$this->assertIsArray(ReliableBotIPList::IP_LIST_ENDPOINTS);
		$this->assertNotEmpty(ReliableBotIPList::IP_LIST_ENDPOINTS);
	}

	public function testGooglebotEndpointExists(): void
	{
		$endpoints = ReliableBotIPList::ipListEndPoints();
		$this->assertArrayHasKey('googlebot', $endpoints);
		$this->assertStringContainsString('googlebot', $endpoints['googlebot']);
	}

	public function testBingbotEndpointExists(): void
	{
		$endpoints = ReliableBotIPList::ipListEndPoints();
		$this->assertArrayHasKey('bingbot', $endpoints);
		$this->assertStringContainsString('bingbot', $endpoints['bingbot']);
	}

	public function testDuckDuckGoEndpointsExist(): void
	{
		$endpoints = ReliableBotIPList::ipListEndPoints();
		$this->assertArrayHasKey('duckduckgo', $endpoints);
		$this->assertArrayHasKey('duckassistbot', $endpoints);
		$this->assertStringContainsString('duckduckgo', $endpoints['duckduckgo']);
		$this->assertStringContainsString('duckduckgo', $endpoints['duckassistbot']);
	}

	public function testGoogleSpecialCrawlersEndpointExists(): void
	{
		$endpoints = ReliableBotIPList::ipListEndPoints();
		$this->assertArrayHasKey('google-special-crawlers', $endpoints);
	}

	public function testGoogleUserTriggeredFetchersEndpointsExist(): void
	{
		$endpoints = ReliableBotIPList::ipListEndPoints();
		$this->assertArrayHasKey('google-user-triggered-fetchers', $endpoints);
		$this->assertArrayHasKey('google-user-triggered-fetchers-2', $endpoints);
	}

	public function testAllEndpointsAreHttps(): void
	{
		$endpoints = ReliableBotIPList::ipListEndPoints();

		foreach ($endpoints as $key => $url) {
			$this->assertStringStartsWith('https://', $url, "Endpoint {$key} should use HTTPS");
		}
	}
}
