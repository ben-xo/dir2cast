<?php

/******************************************************************************
 * Copyright (c) 2008-2022, Ben XO (me@ben-xo.com).
 *
 * All rights reserved.
 * 
 * Redistribution and use in source and binary forms, with or without 
 * modification, are permitted provided that the following conditions are met:
 * 
 *  * Redistributions of source code must retain the above copyright notice, 
 *    this list of conditions and the following disclaimer.
 *  * Redistributions in binary form must reproduce the above copyright notice, 
 *    this list of conditions and the following disclaimer in the documentation 
 *    and/or other materials provided with the distribution.
 *  * Neither the name of dir2cast nor the names of its contributors may be used 
 *    to endorse or promote products derived from this software without specific 
 *    prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR
 * CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL,
 * EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO,
 * PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR
 * PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF
 * LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
 * NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE. 
 ******************************************************************************
 *
 * USAGE:
 * 
 * 1) edit dir2cast.ini and fill in the settings.
 * 
 * 2) visit:
 * 
 * http://www.yoursite.com/dir2cast.php
 * 
 * or
 * 
 * http://www.yoursite.com/dir2cast.php?dir=my_mp3_subdir 
 * ^-- check in the README.txt for a way to get pretty URLS using mod_rewrite
 * 
 * or
 * 
 * user$ php ./dir2cast.php my_mp3_dir 
 * ^-- from the command line
 * 
 * If your MP3 dir is different from the dir the script is in, then you can have
 * have a master dir2cast.ini in the same dir as dir2cast.php, and cast-specific
 * configuration in another dir2cast.ini in the same dir as your MP3s. 
 */

/* DEFAULTS *********************************************/

// error handler needs these, so let's set them now.
define('VERSION', '1.37');
define('DIR2CAST_HOMEPAGE', 'https://github.com/ben-xo/dir2cast/');
define('GENERATOR', 'dir2cast ' . VERSION . ' by Ben XO (' . DIR2CAST_HOMEPAGE . ')');

error_reporting(E_ALL & ~E_DEPRECATED);
set_error_handler( array('ErrorHandler', 'handle_error') );
set_exception_handler( array( 'ErrorHandler', 'handle_exception') );

// Best do everything in UTC.
date_default_timezone_set( 'UTC' );

/* EXTERNALS ********************************************/

function __autoloader($class_name)
{
    switch(strtolower($class_name))
    {
        case 'getid3':
            
            ErrorHandler::prime('getid3');
            if(file_exists('getID3/getid3.php'))
                require_once('getID3/getid3.php');
            else
                require_once('getid3/getid3.php');
            ErrorHandler::defuse();
            break;
            
        default:
            if(file_exists($class_name . '.php'))
                require_once $class_name . '.php';
    }
}
spl_autoload_register('__autoloader');

/* CLASSES **********************************************/

class ExitException extends Exception {}

abstract class GetterSetter {
    
    protected $parameters = array();
    
    /**
     * Missing Method Magic Accessor
     *
     * @param string $method Method to call (get* or set*)
     * @param array $params array of parameters for the method 
     * @return mixed the result of the method
     */
    public function __call($method, $params)
    {
        $var_name = substr($method, 3);
        $var_name[0] = strtolower($var_name[0]);
        switch(strtolower(substr($method, 0, 3)))
        {
            case 'get':
                if(isset($this->parameters[$var_name]))
                    return $this->parameters[$var_name];
                break;
                
            case 'set':
                $this->parameters[$var_name] = $params[0];
                break;
                
            default:
                throw new Exception("Unknown method '" . $method . "' called on " . __CLASS__);
        }
    }    
}

interface Podcast_Helper {
    public function id();
    public function appendToChannel(DOMElement $d, DOMDocument $doc);
    public function appendToItem(DOMElement $d, DOMDocument $doc, RSS_Item $item);
    public function addNamespaceTo(DOMElement $d, DOMDocument $doc);
}

/**
 * Uses external getID3 lib to analyze MP3 files.
 *
 */
class getID3_Podcast_Helper implements Podcast_Helper {
    public function id()
    {
        return get_class($this);
    }
    static $AUTO_SAVE_COVER_ART = false;
        
    public function appendToChannel(DOMElement $d, DOMDocument $doc) { /* nothing */ }
    public function addNamespaceTo(DOMElement $d, DOMDocument $doc) { /* nothing */ }

    /**
     * Fills in a bunch of info on the Item by using getid3->Analyze()
     */
    public function appendToItem(DOMElement $d, DOMDocument $doc, RSS_Item $item)
    {
        if($item instanceof Media_RSS_Item && !$item->getAnalyzed())
        {
            $getid3 = new getID3();
            $getid3->option_tag_lyrics3 = false;
            $getid3->option_tag_apetag = false;
            $getid3->option_tags_html = false;
            // $getid3->option_save_attachments = true; // TODO: set this to a path
            $getid3->encoding = 'UTF-8';
            
            try
            {
                $info = $getid3->analyze($item->getFilename());
                $getid3->CopyTagsToComments($info);
            }
            catch(getid3_exception $e)
            {
                // MP3 couldn't be analyzed.
                return;
            }
            
            unset($this->getid3);
            
            if(!empty($info['comments']))
            {
                if(!empty($info['comments']['title'][0]))
                    $item->setID3Title( $info['comments']['title'][0] );
                if(!empty($info['comments']['artist'][0]))
                    $item->setID3Artist( $info['comments']['artist'][0] );
                if(!empty($info['comments']['album'][0]))
                    $item->setID3Album( $info['comments']['album'][0] );
                if(!empty($info['comments']['comment'][0]))
                    $item->setID3Comment( $info['comments']['comment'][0] );

                if(self::$AUTO_SAVE_COVER_ART)
                {
                    if(!empty($info['comments']['picture'][0]))
                    {
                        $item->saveImage(
                            $info['comments']['picture'][0]['image_mime'],
                            $info['comments']['picture'][0]['data']
                        );
                    }
                }
            }
            
            if(!empty($info['playtime_string']))
                $item->setDuration( $info['playtime_string'] );
            else
                $item->setDuration('0:00');
            
            $item->setAnalyzed(true);
        }
    }
}

/**
 * Loads metadata from a cache file, if possible.
 *
 */
class Caching_getID3_Podcast_Helper implements Podcast_Helper {
    public function id()
    {
        return get_class($this);
    }

    protected $wrapped_helper;
    protected $cache_dir;

    public function __construct($cache_dir, getID3_Podcast_Helper $getID3_podcast_helper) {
        $this->cache_dir = $cache_dir;
        $this->wrapped_helper = $getID3_podcast_helper;
    }

    public function appendToChannel(DOMElement $d, DOMDocument $doc) { 
        return $this->wrapped_helper->appendToChannel($d, $doc);
    }

    public function addNamespaceTo(DOMElement $d, DOMDocument $doc) {
        return $this->wrapped_helper->appendToChannel($d, $doc);
    }

    public function appendToItem(DOMElement $d, DOMDocument $doc, RSS_Item $item)
    {
        if($item instanceof Media_RSS_Item && $item instanceof Serializable && !$item->getAnalyzed())
        {
            $cache_filename = $this->getCacheFileName($this->cache_dir, $item);
            if($this->loadFromCache($cache_filename, $item)) {
                return;
            }

            $this->wrapped_helper->appendToItem($d, $doc, $item);
            $this->saveToCache($cache_filename, $item);
        }
    }

    protected function getCacheFileName($cache_dir, Media_RSS_Item $item) {
        return $cache_dir . '/' . md5($item->getFilename()) . '__' . basename($item->getFilename()) . '__data';
    }

    protected function loadFromCache($filename, Media_RSS_Item $item) {
        if(!file_exists($filename) || !is_readable($filename))
            return false; // no or unreadable cache file

        if(filemtime($filename) < $item->getModificationTime())
            return false; // cache file is older than file, so probably stale

        try
        {
            $item->unserialize(file_get_contents($filename));
            return true;
        }
        catch(SerializationException $e)
        {
            // wrong serialization version. Should re-generate.
            return false;
        }
    }

    protected function saveToCache($filename, Serializable $item) {
        if(is_writable(dirname($filename)))
            file_put_contents($filename, $item->serialize());
    }
}

class Atom_Podcast_Helper extends GetterSetter implements Podcast_Helper {
    public function id()
    {
         return get_class($this);
    }
    
    protected $self_link;
    
    public function __construct() { }
    
    public function getNSURI()
    {
        return 'http://www.w3.org/2005/Atom';
    }
    
    public function addNamespaceTo(DOMElement $d, DOMDocument $doc)
    {
        $d->appendChild( $doc->createAttribute( 'xmlns:atom' ) )
            ->appendChild( new DOMText( $this->getNSURI() ) );
    }
    
