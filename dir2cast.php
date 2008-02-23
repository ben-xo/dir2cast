<?php

/*
 * Copyright 2008 Ben XO (me@ben-xo.com).
 * 
 * USAGE:
 * 
 * After editing the settings below,
 * 
 * http://www.whatever.com/dir2cast.php
 * 
 * or
 * 
 * http://www.whatever.com/dir2cast.php?dir=subdir 
 * ^-- use this form with mod_rewrite perhaps
 * 
 * or
 * 
 * user$ php ./dir2cast.php somedir 
 * ^-- from the command line
 */

/* SETTINGS *********************************************
 * Most of these have defaults, which you can leave     *
 * commented if you want.                               *
 ********************************************************/


# You should specify the following

# Copyright notice of the feed
# This defaults to this year (e.g. '2008')
define('COPYRIGHT', 'Ben XO (2008)');

# Webmaster of the feed
# This defaults to empty
define('WEBMASTER', 'Ben XO (me@ben-xo.com)');

# URL of the feed's home page
# This defaults to the URL of the script or http://www.example.com/
define('LINK', 'http://www.ben-xo.com/');

# Title of the feed
# This defaults to the name of the directory you're casting
//define('TITLE', 'My First dir2cast Podcast');

# Author of the podcast for iTunes
# This defaults to whatever WEBMASTER is set to
//define('ITUNES_AUTHOR', 'Ben XO');

# Name of the Owner of the podcast for iTunes
# This defaults to whatever WEBMASTER is set to
define('ITUNES_OWNER_NAME', 'Ben XO');

# Email of the Author of the podcast for iTunes
# This defaults to empty
define('ITUNES_OWNER_EMAIL', 'me@ben-xo.com');

# Categories for iTunes
# You may add as many as you like from the category list at 
# http://www.apple.com/itunes/store/podcaststechspecs.html
# This is PHP array syntax - it's easy, but be careful.
# Here's an example:
//$itunes_categories = array(
//  "Music" => true,
//  "Technology" => array( "Gadgets" => true ),
//);    
$itunes_categories = array(
	"Music" => true,
);


# The following attempt to read files named like the define

# Where to serve files from
# This defaults to the directory of the script
# If you run this from the command line, it'll also accept the first parameter
//define('DIR', 'somewhere');

# Description of the feed
# This defaults to empty, or if the file 'description.txt' exists
# in the target dir, or in the same dir as the script, that will be read 
# and the contents used
//define('DESCRIPTION', 'My First Podcast');

# Subtitle of the feed for iTunes
# This defaults to DESCRIPTION, or if the file 'itunes_subtitle.txt' exists
# in the target dir, or in the same dir as the script, that will be read 
# and the contents used
//define('ITUNES_SUBTITLE', 'Check it out! It's brilliant.');

# Subtitle of the feed for iTunes
# This defaults to DESCRIPTION, or if the file 'itunes_summary.txt' exists
# in the target dir, or in the same dir as the script, that will be read 
# and the contents used
//define('ITUNES_SUMMARY', 'i could go on for hours about how amazing this podcast is [...] etc');

# Image for the podcast for iTunes
# This defaults to no image, or if the file 'itunes_image.jpg' exists
# in the target dir, or in the same dir as the script, then the URL for that 
# will be used
//define('ITUNES_IMAGE', 'http://www.somewhere.com/podcast.jpg');


# You should check that the following are OK.

# Where to cache RSS feeds (this must be writable by the web server)
# This defaults to a folder called 'temp' alongside the script
//define('TEMPDIR', '/tmp');

# Language of the feed
# This defaults to en-us (US English)
//define('LANGUAGE', 'en-us');


# The following have sensible defaults and should probably not be changed

# Number of items to show in the feed
# This defaults to 10
//define('ITEM_COUNT', 10);

# Number of seconds for which the cache file is guaranteed valid
# Defaults to 5
//define('MIN_CACHE_TIME', 5);

# Time-to-live (Expiry time) of the feed
# This defaults to 60 minutes
//define('TTL', 60);


/* DEFAULTS *********************************************/

if(!defined('TMPDIR'))
	define('TMPDIR', dirname(__FILE__) . '/temp');

if(!defined('DIR'))
{
	if(!empty($_GET['dir']))
		define('DIR', magic_stripslashes($_GET['dir']));
	elseif(!empty($argv[1]))
		define('DIR', magic_stripslashes($argv[1]));
	else
		define('DIR', dirname(__FILE__));
}	

