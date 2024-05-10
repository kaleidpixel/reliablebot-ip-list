<?php
/**
 * PHP 7.4 or later
 *
 * @package    KALEIDPIXEL
 * @author     KAZUKI Otsuhata
 * @copyright  2023 (C) Kaleid Pixel
 * @license    MIT License
 * @version    1.0.0
 **/

namespace kaleidpixel;

use DateTime;
use Exception;
use finfo;

class ReliableBotIPList {
	const IP_LIST_ENDPOINTS = [
		'google'                           => 'https://www.gstatic.com/ipranges/goog.json',
		'googlebot'                        => 'https://developers.google.com/static/search/apis/ipranges/googlebot.json',
		'google-special-crawlers'          => 'https://developers.google.com/static/search/apis/ipranges/special-crawlers.json',
		'google-user-triggered-fetchers'   => 'https://developers.google.com/static/search/apis/ipranges/user-triggered-fetchers.json',
		'google-user-triggered-fetchers-2' => 'https://developers.google.com/static/search/apis/ipranges/user-triggered-fetchers-google.json',
		'bingbot'                          => 'https://www.bing.com/toolbox/bingbot.json',
	];

	/**
	 * @var string
	 * @since 1.0.0
	 */
	protected string $now = '';

	/**
	 * @var int
	 * @since 1.0.0
	 */
	protected int $ipv = 46;

	/**
	 * @var array
	 * @since 1.0.0
	 */
	protected array $add_ip_list = [];

	/**
	 * @var bool
	 * @since 1.0.0
	 */
	protected bool $add_comment = false;

	/**
	 * @var string
	 * @since 1.0.0
	 */
	protected string $output_path = '';

	public function __construct(array $setting = [])
	{
		$this->now = date('Y-m-d');

		if (!empty($setting['ipv']))
		{
			$this->ipv = (int) $setting['ipv'];
		}

		if (!empty($setting['add_comment']) && ($setting['add_comment'] === true || $setting['add_comment'] === 1))
		{
			$this->add_comment = true;
		}

		if (!empty($setting['add_ip_list']))
		{
			$this->add_ip_list = (array) $setting['add_ip_list'];
		}

		if (!empty($setting['output_path']))
		{
			$this->output_path = $setting['output_path'];
		}
		else
		{
			$this->output_path = dirname(__DIR__).DIRECTORY_SEPARATOR.'googlebot_ip_list.csv';
		}
	}

	/**
	 * Wrapper method for the constant IP_LIST_ENDPOINTS.
	 *
	 * @return string[]
	 * @since 1.0.0
	 */
	public static function ipListEndPoints(): array
	{
		return self::IP_LIST_ENDPOINTS;
	}

	/**
	 * Check if the type of interface between the web server and PHP is CLI.
	 *
	 * @return bool
	 * @since 1.0.0
	 */
	public static function is_cli(): bool
	{
		return (
			defined('STDIN') ||
			PHP_SAPI === 'cli' ||
			(stristr(PHP_SAPI, 'cgi') && getenv('TERM')) ||
			array_key_exists('SHELL', $_ENV) ||
			(empty($_SERVER['REMOTE_ADDR']) && !isset($_SERVER['HTTP_USER_AGENT']) && count($_SERVER['argv']) > 0) ||
			!array_key_exists('REQUEST_METHOD', $_SERVER)
		);
	}

	/**
	 * @param array $urls
	 * @param array $headers
	 * @param array $method
	 * @param array $data
	 *
	 * @return array
	 */
	public function curl_get_contents(array $urls, array $headers = [], array $method = [], array $data = []): array
	{
		$multiHandle = curl_multi_init();
		$curlHandles = [];
		$results     = [];
		$running     = null;

		foreach ($urls as $i => $url)
		{
			$curl = curl_init();

			curl_setopt($curl, CURLOPT_URL, $url);
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($curl, CURLOPT_AUTOREFERER, true);
			curl_setopt($curl, CURLOPT_MAXREDIRS, 10);
			curl_setopt($curl, CURLOPT_HEADER, false);

			if (!empty($method[$i]) && $method[$i] === 'POST')
			{
				curl_setopt($curl, CURLOPT_POST, true);
				curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data[$i]));
			}

			if (!empty($headers[$i]))
			{
				curl_setopt($curl, CURLOPT_HTTPHEADER, $headers[$i]);
			}