    public function appendToChannel(DOMElement $channel, DOMDocument $doc)
    {
        foreach ($this->parameters as $name => $val)
        {
            $channel->appendChild( $doc->createElement('atom:' . $name) )
                ->appendChild( new DOMText($val)    );
        }
        
        if(!empty($this->self_link))
        {
            $linkNode = $channel->appendChild( $doc->createElement('atom:link') );
            $linkNode->setAttribute('href', $this->self_link);
            $linkNode->setAttribute('rel', 'self');
            $linkNode->setAttribute('type', ATOM_TYPE);
        }        
    }
    
    public function appendToItem(DOMElement $item_element, DOMDocument $doc, RSS_Item $item)
    {

    }
    
    public function setSelfLink($link)
    {
        $this->self_link = $link;
    }
}

class iTunes_Podcast_Helper extends GetterSetter implements Podcast_Helper {
    public function id()
    {
        return get_class($this);
    }
    
    static $ITUNES_SUBTITLE_SUFFIX = '';

    protected $owner_name, $owner_email, $image_href, $explicit;
    protected $categories = array();
    
    public function __construct() { }
    
    public function getNSURI()
    {
        return 'http://www.itunes.com/dtds/podcast-1.0.dtd';
    }
    
    public function addNamespaceTo(DOMElement $d, DOMDocument $doc)
    {
        $d->appendChild( $doc->createAttribute( 'xmlns:itunes' ) )
            ->appendChild( new DOMText( $this->getNSURI() ) );
    }
    
    public function appendToChannel(DOMElement $channel, DOMDocument $doc)
    {
        foreach ($this->parameters as $name => $val)
        {
            $channel->appendChild( $doc->createElement('itunes:' . $name) )
                ->appendChild( new DOMText($val)    );
        }
        
        foreach ($this->categories as $category => $subcats)
        {
            $this->appendCategory($category, $subcats, $channel, $doc);
        }
        
        if(!empty($this->owner_name) || !empty($this->owner_email))
        {
            $owner = $channel->appendChild( $doc->createElement('itunes:owner') );

            if(!empty($this->owner_name))
            {
                $owner->appendChild( $doc->createElement('itunes:name') )
                    ->appendChild( new DOMText( $this->owner_name ) );
            }
            
            if(!empty($this->owner_email))
            {
                $owner->appendChild( $doc->createElement('itunes:email') )
                    ->appendChild( new DOMText( $this->owner_email ) );
            }
        }
        
        if(!empty($this->explicit))
        {
            $channel->appendChild( $doc->createElement('itunes:explicit') )
                ->appendChild( new DOMText( $this->explicit ) );
        }
        
        
        if(!empty($this->image_href))
        {
            $channel->appendChild( $doc->createElement('itunes:image') )
                ->setAttribute('href', $this->image_href);
        }
    }
    
    public function appendToItem(DOMElement $item_element, DOMDocument $doc, RSS_Item $item)
    {
        /*
         *    <itunes:author>John Doe</itunes:author>
         *    <itunes:duration>7:04</itunes:duration>
         *    <itunes:subtitle>A short primer on table spices</itunes:subtitle>
         *    <itunes:summary>This week we talk about salt and pepper shakers, 
         *                  [...] Come and join the party!</itunes:summary>
         *    <itunes:keywords>salt, pepper, shaker, exciting</itunes:keywords>
         */

        $elements = array(
            'author' => $item->getID3Artist(),
            'duration' => $item->getDuration(),
            //'keywords' => 'not supported yet.'
        );

        // iTunes summary is excluded if it's empty, because the default is to 
        // duplicate what's in the "description field", but iTunes will fall back 
        // to showing the <description> if there is no summary anyway.
        $itunes_summary = $item->getSummary();
        if($itunes_summary !== '')
        {
            $elements['summary'] = $itunes_summary;
        }

        // iTunes subtitle is excluded if it's empty. iTunes will fall back to
        // the itunes:summary or description if there's no subtitle.
        $itunes_subtitle = $item->getSubtitle();
        if($itunes_subtitle !== '')
        {
            $elements['subtitle'] = $itunes_subtitle . iTunes_Podcast_Helper::$ITUNES_SUBTITLE_SUFFIX;
        }
                
        foreach($elements as $key => $val)
            if(!empty($val))
                 $item_element->appendChild( $doc->createElement('itunes:' . $key) )
                    ->appendChild( new DOMText($val) );

        // Look to see if there is a item specific image and include it.
        $item_image = $item->getImage();
        if(!empty($item_image))
        {
            $item_element->appendChild( $doc->createElement('itunes:image') )
                    ->setAttribute('href', $item_image);
        }
    }
    
    public function appendCategory($category, $subcats, DOMElement $e, DOMDocument $doc)
    {
        $e->appendChild( $element = $doc->createElement('itunes:category') )
            ->setAttribute('text', $category);
            
        if(is_array($subcats)) 
            foreach($subcats as $subcategory => $subsubcats)
                $this->appendCategory($subcategory, $subsubcats, $element, $doc);
    }
    
    /**
     * Takes a category specification string and arrayifies it.
     * e.g.  
     * 'Music, Technology > Gadgets '
     * becomes
     * array( 'Music' => true, 'Technology' => array( 'Gadgets' ) );
     * @param string $category_string
     */
    public function addCategories($category_string) {
        $categories = array();
        foreach(explode(',', $category_string) as $top_level_category)
        {
            $sub_categories = explode('>', $top_level_category);
            $top_level_category = trim( array_shift($sub_categories) );
            if('' != $top_level_category)
            {
                if(empty($sub_categories))
                {
                    $categories[$top_level_category] = true;
                }
                else
                {
                    foreach($sub_categories as $sub_category)
                    {
                        $sub_category = trim($sub_category);
                        if('' != $sub_category)
                        {
                            $categories[$top_level_category][$sub_category] = true;
                        }
                    }
                }
            }
        }
        $this->categories = $categories;
    }
    
    public function setOwnerName($name)
    {
        $this->owner_name = $name;
    }

    public function setOwnerEmail($email)
    {
        $this->owner_email = $email;
    }
    
    public function setImage($href)
    {
        $this->image_href = $href;
    }
    
    public function setExplicit($explicit)
    {
        $this->explicit = $explicit;
    }
}

class RSS_Item extends GetterSetter {
    
    protected $helpers = array();
    
    public function __construct() { }
    
    public function appendToChannel(DOMElement $channel, DOMDocument $doc)
    {
        $item_element = $channel->appendChild( new DOMElement('item') );

        foreach($this->helpers as $helper)
        {
            // do helpers first; they may fill in the stuff we add down below.
            $helper->appendToItem($item_element, $doc, $this);
        }
        
        $item_elements = array(
            'title' => $this->getTitle(),
            'link' => $this->getLink(),
            'pubDate' => $this->getPubDate()
        );
        
        $cdata_item_elements = array(
            'description' => $this->getDescription()
        );
        
        if(empty($item_elements['title']))
            $item_elements['title'] = '(untitled)';
        
        foreach($item_elements as $name => $val)
        {
            $item_element->appendChild( new DOMElement($name) )
                ->appendChild(new DOMText($val));
        }
        
        foreach($cdata_item_elements as $name => $val)
        {
            if($name == 'description')
                if(!defined('DESCRIPTION_HTML'))
                    $val = htmlspecialchars((string)$val);
                    
            $item_element->appendChild( new DOMElement($name) )
                ->appendChild( $doc->createCDATASection(
                    // reintroduce newlines as <br />. 
                    nl2br($val)
                  ) );
        }

        // Look to see if there is an item specific image and include it.
        $item_image = $this->getImage();
        if(!empty($item_image))
        {
            $item_element->appendChild( $doc->createElement('image') )
                ->appendChild(new DOMText($item_image));
        }

        $enclosure = $item_element->appendChild(new DOMElement('enclosure'));
        $enclosure->setAttribute('url', $this->getLink());
        $enclosure->setAttribute('length', $this->getLength());
        $enclosure->setAttribute('type', $this->getType());
    }
    
    public function addHelper(Podcast_Helper $helper)
    {
        $this->helpers[$helper->id()] = $helper;
        return $helper;
    }
}

class RSS_File_Item extends RSS_Item {
    
    static $FILES_URL = '';
    static $FILES_DIR = '';

    public function __construct($filename)
    {
        $this->setFilename($filename);
        $this->setLinkFromFilename($filename);
        parent::__construct();
    }
    
    public function setLinkFromFilename($filename)
    {
        $this->setLink($this->filenameToUrl($filename));
    }
    
