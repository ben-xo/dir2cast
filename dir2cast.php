<?php

/******************************************************************************
 * Copyright (c) 2008-2010, Ben XO (me@ben-xo.com).
 *
 * All rights reserved.
 * 
 * Redistribution and use in source and binary forms, with or without 
 * modification, are permitted provided that the following conditions are met:
 * 
 *  * Redistributions of source code must retain the above copyright notice, 
 *	this list of conditions and the following disclaimer.
 *  * Redistributions in binary form must reproduce the above copyright notice, 
 *	this list of conditions and the following disclaimer in the documentation 
 *	and/or other materials provided with the distribution.
 *  * Neither the name of dir2cast nor the names of its contributors may be used 
 *	to endorse or promote products derived from this software without specific 
 *	prior written permission.
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
define('VERSION', '1.7.1');
define('DIR2CAST_HOMEPAGE', 'http://www.ben-xo.com/dir2cast/');
define('GENERATOR', 'dir2cast ' . VERSION . ' by Ben XO (' . DIR2CAST_HOMEPAGE . ')');

error_reporting(E_ALL);
set_error_handler( array('ErrorHandler', 'handle_error') );
set_exception_handler( array( 'ErrorHandler', 'handle_exception') );

// Best do everything in UTC.
date_default_timezone_set( 'UTC' );

/* EXTERNALS ********************************************/

function __autoload($class_name) 
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
			require_once $class_name . '.php';
	}
}

/* CLASSES **********************************************/

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
		$var_name{0} = strtolower($var_name{0});
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
	public function appendToChannel(DOMElement $d, DOMDocument $doc);
	public function appendToItem(DOMElement $d, DOMDocument $doc, RSS_Item $item);
	public function addNamespaceTo(DOMElement $d, DOMDocument $doc);
}

/**
 * Uses external getID3 lib to analyze MP3 files.
 *
 */
class getID3_Podcast_Helper implements Podcast_Helper {
	
	/**
	 * getID3 analyzer
	 *
	 * @var getid3
	 */
	protected $getid3;
	
	public function appendToChannel(DOMElement $d, DOMDocument $doc) { /* nothing */ }
	public function addNamespaceTo(DOMElement $d, DOMDocument $doc) { /* nothing */ }

	/**
	 * Fills in a bunch of info on the Item by using getid3->Analyze()
	 */
	public function appendToItem(DOMElement $d, DOMDocument $doc, RSS_Item $item)
	{
		if($item instanceof MP3_RSS_Item && !$item->getAnalyzed())
		{
			if(!isset($this->getid3))
			{
				$this->getid3 = new getid3();
				$this->getid3->option_tag_lyrics3 = false;
				$this->getid3->option_tag_apetag = false;
				$this->getid3->encoding = 'UTF-8';
			}
			
			try
			{
				$info = $this->getid3->Analyze($item->getFilename());
			}
			catch(getid3_exception $e)
			{
				// MP3 couldn't be analyzed.
				return;
			}
			
			if(!empty($info['bitrate']))
				$item->setBitrate($info['bitrate']);

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
			}
			
			if(!empty($info['playtime_string']))
				$item->setDuration( $info['playtime_string'] );
			
			$item->setAnalyzed(true);
			unset($this->getid3);
		}
	}
}