			curl_multi_add_handle($multiHandle, $curl);
			$curlHandles[$i] = $curl;
		}

		do
		{
			curl_multi_exec($multiHandle, $running);
		} while ($running);

		foreach ($curlHandles as $i => $curl)
		{
			$results[$i]['body']      = curl_multi_getcontent($curl);
			$results[$i]['url']       = curl_getinfo($curl, CURLINFO_EFFECTIVE_URL);
			$results[$i]['http_code'] = curl_getinfo($curl, CURLINFO_HTTP_CODE);

			curl_multi_remove_handle($multiHandle, $curl);
			curl_close($curl);
		}

		curl_multi_close($multiHandle);

		return $results;
	}

	/**
	 * Output the header in the web browser to download the file and initiate the file download.
	 *
	 * @param string      $file_path
	 * @param string|null $mime_type
	 *
	 * @return void
	 * @since  1.0.0
	 * @link   https://qiita.com/fallout/items/3682e529d189693109eb
	 */
	public static function download(string $file_path = '', string $mime_type = null): void
	{
		if (!is_readable($file_path))
		{
			die($file_path);
		}

		$mime_type = (isset($mime_type)) ? $mime_type : (new finfo(FILEINFO_MIME_TYPE))->file($file_path);

		if (!preg_match('/\A\S+?\/\S+/', $mime_type))
		{
			$mime_type = 'application/octet-stream';
		}

		header('Content-Type: '.$mime_type);
		header('X-Content-Type-Options: nosniff');
		header('Content-Length: '.filesize($file_path));
		header('Content-Disposition: attachment; filename="'.basename($file_path).'"');
		header('Connection: close');

		while (ob_get_level())
		{
			ob_end_clean();
		}

		readfile($file_path);
		exit;
	}

	/**
	 * Is Create.
	 *
	 * @param bool $force
	 *
	 * @return bool
	 * @since 1.0.0
	 */
	protected function isCreate(bool $force = false): bool
	{
		$create = false;

		if (file_exists($this->output_path))
		{
			$formattedTime    = null;
			$modificationTime = filemtime($this->output_path);

			if ($modificationTime !== false)
			{
				$formattedTime = new DateTime(date('Y-m-d', $modificationTime));
			}

			if (!is_null($formattedTime))
			{
				try
				{
					$now  = new DateTime($this->now);
					$diff = $now->diff($formattedTime);

					if ($diff->days > 0)
					{
						$create = true;
					}
				}
				catch (Exception $e)
				{
				}
			}

			unset($formattedTime, $modificationTime, $now, $diff);
		}

		if (!file_exists($this->output_path) || $force === true)
		{
			$create = true;
		}

		return $create;
	}

	/**
	 * Create file.
	 *
	 * @param bool $force
	 *
	 * @return array
	 * @since 1.0.0
	 */
	protected function create(bool $force = false): array
	{
		$list = [];

		if ($this->isCreate($force))
		{
			$iplist = $this->addGooglebotIpList();

			if (in_array($this->ipv, [4, 46], true))
			{
				foreach ($iplist['ipv4'] as $key => $ips)
				{
					foreach ($ips as $ip)
					{
						$list[] = $this->add_comment === true ? "$ip,$key" : $ip;
					}
				}
			}

			if (in_array($this->ipv, [6, 46], true))
			{
				foreach ($iplist['ipv6'] as $key => $ips)
				{
					foreach ($ips as $ip)
					{
						$list[] = $this->add_comment === true ? "$ip,$key" : $ip;
					}
				}
			}

			if (!empty($this->add_ip_list))
			{
				foreach ($this->add_ip_list as $key => $ips)
				{
					foreach ($ips as $ip)
					{
						$list[] = $this->add_comment === true ? "$ip,$key" : $ip;
					}
				}
			}

			if (!empty($list))
			{
				file_put_contents($this->output_path, implode(PHP_EOL, $list));
			}
		}

		return $this->curl_get_contents(["file:///$this->output_path"]);
	}

	/**
	 * Output.
	 *
	 * @param bool $force
	 * @param bool $echo
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function read(bool $force = false, bool $echo = false)
	{
		$content = $this->create($force);

		if (empty($content[0]['body']) && $force === false)
		{
			print_r('File is not contents.');
		}
		elseif ($this->is_cli())
		{
			print_r('Output path: '.str_replace('file://', '', $content[0]['url']));
		}
		elseif ($echo)
		{
			print_r("<pre>${$content[0]['body']}</pre>");
		}
		else
		{
			$this->download($content[0]['url']);
		}
	}

	/**
	 * Add Googlebot IP List.
	 *
	 * @return array
	 * @since 1.0.0
	 */
	protected function addGooglebotIpList(): array
	{
		$contents = $this->curl_get_contents($this->ipListEndPoints());
		$result   = [
			'ipv4' => [],
			'ipv6' => [],
		];

		foreach ($contents as $i => $content)
		{
			if ($content['http_code'] === 200)
			{
				$lines = json_decode($content['body']);
			}
			else
			{
				unset($contents[$i]);
				continue;
			}

			foreach ($lines->prefixes as $ii => $prefixe)
			{
				if (isset($prefixe->ipv4Prefix))
				{
					$result['ipv4'][$i][] = $prefixe->ipv4Prefix;
				}

				if (isset($prefixe->ipv6Prefix))
				{
					$result['ipv6'][$i][] = $prefixe->ipv6Prefix;
				}

				unset($lines->prefixes[$ii], $parts);
			}

			unset($contents[$i]);
		}

		return $result;
	}
}