    public function setFilename($filename)
    {
        parent::setFilename($filename);
        $pos = strrpos($this->getFilename(), '.');
        if($pos !== false)
        {
            $this->setExtension(substr($filename, $pos + 1));
        }
        else
        {
            $this->setExtension('');
        }
    }

    protected function filenameToUrl($filename)
    {
        return rtrim(RSS_File_Item::$FILES_URL, '/') . '/' . str_replace('%2F', '/', rawurlencode($this->stripBasePath($filename)));
    }

    protected function stripBasePath($filename)
    {
        if(strlen(RSS_File_Item::$FILES_DIR) && strpos($filename, RSS_File_Item::$FILES_DIR) === 0)
        {
            $filename = ltrim(substr($filename, strlen(RSS_File_Item::$FILES_DIR)), '/');
        }
        return $filename;
    }
    
    /**
     * RSS_File_Items will always have a title (the filename) so, in subclasses, to check if one was set manually
     * you must call this method, not just check if parent::getTitle() is empty.
     */
    protected function hasOverridenTitle()
    {
        return !!parent::getTitle();
    }

    /**
     * Default title for an RSS Item is its filename.
     * Subclasses (Such as Media_RSS_Item, MP3_RSS_Item, etc) override this using e.g. ID3 tags.
     * 
     * @return string
     */
    public function getTitle()
    {
        $overridden_title = parent::getTitle();
        if($overridden_title)
        {
            return $overridden_title;
        }

        return basename($this->getFilename());
    }
    
    public function getType()
    {
        $overridden_type = parent::getType();
        if($overridden_type)
        {
            return $overridden_type;
        }

        return 'application/octet-stream';
    }
        
    /**
     * Place a file with the same name but .txt instead of .<whatever> and the contents will be used
     * as the summary for the item in the podcast.
     * 
     * The summary appears in iTunes when you click the 'more info' icon, and can be
     * multiple lines long.
     *
     * @return String the summary, or null if there's no summary file
     */
    public function getSummary()
    {
        $overridden_summary = parent::getSummary();
        if($overridden_summary)
        {
            return $overridden_summary;
        }

        $summary_file_name = dirname($this->getFilename()) . '/' . basename($this->getFilename(), '.' . $this->getExtension()) . '.txt';
        if(file_exists( $summary_file_name ))
            return file_get_contents($summary_file_name);
    }

    /**
     * Place a file with the same name but _subtitle.txt instead of .<whatever> and the contents will be used
     * as the subtitle for the item in the podcast.
     * 
     * The subtitle appears inline with the podcast item in iTunes, and has a 'more info' icon next
     * to it. It should be a single line.
     *
     * @return String the subtitle, or null if there's no subtitle file
     */
    public function getSubtitle()
    {
        $overridden_subtitle = parent::getSubtitle();
        if($overridden_subtitle)
        {
            return $overridden_subtitle;
        }

        $summary_file_name = dirname($this->getFilename()) . '/' . basename($this->getFilename(), '.' . $this->getExtension()) . '_subtitle.txt';
        if(file_exists( $summary_file_name ))
            return file_get_contents($summary_file_name);
    }

    protected function getImageFilename($type)
    {
        $item_file_name = $this->getFilename();
        $ext_length = strlen($this->getExtension());
        if($ext_length == 0)
        {
            $image_file_name = rtrim($item_file_name, '.') . '.' . $type;
        }
        else {
            $item_file_name_length = strlen($this->getFilename());
            $image_file_name = rtrim(substr($item_file_name, 0, $item_file_name_length - $ext_length), '.') . '.' . $type;
        }
        return $image_file_name;
    }

    /**
     * Place a file with the same name but .jpg or .png instead of .<whatever> and the contents will be used
     * as the cover art for the item in the podcast.
     * 
     * @return String the filename of the cover art or null if there's no cover art file
     */
    public function getImage()
    {
        $overridden_image = parent::getImage();
        if($overridden_image)
        {
            return $overridden_image;
        }

        $image_file_name = $this->getImageFilename('png');
        if(file_exists( $image_file_name ))
            return $this->filenameToUrl($image_file_name);

        $image_file_name = $this->getImageFilename('jpg');
        if(file_exists( $image_file_name ))
            return $this->filenameToUrl($image_file_name);
    }

    public function saveImage($mime_type, $data)
    {
        if(file_exists($this->getImageFilename('jpg')) || file_exists($this->getImageFilename('png')))
        {
            // don't overwrite image which already exists, even if it's of the wrong type.
            return;
        }

        switch($mime_type) {
            case 'image/jpeg':
                $filename = $this->getImageFilename('jpg');
                if(is_writable(dirname($filename)))
                    file_put_contents($filename, $data);
                break;

            case 'image/png':
                $filename = $this->getImageFilename('png');
                if(is_writable(dirname($filename)))
                    file_put_contents($filename, $data);
                break;
        }
    }

    public function getFileSize()
    {
        return filesize($this->getFilename());
    }

    public function getFileTimestamp()
    {
        return filemtime($this->getFilename());
    }

    public function getModificationTime()
    {
        $mtimes = array(
            $this->getFileTimestamp()
        );

        $common_prefix = dirname($this->getFilename()) . '/' . basename($this->getFilename(), '.' . $this->getExtension());

        foreach(array(
            $this->getImageFilename('jpg'),
            $this->getImageFilename('png'),
            $common_prefix . '.txt',
            $common_prefix . '_subtitle.txt'
        ) as $f)
        {
            if(file_exists($f))
            {
                $mtimes[] = filemtime($f);
            }
        }
        return max($mtimes);
    }

    public function getTotalFileSize()
    {
        $sizes = array(
            $this->getFileSize()
        );

        $common_prefix = dirname($this->getFilename()) . '/' . basename($this->getFilename(), '.' . $this->getExtension());

        foreach(array(
            $this->getImageFilename('jpg'),
            $this->getImageFilename('png'),
            $common_prefix . '.txt',
            $common_prefix . '_subtitle.txt'
        ) as $f)
        {
            if(file_exists($f))
            {
                $sizes[] = filesize($f);
            }
        }
        return array_sum($sizes);
    }
}

class Media_RSS_Item extends RSS_File_Item implements Serializable {

    static $LONG_TITLES = false;
    static $DESCRIPTION_SOURCE = 'comment';

    public function __construct($filename)
    {
        parent::__construct($filename);
        $this->setFromMediaFile();
    }

    public function setFromMediaFile()
    { 
        // don't do any heavy-lifting here as this is called by the constructor, which 
        // is called once for every media file in the dir (not just the ITEM_COUNT in the cast)
        // TODO: this will go slightly faster if we don't do these syscalls here
        $this->setLength($this->getFileSize());
        $this->setPubDate(date('r', $this->getFileTimestamp()));
    }

    /**
     * The title of the file item as it should appear in the podcast.
     * 
     * Media_RSS_Items will derive this from the ID3 (or other) tags in the file, if available.
     * 
     * The default title is just the "Title" tag, unless LONG_TITLES is set, in which case
     * it's "Album - Artist - Title" using the applicable tags.
     * 
     * If these are all blank, it falls back to the filename so that you can at least see what is
     * what in the feed.
     * 
     * @see RSS_File_Item::getTitle()
     * 
     * @return string
     */
    public function getTitle()
    {
        if($this->hasOverridenTitle())
        {
            return parent::getTitle();
        }

        $title_parts = array();
        if(self::$LONG_TITLES)
        {
            if($this->getID3Album()) $title_parts[] = $this->getID3Album();
            if($this->getID3Artist()) $title_parts[] = $this->getID3Artist();
        }
        if($this->getID3Title())
        {
            $title_parts[] = $this->getID3Title();
        }
        if(!empty($title_parts))
        {
            return implode(' - ', $title_parts);
        }
        return parent::getTitle();
    }

    public function getDescription()
    {
        $overridden_description = parent::getDescription();
        if($overridden_description)
        {
            return $overridden_description;
        }

        // The default value is "comment". dir2cast prior to v1.19
        // used value "file", so it's here for backward compatibility
        if(self::$DESCRIPTION_SOURCE == 'summary' || self::$DESCRIPTION_SOURCE == 'file')
            return parent::getSummary(); // call to parent because otherwise we could co-recurse.

        return $this->getID3Comment();
    }

    public function getSummary()
    {
        $summary = parent::getSummary();
        if(!$summary)
        {
            // use description as summary if there's no file-based override
            $summary = $this->getDescription();
        }
        return $summary;
    }

    public function getSubtitle()
    {
        $subtitle = parent::getSubtitle();
        if(!$subtitle && !self::$LONG_TITLES)
        {
            // use artist as subtitle if there's no file-based override
            // but not if LONG_TITLES is set (as it's already in the title)
            $subtitle = $this->getID3Artist();
        }
        return $subtitle;
    }

