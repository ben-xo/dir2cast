<?php declare(strict_types = 1);

use PHPUnit\Framework\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
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
    
    public function test_default_defines_set()
    {       
        foreach(self::$DEFINE_LIST as $define_name)
        {
            $this->assertFalse(defined($define_name));
        }
        
        SettingsHandler::bootstrap(array(), array(), array());
        SettingsHandler::defaults();
        
        foreach(self::$DEFINE_LIST as $define_name)
        {
            $this->assertTrue(defined($define_name));
        }
    }

    public function test_behaves_if_anything_is_already_defined()
    {
        foreach(self::$DEFINE_LIST as $define_name)
        {
            define($define_name, $define_name);
        }
        
        SettingsHandler::bootstrap(array(), array(), array());
        SettingsHandler::defaults();
        
        foreach(self::$DEFINE_LIST as $define_name)
        {
            $this->assertEquals($define_name, constant($define_name));
        }
    }
    
}