<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * End-to-end tests for the HTTP status codes that dir2cast sends on errors.
 *
 * http_response_code() is a no-op under the CLI SAPI, so the regular unit
 * tests cannot observe it. These tests run dir2cast through PHP's built-in
 * web server and inspect the actual status line.
 */
final class HttpStatusTest extends TestCase
{
    /** @var string */
    private static $root;

    /** @var resource|null */
    private static $process;

    /** @var int */
    private static $port;

    public static function setUpBeforeClass(): void
    {
        self::$root = sys_get_temp_dir() . '/dir2cast-http-' . getmypid() . '-' . mt_rand();
        mkdir(self::$root, 0777, true);

        $base = dirname(__DIR__);
        foreach (array('bad404', 'bad500') as $dir) {
            $path = self::$root . '/' . $dir;
            mkdir($path);
            copy($base . '/dir2cast.php', $path . '/dir2cast.php');
            copy($base . '/dir2cast.ini', $path . '/dir2cast.ini');
            self::copyDir($base . '/getID3', $path . '/getID3');
        }

        // A real, non-empty and old-enough MP3 so that the feed has an episode.
        $episode = self::$root . '/bad500/episode.mp3';
        copy(__DIR__ . '/fixtures/id3v1_title.mp3', $episode);
        touch($episode, time() - 3600);

        // A TMP_DIR that can never be created forces an error during generation.
        file_put_contents(
            self::$root . '/bad500/dir2cast.ini',
            "\nTMP_DIR = \"/dev/null/dir2cast-cannot-exist\"\n",
            FILE_APPEND
        );

        self::$port = self::findFreePort();
        $command = escapeshellarg(PHP_BINARY)
            . ' -S 127.0.0.1:' . self::$port
            . ' -t ' . escapeshellarg(self::$root);
        self::$process = proc_open(
            $command,
            array(
                1 => array('file', self::$root . '/server.log', 'a'),
                2 => array('file', self::$root . '/server.log', 'a'),
            ),
            $pipes
        );

        if (!is_resource(self::$process)) {
            self::fail('Could not start the PHP built-in web server.');
        }

        $deadline = microtime(true) + 10;
        do {
            $socket = @fsockopen('127.0.0.1', self::$port, $errno, $errstr, 0.2);
            if ($socket) {
                fclose($socket);
                return;
            }
            usleep(50000);
        } while (microtime(true) < $deadline);

        self::fail('The PHP built-in web server did not start in time.');
    }

    public static function tearDownAfterClass(): void
    {
        if (is_resource(self::$process)) {
            proc_terminate(self::$process);
            proc_close(self::$process);
        }
        if (self::$root) {
            self::removeDir(self::$root);
        }
    }

    public function test_bad_dir_returns_404(): void
    {
        $response = $this->request('/bad404/dir2cast.php?dir=does-not-exist');

        $this->assertSame(404, $response['status']);
        $this->assertStringContainsString('Not Found: does-not-exist', $response['body']);
    }

    public function test_generation_error_returns_500(): void
    {
        $response = $this->request('/bad500/dir2cast.php');

        $this->assertSame(500, $response['status']);
    }

    /**
     * @return array{status:int, body:string}
     */
    private function request(string $path): array
    {
        $socket = @fsockopen('127.0.0.1', self::$port, $errno, $errstr, 2);
        if (!$socket) {
            $this->fail("Could not connect to the test web server: $errstr");
        }

        stream_set_timeout($socket, 10);
        fwrite($socket, "GET $path HTTP/1.0\r\nHost: 127.0.0.1\r\nConnection: close\r\n\r\n");
        $raw = stream_get_contents($socket);
        fclose($socket);

        $parts = preg_split("/\r\n\r\n/", $raw, 2);
        $lines = explode("\r\n", $parts[0]);
        if (!preg_match('#^HTTP/\d\.\d (\d{3})#', $lines[0], $matches)) {
            $this->fail('Unexpected HTTP response: ' . $raw);
        }

        return array(
            'status' => (int) $matches[1],
            'body' => isset($parts[1]) ? $parts[1] : '',
        );
    }

    private static function findFreePort(): int
    {
        $socket = stream_socket_server('tcp://127.0.0.1:0', $errno, $errstr);
        if (!$socket) {
            self::fail("Could not find a free port: $errstr");
        }
        $name = stream_socket_get_name($socket, false);
        fclose($socket);

        $parts = explode(':', $name);
        return (int) end($parts);
    }

    private static function copyDir(string $source, string $destination): void
    {
        mkdir($destination, 0777, true);
        foreach (scandir($source) as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $from = $source . '/' . $entry;
            $to = $destination . '/' . $entry;
            if (is_dir($from)) {
                self::copyDir($from, $to);
            } else {
                copy($from, $to);
            }
        }
    }

    private static function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        foreach (scandir($dir) as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $path = $dir . '/' . $entry;
            if (is_dir($path)) {
                self::removeDir($path);
            } else {
                unlink($path);
            }
        }
        rmdir($dir);
    }
}
