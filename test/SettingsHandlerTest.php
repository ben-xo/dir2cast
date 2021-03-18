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
        'DESCRIPTION_SOURCE',
        'RECURSIVE_DIRECTORY_ITERATOR',
        'AUTO_SAVE_COVER_ART',
        'DONT_UNCACHE_IF_OUTPUT_FILE',
        'MIN_FILE_AGE',
    );
    
    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_default_defines_set()
    {       
        foreach(self::$DEFINE_LIST as $define_name)
        {
            $this->assertFalse(defined($define_name));
        }
        
        SettingsHandler::bootstrap(
            /* $SERVER */ array(),
            /* $GET */ array(),
            /* $argv */ array()
        );
        SettingsHandler::defaults();
        
        foreach(self::$DEFINE_LIST as $define_name)
        {
            $this->assertTrue(defined($define_name));
        }
        
        // should not be defined as $argv was empty
        $this->assertFalse(defined('CLI_ONLY'));
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
        SettingsHandler::defaults();
        
        foreach(self::$DEFINE_LIST as $define_name)
        {
            if($define_name != 'DIR2CAST_BASE')
                $this->assertEquals($define_name, constant($define_name));
        }
    }
    
    /**
     * @preserveGlobalState disabled
     * @preserveGlobalState disabled
     */
    public function test_defines_CLI_ONLY_if_argv0()
    {
        // opposite test is in test_default_defines_set() above
        $this->assertFalse(defined('CLI_ONLY'));
        SettingsHandler::bootstrap(array(), array(), array('dir2cast.php'));
        $this->assertTrue(defined('CLI_ONLY'));
    }
    
}