class Atom_Podcast_Helper extends GetterSetter implements Podcast_Helper {
	
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
				->appendChild( new DOMText($val)	);
		}
		
		if(!empty($this->self_link))
		{
			$linkNode = $channel->appendChild( $doc->createElement('atom:link') );
			$linkNode->setAttribute('href', $this->self_link);
			$linkNode->setAttribute('rel', 'self');
			$linkNode->setAttribute('type', 'application/rss+xml');
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
	
	protected $owner_name, $owner_email, $image_href;
	
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
				->appendChild( new DOMText($val)	);
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
		
		if(!empty($this->image_href))
		{
			$channel->appendChild( $doc->createElement('itunes:image') )
				->setAttribute('href', $this->image_href);
		}
	}
	
	public function appendToItem(DOMElement $item_element, DOMDocument $doc, RSS_Item $item)
	{
		/*
		 * 	<itunes:author>John Doe</itunes:author>
		 *	<itunes:duration>7:04</itunes:duration>
		 *	<itunes:subtitle>A short primer on table spices</itunes:subtitle>
		 *	<itunes:summary>This week we talk about salt and pepper shakers, 
		 *				  [...] Come and join the party!</itunes:summary>
		 *	<itunes:keywords>salt, pepper, shaker, exciting</itunes:keywords>
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
			$elements['subtitle'] = $itunes_subtitle . ITUNES_SUBTITLE_SUFFIX;
		}
				
		foreach($elements as $key => $val)
			if(!empty($val))
				 $item_element->appendChild( $doc->createElement('itunes:' . $key) )
					->appendChild( new DOMText($val) );
	}
	
	public function appendCategory($category, $subcats, DOMElement $e, DOMDocument $doc)
	{
		$e->appendChild( $doc->createElement('itunes:category') )
			->setAttribute('text', $category);
			
		if(is_array($subcats)) 
			foreach($subcats as $subcategory => $subsubcats)
				$this->appendCategory($subcategory, $subsubcats, $element, $doc);
	}
	
	/**
	 * Takes a category specification string and arrayifies it.
	 * e.g.  
	 * 'Music | Technology > Gadgets '
	 * becomes
	 * array( 'Music' => true, 'Technology' => array( 'Gadgets' ) );
	 * @param string $category_string
	 */
	public function addCategories($category_string) {
		$categories = array();
		foreach(explode('|', $category_string) as $top_level_category)
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
			$item_element->appendChild( new DOMElement($name) )
				->appendChild( $doc->createCDATASection(
					// Encode the text but reintroduce newlines as <br />. 
					// Helps with most RSS readers, as this is usually parsed as HTML
					nl2br(htmlspecialchars($val))) 
				  );
		}
		
		$enclosure = $item_element->appendChild(new DOMElement('enclosure'));
		$enclosure->setAttribute('url', $this->getLink());
		$enclosure->setAttribute('length', $this->getLength());
		$enclosure->setAttribute('type', $this->getType());
	}
	
	public function addHelper(Podcast_Helper $helper)
	{
		$this->helpers[] = $helper;
		return $helper;
	}
}

class RSS_File_Item extends RSS_Item {
	
	protected $filename;
	protected $extension;
	
	public function __construct($filename)
	{
		$this->filename = $filename;
		$this->extension = 
		$this->setLinkFromFilename($filename);
		parent::__construct();
	}
	
	public function setLinkFromFilename($filename)
	{
		$url = rtrim(MP3_URL, '/') . '/' . rawurlencode(basename($filename));
		$this->setLink($url);
	}
	
	public function getType()
	{
		return 'application/octet-stream';
	}
	
	public function getFilename()
	{
		return $this->filename;
	}
	
	public function getExtension()
	{
		if(!isset($this->extension))
		{
			$pos = strrpos($this->getFilename(), '.');
			if($pos !== false)
			{
				$this->extension = substr($this->getFilename(), $pos + 1);
			}
			else
			{
				$this->extension = '';
			}
		}
		return $this->extension;
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
		$summary_file_name = dirname($this->getFilename()) . '/' . basename($this->getFilename(), '.' . $this->getExtension()) . '.txt';
		if(file_exists( $summary_file_name ))
			return file_get_contents($summary_file_name);
	}

	/**
	 * Place a file with the same name but .txt instead of .<whatever> and the contents will be used
	 * as the subtitle for the item in the podcast.
	 * 
	 * The subtitle appears inline with the podcast item in iTunes, and has a 'more info' icon next
	 * to it. It should be a single line.
	 *
	 * @return String the subtitle, or null if there's no subtitle file
	 */
	public function getSubtitle()
	{
		$summary_file_name = dirname($this->getFilename()) . '/' . basename($this->getFilename(), '.' . $this->getExtension()) . '_subtitle.txt';
		if(file_exists( $summary_file_name ))
			return file_get_contents($summary_file_name);
	}
}