    /**
     * Version number used in the saved cache files. If the used fields change, increment this number.
     * @var integer
     */
    const SERIAL_VERSION = 1;

    public function __serialize()
    {
        $this->setSerialVersion(self::SERIAL_VERSION);
        $serialized_parameters = $this->parameters;

        // these are all set from the filesystem metadata, not the file content.
        unset($serialized_parameters['length']);
        unset($serialized_parameters['pubDate']);
        unset($serialized_parameters['filename']);
        unset($serialized_parameters['extension']);
        unset($serialized_parameters['link']);

        return $serialized_parameters;
    }

    public function serialize()
    {
        return serialize($this->__serialize());
    }

    public function __unserialize($serialized)
    {
        if($serialized['serialVersion'] != self::SERIAL_VERSION)
            throw new SerializationException("Wrong serialized version");
        
        // keep properties we've already set. This should make cache files transferable 
        // whilst still gaining a speed-up over ID3-reading.
        $this->parameters = array_merge($serialized, $this->parameters);
    }

    public function unserialize($serialized)
    {
        $this->__unserialize(unserialize($serialized));
    }
}

class MP3_RSS_Item extends Media_RSS_Item 
{
    public function __construct($filename)
    {
        parent::__construct($filename);
        $this->setType('audio/mpeg');
    }
}

class M4A_RSS_Item extends Media_RSS_Item
{
    public function __construct($filename)
    {
        parent::__construct($filename);
        $this->setType('audio/mp4');
    }
}

class MP4_RSS_Item extends Media_RSS_Item
{
    public function __construct($filename)
    {
        parent::__construct($filename);
        $this->setType('video/mp4');
    }
}


abstract class Podcast extends GetterSetter
{
    protected $items = array();
    protected $helpers = array();
    
    public function addHelper(Podcast_Helper $helper)
    {
        $this->helpers[$helper->id()] = $helper;
        
        // attach helper to items already added.
        // new items will have the helper attached when they are added.
        foreach($this->items as $item)
            $item->addHelper($helper);
            
        return $helper;
    }
    
//    public function getNSURI()
//    {
//        return 'http://backend.userland.com/RSS2';
//    }
    
    public function http_headers($content_length)
    {
        // The correct content type is application/rss+xml; however, the de-facto standard is now text/xml. 
        // See https://stackoverflow.com/questions/595616/what-is-the-correct-mime-type-to-use-for-an-rss-feed
        header('Content-type: text/xml; charset=UTF-8');
        header('Content-length: ' . $content_length);
        header('Last-modified: ' . $this->getLastBuildDate());
    }
    
    /**
     * Generates and returns the podcast RSS
     *
     * @return String the PodCast RSS
     */
    public function generate()
    {
        $this->pre_generate();
        
        $this->setLastBuildDate(date('r'));
        
        $doc = new DOMDocument('1.0', 'UTF-8');
        $doc->formatOutput = true;
                    
        $rss = $doc->createElement('rss');
        $doc->appendChild($rss);
        
        $rss->setAttribute('version', '2.0');
        
        // we do not show the default xmlns. Seems to break the validator.
        //    $rss->appendChild( $doc->createAttribute( 'xmlns' ) )
        //        ->appendChild( new DOMText( $this->getNSURI() ) );
            
        foreach($this->helpers as $helper)
            $helper->addNamespaceTo($rss, $doc);
        
        // the channel
        $channel = $rss->appendChild(new DOMElement('channel'));
        $channel_elements = array(
            'title' => $this->getTitle(),
            'link' => $this->getLink(),
            'description' => $this->getDescription(),
            'lastBuildDate' => $this->getLastBuildDate(),
            'language' => $this->getLanguage(),
            'copyright' => str_replace('%YEAR%', date('Y'), (string)$this->getCopyright()),
            'generator' => $this->getGenerator(),
            'webMaster' => $this->getWebMaster(),
            'ttl' => $this->getTtl()
        );
                
        foreach($channel_elements as $name => $val)
        {
            $channel->appendChild( new DOMElement($name) )
                ->appendChild(new DOMText((string)$val));
        }
        
        $this->appendImage($channel);
        
        foreach($this->helpers as $helper)
        {
            $helper->appendToChannel($channel, $doc);
        }
        
        // channel item list
        foreach($this->getItems() as $item)
        {
            /* @var $item RSS_Item */
            $item->appendToChannel($channel, $doc);
        }

        $this->post_generate($doc);
        
        $doc->normalizeDocument();
        
        // see http://validator.w3.org/feed/docs/warning/CharacterData.html
        return str_replace( 
            array('&amp;', '&lt;', '&gt;'), 
            array('&#x26;', '&#x3C;', '&#x3E;'), 
            utf8_for_xml($doc->saveXML())
        );
    }

    public function addRssItem(RSS_Item $item)
    {
        $this->items[] = $item;

        // attach helpers to the new item.
        // new helpers will be attached if they are added later.
       foreach($this->helpers as $helper)
            $item->addHelper($helper);
    }

    /**
     * @return array of RSS_Item
     */
    public function getItems()
    {
        return $this->items;
    }

    protected function appendImage(DOMElement $channel)
    {
        $image_url = $this->getImage();
        if(strlen((string)$image_url))
        {
            $image = $channel->appendChild( new DOMElement('image'));
            $image->appendChild( new DOMElement('url') )
                ->appendChild(new DOMText($image_url));
            $image->appendChild( new DOMElement('link') )
                ->appendChild(new DOMText($this->getLink()));
            $image->appendChild( new DOMElement('title') )
                ->appendChild(new DOMText($this->getTitle()));
            $channel->appendChild($image);
        }
    }
    
    protected function pre_generate() {    }
    protected function post_generate(DOMDocument $doc) { }

}

class Dir_Podcast extends Podcast
{
    static $EMPTY_PODCAST_IS_ERROR = false;
    static $RECURSIVE_DIRECTORY_ITERATOR = false;
    static $ITEM_COUNT = 10;
    static $MIN_FILE_AGE = 0;
    static $DEBUG = false;

    protected $source_dir;
    protected $scanned = false;
    protected $unsorted_items = array();
    protected $max_mtime = 0;
    protected $item_hash_list = array();
    protected $item_hash;

    protected $clock_offset = 0;
    
    /**
     * Constructor
     *
     * @param string $source_dir
     */
    public function __construct($source_dir)
    {
        $this->source_dir = $source_dir;
    }

