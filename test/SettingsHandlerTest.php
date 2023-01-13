<?php declare(strict_types = 1);

use PHPUnit\Framework\TestCase;

class SettingsHandlerTest extends TestCase
{
    static $DEFINE_LIST = array(
        // from boostrap
        'DIR2CAST_BASE',
        'MIN_CACHE_TIME',
        'FORCE_PASSWORD',
        'TMP_DIR',
        'MP3_BASE',
        'MP3_DIR',
        
        // from defaults
        'MP3_URL',
        'TITLE',
        'LINK',
        'RSS_LINK',
        'DESCRIPTION',
        'ATOM_TYPE',
        'LANGUAGE',
        'COPYRIGHT',
        'TTL',
        'ITEM_COUNT',
        'ITUNES_SUBTITLE',
        'ITUNES_SUMMARY',
        'IMAGE',
        'ITUNES_IMAGE',
        'ITUNES_OWNER_NAME',
        'ITUNES_OWNER_EMAIL',
        'WEBMASTER',
        'ITUNES_AUTHOR',
        'ITUNES_CATEGORIES',
        'ITUNES_EXPLICIT',
        'LONG_TITLES',
        'ITUNES_SUBTITLE_SUFFIX',
        'ITUNES_TYPE',
        'DESCRIPTION_SOURCE',
        'RECURSIVE_DIRECTORY_ITERATOR',
        'AUTO_SAVE_COVER_ART',
        'DONT_UNCACHE_IF_OUTPUT_FILE',
        'MIN_FILE_AGE',
    );

    public $temp_file = false;
    public $starting_dir = false;

    public function setUp(): void
    {
        $this->temp_file = false;
        $this->starting_dir = false;
    }
    