class MP3_RSS_Item extends RSS_File_Item {
	
	public function __construct($filename)
	{
		$this->setFromMP3File($filename);
		parent::__construct($filename);
	}

	public function setFromMP3File($file)
	{ 
		// don't do any heavy-lifting here as this is called by the constructor, which 
		// is called once for every file in the dir (not just the ITEM_COUNT in the cast) 
		$this->setLength(filesize($file));
		$this->setPubDate(date('r', filemtime($file)));
	}
	
	public function getTitle()
	{
		$title_parts = array();
		if(LONG_TITLES)
		{
			if($this->getID3Album()) $title_parts[] = $this->getID3Album();
			if($this->getID3Artist()) $title_parts[] = $this->getID3Artist();
		}
		if($this->getID3Title()) $title_parts[] = $this->getID3Title();
		return implode(' - ', $title_parts);
	}
	
	public function getType()
	{
		return 'audio/mpeg';
	}
	
	public function getDescription()
	{
		return $this->getID3Comment();
	}
	
	public function getSummary()
	{
		$summary = parent::getSummary();
		if(null == $summary && !LONG_TITLES)
		{
			// use description as summary if there's no file-based override
			$summary = $this->getDescription();
		}
		return $summary;
	}

	public function getSubtitle()
	{
		$subtitle = parent::getSubtitle();
		if(null == $subtitle && !LONG_TITLES)
		{
			// use artist as summary if there's no file-based override
			$subtitle = $this->getID3Artist();
		}
		return $subtitle;
	}
}

abstract class Podcast extends GetterSetter
{
	protected $max_mtime = 0;
	protected $items = array();
	protected $helpers = array();
	
	public function addHelper(Podcast_Helper $helper)
	{
		$this->helpers[] = $helper;
		
		// attach helper to items already added.
		// new items will have the helper attacged when they are added.
		foreach($this->items as $item)
			$item->addHelper($helper);
			
		return $helper;
	}
	
//	public function getNSURI()
//	{
//		return 'http://backend.userland.com/RSS2';
//	}
	
	public function http_headers()
	{
		header('Content-type: application/rss+xml');
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
		//	$rss->appendChild( $doc->createAttribute( 'xmlns' ) )
		//		->appendChild( new DOMText( $this->getNSURI() ) );
			
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
			'copyright' => $this->getCopyright(),
			'generator' => $this->getGenerator(),
			'webMaster' => $this->getWebMaster(),
			'ttl' => $this->getTtl()
		);
				
		foreach($channel_elements as $name => $val)
		{
			$channel->appendChild( new DOMElement($name) )
				->appendChild(new DOMText($val));
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
			$doc->saveXML()
		);
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
		if(strlen($image_url))
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
	
	protected function pre_generate() {	}
	protected function post_generate(DOMDocument $doc) { }

}

class Dir_Podcast extends Podcast
{
	protected $source_dir;
	protected $scanned = false;
	protected $unsorted_items = array();
	
	/**
	 * Constructor
	 *
	 * @param string $source_dir
	 */
	public function __construct($source_dir)
	{
		$this->source_dir = $source_dir;
	}

	protected function scan()
	{		
		if(!$this->scanned)
		{
			$this->pre_scan();
			
			// scan the dir

			$di = new DirectoryIterator($this->source_dir);
			
			$item_count = 0;
			foreach($di as $file)
			{
				$item_count = $this->addItem($file->getPath() . '/' . $file->getFileName());
			}
			
			if(0 == $item_count)
				throw new Exception("No Items found in {$this->source_dir}");
					
			$this->scanned = true;
			$this->post_scan();
		}
	}
	
