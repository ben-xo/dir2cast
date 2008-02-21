<?php

/*
 * 2008 Ben XO (me@ben-xo.com). Released as freeware.
 */

/*********************** SETTINGS ***********************/


/*********************** DEFAULTS ***********************/

if(!defined('TMPDIR'))
{
	define('TMPDIR', dirname(__FILE__) . '/temp');
}

/*********************** DISPATCH ***********************/

$dir = magic_stripslashes($_GET['dir']);
$podcast = new Cached_Dir_Podcast($dir, TMPDIR);
$podcast->http_headers();
echo $podcast->generate();

exit();
/*********************** CLASSES ***********************/

abstract class GetterSetter {
	
	/**
	 * Missing Method Magic Accessor
	 *
	 * @param string $method Method to call (get* or set*)
	 * @param array $params array of parameters for the method 
	 * @return mixed the result of the method
	 */
	public function __call($method, $params)
	{
		switch(strtolower(substr($method, 0, 3)))
		{
			case 'get':
				$var_name = substr($method, 3);
				if(isset($this->$var_name))
					return $this->$var_name;
				break;
				
			case 'set':
				$this->$var_name = $params[0];
				break;
		}
	}	
}

class RSS_Item extends GetterSetter {
	
}

class Podcast extends GetterSetter
{
	protected $title;
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

		return $doc->saveXML();
	}
	
	public function getItems()
	{
//		return $this->items;
		$items = array(
			new RSS_Item(1),
			new RSS_Item(2)
		);
		return $items;
	}
	
	protected function pre_generate() {	}
	protected function post_generate() { }

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
		
		$this->temp_file = rtrim($temp_dir, '/') . '/' . md5($source_dir) . '_' . $safe_source_dir . 'rss';
		
		parent::__construct($source_dir);
	}
	

}


/*********************** FUNCTIONS ***********************/

/**
 * Utility wrapper for htmlspecialchars()
 *
 * @param string $s
 * @return string htmlspecialchars($s);
 */
function h($s) 
{ 
	return htmlspecialchars($s); 
}

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