if(!defined('TITLE'))
{
	if(basename(DIR))
		define('TITLE', basename(DIR));
	else
		define('TITLE', 'My First dir2cast Podcast');
}

if(!defined('LINK'))
{
	if(!empty($_SERVER['HTTP_HOST']))
		define('LINK', 'http' . ($_SERVER['HTTPS'] ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF']);
	else
		define('LINK', 'http://www.example.com/');
}

if(!defined('DESCRIPTION'))
{
	if(file_exists(DIR . '/description.txt'))
		define('DESCRIPTION', file_get_contents(DIR . '/description.txt'));
	elseif(file_exists(dirname(__FILE__) . '/description.txt'))
		define('DESCRIPTION', file_get_contents(dirname(__FILE__) . '/description.txt'));
	else
		define('DESCRIPTION', '');
}

if(!defined('LANGUAGE'))
	define('LANGUAGE', 'en-us');

if(!defined('COPYRIGHT'))
	define('COPYRIGHT', date('Y'));

if(!defined('WEBMASTER'))
	define('WEBMASTER', '');
	
if(!defined('TTL'))
	define('TTL', 60);

if(!defined('URL_BASE'))
{
	if(!empty($_SERVER['HTTP_HOST']))
		define('URL_BASE', 'http' . ($_SERVER['HTTPS'] ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . get_url_path(DIR) );
	else
		define('URL_BASE', 'file://' . rtrim(DIR, '/') . '/' );
}
	
if(!defined('ITEM_COUNT'))
	define('ITEM_COUNT', 10);
	
if(!defined('MIN_CACHE_TIME'))
	define('MIN_CACHE_TIME', 5);
	
if(!defined('ITUNES_SUBTITLE'))
{
	if(file_exists(DIR . '/itunes_subtitle.txt'))
		define('ITUNES_SUBTITLE', file_get_contents(DIR . '/itunes_subtitle.txt'));
	elseif(file_exists(dirname(__FILE__) . '/itunes_subtitle.txt'))
		define('ITUNES_SUBTITLE', file_get_contents(dirname(__FILE__) . '/itunes_subtitle.txt'));
	else
		define('ITUNES_SUBTITLE', DESCRIPTION);
}

if(!defined('ITUNES_SUMMARY'))
{
	if(file_exists(DIR . '/itunes_summary.txt'))
		define('ITUNES_SUMMARY', file_get_contents(DIR . '/itunes_summary.txt'));
	elseif(file_exists(dirname(__FILE__) . '/itunes_summary.txt'))
		define('ITUNES_SUMMARY', file_get_contents(dirname(__FILE__) . '/itunes_summary.txt'));
	else
		define('ITUNES_SUMMARY', DESCRIPTION);
}

if(!defined('ITUNES_IMAGE'))
{
	if(file_exists(rtrim(DIR, '/') . '/itunes_image.jpg'))
		define('ITUNES_IMAGE', rtrim(URL_BASE, '/') . get_url_path(rtrim(DIR, '/') . '/itunes_image.jpg'));
	elseif(file_exists(dirname(__FILE__) . '/itunes_image.jpg'))
		define('ITUNES_IMAGE', rtrim(URL_BASE, '/') . get_url_path(dirname(__FILE__) . '/itunes_image.jpg'));
	else
		define('ITUNES_IMAGE', '');
}


if(!defined('ITUNES_AUTHOR'))
	define('ITUNES_AUTHOR', WEBMASTER);

if(!defined('ITUNES_OWNER_NAME'))
	define('ITUNES_OWNER_NAME', WEBMASTER);

if(!defined('ITUNES_OWNER_EMAIL'))
	define('ITUNES_OWNER_EMAIL', '');
	
define('VERSION', '0.2');

/* EXTENALS *********************************************/

require_once('getID3/getid3.php');

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
 * Uses external getID3 lib to analyse MP3 files.
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

	public function __construct() { 
		$this->getid3 = new getid3();
	}
	
	/**
	 * Fills in a bunch of info on the Item by using getid3->Analyse()
	 */
	public function appendToItem(DOMElement $d, DOMDocument $doc, RSS_Item $item)
	{
		if($item instanceof MP3_RSS_Item && !$item->getAnalyzed())
		{
			try {
				
				@$this->getid3->Analyze($item->getFilename());
				
				$item->setBitrate($this->getid3->info['bitrate']);
				
				$item->setID3Title( $this->getid3->info['comments']['title'][0] );
				$item->setID3Artist( $this->getid3->info['comments']['artist'][0] );
				$item->setID3Album( $this->getid3->info['comments']['album'][0] );					
				$item->setID3Comment( $this->getid3->info['comments']['comment'][0] );
				
				$item->setDuration( $this->getid3->info['playtime_string']);
				
				
				$item->setAnalyzed(true);
				unset($this->getid3->info);
			} 
			catch (Exception $e)
			{
				// oh well! No MP3 info for us, eh?
			}
		}
	}
}

class iTunes_Podcast_Helper extends GetterSetter implements Podcast_Helper {
	
	protected $owner_name, $owner_email;
	
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
	}
	
	public function appendToItem(DOMElement $item_element, DOMDocument $doc, RSS_Item $item)
	{
		/*
		 * 	<itunes:author>John Doe</itunes:author>
		 *	<itunes:duration>7:04</itunes:duration>
		 *	<itunes:subtitle>A short primer on table spices</itunes:subtitle>
		 *	<itunes:summary>This week we talk about salt and pepper shakers, comparing and contrasting pour rates, construction materials, and overall aesthetics. Come and join the party!</itunes:summary>
		 *	<itunes:keywords>salt, pepper, shaker, exciting</itunes:keywords>
		 */

		$elements = array(
			'author' => $item->getID3Artist(),
			'duration' => $item->getDuration(),
			'subtitle' => $item->getID3Comment(),
			'summary' => $item->getSummaryFromFile(),
			//'keywords' => 'not supported yet.'
		);
				
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
	
	public function addCategories($cats) {
		$this->categories = $cats;
	}
	
	public function setOwnerName($name)
	{
		$this->owner_name = $name;
	}

	public function setOwnerEmail($email)
	{
		$this->owner_email = $email;
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
			'description' => $this->getDescription(),
			'pubDate' => $this->getPubDate()
		);
		
		foreach($item_elements as $name => $val)
		{
			$item_element->appendChild( new DOMElement($name) )
				->appendChild(new DOMText($val));
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
		$url = URL_BASE . rawurlencode(basename($filename));
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
		if(empty($this->extension))
			$this->extension = substr($this->getFilename(), strrpos($this->getFilename(), '.') + 1);
		
		return $this->extension;
	}
	
	/**
	 * Place a file with the same name but .txt instead of .<whatever> and the contents will be used
	 * as the summary for the item in the podcast.
	 *
	 * @return String the summary, or null if there's no summary file
	 */
	public function getSummaryFromFile()
	{
		$summary_file_name = dirname($this->getFilename()) . '/' . basename($this->getFilename(), '.' . $this->getExtension()) . '.txt';
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
		$this->setLength(filesize($file));
		$this->setPubDate(date('r', filectime($file)));
    }
    
    public function getTitle()
    {
    	$title_parts = array();
    	if($this->getID3Album()) $title_parts[] = $this->getID3Album();
    	if($this->getID3Artist()) $title_parts[] = $this->getID3Artist();
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
}

abstract class Podcast extends GetterSetter
{
	protected $max_mtime = 0;
	protected $items = array();
	protected $helpers = array();
	
	/**
	 * Constructor
	 */
	public function __construct() {	}
	
	public function addHelper(Podcast_Helper $helper)
	{
		$this->helpers[] = $helper;
		return $helper;
	}
	
	public function getNSURI()
	{
		return 'http://backend.userland.com/rss2';
	}
	
	public function http_headers()
	{
		header('Content-type: text/xml; charset: utf-8');
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
		
		$rss = $doc->createElementNS($this->getNSURI(), 'rss');
		$doc->appendChild($rss);
		
		$rss->setAttribute('version', '2.0');
		
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
		
		foreach($this->helpers as $helper)
		{
			$helper->appendToChannel($channel, $doc);
		}
		
		// channel item list
		foreach($this->getItems() as $item)
		{
			$item->appendToChannel($channel, $doc);
		}

		$this->post_generate($doc);
		
		$doc->normalizeDocument();
		return $doc->saveXML();
	}
	
	public function addItem($filename)
	{
		$file_ext = substr($filename, strrpos($filename, '.') + 1);
		switch(strtolower($file_ext))
		{
			case 'mp3': 
				// one array per mtime, just in case several MP3s share the same mtime.
				$filectime = filectime($filename);
				$the_item = new MP3_RSS_Item($filename);
				$this->unsorted_items[$filectime][] = $the_item;
				if($filectime > $this->max_mtime)
					$this->max_mtime = $filectime;
				
				foreach($this->helpers as $helper)
					$the_item->addHelper($helper);

				break;
			
			default:
		}
	}
	
	/**
	 * Get the final list of items, sorting and limiting as we do so.
	 * You should not addItem() any more items after this.
	 */
	public function getItems()
	{
		if(empty($this->items)) {
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

		return $this->items;
	}
	
	protected function pre_generate() {	}
	protected function post_generate(DOMDocument $doc) { }

}

class Dir_Podcast extends Podcast
{
	protected $source_dir;
	protected $scanned = false;
	
	/**
	 * Constructor
	 *
	 * @param string $source_dir
	 */
	public function __construct($source_dir)
	{
		$this->source_dir = $source_dir;
		parent::__construct();
	}

	protected function scan()
	{
		$this->pre_scan();
		// scan the dir
		$di = new DirectoryIterator($this->source_dir);
		foreach($di as $file)
			$this->addItem($file->getPath() . '/' . $file->getFileName());
		$this->scanned = true;
		$this->post_scan();
	}
	
	protected function pre_scan() { }
	protected function post_scan() { }

	protected function pre_generate()
	{
		if(!$this->scanned)
			$this->scan();
	}
}

/**
 * Podcast with cached output. 
 */
class Cached_Dir_Podcast extends Dir_Podcast
{
	protected $temp_dir;
	protected $temp_file;
	protected $cache_date;

	/**
	 * Constructor
	 *
	 * @param string $source_dir
	 * @param string $temp_dir
	 */
	public function __construct($source_dir, $temp_dir)
	{
		$this->temp_dir = $temp_dir;
		$safe_source_dir = str_replace('/', '_', $source_dir);
		
		// something unique, safe, stable and easily identifiable
		$this->temp_file = rtrim($temp_dir, '/') . '/' . md5($source_dir) . '_' . $safe_source_dir . '.xml';

		parent::__construct($source_dir);

		if(file_exists($this->temp_file))
		{
			$this->cache_date = filectime($this->temp_file);

			if( $this->cache_date <= time() - MIN_CACHE_TIME ) 
			{
				$this->scan();
				if( $this->cache_date <= $this->max_mtime )
				{
					unset($this->cache_date);
					unlink($this->temp_file);
				}
				else
				{
					touch($this->temp_file); // renew cache file life expectancy
				}
			}
		}

	}
	
	public function generate()
	{
		if(isset($this->cache_date))
		{
			return file_get_contents($this->temp_file); // serve cached copy
		}
		
		$output = parent::generate();
		file_put_contents($this->temp_file, $output); // save cached copy
		return $output;
	}

	function getLastBuildDate()
	{
		if(isset($this->cache_date))
			return date('r', $this->cache_date);
		else
			return $this->__call('getLastBuildDate', array());
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

function get_url_path($dir)
{
	// assumes that $dir is under DOCUMENT_ROOT otherwise the results are undefined
	#return '/' . ltrim( substr($dir, strlen($_SERVER['DOCUMENT_ROOT'])), '/' );
	return '/' . ltrim( rtrim(substr($dir, strlen($_SERVER['DOCUMENT_ROOT'])), '/'), '/' ) . '/' ;
}

/* DISPATCH *********************************************/

$podcast = new Cached_Dir_Podcast(DIR, TMPDIR);

$getid3 = $podcast->addHelper(new getID3_Podcast_Helper());
$itunes = $podcast->addHelper(new iTunes_Podcast_Helper());

$podcast->setTitle(TITLE);
$podcast->setLink(LINK);
$podcast->setDescription(DESCRIPTION);
$podcast->setLanguage(LANGUAGE);
$podcast->setCopyright(COPYRIGHT);
$podcast->setWebMaster(WEBMASTER);
$podcast->setTtl(TTL);

$itunes->setSubtitle(ITUNES_SUBTITLE);
$itunes->setAuthor(ITUNES_AUTHOR);
$itunes->setSummary(ITUNES_SUMMARY);
$itunes->setImage(ITUNES_IMAGE);

$itunes->setOwnerName(ITUNES_OWNER_NAME);
$itunes->setOwnerEmail(ITUNES_OWNER_EMAIL);

$itunes->addCategories($itunes_categories);

$podcast->setGenerator('dir2cast ' . VERSION . ' by Ben XO');

$podcast->http_headers();
echo $podcast->generate();

/* THE END *********************************************/