	public function addItem($filename)
	{
		$pos = strrpos($filename, '.');
		if(false === $pos)
			$file_ext = '';
		else
			$file_ext = substr($filename, $pos + 1);

		switch(strtolower($file_ext))
		{
			case 'mp3':
				// skip 0-length mp3 files. getID3 chokes on them.
				if(filesize($filename))
				{
					// one array per mtime, just in case several MP3s share the same mtime.
					$filemtime = filemtime($filename);
					$the_item = new MP3_RSS_Item($filename);
					$this->unsorted_items[$filemtime][] = $the_item;
					if($filemtime > $this->max_mtime)
						$this->max_mtime = $filemtime;
				}
				break;
				
			default:
		}
		
		return count($this->unsorted_items);
	}
	
	protected function pre_generate()
	{
		$this->scan();
		$this->sort();
		
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
				if($i >= ITEM_COUNT)
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
	protected $cache_date;
	protected $serve_from_cache;

	/**
	 * Constructor
	 *
	 * @param string $source_dir
	 * @param string $temp_dir
	 */
	public function __construct($source_dir, $temp_dir)
	{
		$this->temp_dir = $temp_dir;
		$safe_source_dir = str_replace(array('/', '\\'), '_', $source_dir);
		
		// something unique, safe, stable and easily identifiable
		$this->temp_file = rtrim($temp_dir, '/') . '/' . md5($source_dir) . '_' . $safe_source_dir . '.xml';

		parent::__construct($source_dir);

		$this->init();
	}

	/**
	 * Initialises the cache file
	 */
	public function init()
	{ 
		if($this->isCached()) // this call sets $this->serve_from_cache
		{
			$cache_date = filemtime($this->temp_file);

			if( $cache_date < time() - MIN_CACHE_TIME ) 
			{
				$this->scan();
				if( $cache_date < $this->max_mtime || $cache_date < filemtime($this->source_dir))
				{
					$this->uncache();
				}
				else
				{
					$this->renew();
				}
			}
		}
	}
	
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
			$output = file_get_contents($this->temp_file); // serve cached copy
		}
		else
		{
			$output = parent::generate();
			file_put_contents($this->temp_file, $output); // save cached copy
			$this->serve_from_cache = true;
		}
			
		return $output;
	}

	public function getLastBuildDate()
	{
		if(isset($this->cache_date))
			return date('r', $this->cache_date);
		else
			return $this->__call('getLastBuildDate', array());
	}
	
	public function isCached()
	{
		if(!isset($this->serve_from_cache))
			$this->serve_from_cache = file_exists($this->temp_file) && filesize($this->temp_file);
			
		return $this->serve_from_cache;
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
		$this->file_handle = fopen($this->temp_file, 'a');
		if(!flock($this->file_handle, LOCK_EX))
			throw new Exception('Locking cache file failed.');
	}
	
	/**
	 * releaseLock will delete the cache file before unlocking if it's empty.
	 */
	protected function releaseLock()
	{
		if(!$this->serve_from_cache)
			unlink($this->temp_file);
		
		// this releases the lock implicitly
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
	
	public static function handle_exception( Exception $e )
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
			if(ini_get('html_errors'))
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
						<br><br>
						<?php if(!empty(ErrorHandler::$primer)): ?>
							<?php echo self::get_primed_error(ErrorHandler::$primer); ?>
							<br><br>
						<?php endif; ?>
						<div id="additional_error">
							This error occurred on line <?php echo $errline; ?> of <?php echo $errfile; ?>.
						</div>
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
				header("Content-type: text/plain"); // reset the content-type
				echo "Error: $message (on line $errline of $errfile)\n";
				if(!empty(ErrorHandler::$primer))
					echo strip_tags(self::get_primed_error(ErrorHandler::$primer)) . "\n";
			}
			exit(-1);
		}
	}
	
}

class SettingsHandler
{
	private static $settings_cache = array();
	