    public function test_getopt_hook()
    {
        $argv_copy = $GLOBALS['argv'];
        $argc_copy = $GLOBALS['argc'];

        $short_options = '';
        $long_options = array('help', 'media-dir::', 'bootstrap');

        $cli_options = SettingsHandler::getopt(
            array(),
            $short_options, $long_options
        );
        $this->assertEquals($cli_options, array());

        $cli_options = SettingsHandler::getopt(
            array('dir2cast.php'),
            $short_options, $long_options
        );
        $this->assertEquals($cli_options, array());

        $cli_options = SettingsHandler::getopt(
            array('dir2cast.php', '--help'),
            $short_options, $long_options
        );
        $this->assertEquals($cli_options, array('help' => false));

        $cli_options = SettingsHandler::getopt(
            array('dir2cast.php', '--media-dir=test1'),
            $short_options, $long_options
        );
        $this->assertEquals($cli_options, array('media-dir' => 'test1'));

        $cli_options = SettingsHandler::getopt(
            array('dir2cast.php', '--media-dir=test2', '--bootstrap'),
            $short_options, $long_options
        );
        $this->assertEquals($cli_options, array('media-dir' => 'test2', 'bootstrap' => false));

        $cli_options = SettingsHandler::getopt(
            array('dir2cast.php', '--bootstrap', '--media-dir=test3'),
            $short_options, $long_options
        );
        $this->assertEquals($cli_options, array('media-dir' => 'test3', 'bootstrap' => false));

        $this->assertEquals($argv_copy, $GLOBALS['argv']);
        $this->assertEquals($argc_copy, $GLOBALS['argc']);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_default_defines_set()
    {
        $this->assertFalse(Dir_Podcast::$EMPTY_PODCAST_IS_ERROR);
        $this->assertFalse(Dir_Podcast::$RECURSIVE_DIRECTORY_ITERATOR);
        $this->assertEquals(10, Dir_Podcast::$ITEM_COUNT);
        $this->assertEquals(0, Dir_Podcast::$MIN_FILE_AGE);
        $this->assertEquals(5, Cached_Dir_Podcast::$MIN_CACHE_TIME);
        $this->assertFalse(getID3_Podcast_Helper::$AUTO_SAVE_COVER_ART);
        $this->assertEmpty(RSS_File_Item::$FILES_URL);
        $this->assertEmpty(RSS_File_Item::$FILES_DIR);
        $this->assertEmpty(iTunes_Podcast_Helper::$ITUNES_SUBTITLE_SUFFIX);
        $this->assertEquals('episodic', iTunes_Podcast_Helper::$ITUNES_TYPE);
        $this->assertFalse(Media_RSS_Item::$LONG_TITLES);
        $this->assertEquals('comment', Media_RSS_Item::$DESCRIPTION_SOURCE);
        
        foreach(self::$DEFINE_LIST as $define_name)
        {
            $this->assertFalse(defined($define_name));
        }
        
        SettingsHandler::bootstrap(
            /* $SERVER */ array(),
            /* $GET */ array(),
            /* $argv */ array()
        );
        SettingsHandler::defaults(array());
        
        foreach(self::$DEFINE_LIST as $define_name)
        {
            $this->assertTrue(defined($define_name));
        }
        
        // should not be defined as $argv was empty
        $this->assertFalse(defined('CLI_ONLY'));
        $this->assertEquals(DIR2CAST_BASE(), slashdir(realpath('..'))); // from bootstrap.php
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_pre_defined_slashed()
    {
        define('DIR2CAST_BASE', '/tmp/');
        $this->assertEquals(DIR2CAST_BASE(), '/tmp/');
        define('MP3_BASE', '/tmp/');
        $this->assertEquals(DIR2CAST_BASE(), '/tmp/');
        define('MP3_PATH', '/tmp/');
        $this->assertEquals(DIR2CAST_BASE(), '/tmp/');
    }
    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_pre_defined_slashless()
    {
        define('DIR2CAST_BASE', '/tmp');
        $this->assertEquals(DIR2CAST_BASE(), '/tmp/');
        define('MP3_BASE', '/tmp');
        $this->assertEquals(DIR2CAST_BASE(), '/tmp/');
        define('MP3_PATH', '/tmp');
        $this->assertEquals(DIR2CAST_BASE(), '/tmp/');
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_behaves_if_anything_is_already_defined()
    {
        foreach(self::$DEFINE_LIST as $define_name)
        {
            if($define_name != 'DIR2CAST_BASE') // always defined by bootstrap()
                define($define_name, $define_name);
        }
        
        SettingsHandler::bootstrap(array(), array(), array());
        SettingsHandler::defaults(array());
        
        foreach(self::$DEFINE_LIST as $define_name)
        {
            if($define_name != 'DIR2CAST_BASE')
                $this->assertEquals($define_name, constant($define_name));
        }
    }
    
    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_defines_CLI_ONLY_if_argv0()
    {
        // opposite test is in test_default_defines_set() above
        $this->assertFalse(defined('CLI_ONLY'));
        SettingsHandler::bootstrap(array(), array(), array('dir2cast.php'));
        $this->assertTrue(defined('CLI_ONLY'));
        $this->assertEquals(DIR2CAST_BASE(), slashdir(getcwd())); // from fake $argv
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @testWith [null, null]
     *           ["dir2cast.php", null]
     *           ["dir2cast.php", "--media-dir="]
     */
    public function test_bootstrap_sets_sensible_global_defaults_for_entire_installation($argv0, $argv1)
    {
        SettingsHandler::bootstrap(array(), array(), array($argv0, $argv1));
        $this->assertEquals(MIN_CACHE_TIME, 5);
        $this->assertEquals(FORCE_PASSWORD, '');
        $this->assertEquals(TMP_DIR, DIR2CAST_BASE() . 'temp');
        $this->assertEquals(MP3_BASE(), DIR2CAST_BASE());
        $this->assertEquals(MP3_DIR(), DIR2CAST_BASE());
    }
    
    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_when_SERVER_HTTP_HOST_then_MP3_BASE_defaults_to_same_dir()
    {
        $SERVER = array(
            'HTTP_HOST' => 'www.example.com',
            'SCRIPT_FILENAME' => '/var/www/dir2cast.php',
        );
        SettingsHandler::bootstrap(
            $SERVER,
            /* $GET */ array(),
            /* $argv */ array()
        );
        $this->assertEquals(MP3_BASE(), '/var/www/');
        $this->assertEquals(MP3_DIR(), '/var/www/');
    }
    
    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_cli_media_404()
    {
        $this->temp_file = basename(tempnam('./', 'test_cli_media_404'));
        $this->assertFalse(strpos($this->temp_file, '/'));
        unlink($this->temp_file);
        
        $this->expectException("ExitException");
        $this->expectExceptionMessage("Not Found: {$this->temp_file}");
        $this->expectExceptionCode(-2);
        SettingsHandler::bootstrap(array(), array(), array("dir2cast.php", "--media-dir={$this->temp_file}"));
        $this->assertFalse(http_response_code());
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_GET_media_404()
    {
        $this->temp_file = basename(tempnam('../', 'test_GET_media_404'));
        $this->assertFalse(strpos($this->temp_file, '/'));
        unlink('../' . $this->temp_file);
        
        $this->expectException("ExitException");
        $this->expectExceptionMessage("Not Found: {$this->temp_file}");
        $this->expectExceptionCode(-2);
        SettingsHandler::bootstrap(array(), array("dir" => $this->temp_file), array());
        $this->assertEquals(http_response_code(), 404);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_cli_media_not_dir_404()
    {
        $this->temp_file = basename(tempnam('./', 'test_cli_media_not_dir_404'));

        $this->expectException("ExitException");
        $this->expectExceptionMessage("Not Found: {$this->temp_file}");
        $this->expectExceptionCode(-2);
        SettingsHandler::bootstrap(array(), array(), array("dir2cast.php", "--media-dir={$this->temp_file}"));
        $this->assertFalse(http_response_code());
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_GET_media_not_dir_404()
    {
        $this->temp_file = basename(tempnam('../', 'test_GET_media_not_dir_404'));

        $this->expectException("ExitException");
        $this->expectExceptionMessage("Not Found: {$this->temp_file}");
        $this->expectExceptionCode(-2);
        SettingsHandler::bootstrap(array(), array("dir" => $this->temp_file), array());
        $this->assertEquals(http_response_code(), 404);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_cli_media_dir_but_no_permissions_404()
    {
        $this->temp_file = basename(tempnam('./', 'test_cli_media_dir_but_no_permissions_404'));
        unlink($this->temp_file);
        mkdir($this->temp_file);
        chmod($this->temp_file, 0);

        $this->expectException("ExitException");
        $this->expectExceptionMessage("Not Found: {$this->temp_file}");
        $this->expectExceptionCode(-2);
        SettingsHandler::bootstrap(array(), array(), array("dir2cast.php", "--media-dir={$this->temp_file}"));
        $this->assertFalse(http_response_code());
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_GET_media_dir_but_no_permissions_404()
    {
        $this->temp_file = basename(tempnam('../', 'test_GET_media_dir_but_no_permissions_404'));
        unlink('../' . $this->temp_file);
        mkdir('../' . $this->temp_file);
        chmod('../' . $this->temp_file, 0);

        $this->expectException("ExitException");
        $this->expectExceptionMessage("Not Found: {$this->temp_file}");
        $this->expectExceptionCode(-2);
        SettingsHandler::bootstrap(array(), array("dir" => $this->temp_file), array());
        $this->assertEquals(http_response_code(), 404);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_cli_media_dir_a_ok()
    {
        $this->temp_file = basename(tempnam('./', 'test_cli_media_dir_a_ok'));
        unlink($this->temp_file);
        mkdir($this->temp_file);

        SettingsHandler::bootstrap(array(), array(), array("dir2cast.php", "--media-dir={$this->temp_file}"));
        $this->assertEquals(MP3_BASE(), slashdir(realpath('.')));
        $this->assertEquals(MP3_DIR(), slashdir(realpath($this->temp_file)));
        $this->assertFalse(http_response_code());
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_GET_media_dir_a_ok()
    {
        $this->temp_file = basename(tempnam('../', 'test_GET_media_dir_a_ok'));
        unlink('../' . $this->temp_file);
        mkdir('../' . $this->temp_file);

        SettingsHandler::bootstrap(array(), array("dir" => $this->temp_file), array());
        $this->assertEquals(MP3_BASE(), slashdir(realpath('..')));  // due to bootstrap.php chdir
        $this->assertEquals(MP3_DIR(), slashdir(realpath('../' . $this->temp_file)));
        $this->assertFalse(http_response_code());
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_GET_media_dir_safe_dot_dot_1()
    {
        $this->starting_dir = getcwd();
        mkdir('deep');
        mkdir('deep/root');
        chdir('deep/root');
        SettingsHandler::bootstrap(array(), array("dir" => ".."), array());

        $this->assertEquals(MP3_BASE(), slashdir(realpath("{$this->starting_dir}/..")));  // due to bootstrap.php chdir
        $this->assertEquals(MP3_DIR(), MP3_BASE());
        $this->assertFalse(http_response_code());
    }
    
    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_GET_media_dir_safe_dot_dot_2()
    {
        $this->starting_dir = getcwd();
        mkdir('deep');
        mkdir('deep/root');
        chdir('deep/root');
        SettingsHandler::bootstrap(array(), array("dir" => "../../.."), array());

        $this->assertEquals(MP3_BASE(), slashdir(realpath("{$this->starting_dir}/..")));  // due to bootstrap.php chdir
        $this->assertEquals(MP3_DIR(), MP3_BASE());
        $this->assertFalse(http_response_code());
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_GET_media_dir_safe_slash_dir()
    {
        $this->starting_dir = getcwd();
        mkdir('deep');
        mkdir('deep/root');
        chdir('deep/root');
        $this->expectException("ExitException");
        $this->expectExceptionMessage("Not Found: /etc");
        $this->expectExceptionCode(-2);
        SettingsHandler::bootstrap(array(), array("dir" => "/etc"), array());
        $this->assertEquals(http_response_code(), 404);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_GET_media_dir_safe_slash_dir_2()
    {
        $this->starting_dir = getcwd();
        mkdir('deep');
        mkdir('deep/root');
        chdir('deep/root');
        $this->expectException("ExitException");
        $this->expectExceptionMessage("Not Found: ////etc");
        $this->expectExceptionCode(-2);
        SettingsHandler::bootstrap(array(), array("dir" => "////etc"), array());
        $this->assertEquals(http_response_code(), 404);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_GET_media_dir_safe_dir_with_good_base()
    {
        $this->starting_dir = getcwd();
        mkdir('deep');
        mkdir('deep/root');
        chdir('deep/root');
        define('MP3_BASE', realpath('..'));
        SettingsHandler::bootstrap(array(), array("dir" => "root"), array());

        $this->assertEquals(MP3_BASE(), realpath("..") . '/');
        $this->assertEquals(MP3_DIR(), realpath('.') . '/');
        $this->assertFalse(http_response_code());
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_GET_media_dir_unsafe_slash_dir_with_good_base()
    {
        $this->starting_dir = getcwd();
        mkdir('deep');
        mkdir('deep/root');
        chdir('deep/root');
        define('MP3_BASE', realpath('..'));
        $this->expectException("ExitException");
        $this->expectExceptionMessage("Not Found: ../deep/root");
        $this->expectExceptionCode(-2);
        SettingsHandler::bootstrap(array(), array("dir" => "../deep/root"), array());
        $this->assertEquals(http_response_code(), 404);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    // public function test_cli_arg_parsing()
    // {

    // }
    
    // TODO: test HTTP_HOST + GET dir

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @testWith [null]
     *           ["dir2cast.php"]
     */
    public function test_sensible_defaults($argv0)
    {
        SettingsHandler::bootstrap(array(), array(), array($argv0));
        SettingsHandler::defaults(array());
        
        $this->assertEquals(DESCRIPTION, 'Podcast');
        $this->assertEquals(ATOM_TYPE, 'application/rss+xml');
        $this->assertEquals(LANGUAGE, 'en-us');
        $this->assertEquals(COPYRIGHT, date('Y'));
        $this->assertEquals(TTL, 60);
        $this->assertEquals(ITEM_COUNT, 10);
        $this->assertEquals(ITUNES_OWNER_NAME, '');
        $this->assertEquals(ITUNES_OWNER_EMAIL, '');
        $this->assertEquals(WEBMASTER, '');
        $this->assertEquals(ITUNES_AUTHOR, '');
        $this->assertEquals(ITUNES_CATEGORIES, '');
        $this->assertEquals(ITUNES_EXPLICIT, '');
        $this->assertEquals(LONG_TITLES, false);
        $this->assertEquals(ITUNES_SUBTITLE_SUFFIX, '');
        $this->assertEquals(ITUNES_TYPE, 'episodic');
        $this->assertEquals(DESCRIPTION_SOURCE, 'comment');
        $this->assertEquals(RECURSIVE_DIRECTORY_ITERATOR, false);
        $this->assertEquals(AUTO_SAVE_COVER_ART, true);
        $this->assertEquals(DONT_UNCACHE_IF_OUTPUT_FILE, false);
        $this->assertEquals(MIN_FILE_AGE, 30);
        
        $this->assertSame(Dir_Podcast::$EMPTY_PODCAST_IS_ERROR, empty($argv0));
        $this->assertSame(Dir_Podcast::$RECURSIVE_DIRECTORY_ITERATOR, RECURSIVE_DIRECTORY_ITERATOR);
        $this->assertSame(Dir_Podcast::$ITEM_COUNT, ITEM_COUNT);
        $this->assertSame(Dir_Podcast::$MIN_FILE_AGE, MIN_FILE_AGE);
        $this->assertSame(Cached_Dir_Podcast::$MIN_CACHE_TIME, MIN_CACHE_TIME);
        $this->assertSame(getID3_Podcast_Helper::$AUTO_SAVE_COVER_ART, AUTO_SAVE_COVER_ART);
        $this->assertSame(iTunes_Podcast_Helper::$ITUNES_SUBTITLE_SUFFIX, ITUNES_SUBTITLE_SUFFIX);
        $this->assertSame(iTunes_Podcast_Helper::$ITUNES_TYPE, ITUNES_TYPE);
        $this->assertSame(RSS_File_Item::$FILES_URL, MP3_URL);
        $this->assertSame(RSS_File_Item::$FILES_DIR, MP3_DIR());
        $this->assertSame(Media_RSS_Item::$LONG_TITLES, LONG_TITLES);
        $this->assertSame(Media_RSS_Item::$DESCRIPTION_SOURCE, DESCRIPTION_SOURCE);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @testWith [null]
     *           ["dir2cast.php"]
     */
    public function test_webmaster_default_if_itunes_author($argv0)
    {
        define('ITUNES_OWNER_NAME', 'Ben');
        define('ITUNES_OWNER_EMAIL', 'test@example.com');
        
        SettingsHandler::bootstrap(array(), array(), array($argv0));
        SettingsHandler::defaults(array());
        
        $this->assertEquals('test@example.com (Ben)', WEBMASTER);
    }
    
    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_CLI_ONLY_sensible_defaults()
    {
        SettingsHandler::bootstrap(array(), array(), array('dir2cast.php'));
        SettingsHandler::defaults(array());
        
        $this->assertEquals(MP3_URL, 'file://' . getcwd() . '/');
        $this->assertEquals(LINK, 'http://www.example.com/');
        $this->assertEquals(RSS_LINK, 'http://www.example.com/rss');
        $this->assertEquals(TITLE, 'test'); // name of this folder
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_HTTP_HOST_sensible_defaults()
    {
        $SERVER = array(
            'HTTP_HOST' => 'www.example.com',
            'SCRIPT_FILENAME' => realpath('..') . '/dir2cast.php',
            'PHP_SELF' => '/dir2cast.php',
            'DOCUMENT_ROOT' => realpath('..'),
        );
        SettingsHandler::bootstrap(
            $SERVER,
            /* $GET */ array(),
            /* $argv */ array()
        );
        SettingsHandler::defaults(
            $SERVER
        );
        
        // note that with HTTP_HOST we trust SCRIPT_FILENAME over dirname(__FILE__)
        // because it could be a symlink or a mapping inside the web server config.
        $this->assertEquals('http://www.example.com/', MP3_URL);
        $this->assertEquals('http://www.example.com/dir2cast.php', LINK);
        $this->assertEquals('http://www.example.com/dir2cast.php', RSS_LINK);
        $this->assertEquals(basename(realpath('..')), TITLE); // name of fodler from SCRIPT_FILENAME
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_picks_up_feed_text_files_if_they_exist()
    {
        file_put_contents('description.txt', 'test description');
        file_put_contents('itunes_subtitle.txt', 'test itunes subtitle');
        file_put_contents('itunes_summary.txt', 'test itunes summary');
        touch('image.jpg');
        touch('itunes_image.jpg');
        $SERVER = array(
            'HTTP_HOST' => 'www.example.com',
            'SCRIPT_FILENAME' => realpath('.') . '/dir2cast.php',
            'PHP_SELF' => '/dir2cast.php',
            'DOCUMENT_ROOT' => realpath('.'),
        );
        SettingsHandler::bootstrap(
            $SERVER,
            /* $GET */ array(),
            /* $argv */ array()
        );
        SettingsHandler::defaults(
            $SERVER
        );
        
        $this->assertEquals('test description', DESCRIPTION);
        $this->assertEquals('test itunes subtitle', ITUNES_SUBTITLE);
        $this->assertEquals('test itunes summary', ITUNES_SUMMARY);
        $this->assertEquals('http://www.example.com/image.jpg', IMAGE);
        $this->assertEquals('http://www.example.com/itunes_image.jpg', ITUNES_IMAGE);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_HTTPS_URLs_exist()
    {
        touch('image.jpg');
        touch('itunes_image.jpg');
        $SERVER = array(
            'HTTP_HOST' => 'www.example.com',
            'SCRIPT_FILENAME' => realpath('.') . '/dir2cast.php',
            'PHP_SELF' => '/dir2cast.php',
            'DOCUMENT_ROOT' => realpath('.'),
            'HTTPS' => 1,
        );
        SettingsHandler::bootstrap(
            $SERVER,
            /* $GET */ array(),
            /* $argv */ array()
        );
        SettingsHandler::defaults(
            $SERVER
        );
        
        $this->assertEquals('https://www.example.com/', MP3_URL);
        $this->assertEquals('https://www.example.com/dir2cast.php', LINK);
        $this->assertEquals('https://www.example.com/dir2cast.php', RSS_LINK);
        $this->assertEquals('https://www.example.com/image.jpg', IMAGE);
        $this->assertEquals('https://www.example.com/itunes_image.jpg', ITUNES_IMAGE);
    }

    public function tearDown(): void
    {
        if($this->starting_dir) {
            chdir($this->starting_dir);
            rmrf('deep');
        }
        file_exists('description.txt') && unlink('description.txt');
        file_exists('itunes_subtitle.txt') && unlink('itunes_subtitle.txt');
        file_exists('itunes_summary.txt') && unlink('itunes_summary.txt');
        file_exists('image.jpg') && unlink('image.jpg');
        file_exists('itunes_image.jpg') && unlink('itunes_image.jpg');
        if($this->temp_file)
        {
            if(file_exists($this->temp_file)) {
                chmod($this->temp_file, 755);
                if(is_dir($this->temp_file)) rmdir($this->temp_file);
                else unlink($this->temp_file);
            }
            elseif(file_exists('../'.$this->temp_file)) {
                chmod('../'.$this->temp_file, 755);
                if(is_dir('../'.$this->temp_file)) rmdir('../'.$this->temp_file);
                else unlink('../'.$this->temp_file);
            }
        }
    }
}

