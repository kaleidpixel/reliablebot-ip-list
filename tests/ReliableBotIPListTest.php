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

		$reflection = new \ReflectionClass($instance);

		$ipvProperty = $reflection->getProperty('ipv');
		$ipvProperty->setAccessible(true);
		$this->assertEquals(46, $ipvProperty->getValue($instance), 'Default ipv should be 46');

		$addCommentProperty = $reflection->getProperty('add_comment');
		$addCommentProperty->setAccessible(true);
		$this->assertFalse($addCommentProperty->getValue($instance), 'Default add_comment should be false');

		$addIpListProperty = $reflection->getProperty('add_ip_list');
		$addIpListProperty->setAccessible(true);
		$this->assertEquals([], $addIpListProperty->getValue($instance), 'Default add_ip_list should be empty');
	}

	public function testConstructorSetsIpvProperty(): void
	{
		$instance = new ReliableBotIPList(['ipv' => 4]);

		$reflection = new \ReflectionClass($instance);
		$ipvProperty = $reflection->getProperty('ipv');
		$ipvProperty->setAccessible(true);

		$this->assertEquals(4, $ipvProperty->getValue($instance));
	}

	public function testConstructorSetsIpv6Property(): void
	{
		$instance = new ReliableBotIPList(['ipv' => 6]);

		$reflection = new \ReflectionClass($instance);
		$ipvProperty = $reflection->getProperty('ipv');
		$ipvProperty->setAccessible(true);

		$this->assertEquals(6, $ipvProperty->getValue($instance));
	}

	public function testConstructorSetsAddCommentPropertyWithBoolean(): void
	{
		$instance = new ReliableBotIPList(['add_comment' => true]);

		$reflection = new \ReflectionClass($instance);
		$addCommentProperty = $reflection->getProperty('add_comment');
		$addCommentProperty->setAccessible(true);

		$this->assertTrue($addCommentProperty->getValue($instance));
	}

	public function testConstructorSetsAddCommentPropertyWithInteger(): void
	{
		$instance = new ReliableBotIPList(['add_comment' => 1]);

		$reflection = new \ReflectionClass($instance);
		$addCommentProperty = $reflection->getProperty('add_comment');
		$addCommentProperty->setAccessible(true);

		$this->assertTrue($addCommentProperty->getValue($instance));
	}

	public function testConstructorSetsAddIpListProperty(): void
	{
		$customIps = ['custom' => ['192.168.1.1']];
		$instance = new ReliableBotIPList(['add_ip_list' => $customIps]);

		$reflection = new \ReflectionClass($instance);
		$addIpListProperty = $reflection->getProperty('add_ip_list');
		$addIpListProperty->setAccessible(true);

		$this->assertEquals($customIps, $addIpListProperty->getValue($instance));
	}

	public function testConstructorSetsOutputPathProperty(): void
	{
		$instance = new ReliableBotIPList(['output_path' => $this->testOutputPath]);

		$reflection = new \ReflectionClass($instance);
		$outputPathProperty = $reflection->getProperty('output_path');
		$outputPathProperty->setAccessible(true);

		$this->assertEquals($this->testOutputPath, $outputPathProperty->getValue($instance));
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

	/**
	 * NOTE: The download() method calls exit() at the end (L240), which makes
	 * full integration testing difficult in PHPUnit. The method performs the following:
	 * 1. Validates file is readable (tested above)
	 * 2. Detects MIME type using finfo (not directly testable due to exit)
	 * 3. Sets HTTP headers (not directly testable due to exit)
	 * 4. Clears output buffers (not directly testable due to exit)
	 * 5. Reads and outputs file content (not directly testable due to exit)
	 *
	 * To properly test download(), the method would need to be refactored to:
	 * - Separate header preparation from sending
	 * - Make exit() optional or inject a response object
	 * - Or use process isolation for integration tests
	 */

	public function testCurlGetContentsWithEmptyUrlArray(): void
	{
		$instance = new ReliableBotIPList(['output_path' => $this->testOutputPath]);
		$urls = [];

		$results = $instance->curl_get_contents($urls);

		$this->assertIsArray($results);
		$this->assertEmpty($results);
	}

	public function testIsValueExistsRecursiveFindsExistingValue(): void
	{
		$haystack = [
			['192.168.1.1/32', '192.168.1.2/32'],
			['10.0.0.1/32', '10.0.0.2/32'],
		];

		$result = ReliableBotIPList::is_value_exists_recursive($haystack, '192.168.1.1/32');
		$this->assertTrue($result);
	}

	public function testIsValueExistsRecursiveDoesNotFindNonExistingValue(): void
	{
		$haystack = [
			['192.168.1.1/32', '192.168.1.2/32'],
			['10.0.0.1/32', '10.0.0.2/32'],
		];

		$result = ReliableBotIPList::is_value_exists_recursive($haystack, '172.16.0.1/32');
		$this->assertFalse($result);
	}

	public function testIsValueExistsRecursiveWithEmptyArray(): void
	{
		$haystack = [];

		$result = ReliableBotIPList::is_value_exists_recursive($haystack, '192.168.1.1/32');
		$this->assertFalse($result);
	}

	public function testIsValueExistsRecursiveUsesStrictComparison(): void
	{
		$haystack = [
			['192.168.1.1', '192.168.1.2'],
			['10.0.0.1', '10.0.0.2'],
		];

		$result = ReliableBotIPList::is_value_exists_recursive($haystack, '192.168.1.1/32');
		$this->assertFalse($result, 'Should use strict comparison (192.168.1.1 !== 192.168.1.1/32)');
	}

	public function testReadCreatesFileWithForceParameter(): void
	{
		$settings = [
			'output_path' => $this->testOutputPath,
			'ipv' => 4,
		];

		$instance = new ReliableBotIPList($settings);

		ob_start();
		$instance->read(true, false);
		ob_end_clean();

		$this->assertFileExists($this->testOutputPath);
		$content = file_get_contents($this->testOutputPath);
		$this->assertNotEmpty($content);
	}

	public function testReadWithEchoParameterInCliEnvironment(): void
	{
		$settings = [
			'output_path' => $this->testOutputPath,
			'ipv' => 4,
		];

		$instance = new ReliableBotIPList($settings);

		ob_start();
		$instance->read(true, true);
		$output = ob_get_clean();

		$this->assertStringContainsString('Output path:', $output, 'In CLI environment, should output file path instead of HTML');
	}

	/**
	 * NOTE: The read() method has a code path (src/ReliableBotIPList.php:381-383)
	 * that calls download() when not in CLI mode and echo=false. This path cannot
	 * be tested in PHPUnit because:
	 * 1. PHPUnit always runs in CLI mode (is_cli() returns true)
	 * 2. download() calls exit(), terminating the test process
	 *
	 * Code coverage for read() is approximately 75% due to this limitation.
	 * To test this path, consider:
	 * - Browser-based integration tests
	 * - Refactoring to inject an environment detector
	 * - Mocking is_cli() behavior (requires design changes)
	 */

	public function testReadCreatesFileWithComments(): void
	{
		$settings = [
			'output_path' => $this->testOutputPath,
			'ipv' => 4,
			'add_comment' => true,
		];

		$instance = new ReliableBotIPList($settings);

		ob_start();
		$instance->read(true, false);
		ob_end_clean();

		$this->assertFileExists($this->testOutputPath);
		$content = file_get_contents($this->testOutputPath);
		$this->assertStringContainsString(',', $content);
	}

	public function testReadIncludesCustomIpList(): void
	{
		$customIps = [
			'custom-test-bot' => ['192.168.99.1/32', '192.168.99.2/32'],
		];

		$settings = [
			'output_path' => $this->testOutputPath,
			'ipv' => 4,
			'add_ip_list' => $customIps,
			'add_comment' => true,
		];

		$instance = new ReliableBotIPList($settings);

		ob_start();
		$instance->read(true, false);
		ob_end_clean();

		$this->assertFileExists($this->testOutputPath);
		$content = file_get_contents($this->testOutputPath);
		$this->assertStringContainsString('192.168.99.1/32', $content);
		$this->assertStringContainsString('custom-test-bot', $content);
	}

	public function testCurlGetContentsFollowsRedirects(): void
	{
		$instance = new ReliableBotIPList(['output_path' => $this->testOutputPath]);
		$urls = ['https://httpbin.org/redirect-to?url=https://httpbin.org/json'];

		$results = $instance->curl_get_contents($urls);

		$this->assertIsArray($results);
		$this->assertEquals(200, $results[0]['http_code']);
	}

	public function testReadDoesNotRecreateFileOnSameDay(): void
	{
		$settings = [
			'output_path' => $this->testOutputPath,
			'ipv' => 4,
		];

		$instance = new ReliableBotIPList($settings);

		ob_start();
		$instance->read(true, false);
		ob_end_clean();

		$firstModTime = filemtime($this->testOutputPath);

		sleep(1);

		ob_start();
		$instance->read(false, false);
		ob_end_clean();

		$secondModTime = filemtime($this->testOutputPath);

		$this->assertEquals($firstModTime, $secondModTime);
	}

	// ====================================================================
	// Protected Methods Tests (using Reflection)
	// ====================================================================

	public function testIsCreateReturnsTrueWhenFileDoesNotExist(): void
	{
		$instance = new ReliableBotIPList(['output_path' => $this->testOutputPath]);

		$reflection = new \ReflectionClass($instance);
		$method = $reflection->getMethod('is_create');
		$method->setAccessible(true);

		$result = $method->invoke($instance, false);

		$this->assertTrue($result, 'Should return true when file does not exist');
	}

	public function testIsCreateReturnsTrueWithForceParameter(): void
	{
		file_put_contents($this->testOutputPath, 'test content');

		$instance = new ReliableBotIPList(['output_path' => $this->testOutputPath]);

		$reflection = new \ReflectionClass($instance);
		$method = $reflection->getMethod('is_create');
		$method->setAccessible(true);

		$result = $method->invoke($instance, true);

		$this->assertTrue($result, 'Should return true when force=true even if file exists');
	}

	public function testIsCreateReturnsFalseWhenFileIsFromToday(): void
	{
		file_put_contents($this->testOutputPath, 'test content');
		touch($this->testOutputPath);

		$instance = new ReliableBotIPList(['output_path' => $this->testOutputPath]);

		$reflection = new \ReflectionClass($instance);
		$method = $reflection->getMethod('is_create');
		$method->setAccessible(true);

		$result = $method->invoke($instance, false);

		$this->assertFalse($result, 'Should return false when file is from today');
	}

	public function testIsCreateReturnsTrueWhenFileIsOld(): void
	{
		file_put_contents($this->testOutputPath, 'test content');
		touch($this->testOutputPath, strtotime('-2 days'));

		$instance = new ReliableBotIPList(['output_path' => $this->testOutputPath]);

		$reflection = new \ReflectionClass($instance);
		$method = $reflection->getMethod('is_create');
		$method->setAccessible(true);

		$result = $method->invoke($instance, false);

		$this->assertTrue($result, 'Should return true when file is older than 1 day');
	}

	/**
	 * NOTE: The is_create() method has a DateMalformedStringException catch block
	 * (src/ReliableBotIPList.php:278-282) that is difficult to test because:
	 * 1. $this->now is always set to date('Y-m-d') in __construct(), which is always valid
	 * 2. Triggering this exception requires injecting an invalid date into $this->now
	 * 3. Current design doesn't allow external injection of $this->now
	 *
	 * The catch block logs errors and continues with $create = false, preventing crashes.
	 * To test this catch block, consider:
	 * - Injecting a date validator dependency
	 * - Using Reflection to set $this->now to an invalid value (artificial test)
	 * - Accepting this as defensive programming that's difficult to test
	 */

	public function testAddBotIpListReturnsCorrectStructure(): void
	{
		$instance = new ReliableBotIPList(['output_path' => $this->testOutputPath]);

		$reflection = new \ReflectionClass($instance);
		$method = $reflection->getMethod('add_bot_ip_list');
		$method->setAccessible(true);

		$result = $method->invoke($instance);

		$this->assertIsArray($result);
		$this->assertArrayHasKey('ipv4', $result);
		$this->assertArrayHasKey('ipv6', $result);
		$this->assertIsArray($result['ipv4']);
		$this->assertIsArray($result['ipv6']);
	}

	public function testAddBotIpListExtractsIpv4Prefixes(): void
	{
		$instance = new ReliableBotIPList(['output_path' => $this->testOutputPath]);

		$reflection = new \ReflectionClass($instance);
		$method = $reflection->getMethod('add_bot_ip_list');
		$method->setAccessible(true);

		$result = $method->invoke($instance);

		$hasIpv4 = false;
		foreach ($result['ipv4'] as $ips) {
			if (!empty($ips)) {
				$hasIpv4 = true;
				foreach ($ips as $ip) {
					$this->assertMatchesRegularExpression('/^\d+\.\d+\.\d+\.\d+\/\d+$/', $ip, 'IPv4 should match CIDR format');
				}
			}
		}

		$this->assertTrue($hasIpv4, 'Should extract at least some IPv4 addresses');
	}

	public function testAddBotIpListExtractsIpv6Prefixes(): void
	{
		$instance = new ReliableBotIPList(['output_path' => $this->testOutputPath]);

		$reflection = new \ReflectionClass($instance);
		$method = $reflection->getMethod('add_bot_ip_list');
		$method->setAccessible(true);

		$result = $method->invoke($instance);

		$hasIpv6 = false;
		foreach ($result['ipv6'] as $ips) {
			if (!empty($ips)) {
				$hasIpv6 = true;
				foreach ($ips as $ip) {
					$this->assertMatchesRegularExpression('/^[0-9a-fA-F:]+\/\d+$/', $ip, 'IPv6 should match CIDR format');
				}
			}
		}

		$this->assertTrue($hasIpv6, 'Should extract at least some IPv6 addresses');
	}

	public function testCreateFiltersIpv4Only(): void
	{
		$instance = new ReliableBotIPList([
			'output_path' => $this->testOutputPath,
			'ipv' => 4,
		]);

		$reflection = new \ReflectionClass($instance);
		$method = $reflection->getMethod('create');
		$method->setAccessible(true);

		$result = $method->invoke($instance, true);

		$this->assertFileExists($this->testOutputPath);
		$content = file_get_contents($this->testOutputPath);

		// Check that file contains IPv4 addresses
		$this->assertMatchesRegularExpression('/\d+\.\d+\.\d+\.\d+/', $content);

		// Properly check that each line is IPv4 format (not just checking for colons)
		$lines = explode("\n", $content);
		$ipv4Count = 0;
		foreach ($lines as $line) {
			if (!empty(trim($line))) {
				// Extract IP part (in case of comments: "IP,key" format)
				$parts = explode(',', trim($line));
				$ipPart = $parts[0];

				// Verify it's IPv4 CIDR format
				$this->assertMatchesRegularExpression(
					'/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\/\d{1,2}$/',
					$ipPart,
					"Each line should start with IPv4 CIDR format, got: $line"
				);
				$ipv4Count++;
			}
		}

		$this->assertGreaterThan(0, $ipv4Count, 'Should have at least one IPv4 address');
	}

	public function testCreateFiltersIpv6Only(): void
	{
		$instance = new ReliableBotIPList([
			'output_path' => $this->testOutputPath,
			'ipv' => 6,
		]);

		$reflection = new \ReflectionClass($instance);
		$method = $reflection->getMethod('create');
		$method->setAccessible(true);

		$result = $method->invoke($instance, true);

		$this->assertFileExists($this->testOutputPath);
		$content = file_get_contents($this->testOutputPath);

		// Check that file contains IPv6 addresses (with colons)
		$this->assertMatchesRegularExpression('/[0-9a-fA-F:]+\/\d+/', $content);
	}

	public function testCreateIncludesCustomIpList(): void
	{
		$customIps = [
			'test-bot' => ['192.168.100.1/32', '192.168.100.2/32'],
		];

		$instance = new ReliableBotIPList([
			'output_path' => $this->testOutputPath,
			'ipv' => 4,
			'add_ip_list' => $customIps,
			'add_comment' => true,
		]);

		$reflection = new \ReflectionClass($instance);
		$method = $reflection->getMethod('create');
		$method->setAccessible(true);

		$method->invoke($instance, true);

		$this->assertFileExists($this->testOutputPath);
		$content = file_get_contents($this->testOutputPath);

		$this->assertStringContainsString('192.168.100.1/32', $content);
		$this->assertStringContainsString('test-bot', $content);
	}

	public function testCreateDoesNotWriteFileWhenIpvFilterResultsInEmptyList(): void
	{
		// Using invalid ipv value that won't match any IP version
		$instance = new ReliableBotIPList([
			'output_path' => $this->testOutputPath,
			'ipv' => 999,  // Invalid ipv - won't match 4, 6, or 46
		]);

		$reflection = new \ReflectionClass($instance);
		$method = $reflection->getMethod('create');
		$method->setAccessible(true);

		$method->invoke($instance, true);

		// When list is empty, file_put_contents should not be called
		// The file should not exist (or should not be created)
		$this->assertFileDoesNotExist($this->testOutputPath,
			'File should not be created when filtered IP list is empty');
	}

	// ====================================================================
	// Error Handling and Edge Cases Tests
	// ====================================================================

	public function testCurlGetContentsHandlesInvalidUrl(): void
	{
		$instance = new ReliableBotIPList(['output_path' => $this->testOutputPath]);
		$urls = ['https://this-domain-absolutely-does-not-exist-12345.com/test'];

		$results = $instance->curl_get_contents($urls);

		$this->assertIsArray($results);
		$this->assertArrayHasKey(0, $results);
		// Network errors typically result in http_code 0
		$this->assertTrue(
			$results[0]['http_code'] === 0 || $results[0]['http_code'] >= 400,
			'Should handle invalid URLs gracefully'
		);
	}

	public function testCurlGetContentsHandles500Error(): void
	{
		$instance = new ReliableBotIPList(['output_path' => $this->testOutputPath]);
		$urls = ['https://httpbin.org/status/500'];

		$results = $instance->curl_get_contents($urls);

		$this->assertEquals(500, $results[0]['http_code']);
	}

	public function testIsValueExistsRecursiveHandlesNonArraySubElements(): void
	{
		$haystack = [
			'not-an-array',
			['valid', 'array'],
			123,
		];

		$result = ReliableBotIPList::is_value_exists_recursive($haystack, 'valid');

		$this->assertTrue($result, 'Should handle mixed array elements gracefully');
	}

	public function testIsValueExistsRecursiveHandlesNull(): void
	{
		$haystack = [
			[null, 'value1'],
			['value2', null],
		];

		$result = ReliableBotIPList::is_value_exists_recursive($haystack, 'value1');

		$this->assertTrue($result, 'Should handle null values in arrays');
	}

	public function testConstructorHandlesInvalidIpvValue(): void
	{
		$instance = new ReliableBotIPList(['ipv' => 999]);

		$reflection = new \ReflectionClass($instance);
		$ipvProperty = $reflection->getProperty('ipv');
		$ipvProperty->setAccessible(true);

		// Should cast to int but not validate range
		$this->assertEquals(999, $ipvProperty->getValue($instance));
	}

	public function testConstructorHandlesStringIpvValue(): void
	{
		$instance = new ReliableBotIPList(['ipv' => '6']);

		$reflection = new \ReflectionClass($instance);
		$ipvProperty = $reflection->getProperty('ipv');
		$ipvProperty->setAccessible(true);

		$this->assertEquals(6, $ipvProperty->getValue($instance));
	}

	public function testConstructorHandlesAddCommentFalseValue(): void
	{
		$instance = new ReliableBotIPList(['add_comment' => false]);

		$reflection = new \ReflectionClass($instance);
		$addCommentProperty = $reflection->getProperty('add_comment');
		$addCommentProperty->setAccessible(true);

		$this->assertFalse($addCommentProperty->getValue($instance));
	}

	public function testConstructorHandlesAddCommentZeroValue(): void
	{
		$instance = new ReliableBotIPList(['add_comment' => 0]);

		$reflection = new \ReflectionClass($instance);
		$addCommentProperty = $reflection->getProperty('add_comment');
		$addCommentProperty->setAccessible(true);

		$this->assertFalse($addCommentProperty->getValue($instance), 'Zero should be treated as false');
	}

	public function testConstructorWithIpvZeroUsesDefault(): void
	{
		// ipv=0 is considered empty by !empty(), so default 46 should be used
		$instance = new ReliableBotIPList(['ipv' => 0]);

		$reflection = new \ReflectionClass($instance);
		$ipvProperty = $reflection->getProperty('ipv');
		$ipvProperty->setAccessible(true);

		$this->assertEquals(46, $ipvProperty->getValue($instance),
			'ipv=0 is empty in PHP, should use default value 46');
	}

	public function testConstructorWithAddCommentIntegerTwoIsFalse(): void
	{
		// add_comment must be exactly true or 1, so 2 should result in false
		$instance = new ReliableBotIPList(['add_comment' => 2]);

		$reflection = new \ReflectionClass($instance);
		$addCommentProperty = $reflection->getProperty('add_comment');
		$addCommentProperty->setAccessible(true);

		$this->assertFalse($addCommentProperty->getValue($instance),
			'add_comment=2 is not === true and not === 1, should be false');
	}

	public function testConstructorWithAddCommentStringOneIsFalse(): void
	{
		// Strict comparison: '1' !== 1, so string '1' should result in false
		$instance = new ReliableBotIPList(['add_comment' => '1']);

		$reflection = new \ReflectionClass($instance);
		$addCommentProperty = $reflection->getProperty('add_comment');
		$addCommentProperty->setAccessible(true);

		$this->assertFalse($addCommentProperty->getValue($instance),
			'add_comment="1" (string) !== 1 (int) with strict comparison, should be false');
	}

	public function testReadOutputsMessageWhenFileIsEmpty(): void
	{
		// Create an empty file and make it old (from 2 days ago)
		// This ensures is_create() returns true, triggering a recreation attempt
		file_put_contents($this->testOutputPath, '');
		touch($this->testOutputPath, strtotime('-2 days'));

		// Create instance with invalid ipv to ensure empty list
		$instance = new ReliableBotIPList([
			'output_path' => $this->testOutputPath,
			'ipv' => 999,  // Invalid ipv results in empty IP list
		]);

		ob_start();
		$instance->read(false, false);
		$output = ob_get_clean();

		// After recreation with ipv=999, list will be empty and file won't be written
		// Then curl_get_contents will try to read the file, which is empty
		$this->assertStringContainsString('File is not contents', $output,
			'Should output error message when file content is empty');
	}

	public function testCurlGetContentsWithMixedSuccessAndFailure(): void
	{
		$instance = new ReliableBotIPList(['output_path' => $this->testOutputPath]);
		$urls = [
			'https://httpbin.org/status/200',
			'https://httpbin.org/status/404',
			'https://httpbin.org/status/500',
		];

		$results = $instance->curl_get_contents($urls);

		$this->assertCount(3, $results);
		$this->assertEquals(200, $results[0]['http_code']);
		$this->assertEquals(404, $results[1]['http_code']);
		$this->assertEquals(500, $results[2]['http_code']);
	}

	public function testAddBotIpListHandlesInvalidJsonGracefully(): void
	{
		// Test that the method properly handles invalid JSON by skipping those endpoints
		$mockInstance = new MockReliableBotIPList(['output_path' => $this->testOutputPath]);

		// Mock responses: mix of valid, invalid JSON, and successful endpoints
		$mockResponses = [
			// Index 0: Invalid JSON (triggers L412-416: if ($lines === null))
			0 => [
				'body' => '{invalid json syntax here',
				'url' => 'https://example.com/invalid.json',
				'http_code' => 200,
			],
			// Index 1: Valid JSON with proper structure
			1 => [
				'body' => '{"prefixes":[{"ipv4Prefix":"192.0.2.0/24"}]}',
				'url' => 'https://example.com/valid.json',
				'http_code' => 200,
			],
			// Index 2: Another invalid JSON
			2 => [
				'body' => 'not json at all',
				'url' => 'https://example.com/invalid2.json',
				'http_code' => 200,
			],
		];

		$mockInstance->setMockResponses($mockResponses);

		$reflection = new \ReflectionClass($mockInstance);
		$method = $reflection->getMethod('add_bot_ip_list');
		$method->setAccessible(true);

		// Should not throw exception even with invalid JSON
		$result = $method->invoke($mockInstance);

		$this->assertIsArray($result);
		$this->assertArrayHasKey('ipv4', $result);
		$this->assertArrayHasKey('ipv6', $result);

		// Should only have data from index 1 (valid endpoint)
		// Invalid JSON endpoints (0 and 2) should be skipped
		$this->assertArrayHasKey(1, $result['ipv4'], 'Should have data from valid endpoint at index 1');
		$this->assertArrayNotHasKey(0, $result['ipv4'], 'Should skip invalid JSON at index 0');
		$this->assertArrayNotHasKey(2, $result['ipv4'], 'Should skip invalid JSON at index 2');

		// Verify the valid data was extracted correctly
		$this->assertContains('192.0.2.0/24', $result['ipv4'][1]);
	}

	public function testAddBotIpListSkipsEndpointsWithMissingPrefixesProperty(): void
	{
		// Test that endpoints returning JSON without 'prefixes' property are properly skipped
		$mockInstance = new MockReliableBotIPList(['output_path' => $this->testOutputPath]);

		// Mock responses: test different invalid JSON structures
		$mockResponses = [
			// Index 0: Missing 'prefixes' property (triggers L420-424)
			0 => [
				'body' => '{"someOtherProperty":"value"}',
				'url' => 'https://example.com/no-prefixes.json',
				'http_code' => 200,
			],
			// Index 1: 'prefixes' exists but is not an array (triggers L420-424)
			1 => [
				'body' => '{"prefixes":"not-an-array"}',
				'url' => 'https://example.com/prefixes-not-array.json',
				'http_code' => 200,
			],
			// Index 2: Valid structure
			2 => [
				'body' => '{"prefixes":[{"ipv6Prefix":"2001:db8::/32"}]}',
				'url' => 'https://example.com/valid.json',
				'http_code' => 200,
			],
			// Index 3: 'prefixes' is null
			3 => [
				'body' => '{"prefixes":null}',
				'url' => 'https://example.com/prefixes-null.json',
				'http_code' => 200,
			],
		];

		$mockInstance->setMockResponses($mockResponses);

		$reflection = new \ReflectionClass($mockInstance);
		$method = $reflection->getMethod('add_bot_ip_list');
		$method->setAccessible(true);

		$result = $method->invoke($mockInstance);

		$this->assertIsArray($result);
		$this->assertArrayHasKey('ipv4', $result);
		$this->assertArrayHasKey('ipv6', $result);

		// Only index 2 should have valid data
		$this->assertArrayHasKey(2, $result['ipv6'], 'Should have data from valid endpoint at index 2');
		$this->assertArrayNotHasKey(0, $result['ipv4'], 'Should skip endpoint with missing prefixes');
		$this->assertArrayNotHasKey(0, $result['ipv6'], 'Should skip endpoint with missing prefixes');
		$this->assertArrayNotHasKey(1, $result['ipv4'], 'Should skip endpoint where prefixes is not an array');
		$this->assertArrayNotHasKey(1, $result['ipv6'], 'Should skip endpoint where prefixes is not an array');
		$this->assertArrayNotHasKey(3, $result['ipv4'], 'Should skip endpoint where prefixes is null');
		$this->assertArrayNotHasKey(3, $result['ipv6'], 'Should skip endpoint where prefixes is null');

		// Verify the valid data was extracted correctly
		$this->assertContains('2001:db8::/32', $result['ipv6'][2]);
	}

	public function testAddBotIpListSkipsHttpErrorResponses(): void
	{
		// Test that HTTP error responses (non-200) are properly skipped
		$mockInstance = new MockReliableBotIPList(['output_path' => $this->testOutputPath]);

		// Mock responses with various HTTP error codes
		$mockResponses = [
			// Index 0: 404 Not Found (triggers L403-406)
			0 => [
				'body' => '<!DOCTYPE html><html><body>Not Found</body></html>',
				'url' => 'https://example.com/404.json',
				'http_code' => 404,
			],
			// Index 1: 500 Internal Server Error (triggers L403-406)
			1 => [
				'body' => 'Internal Server Error',
				'url' => 'https://example.com/500.json',
				'http_code' => 500,
			],
			// Index 2: 403 Forbidden (triggers L403-406)
			2 => [
				'body' => 'Forbidden',
				'url' => 'https://example.com/403.json',
				'http_code' => 403,
			],
			// Index 3: 0 Network error (triggers L403-406)
			3 => [
				'body' => '',
				'url' => 'https://example.com/network-error.json',
				'http_code' => 0,
			],
			// Index 4: 200 OK with valid data
			4 => [
				'body' => '{"prefixes":[{"ipv4Prefix":"192.0.2.0/24"}]}',
				'url' => 'https://example.com/valid.json',
				'http_code' => 200,
			],
		];

		$mockInstance->setMockResponses($mockResponses);

		$reflection = new \ReflectionClass($mockInstance);
		$method = $reflection->getMethod('add_bot_ip_list');
		$method->setAccessible(true);

		$result = $method->invoke($mockInstance);

		$this->assertIsArray($result);
		$this->assertArrayHasKey('ipv4', $result);
		$this->assertArrayHasKey('ipv6', $result);

		// Only index 4 (HTTP 200) should have data
		$this->assertArrayHasKey(4, $result['ipv4'], 'Should have data from HTTP 200 response');

		// All HTTP errors should be skipped
		$this->assertArrayNotHasKey(0, $result['ipv4'], 'Should skip HTTP 404');
		$this->assertArrayNotHasKey(1, $result['ipv4'], 'Should skip HTTP 500');
		$this->assertArrayNotHasKey(2, $result['ipv4'], 'Should skip HTTP 403');
		$this->assertArrayNotHasKey(3, $result['ipv4'], 'Should skip HTTP 0 (network error)');

		// Verify the valid data
		$this->assertContains('192.0.2.0/24', $result['ipv4'][4]);
	}

	public function testAddBotIpListReturnsValidDataDespitePartialFailures(): void
	{
		// Comprehensive test: mix of HTTP errors, invalid JSON, missing prefixes, and valid responses
		$mockInstance = new MockReliableBotIPList(['output_path' => $this->testOutputPath]);

		// Mock responses with various failure scenarios
		$mockResponses = [
			// Index 0: HTTP 404 error (triggers L403-406: http_code !== 200)
			0 => [
				'body' => 'Not Found',
				'url' => 'https://example.com/404.json',
				'http_code' => 404,
			],
			// Index 1: HTTP 500 error (triggers L403-406)
			1 => [
				'body' => 'Internal Server Error',
				'url' => 'https://example.com/500.json',
				'http_code' => 500,
			],
			// Index 2: Invalid JSON (triggers L412-416)
			2 => [
				'body' => '{broken json',
				'url' => 'https://example.com/invalid.json',
				'http_code' => 200,
			],
			// Index 3: Missing prefixes (triggers L420-424)
			3 => [
				'body' => '{"data":"value"}',
				'url' => 'https://example.com/no-prefixes.json',
				'http_code' => 200,
			],
			// Index 4: Valid IPv4 data
			4 => [
				'body' => '{"prefixes":[{"ipv4Prefix":"203.0.113.0/24"},{"ipv4Prefix":"198.51.100.0/24"}]}',
				'url' => 'https://example.com/valid-ipv4.json',
				'http_code' => 200,
			],
			// Index 5: Valid IPv6 data
			5 => [
				'body' => '{"prefixes":[{"ipv6Prefix":"2001:db8::/32"}]}',
				'url' => 'https://example.com/valid-ipv6.json',
				'http_code' => 200,
			],
			// Index 6: HTTP 0 (network error, triggers L403-406)
			6 => [
				'body' => '',
				'url' => 'https://example.com/network-error.json',
				'http_code' => 0,
			],
		];

		$mockInstance->setMockResponses($mockResponses);

		$reflection = new \ReflectionClass($mockInstance);
		$method = $reflection->getMethod('add_bot_ip_list');
		$method->setAccessible(true);

		$result = $method->invoke($mockInstance);

		// Should have valid structure
		$this->assertIsArray($result);
		$this->assertArrayHasKey('ipv4', $result);
		$this->assertArrayHasKey('ipv6', $result);

		// Only indices 4 and 5 should have data (all others should be skipped)
		$this->assertArrayHasKey(4, $result['ipv4'], 'Should have IPv4 data from index 4');
		$this->assertArrayHasKey(5, $result['ipv6'], 'Should have IPv6 data from index 5');

		// Verify failed endpoints were skipped
		$this->assertArrayNotHasKey(0, $result['ipv4'], 'Should skip 404 error');
		$this->assertArrayNotHasKey(1, $result['ipv4'], 'Should skip 500 error');
		$this->assertArrayNotHasKey(2, $result['ipv4'], 'Should skip invalid JSON');
		$this->assertArrayNotHasKey(3, $result['ipv4'], 'Should skip missing prefixes');
		$this->assertArrayNotHasKey(6, $result['ipv4'], 'Should skip network error');

		// Verify correct data extraction
		$this->assertContains('203.0.113.0/24', $result['ipv4'][4]);
		$this->assertContains('198.51.100.0/24', $result['ipv4'][4]);
		$this->assertContains('2001:db8::/32', $result['ipv6'][5]);

		// Count total IPs to ensure we only got the valid ones
		$totalIpv4 = 0;
		foreach ($result['ipv4'] as $ips) {
			$totalIpv4 += count($ips);
		}
		$totalIpv6 = 0;
		foreach ($result['ipv6'] as $ips) {
			$totalIpv6 += count($ips);
		}

		$this->assertEquals(2, $totalIpv4, 'Should have exactly 2 IPv4 addresses from index 4');
		$this->assertEquals(1, $totalIpv6, 'Should have exactly 1 IPv6 address from index 5');
	}
}

/**
 * Mock class for testing error scenarios
 * Allows injecting custom curl_get_contents responses
 */
class MockReliableBotIPList extends ReliableBotIPList
{
	private array $mockResponses = [];

	public function setMockResponses(array $responses): void
	{
		$this->mockResponses = $responses;
	}

	public function curl_get_contents(array $urls, array $headers = [], array $method = [], array $data = []): array
	{
		if (!empty($this->mockResponses)) {
			return $this->mockResponses;
		}
		return parent::curl_get_contents($urls, $headers, $method, $data);
	}
}