    /**
     * Looks for all files in the media path and adds them to the podcast, 
     * tracking the most recently modified date in ->max_mtime 
     */
    protected function scan()
    {
        if(!$this->scanned)
        {
            self::$DEBUG && print("Scanning…\n");

            $this->pre_scan();
            
            // scan the dir

            if(self::$RECURSIVE_DIRECTORY_ITERATOR)
            {
                $di = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($this->source_dir),
                    RecursiveIteratorIterator::SELF_FIRST
                );
            }
            else
            {
                $di = new DirectoryIterator($this->source_dir);
            }
            
            $item_count = 0;
            foreach($di as $file)
            {
                $filepath = $file->getPath() . '/' . $file->getFileName();
                self::$DEBUG && print("Considering {$filepath}…\n");
                $item_count = $this->addItem($filepath);
            }

            self::$DEBUG && print("$item_count items added.\n");

            if(self::$EMPTY_PODCAST_IS_ERROR && 0 == $item_count)
            {
                http_response_code(404);
                throw new Exception("No content yet.");
            }

            $this->calculateItemHash();

            $this->scanned = true;
            $this->post_scan();
            $this->sort();
       }
    }

    /**
     * Adds file to ->unsorted_items, and updates ->max_mtime, if it is of a supported type
     *
     * @param string $filename
     */
    public function addItem($filename)
    {
        $pos = strrpos($filename, '.');
        if(false === $pos)
            $file_ext = '';
        else
            $file_ext = strtolower(substr($filename, $pos + 1));

        switch($file_ext)
        {
            case 'mp3':
                $this->addRssFileItem(new MP3_RSS_Item($filename));
                break;

            case 'm4a':
            case 'm4b':
                $this->addRssFileItem(new M4A_RSS_Item($filename));
                break;

            case 'mp4':
                $this->addRssFileItem(new MP4_RSS_Item($filename));
                break;

            default:
                // no other file types are considered for the podcast
        }
        
        return count($this->unsorted_items);
    }

    public function updateMaxMtime($date, $filename)
    {
        if($date > $this->max_mtime) 
        {
            self::$DEBUG && print("mtime ceiling {$this->max_mtime} ➡ {$date} (now: " . time() . ") from $filename\n");
            $this->max_mtime = $date;
        }
    }

    public function getMaxMtime()
    {
        return $this->max_mtime;
    }

    public function calculateItemHash()
    {
        sort($this->item_hash_list);
        $this->item_hash = md5(implode("\n", $this->item_hash_list));
    }

    public function getItemHash()
    {
        return $this->item_hash;
    }

    public function setClockOffset($offset)
    {
        $this->clock_offset = $offset;
    }

    /**
     * Adds file to ->unsorted_items, and updates ->max_mtime
     * 
     * @param RSS_File_Item $the_item
     */
    protected function addRssFileItem(RSS_File_Item $the_item)
    {
        // skip 0-length files. getID3 chokes on them and listeners dislike them
        if($the_item->getFileSize())
        {
            $filemtime_media_only = $the_item->getFileTimestamp();

            if((self::$MIN_FILE_AGE > 0) && $filemtime_media_only > (time() - self::$MIN_FILE_AGE))
            {
                // don't add files which are so new that they may still be being uploaded
                return;
            }

            $filemtime_inclusive = $the_item->getModificationTime();

            // one array per mtime, just in case several MP3s share the same mtime.
            $this->updateMaxMtime($filemtime_inclusive, $the_item->getFilename());
            $this->unsorted_items[$filemtime_media_only][] = $the_item;
            $hashlist_mtime = $filemtime_inclusive + $this->clock_offset; // clock offset is just used in testing.
            $this->item_hash_list[] = "{$hashlist_mtime}:{$the_item->getTotalFileSize()}";
        }
    }

    protected function pre_generate()
    {
        $this->scan();
        
        // Add helpers here, NOT during scan(). 
        // scan() is also used just to get mtimes to see if we need to regenerate the feed.
        foreach($this->helpers as $helper)
            foreach($this->items as $the_item)
                $the_item->addHelper($helper);
    }

    protected function sort() { 
        krsort($this->unsorted_items); // newest first
        $this->items = array();

        $i = 0;
        foreach($this->unsorted_items as $item_list)
        {
            foreach($item_list as $item)
            {
                $this->items[$i++] = $item;
                if($i >= self::$ITEM_COUNT)
                    break 2;
            }
        }

        unset($this->unsorted_items);
    }

    protected function pre_scan() { }

    protected function post_scan() { }
}

/**
 * Podcast with cached output. The cache file will be created and a file lock
 * obtained at object construction time. The lock will be released in the object's
 * destructor.
 */
class Cached_Dir_Podcast extends Dir_Podcast
{
    protected $temp_dir;
    protected $temp_file;
    protected $item_hash_file;
    protected $cache_date;
    protected $serve_from_cache;

    static $MIN_CACHE_TIME = 5; // seconds

    /**
     * Constructor
     * 
     * After constructing, you should call ->init() to make use of the whole-feed cache
     *
     * @param string $source_dir
     * @param string $temp_dir
     */
    public function __construct($source_dir, $temp_dir)
    {
        $this->temp_dir = $temp_dir;
        $safe_source_name = preg_replace('/[^\w]/', '_', dirname($source_dir) . '/' . basename($source_dir) );
        
        // something unique, safe, stable and easily identifiable
        $this->temp_file = rtrim($temp_dir, '/') . '/' . md5($source_dir) . '_' . $safe_source_name . '.xml';
        $this->item_hash_file = rtrim($temp_dir, '/') . '/' . md5($source_dir) . '_' . $safe_source_name . '__item_hash.txt';

        parent::__construct($source_dir);
    }

    /**
     * Initialises the cache file
     */
    public function init()
    { 
        if($this->isCached())
        {
            self::$DEBUG && print("Found cache file\n");
            $this->serve_from_cache = true;

            $cache_date = filemtime($this->temp_file);

            // if the cache file is quite new, don't bother regenerating.
            if( $cache_date < time() - self::$MIN_CACHE_TIME ) 
            {
                self::$DEBUG && print("Cache file is older than " . self::$MIN_CACHE_TIME . " seconds\n");

                $previous_item_hash = "";
                if(file_exists($this->item_hash_file))
                    $previous_item_hash = file_get_contents($this->item_hash_file);

                $this->scan(); // sets $this->max_mtime and $this->item_hash
                if( $this->cache_is_stale($cache_date, $this->max_mtime) )
                {
                    self::$DEBUG && print("Cache is stale (cache file mtime: $cache_date, max mtime: {$this->max_mtime}). Uncaching\n");
                    $this->uncache();
                }
                elseif( $previous_item_hash != $this->item_hash )
                {
                    self::$DEBUG && print("Cache has changed (before: $previous_item_hash, after: {$this->item_hash}). Uncaching\n");
                    $this->uncache();
                }
                else
                {
                    self::$DEBUG && print("Cache is not stale (cache file mtime: $cache_date, max mtime: {$this->max_mtime} and previous hash {$previous_item_hash} and hash {$this->item_hash}). Renewing\n");
                    $this->renew();
                }
            }
        }
    }
    
    /**
     * Cache is considered stale (i.e. not a good representation of the source folder) if:
     * * the date of the cache is < the date of the most recent modified file OR
     *   the date of the cache is < the date of the most recent modification to the folder of media
     * * AND the most recent change is more than MIN_CACHE_TIME in the past (to avoid incomplete files)
     *
     * $cache_date is usually the modification time of the cache file itself.
     *  
     * $most_recent_modification is usually passed in from $this->max_mtime i.e. the most recently modified
     * podcast media file, and can be 0 if there are no files at all in the podcast yet.
     *
     * @param int $cache_date
     * @param int $most_recent_modification
     * @return boolean
     */
    protected function cache_is_stale($cache_date, $most_recent_modification)
    {
        return $cache_date < $most_recent_modification;
    }

    /**
     * Update the date on the cache file so that it's still considered fresh.
     */
    public function renew()
    {
        touch($this->temp_file); // renew cache file life expectancy
    }

    public function uncache()
    {
        if($this->isCached())
        {
            unlink($this->temp_file);
            $this->serve_from_cache = false;
        }
    }

    public function generate()
    {
        if($this->serve_from_cache)
        {
            if(!file_exists($this->temp_file))
                throw new RuntimeException("serve_from_cache set, but cache file not found");

            $output = file_get_contents($this->temp_file); // serve cached copy

            // extract lastBuiltDate from the cache file. We can't simply use filemtime() of the cache file as
            // the mtime is refreshed (to stop us continually rescanning for new content) Also, we don't want to
            // parse the whole file, so it's okay to carefully extract it with a regex here, even though that's
            // not usually recommended. The regex is chosen to ensure the captured text can't lead to header injection.

            preg_match('#<lastBuildDate>([0-9a-zA-Z,:+ ]{1,64})</lastBuildDate>#', $output, $matches);
            if(isset($matches[1]))
            {
                $this->setLastBuildDate($matches[1]);
            }
        }
        else
        {
            $output = parent::generate();
            file_put_contents($this->temp_file, $output); // save cached copy
            file_put_contents($this->item_hash_file, $this->item_hash);
            $this->serve_from_cache = true;
        }

        return $output;
    }

    public function isCached()
    {
        return file_exists($this->temp_file) && filesize($this->temp_file);
    }
}

class Locking_Cached_Dir_Podcast extends Cached_Dir_Podcast
{
    protected $file_handle;
    
    public function init()
    {
        $this->acquireLock();
        parent::init();
    }
    
    /**
     * acquireLock always creates the cache file.
     */
    protected function acquireLock()
    {
        if (!file_exists($this->temp_dir))
        {
            mkdir($this->temp_dir, 0755, true);
        }

        $this->file_handle = fopen($this->temp_file, 'a');
        if(!flock($this->file_handle, LOCK_EX))
            throw new Exception('Locking cache file failed.');
    }
    
    /**
     * releaseLock will delete the cache file before unlocking if it's empty.
     */
    protected function releaseLock()
    {
        clearstatcache();
        if(file_exists($this->temp_file) && !filesize($this->temp_file))
            unlink($this->temp_file);
        
        // this releases the lock implicitly
        if($this->file_handle)
            fclose($this->file_handle);
    }
    
    public function __destruct()
    {
        $this->releaseLock();
    }
}

class ErrorHandler
{
    public static $primer;
    private static $errors = true;
    
    public static function prime($type)
    {
        self::$primer = $type;
    }
    
    public static function defuse()
    {
        self::$primer = null;
    }
    
    public static function errors($state)
    {
        self::$errors = $state;
    }
    
    public static function handle_error($errno, $errstr, $errfile=null, $errline=null, $errcontext=null)
    {    
        // note: this is required to support the @ operator, which getID3 uses extensively
        if(error_reporting() & $errno)
        {
            ErrorHandler::display($errstr, $errfile, $errline);
        }
    }
    