	/**
	 * This method sets up all app-wide settings that are required at initialization time.
	 * 
	 * @param $SERVER HTTP Server array containing HTTP_HOST, SCRIPT_FILENAME, DOCUMENT_ROOT, HTTPS
	 * @param $GET HTTP GET array
	 * @param $argv command line options array
	 */
	public static function bootstrap(array $SERVER, array $GET, array $argv)
	{
		// If an installation-wide config file exists, load it now.
		// Installation-wide config can contain TMP_DIR, MP3_DIR, MP3_URL and MIN_CACHE_TIME.
		// Anything else it contains will be used as a fall-back if no dir-specific dir2cast.ini exists
		if(file_exists( dirname(__FILE__) . '/dir2cast.ini' ))
		{
			self::load_from_ini(dirname(__FILE__) . '/dir2cast.ini' );
			self::finalize(array('TMP_DIR', 'MP3_BASE', 'MP3_DIR', 'MP3_URL', 'MIN_CACHE_TIME', 'FORCE_PASSWORD'));
		}
		
		if(!defined('TMP_DIR'))
			define('TMP_DIR', dirname(__FILE__) . '/temp');
		
		if(!defined('MP3_BASE'))
		{
			if(!empty($SERVER['HTTP_HOST']))
				define('MP3_BASE', dirname($SERVER['SCRIPT_FILENAME']));
			else
				define('MP3_BASE', dirname(__FILE__));
		}
			
		if(!defined('MP3_DIR'))
		{
			if(!empty($GET['dir']))
				define('MP3_DIR', MP3_BASE . '/' . safe_path(magic_stripslashes($GET['dir'])));
			elseif(!empty($argv[1]) && realpath($argv[1]))
				define('MP3_DIR', realpath($argv[1]));
			else
				define('MP3_DIR', MP3_BASE);
		}
		
		if(!defined('MP3_URL'))
		{
			# This works on the principle that MP3_DIR must be under DOCUMENT_ROOT (otherwise how will you serve the MP3s?)
			# This may fail if MP3_DIR, or one of its parents under DOCUMENT_ROOT, is a symlink. In that case you will have
			# to set this manually.
			
			$path_part = substr(MP3_DIR, strlen($SERVER['DOCUMENT_ROOT']));	
			if(!empty($SERVER['HTTP_HOST']))
				define('MP3_URL', 
					'http' . (!empty($SERVER['HTTPS']) ? 's' : '') . '://' . $SERVER['HTTP_HOST'] . '/' . ltrim( rtrim( $path_part, '/' ) . '/', '/' ));
			else
				define('MP3_URL', 'file://' . MP3_DIR );
		}
		
		if(!defined('MIN_CACHE_TIME'))
			define('MIN_CACHE_TIME', 5);
		
		if(!defined('FORCE_PASSWORD'))
			define('FORCE_PASSWORD', '');
	}
	
