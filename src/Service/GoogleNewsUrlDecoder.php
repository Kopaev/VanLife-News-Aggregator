<?php

declare(strict_types=1);

namespace App\Service;

use App\Core\Config;
use App\Core\Database;

class GoogleNewsUrlDecoder
{
    private const GOOGLE_NEWS_URL_PREFIX = 'https://news.google.com/';

    private Database $database;
    private LoggerService $logger;
    private int $requestDelay;
    private float $lastRequestAt = 0.0;

    public function __construct(Database $database, LoggerService $logger, Config $config)
    {
        $this->database = $database;
        $this->logger = $logger;
        $this->requestDelay = max(0, (int)$config->get('rate_limit.google_news_delay_ms', 1000));
    }

    public function decode(string $googleNewsUrl): ?string
    {
        if (!$this->isGoogleNewsUrl($googleNewsUrl)) {
            return $googleNewsUrl;
        }

        $cached = $this->getCachedUrl($googleNewsUrl);
        if ($cached !== null) {
            $this->logger->debug('GoogleNewsDecoder', 'Cache hit for URL', ['url' => $googleNewsUrl]);

            return $cached;
        }

        $encodedPart = $this->extractEncodedPart($googleNewsUrl);
        if (!$encodedPart) {
            $this->logger->warning('GoogleNewsDecoder', 'Cannot extract encoded part', ['url' => $googleNewsUrl]);
            $this->storeCache($googleNewsUrl, null, 'failed', null, 'encoded part missing');

            return null;
        }

        $decoded = $this->decodeBase64($encodedPart);
        if ($this->isValidUrl($decoded)) {
            $this->storeCache($googleNewsUrl, $decoded, 'success', 'base64');

            return $decoded;
        }

        $decoded = $this->decodeViaGoogleApi($encodedPart);
        if ($this->isValidUrl($decoded)) {
            $this->storeCache($googleNewsUrl, $decoded, 'success', 'api');

            return $decoded;
        }

        $decoded = $this->decodeViaRedirect($googleNewsUrl);
        if ($this->isValidUrl($decoded)) {
            $this->storeCache($googleNewsUrl, $decoded, 'success', 'redirect');

            return $decoded;
        }

        $this->logger->error('GoogleNewsDecoder', 'All decode methods failed', ['url' => $googleNewsUrl]);
        $this->storeCache($googleNewsUrl, null, 'failed', 'failed', 'decode failed');

        return null;
    }

    public function decodeBatch(array $urls): array
    {
        $results = [];

        foreach ($urls as $url) {
            $results[$url] = $this->decode($url);
            $this->applyRequestDelay();
        }

        return $results;
    }

    public function isGoogleNewsUrl(string $url): bool
    {
        return str_starts_with($url, self::GOOGLE_NEWS_URL_PREFIX);
    }

    private function extractEncodedPart(string $url): ?string
    {
        $parsed = parse_url($url);
        if (!$parsed || !isset($parsed['path'])) {
            return null;
        }

        $path = $parsed['path'];

        foreach (['/articles/', '/read/'] as $needle) {
            if (str_contains($path, $needle)) {
                $parts = explode($needle, $path);
                if (isset($parts[1])) {
                    return explode('?', $parts[1])[0];
                }
            }
        }

        return null;
    }

    private function decodeBase64(string $encoded): ?string
    {
        try {
            $padded = $encoded . str_repeat('=', (4 - strlen($encoded) % 4) % 4);
            $decoded = base64_decode(strtr($padded, '-_', '+/'), true);
            if ($decoded === false) {
                return null;
            }

            if (preg_match('/https?:\/\/[^\x00-\x1f\x7f-\xff]+/', $decoded, $matches)) {
                $url = preg_replace('/[\x00-\x1f\x7f-\xff].*$/', '', $matches[0]);
                if ($this->isValidUrl($url)) {
                    return $url;
                }
            }

            $bytes = array_values(unpack('C*', $decoded));
            $startIndex = 0;
            for ($i = 0; $i < min(10, count($bytes)); $i++) {
                if ($bytes[$i] === 0x22) {
                    $startIndex = $i + 1;
                    break;
                }
            }

            if ($startIndex === 0 || $startIndex >= count($bytes)) {
                return null;
            }

            $length = $bytes[$startIndex];
            if ($length >= 0x80 && $startIndex + 1 < count($bytes)) {
                $length = ($length & 0x7f) | ($bytes[$startIndex + 1] << 7);
                $startIndex++;
            }

            $startIndex++;
            $endIndex = min($startIndex + $length, count($bytes));
            $urlBytes = array_slice($bytes, $startIndex, $endIndex - $startIndex);
            $url = implode('', array_map('chr', $urlBytes));

            if (preg_match('/^(https?:\/\/[^\x00-\x1f\x7f-\xff]+)/', $url, $matches)) {
                if ($this->isValidUrl($matches[1])) {
                    return $matches[1];
                }
            }

            return null;
        } catch (\Throwable $e) {
            $this->logger->debug('GoogleNewsDecoder', 'Base64 decode failed', ['error' => $e->getMessage()]);

            return null;
        }
    }

    private function decodeViaGoogleApi(string $encodedPart): ?string
    {
        try {
            $this->applyRequestDelay();
            $params = $this->getDecodingParams($encodedPart);
            if (!$params) {
                return null;
            }

            $this->applyRequestDelay();

            return $this->callBatchExecute($params);
        } catch (\Throwable $e) {
            $this->logger->warning('GoogleNewsDecoder', 'Google API decode failed', ['error' => $e->getMessage()]);

            return null;
        }
    }