    /**
     * In PHP5, this will be an Exception, but in PHP7 is can be a Throwable
     */
    public static function handle_exception( $e )
    {
        ErrorHandler::display($e->getMessage(), $e->getFile(), $e->getLine());
    }
    
    public static function get_primed_error($type)
    {
        switch($type)
        {
            case 'ini':
                return 'Suggestion: Make sure that your ini file is valid. If the error is on a specific line, try enclosing the value in "quotes".';
            
            case 'getid3':
                return 'dir2cast requires getID3. You should download this from <a href="' . DIR2CAST_HOMEPAGE . '">' . DIR2CAST_HOMEPAGE .'</a> and install it with dir2cast.';
        }
    }

    public static function display($message, $errfile, $errline)
    {    
        if(self::$errors)
        {
            if(!defined('CLI_ONLY'))
            {
                if(!http_response_code())
                {
                    http_response_code(500);
                }
            }

            if((!defined('CLI_ONLY')) && !ini_get('html_errors'))
            {
                header("Content-type: text/plain"); // reset the content-type
            }

            if((!defined('CLI_ONLY')) && ini_get('html_errors'))
            {
                header("Content-type: text/html"); // reset the content-type
                        
                ?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
                <html><head><title>dir2cast <?php echo VERSION; ?> error</title>
                <meta http-equiv="Content-Type" content="text/html; charset=utf-8"> 
                <style type="text/css">
                    body { font-family: Calibri, Arial, Helvetica, sans-serif; font-size: 16px; }
                    h1 { font-weight: bold; font-size: 200%; }
                    a img { border: 0px; }
                    #footer { font-size: 12px; margin-top: 1em;}
                    #the_error { border: 1px red solid; padding: 1em; } 
                    #additional_error { font-size: 14px; }
                </style>
                </head>
                <body>
                    <h1>An error occurred generating your podcast.</h1>
                    <div id="the_error">
                        <?php echo $message; ?>
                        <?php if(!empty(ErrorHandler::$primer)): ?>
                            <br><br>
                            <?php echo self::get_primed_error(ErrorHandler::$primer); ?>
                        <?php endif; ?>
                        <?php if(http_response_code() == 500): ?>
                            <br><br>
                            <div id="additional_error">
                                This error occurred on line <?php echo $errline; ?> of <?php echo $errfile; ?>.
                            </div>
                        <?php endif; ?>
                    </div>
                    <div id="footer"><a href="<?php echo DIR2CAST_HOMEPAGE ?>">dir2cast</a> <?php echo VERSION; ?> by Ben XO</div>
                    <p>
                        <a href="http://validator.w3.org/check?uri=referer"><img
                            src="http://www.w3.org/Icons/valid-html401"
                            alt="Valid HTML 4.01 Strict" height="31" width="88"></a>
                    </p>
                </body></html>
                <?php
            }
            else
            {
                // This case happens when define('CLI_ONLY') || !ini_get('html_errors')
                echo "Error: $message (on line $errline of $errfile)\n";
                if(!empty(ErrorHandler::$primer))
                    echo strip_tags(self::get_primed_error(ErrorHandler::$primer)) . "\n";
            }

            exit(-1); // can't throw - this is the exception handler
        }
    }

    public static function display404($message)
    {
        if(defined('CLI_ONLY'))
        {
            http_response_code(404);
            header("Content-type: text/plain");
        }
        throw new ExitException("Not Found: $message", -2);
    }
}

class SettingsHandler
{
    private static $settings_cache = array();
    
    /**
     * getopt() uses argv directly and is a pain to mock. It's nicer to pass argv in,
     * but mocking it a pain.
     */
    public static function getopt($argv_in, $short_options, $long_options)
    {
        if(isset($GLOBALS['argv']) && $argv_in != $GLOBALS['argv'])
        {
            return fake_getopt($argv_in, $short_options, $long_options);
        }
        return getopt($short_options, $long_options);
    }



    /**
     * This method sets up all app-wide settings that are required at initialization time.
     * 
     * @param $SERVER Array HTTP Server array containing HTTP_HOST, SCRIPT_FILENAME, DOCUMENT_ROOT, HTTPS
     * @param $GET Array HTTP GET
     * @param $argv Array command line options
     */
    public static function bootstrap(array $SERVER, array $GET, array $argv)
    {

        if(!defined('CLI_ONLY') && isset($argv) && isset($argv[0])) {
            define('CLI_ONLY', true);
        }

        // do not use DIR2CAST_BASE directly. use DIR2CAST_BASE()
        if(defined('CLI_ONLY')) {
            define('DIR2CAST_BASE', realpath(dirname($argv[0])));
        } else {
            define('DIR2CAST_BASE', dirname(__FILE__));
        }

        // If an installation-wide config file exists, load it now.
        // Installation-wide config can contain TMP_DIR, MP3_DIR and MIN_CACHE_TIME.
        // Anything else it contains will be used as a fall-back if no dir-specific dir2cast.ini exists
        if(file_exists( DIR2CAST_BASE() . 'dir2cast.ini' ))
        {
            $ini_file_name = DIR2CAST_BASE() . 'dir2cast.ini';
            self::load_from_ini( $ini_file_name );
            self::finalize(array('TMP_DIR', 'MP3_BASE', 'MP3_DIR', 'MIN_CACHE_TIME', 'FORCE_PASSWORD'));
            define('INI_FILE', $ini_file_name);
        }
        
        $cli_options = self::getopt($argv, '', array('help', 'media-dir::', 'media-url::', 'output::', 'dont-uncache', 'min-file-age::', 'debug', 'ignore-dir2cast-mtime', 'clock-offset::'));
        if($cli_options) {
            if(isset($cli_options['help'])) {
                print "Usage: php dir2cast.php [--help] [--media-dir=MP3_DIR] [--media-url=MP3_URL] [--output=OUTPUT_FILE]\n";

                // extra debugging / test harness options:
                // [--dont-uncache]
                // [--min-file-age=MIN_FILE_AGE]
                // [--debug]
                // [--ignore-dir2cast-mtime]
                // [--clock-offset=CLOCK_OFFSET]

                exit;
            }
            if(!defined('MP3_DIR') && !empty($cli_options['media-dir']))
            {

                if(!is_dir($cli_options['media-dir']) or !is_readable($cli_options['media-dir']))
                {
                    ErrorHandler::display404($cli_options['media-dir']);
                }
                // do not use MP3_DIR directly. use MP3_DIR()
                define('MP3_DIR', slashdir(realpath($cli_options['media-dir'])));
            }
            if(!defined('MP3_URL') && !empty($cli_options['media-url']))
            {
                define('MP3_URL', $cli_options['media-url']);
            }
            if(!defined('OUTPUT_FILE') && !empty($cli_options['output']))
            {
                define('OUTPUT_FILE', $cli_options['output']);
            }
            if(!defined('DONT_UNCACHE_IF_OUTPUT_FILE') && isset($cli_options['dont-uncache']))
            {
                define('DONT_UNCACHE_IF_OUTPUT_FILE', true);
            }
            if(!defined('MIN_FILE_AGE') && isset($cli_options['min-file-age']))
            {
                define('MIN_FILE_AGE', (int)$cli_options['min-file-age']);
            }
            if(!defined('DEBUG') && isset($cli_options['debug']))
            {
                define('DEBUG', true);
            }
            if(!defined('IGNORE_DIR2CAST_MTIME') && isset($cli_options['ignore-dir2cast-mtime']))
            {
                define('IGNORE_DIR2CAST_MTIME', true);
            }
            if(!defined('CLOCK_OFFSET') && isset($cli_options['clock-offset']))
            {
                define('CLOCK_OFFSET', (int)$cli_options['clock-offset']);
            }
        }
       
        if(!defined('MIN_CACHE_TIME'))
            define('MIN_CACHE_TIME', 5);
        
        if(!defined('FORCE_PASSWORD'))
            define('FORCE_PASSWORD', '');

        if(!defined('TMP_DIR')) {
            define('TMP_DIR', DIR2CAST_BASE() . 'temp');
        }

        // do not use MP3_BASE directly. use MP3_BASE()
        if(!defined('MP3_BASE'))
        {
            if(!empty($SERVER['HTTP_HOST']))
                define('MP3_BASE', dirname($SERVER['SCRIPT_FILENAME']));
            else
                define('MP3_BASE', DIR2CAST_BASE());
        }

        // do not use MP3_DIR directly. use MP3_DIR()
        if(!defined('MP3_DIR'))
        {
            if(!empty($GET['dir']))
            {
                define('MP3_DIR', MP3_BASE() . safe_path(magic_stripslashes($GET['dir'])));
                if(!is_dir(MP3_DIR()) or !is_readable(MP3_DIR()))
                {
                    ErrorHandler::display404($GET['dir']);
                }
            }
            else
                define('MP3_DIR', MP3_BASE());
        }
    }
    
