<?php

/*
 * 2008 Ben XO (me@ben-xo.com). Released as freeware.
 */

/* SETTINGS *********************************************
 * All of these have defaults, so you can leave them    *
 * commented if you want.                               *
 ********************************************************/

# Where to cache RSS feeds
# This defaults to a folder called 'temp' alongside the script
# define('TEMPDIR', '/tmp');

# Where to serve files from
# This defaults to the directory of the script
# define('DIR', 'somewhere');

# Title of the feed
# This defaults to the name of the directory you're casting
# define('TITLE', 'My First dir2cast Podcast');

# URL of the feed's home page
# This defaults to the URL of the script or http://www.example.com/
define('LINK', 'http://www.ben-xo.com/');

# Description of the feed
# This defaults to empty, or if the file 'description.txt' exists
# in the same dir as the script, that will be read and the contents used
# define('DESCRIPTION', 'My First Podcast');

# Language of the feed
# This defaults to en-us (US English)
# define('LANGUAGE', 'en-us');

# Copyright notice of the feed
# This defaults to this year (e.g. '2008')
define('COPYRIGHT', 'Ben XO (2008)');

# Webmaster of the feed
# This defaults to empty
define('WEBMASTER', 'Ben XO (me@ben-xo.com)');

# Time-to-live (Expiry time) of the feed
# This defaults to 60 minutes
# define('TTL', 60);

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
		define('LINK', 'http://' . ($_SERVER['HTTPS'] ? 's' : '') . $_SERVER['PHP_SELF']);
	else
		define('LINK', 'http://www.example.com/');
}

if(!defined('DESCRIPTION'))
{
	if(file_exists(dirname(__FILE__) . '/description.txt'))
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
	
define('VERSION', '0.1');

/* DISPATCH *********************************************/

$podcast = new Cached_Dir_Podcast(DIR, TMPDIR);

$podcast->setTitle(TITLE);
$podcast->setLink(LINK);
$podcast->setDescription(DESCRIPTION);
$podcast->setLanguage(LANGUAGE);
$podcast->setCopyright(COPYRIGHT);
$podcast->setWebMaster(WEBMASTER);
$podcast->setTtl(TTL);

$podcast->setGenerator('dir2cast ' . VERSION . ' by Ben XO');

$podcast->http_headers();
echo $podcast->generate();
exit();

/* CLASSES **********************************************/

abstract class GetterSetter {
	
	var $parameters = array();
	
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
		switch(strtolower(substr($method, 0, 3)))
		{
			case 'get':
				if(isset($this->parameters[$var_name]))
					return $this->parameters[$var_name];
				break;
				
			case 'set':
				$this->parameters[$var_name] = $params[0];
				break;
		}
	}	
}

class RSS_Item extends GetterSetter {
	public function __construct() { }
}

class RSS_File_Item extends RSS_Item {
	
	public function __construct($filename)
	{
		$this->setLinkFromFilename($filename);
		parent::__construct();
	}
	
	public function setLinkFromFilename($filename)
	{
		$url = 'http://whatwhatwhat';
		$this->setLink($url);
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
    	// read the ID3v1 from the MP3 file    	
		$id_start = filesize($file) - 128;
		$fp = fopen($file, 'r');
		fseek($fp, $id_start);
		if ('TAG' == fread($fp,3))
		{
			$this->setID3Title(trim(fread($fp, 30)));
			$this->setID3Artist(trim(fread($fp, 30)));
			$this->setID3Album(trim(fread($fp, 30)));
			$this->setID3Year(trim(fread($fp, 4)));
			$this->setID3Comment(trim(fread($fp, 30)));
			$this->setID3Genre(trim(fread($fp, 1)));
			fclose($fp);
		}
		
		// do the length
		$this->setLength(filesize($file));
		
		$this->setPubDate(date('r', filemtime($file)));
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
	protected $items = array();
	
	/**
	 * Constructor
	 */
	public function __construct() {	}
	
	public function http_headers()
	{
		header('Content-type: text/xml');
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
		
		$doc = new DOMDocument('1.0');
		$doc->formatOutput = true;
		
		$rss = $doc->appendChild(new DOMElement('rss'));
		$rss->setAttribute('version', '2.0');
		
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
			$element = $channel->appendChild(new DOMElement($name));
			$element->appendChild(new DOMText($val));
		}
		
		// channel item list
		foreach($this->getItems() as $item)
		{
			$item_element = $channel->appendChild(new DOMElement('item'));
			
			$item_elements = array(
				'title' => $item->getTitle(),
				'link' => $item->getLink(),
				'description' => $item->getDescription(),
				'pubDate' => $item->getPubDate()
			);
			foreach($item_elements as $name => $val)
			{
				$element = $item_element->appendChild(new DOMElement($name));
				$element->appendChild(new DOMText($val));

			}
			$enclosure = $item_element->appendChild(new DOMElement('enclosure'));
			$enclosure->setAttribute('url', $item->getLink());
			$enclosure->setAttribute('length', $item->getLength());
			$enclosure->setAttribute('type', $item->getType());
		}

		$this->post_generate($doc);
		
		return $doc->saveXML();
	}
	
	public function addItem($filename)
	{
		$file_ext = substr($filename, strrpos($filename, '.') + 1);
		switch(strtolower($file_ext))
		{
			case 'mp3': 
				$this->items[] = new MP3_RSS_Item($filename);
				break;
			
			default:
		}
	}
	
	public function getItems()
	{
		return $this->items;
	}
	
	protected function pre_generate() {	}
	protected function post_generate(DOMDocument $doc) { }

}

class Dir_Podcast extends Podcast
{
	protected $source_dir;
	
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
		$this->post_scan();		
	}
	
	protected function pre_scan() { }
	protected function post_scan() { }
	
	protected function pre_generate()
	{
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
		
		$this->temp_file = rtrim($temp_dir, '/') . '/' . md5($source_dir) . '_' . $safe_source_dir . '.rss';
		
		parent::__construct($source_dir);
	}
	
	public function generate()
	{
		if(file_exists($this->temp_file))
		{
			if(filemtime($this->temp_file) > filemtime($this->source_dir))
			{
				return file_get_contents($this->temp_file);
			}
			else
			{
				unlink($this->temp_file);
			}
		}
		
		$output = parent::generate();
		file_put_contents($this->temp_file, $output);
		return $output;
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