    private function getDecodingParams(string $gnArtId): ?array
    {
        $urls = [
            "https://news.google.com/articles/{$gnArtId}",
            "https://news.google.com/rss/articles/{$gnArtId}",
        ];

        foreach ($urls as $url) {
            $this->applyRequestDelay();
            $response = $this->httpGet($url);
            if (!$response) {
                continue;
            }

            $dom = new \DOMDocument();
            @$dom->loadHTML($response, LIBXML_NOERROR);
            $xpath = new \DOMXPath($dom);

            $divs = $xpath->query("//c-wiz/div[@data-n-a-sg]");
            if ($divs->length > 0) {
                $div = $divs->item(0);

                return [
                    'signature' => $div->getAttribute('data-n-a-sg'),
                    'timestamp' => $div->getAttribute('data-n-a-ts'),
                    'gn_art_id' => $gnArtId,
                ];
            }

            $divs = $xpath->query("//*[@data-n-a-sg]");
            if ($divs->length > 0) {
                $div = $divs->item(0);

                return [
                    'signature' => $div->getAttribute('data-n-a-sg'),
                    'timestamp' => $div->getAttribute('data-n-a-ts'),
                    'gn_art_id' => $gnArtId,
                ];
            }
        }

        return null;
    }

    private function callBatchExecute(array $params): ?string
    {
        $reqData = [
            [
                'Fbv4je',
                json_encode([
                    [
                        'garturlreq',
                        [
                            ['X', 'X', ['X', 'X'], null, null, 1, 1, 'US:en', null, 1, null, null, null, null, null, 0, 1],
                            'X',
                            'X',
                            1,
                            [1, 1, 1],
                            1,
                            1,
                            null,
                            0,
                            0,
                            null,
                            0,
                        ],
                        $params['gn_art_id'],
                        $params['timestamp'],
                        $params['signature'],
                    ],
                ]),
            ],
        ];

        $payload = 'f.req=' . urlencode(json_encode([$reqData]));
        $response = $this->httpPost(
            'https://news.google.com/_/DotsSplashUi/data/batchexecute',
            $payload,
            ['Content-Type: application/x-www-form-urlencoded;charset=UTF-8']
        );

        if (!$response) {
            return null;
        }

        if (preg_match('/"(https?:[^"\\\\]+(?:\\\\.[^"\\\\]*)*)"/', $response, $matches)) {
            $url = stripcslashes($matches[1]);
            if ($this->isValidUrl($url)) {
                return $url;
            }
        }

        return null;
    }

    private function decodeViaRedirect(string $url): ?string
    {
        $this->applyRequestDelay();

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            CURLOPT_HTTPHEADER => [
                'Accept: text/html,application/xhtml+xml',
                'Accept-Language: en-US,en;q=0.9',
            ],
        ]);

        curl_exec($ch);
        $finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200 && $finalUrl && !$this->isGoogleNewsUrl($finalUrl)) {
            return $finalUrl;
        }

        return null;
    }

    private function httpGet(string $url): ?string
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return ($httpCode === 200) ? $response : null;
    }

    private function httpPost(string $url, string $body, array $headers = []): ?string
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            CURLOPT_HTTPHEADER => $headers,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return ($httpCode === 200) ? $response : null;
    }

    private function storeCache(string $googleUrl, ?string $decodedUrl, string $status, ?string $method, ?string $error = null): void
    {
        $hash = md5($googleUrl);
        $sql = <<<SQL
            INSERT INTO decoded_urls_cache
                (google_url_hash, google_url, decoded_url, decode_method, status, attempts, last_error, last_attempt_at)
            VALUES
                (:hash, :url, :decoded, :method, :status, 1, :error, NOW())
            ON DUPLICATE KEY UPDATE
                decoded_url = VALUES(decoded_url),
                decode_method = VALUES(decode_method),
                status = VALUES(status),
                last_error = VALUES(last_error),
                attempts = attempts + 1,
                last_attempt_at = VALUES(last_attempt_at)
        SQL;

        $this->database->query($sql, [
            'hash' => $hash,
            'url' => $googleUrl,
            'decoded' => $decodedUrl,
            'method' => $method,
            'status' => $status,
            'error' => $error,
        ]);
    }

    private function getCachedUrl(string $googleUrl): ?string
    {
        $hash = md5($googleUrl);
        $row = $this->database->fetch(
            'SELECT decoded_url FROM decoded_urls_cache WHERE google_url_hash = :hash AND status = "success"',
            ['hash' => $hash]
        );

        return $row['decoded_url'] ?? null;
    }

    private function isValidUrl(?string $value): bool
    {
        return $value !== null && filter_var($value, FILTER_VALIDATE_URL) !== false;
    }

    private function applyRequestDelay(): void
    {
        if ($this->requestDelay <= 0) {
            return;
        }

        $now = microtime(true);
        $elapsed = ($now - $this->lastRequestAt) * 1000;
        if ($this->lastRequestAt > 0 && $elapsed < $this->requestDelay) {
            usleep((int)(($this->requestDelay - $elapsed) * 1000));
        }

        $this->lastRequestAt = microtime(true);
    }
}