    /**
     * This method sets up all fall-back default instance settings AFTER all .ini files have been loaded.
     */
    public static function defaults(array $SERVER)
    {
        // if an MP3_DIR specific config file exists, load it now, as long as it's not the same file as the global one!
        if( 
            file_exists( MP3_DIR() . 'dir2cast.ini' ) and    
            realpath(DIR2CAST_BASE() . 'dir2cast.ini') != realpath( MP3_DIR() . 'dir2cast.ini' ) 
        ) {
            self::load_from_ini( MP3_DIR() . 'dir2cast.ini' );
        }
        
        self::finalize();
        
        if(!defined('MP3_URL'))
        {
            # This works on the principle that MP3_DIR must be under DOCUMENT_ROOT (otherwise how will you serve the MP3s?)
            # This may fail if MP3_DIR, or one of its parents under DOCUMENT_ROOT, is a symlink. In that case you will have
            # to set this manually.
            
            if(!empty($SERVER['HTTP_HOST']))
            {
                $path_part = substr(MP3_DIR(), strlen(slashdir($SERVER['DOCUMENT_ROOT'])));
                define('MP3_URL', 
                    'http' . (!empty($SERVER['HTTPS']) ? 's' : '') . '://' . $SERVER['HTTP_HOST'] . '/' . ltrim( slashdir( $path_part ), '/' ));
            }
            else
                define('MP3_URL', 'file://' . MP3_DIR() );
        }

        if(!defined('TITLE'))
        {
            if(basename(MP3_DIR()))
                define('TITLE', basename(MP3_DIR()));
            else
                define('TITLE', 'My First dir2cast Podcast');
        }
        
        if(!defined('LINK'))
        {
            if(!empty($SERVER['HTTP_HOST']))
                define('LINK', 'http' . (empty($SERVER['HTTPS']) ? '' : 's') . '://' . $SERVER['HTTP_HOST'] . $SERVER['PHP_SELF']);
            else
                define('LINK', 'http://www.example.com/');
        }

        if(!defined('RSS_LINK'))
        {
            if(!empty($SERVER['HTTP_HOST']))
                define('RSS_LINK', 'http' . (empty($SERVER['HTTPS']) ? '' : 's') . '://' . $SERVER['HTTP_HOST'] . $SERVER['PHP_SELF']);
            else
                define('RSS_LINK', 'http://www.example.com/rss');
        }
        
        if(!defined('DESCRIPTION'))
        {
            if(file_exists(MP3_DIR() . 'description.txt'))
                define('DESCRIPTION', file_get_contents(MP3_DIR() . 'description.txt'));
            elseif(file_exists(DIR2CAST_BASE() . 'description.txt'))
                define('DESCRIPTION', file_get_contents(DIR2CAST_BASE() . 'description.txt'));
            else
                define('DESCRIPTION', 'Podcast');
        }
        
        if(!defined('ATOM_TYPE'))
            define('ATOM_TYPE','application/rss+xml');

        if(!defined('LANGUAGE'))
            define('LANGUAGE', 'en-us');
        
        if(!defined('COPYRIGHT'))
            define('COPYRIGHT', date('Y'));
            
        if(!defined('TTL'))
            define('TTL', 60);
            
        if(!defined('ITEM_COUNT'))
            define('ITEM_COUNT', 10);
            
        if(!defined('ITUNES_SUBTITLE'))
        {
            if(file_exists(MP3_DIR() . 'itunes_subtitle.txt'))
                define('ITUNES_SUBTITLE', file_get_contents(MP3_DIR() . 'itunes_subtitle.txt'));
            elseif(file_exists(DIR2CAST_BASE() . 'itunes_subtitle.txt'))
                define('ITUNES_SUBTITLE', file_get_contents(DIR2CAST_BASE() . 'itunes_subtitle.txt'));
            else
                define('ITUNES_SUBTITLE', DESCRIPTION);
        }
        
        if(!defined('ITUNES_SUMMARY'))
        {
            if(file_exists(MP3_DIR() . 'itunes_summary.txt'))
                define('ITUNES_SUMMARY', file_get_contents(MP3_DIR() . 'itunes_summary.txt'));
            elseif(file_exists(DIR2CAST_BASE() . 'itunes_summary.txt'))
                define('ITUNES_SUMMARY', file_get_contents(DIR2CAST_BASE() . 'itunes_summary.txt'));
            else
                define('ITUNES_SUMMARY', DESCRIPTION);
        }

        if(!defined('IMAGE'))
        {
            if(file_exists(MP3_DIR() . 'image.jpg'))
                define('IMAGE', rtrim(MP3_URL, '/') . '/image.jpg');
            elseif(file_exists(MP3_DIR() . 'image.png'))
                define('IMAGE', rtrim(MP3_URL, '/') . '/image.png');
            elseif(file_exists(DIR2CAST_BASE() . 'image.jpg'))
                define('IMAGE', rtrim(MP3_URL, '/') . '/image.jpg');
            elseif(file_exists(DIR2CAST_BASE() . 'image.png'))
                define('IMAGE', rtrim(MP3_URL, '/') . '/image.png');
            else
                define('IMAGE', '');
        }
        
        if(!defined('ITUNES_IMAGE'))
        {
            if(file_exists(MP3_DIR() . 'itunes_image.jpg'))
                define('ITUNES_IMAGE', rtrim(MP3_URL, '/') . '/itunes_image.jpg');
            elseif(file_exists(MP3_DIR() . 'itunes_image.png'))
                define('ITUNES_IMAGE', rtrim(MP3_URL, '/') . '/itunes_image.png');
            elseif(file_exists(DIR2CAST_BASE() . 'itunes_image.jpg'))
                define('ITUNES_IMAGE', rtrim(MP3_URL, '/') . '/itunes_image.jpg');
            elseif(file_exists(DIR2CAST_BASE() . 'itunes_image.png'))
                define('ITUNES_IMAGE', rtrim(MP3_URL, '/') . '/itunes_image.png');
            else
                define('ITUNES_IMAGE', '');
        }
        
        if(!defined('ITUNES_OWNER_NAME'))
            define('ITUNES_OWNER_NAME', '');
        
        if(!defined('ITUNES_OWNER_EMAIL'))
            define('ITUNES_OWNER_EMAIL', '');
        
        if(!defined('WEBMASTER'))
        {
            if(ITUNES_OWNER_NAME != '' and ITUNES_OWNER_EMAIL != '')
                define('WEBMASTER', ITUNES_OWNER_EMAIL . ' (' . ITUNES_OWNER_NAME . ')');
            else
                define('WEBMASTER', '');
        }
        
        if(!defined('ITUNES_AUTHOR'))
            define('ITUNES_AUTHOR', WEBMASTER);
        
        if(!defined('ITUNES_CATEGORIES'))
            define('ITUNES_CATEGORIES', '');
        
        if(!defined('ITUNES_EXPLICIT'))
            define('ITUNES_EXPLICIT', '');
            
        if(!defined('LONG_TITLES'))
            define('LONG_TITLES', false);

        if(!defined('ITUNES_SUBTITLE_SUFFIX'))
            define('ITUNES_SUBTITLE_SUFFIX', '');

        if(!defined('DESCRIPTION_SOURCE'))
            define('DESCRIPTION_SOURCE', 'comment');

        if(!defined('RECURSIVE_DIRECTORY_ITERATOR'))
            define('RECURSIVE_DIRECTORY_ITERATOR', false);

        if(!defined('AUTO_SAVE_COVER_ART'))
            define('AUTO_SAVE_COVER_ART', true);

        if(!defined('DONT_UNCACHE_IF_OUTPUT_FILE'))
            define('DONT_UNCACHE_IF_OUTPUT_FILE', false);

        if(!defined('MIN_FILE_AGE'))
            define('MIN_FILE_AGE', 30);

        if(!defined('DEBUG'))
            define('DEBUG', false);

        if(!defined('CLOCK_OFFSET'))
            define('CLOCK_OFFSET', 0);

        // Set up factory settings for Podcast subclasses
        Dir_Podcast::$EMPTY_PODCAST_IS_ERROR = !defined('CLI_ONLY');
        Dir_Podcast::$RECURSIVE_DIRECTORY_ITERATOR = RECURSIVE_DIRECTORY_ITERATOR;
        Dir_Podcast::$ITEM_COUNT = ITEM_COUNT;
        Dir_Podcast::$MIN_FILE_AGE = MIN_FILE_AGE;
        Dir_Podcast::$DEBUG = DEBUG;
        Cached_Dir_Podcast::$MIN_CACHE_TIME = MIN_CACHE_TIME;
        getID3_Podcast_Helper::$AUTO_SAVE_COVER_ART = AUTO_SAVE_COVER_ART;
        iTunes_Podcast_Helper::$ITUNES_SUBTITLE_SUFFIX = ITUNES_SUBTITLE_SUFFIX;

        // Set up up factory settings for RSS Items
        RSS_File_Item::$FILES_URL = MP3_URL; // TODO: rename this to MEDIA_URL
        RSS_File_Item::$FILES_DIR = MP3_DIR(); // TODO: rename this to MEDIA_DIR
        Media_RSS_Item::$LONG_TITLES = LONG_TITLES;
        Media_RSS_Item::$DESCRIPTION_SOURCE = DESCRIPTION_SOURCE;
    }
    