	/**
	 * This method sets up all fall-back default instance settings AFTER all .ini files have been loaded.
	 */
	public static function defaults()
	{
		// if an MP3_DIR specific config file exists, load it now, as long as it's not the same file as the global one!
		if( 
			file_exists( MP3_DIR . '/dir2cast.ini' ) and	
			realpath(dirname(__FILE__) . '/dir2cast.ini') != realpath( MP3_DIR . '/dir2cast.ini' ) 
		) {
			self::load_from_ini( MP3_DIR . '/dir2cast.ini' );
		}
		
		self::finalize();
		
		if(!defined('TITLE'))
		{
			if(basename(MP3_DIR))
				define('TITLE', basename(MP3_DIR));
			else
				define('TITLE', 'My First dir2cast Podcast');
		}
		
		if(!defined('LINK'))
		{
			if(!empty($_SERVER['HTTP_HOST']))
				define('LINK', 'http' . (empty($_SERVER['HTTPS']) ? '' : 's') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF']);
			else
				define('LINK', 'http://www.example.com/');
		}

		if(!defined('RSS_LINK'))
		{
			if(!empty($_SERVER['HTTP_HOST']))
				define('RSS_LINK', 'http' . (empty($_SERVER['HTTPS']) ? '' : 's') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF']);
			else
				define('RSS_LINK', 'http://www.example.com/rss');
		}
		
		if(!defined('DESCRIPTION'))
		{
			if(file_exists(MP3_DIR . '/description.txt'))
				define('DESCRIPTION', file_get_contents(MP3_DIR . '/description.txt'));
			elseif(file_exists(dirname(__FILE__) . '/description.txt'))
				define('DESCRIPTION', file_get_contents(dirname(__FILE__) . '/description.txt'));
			else
				define('DESCRIPTION', '');
		}
		
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
			if(file_exists(MP3_DIR . '/itunes_subtitle.txt'))
				define('ITUNES_SUBTITLE', file_get_contents(MP3_DIR . '/itunes_subtitle.txt'));
			elseif(file_exists(dirname(__FILE__) . '/itunes_subtitle.txt'))
				define('ITUNES_SUBTITLE', file_get_contents(dirname(__FILE__) . '/itunes_subtitle.txt'));
			else
				define('ITUNES_SUBTITLE', DESCRIPTION);
		}
		
		if(!defined('ITUNES_SUMMARY'))
		{
			if(file_exists(MP3_DIR . '/itunes_summary.txt'))
				define('ITUNES_SUMMARY', file_get_contents(MP3_DIR . '/itunes_summary.txt'));
			elseif(file_exists(dirname(__FILE__) . '/itunes_summary.txt'))
				define('ITUNES_SUMMARY', file_get_contents(dirname(__FILE__) . '/itunes_summary.txt'));
			else
				define('ITUNES_SUMMARY', DESCRIPTION);
		}

		if(!defined('IMAGE'))
		{
			if(file_exists(rtrim(MP3_DIR, '/') . '/image.jpg'))
				define('IMAGE', rtrim(MP3_URL, '/') . '/image.jpg');
			elseif(file_exists(dirname(__FILE__) . '/image.jpg'))
				define('IMAGE', rtrim(MP3_URL, '/') . '/image.jpg');
			else
				define('IMAGE', '');
		}
		
		if(!defined('ITUNES_IMAGE'))
		{
			if(file_exists(rtrim(MP3_DIR, '/') . '/itunes_image.jpg'))
				define('ITUNES_IMAGE', rtrim(MP3_URL, '/') . '/itunes_image.jpg');
			elseif(file_exists(dirname(__FILE__) . '/itunes_image.jpg'))
				define('ITUNES_IMAGE', rtrim(MP3_URL, '/') . '/itunes_image.jpg');
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
			
		if(!defined('LONG_TITLES'))
			define('LONG_TITLES', false);

		if(!defined('ITUNES_SUBTITLE_SUFFIX'))
			define('ITUNES_SUBTITLE_SUFFIX', '');
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
			// define all
			foreach(self::$settings_cache as $s => $s_val)
				!defined($s) and 
					define($s, $s_val);
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
	return get_magic_quotes_gpc() ? stripslashes($s) : $s;
}

/*
 * Filters a path so that it is not absolute and contains no ".." components.
 * 
 * @param string the path to filter
 */
function safe_path($p)
{
	return preg_replace('#(?<=^|/)(?:\.\.(?:/|$)|/)#', '', $p);
}

/* DISPATCH *********************************************/

// define NO_DISPATCHER in, say, your test harness
if(!defined('NO_DISPATCHER'))
{
	SettingsHandler::bootstrap(
		empty($_SERVER) ? array() : $_SERVER, 
		empty($_GET) ? array() : $_GET, 
		empty($argv) ? array() : $argv 
	);
	
	$podcast = new Locking_Cached_Dir_Podcast(MP3_DIR, TMP_DIR);
	if( strlen(FORCE_PASSWORD) && isset($_GET['force']) && FORCE_PASSWORD == $_GET['force'] )
	{
		$podcast->uncache();	
	}
	
	if(!$podcast->isCached())
	{
		SettingsHandler::defaults();
		
		$getid3 = $podcast->addHelper(new getID3_Podcast_Helper());
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
		
		$itunes->setOwnerName(ITUNES_OWNER_NAME);
		$itunes->setOwnerEmail(ITUNES_OWNER_EMAIL);
		
		$itunes->addCategories(ITUNES_CATEGORIES);
		
		$podcast->setGenerator(GENERATOR);
	}
	
	$podcast->http_headers();
	
	echo $podcast->generate();
}

/* THE END *********************************************/