    public static function load_from_ini($file)
    {
        ErrorHandler::prime('ini');
        $settings = parse_ini_file($file); 
        ErrorHandler::defuse();
        
        self::$settings_cache = array_merge(self::$settings_cache, $settings);
    }
    
    public static function finalize($setting_names=null)
    {
        if(is_array($setting_names))
            // define only those listed
            foreach($setting_names as $s)
                !defined($s) and 
                    isset(self::$settings_cache[$s]) and
                        define($s, self::$settings_cache[$s]);
        else
        {
            // define all
            foreach(self::$settings_cache as $s => $s_val)
                if(!defined($s)) 
                    define($s, $s_val);
        }
    }
}

class SerializationException extends Exception {}

class Dispatcher
{
    protected $podcast;

    public function __construct(Locking_Cached_Dir_Podcast $podcast)
    {
        $this->podcast = $podcast;
    }

    public function uncache_if_forced($force_password, $get)
    {
        if( strlen($force_password) && isset($get['force']) && $force_password == $get['force'] )
        {
            $this->podcast->uncache();
        }
    }

    public function uncache_if_output_file()
    {
        if(defined('OUTPUT_FILE') && !DONT_UNCACHE_IF_OUTPUT_FILE)
        {
            $this->podcast->uncache();
        }
    }

    public function update_mtime_if_dir2cast_or_settings_modified()
    {
        // Ensure that the cache is invalidated if we have updated dir2cast.php or dir2cast.ini
        // n.b. this doesn't uncache individual media file caches, but also they have a versioning mechanism.
        $this->podcast->updateMaxMtime(filemtime(__FILE__), __FILE__);
        if(defined('INI_FILE'))
            $this->podcast->updateMaxMtime(filemtime(INI_FILE), INI_FILE);
    }

    public function update_mtime_if_metadata_files_modified()
    {
        // Ensure that the cache is invalidated if we have updated any of non-episode files used for feed metadata
        $metadata_files = array(
            'description.txt',
            'itunes_summary.txt',
            'itunes_subtitle.txt',
            'image.jpg',
            'image.png',
            'itunes_image.jpg',
            'itunes_image.png',
        );
        foreach($metadata_files as $file)
        {
            $filepath = MP3_DIR() . $file;
            if(!file_exists($filepath))
            {
                $filepath = DIR2CAST_BASE() . $file;
            }
            if(!file_exists($filepath))
            {
                continue;
            }

            $this->podcast->updateMaxMtime(filemtime($filepath), $filepath);
        }
    }

    public function init()
    {
        $podcast = $this->podcast;

        $podcast->init(); // checks the cache file, or scans for media folder if the cache is out of date.

        if(!$podcast->isCached())
        {
            $getid3 = $podcast->addHelper(new Caching_getID3_Podcast_Helper(TMP_DIR, new getID3_Podcast_Helper()));
            $atom   = $podcast->addHelper(new Atom_Podcast_Helper());
            $itunes = $podcast->addHelper(new iTunes_Podcast_Helper());

            $podcast->setTitle(TITLE);
            $podcast->setLink(LINK);
            $podcast->setDescription(DESCRIPTION);
            $podcast->setLanguage(LANGUAGE);
            $podcast->setCopyright(COPYRIGHT);
            $podcast->setWebMaster(WEBMASTER);
            $podcast->setTtl(TTL);
            $podcast->setImage(IMAGE);

            $atom->setSelfLink(RSS_LINK);

            $itunes->setSubtitle(ITUNES_SUBTITLE);
            $itunes->setAuthor(ITUNES_AUTHOR);
            $itunes->setSummary(ITUNES_SUMMARY);
            $itunes->setImage(ITUNES_IMAGE);
            $itunes->setExplicit(ITUNES_EXPLICIT);

            $itunes->setOwnerName(ITUNES_OWNER_NAME);
            $itunes->setOwnerEmail(ITUNES_OWNER_EMAIL);

            $itunes->addCategories(ITUNES_CATEGORIES);

            $podcast->setGenerator(GENERATOR);
        }
    }

    public function output()
    {
        $podcast = $this->podcast;
        if(!defined('OUTPUT_FILE'))
        {
            $output = $podcast->generate();
            if(!defined('CLI_ONLY'))
            {
                $podcast->http_headers(strlen($output));
            }
            echo $output;
        }
        else
        {
            echo "Writing RSS to: ". OUTPUT_FILE ."\n";
            $fh = fopen(OUTPUT_FILE, "w");
            fwrite($fh,$podcast->generate());
            fclose($fh);

            $items = $podcast->getItems();
            if(empty($items))
            {
                echo "** Warning: generated podcast found no episodes.\n";
                return -1;
            }
        }
        return 0;
    }
}

/* FUNCTIONS **********************************************/

/**
 * Strips slashes from a string if magic quotes GPC is enabled; otherwise it's a NO-OP.
 *
 * @param string $s
 * @return string un-mangled $s
 */
function magic_stripslashes($s) 
{ 
    if(function_exists('get_magic_quotes_gpc'))
    {
        return get_magic_quotes_gpc() ? stripslashes($s) : $s;
    }
    return $s;
}

/**
 * Filters a path so that it is not absolute and contains no ".." components.
 * 
 * @param string the path to filter
 * @return string filtered path
 * 
 */
function safe_path($p)
{
    return preg_replace('#(?<=^|/)(?:\.\.(?:/|$)|/)#', '', $p);
}

/**
 * https://stackoverflow.com/questions/12229572/php-generated-xml-shows-invalid-char-value-27-message
 * https://github.com/ben-xo/dir2cast/issues/35
 * 
 * Not all valid UTF-8 characters are valid XML characters. In particular, legacy ASCII control codes
 * (such as field separators) are valid UTF-8 but not valid XML. We strip them from the text when 
 * rendering the XML.
 * 
 * 
 * @param string text that will go in an XML document
 * @return string safer text that will go in an XML
 */
function utf8_for_xml($s)
{
    return preg_replace('/[^\x{0009}\x{000a}\x{000d}\x{0020}-\x{D7FF}\x{E000}-\x{FFFD}]+/u', '', $s);
}

function slashdir($dir)
{
    return rtrim($dir, '/') . '/';
}

function DIR2CAST_BASE() {
    return slashdir(DIR2CAST_BASE);
}

function MP3_BASE() {
    return slashdir(MP3_BASE);
}

function MP3_DIR() {
    return slashdir(MP3_DIR);
}

/* DISPATCH *********************************************/

function main($args)
{
    SettingsHandler::bootstrap(
        empty($_SERVER) ? array() : $_SERVER, 
        empty($_GET) ? array() : $_GET, 
        empty($args) ? array() : $args 
    );

    SettingsHandler::defaults(
        empty($_SERVER) ? array() : $_SERVER
    );
    
    $podcast = new Locking_Cached_Dir_Podcast(MP3_DIR(), TMP_DIR);
    $podcast->setClockOffset(CLOCK_OFFSET);
    $dispatcher = new Dispatcher($podcast);

    $dispatcher->uncache_if_forced(FORCE_PASSWORD, $_GET);
    $dispatcher->uncache_if_output_file();
    if(!defined('IGNORE_DIR2CAST_MTIME'))
        $dispatcher->update_mtime_if_dir2cast_or_settings_modified();
    $dispatcher->update_mtime_if_metadata_files_modified();
    $dispatcher->init();
    return $dispatcher->output(); // returns exit code
}

// define NO_DISPATCHER in, say, your test harness
if(!defined('NO_DISPATCHER'))
{
    $args = array();
    if(isset($GLOBALS['argv'])) {
        $args = $argv;
    }
    try
    {
        exit(main($args));
    }
    catch(ExitException $e)
    {
        print($e->getMessage()."\n");
        exit($e->getCode());
    }
}

/* THE END *********************************************/
