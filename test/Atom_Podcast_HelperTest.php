<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class Atom_Podcast_HelperTest extends TestCase
{

    public static function setUpBeforeClass(): void
    {
        define('ATOM_TYPE', 'application/rss+xml');
    }

    public function test_adds_namespace_to_podcast()
    {
        $mp = new MyPodcast();
        $atom = $mp->addHelper(new Atom_Podcast_Helper());
        $content = $mp->generate();

        $this->assertEquals(1, preg_match(
            '#<rss[^>]* xmlns:atom="http://www.w3.org/2005/Atom"[^>]*>#', 
            $content
        ));
    }

    public function test_adds_atom_link_to_channel_if_set()
    {
        $mp = new MyPodcast();
        $atom = $mp->addHelper(new Atom_Podcast_Helper());
        $atom->setSelfLink('https://xo.am/dnb/bd/rss');
        $content = $mp->generate();

        $data = simplexml_load_string($content, 'SimpleXMLElement');
        $link = $data->channel->children('http://www.w3.org/2005/Atom')->link;

        $this->assertEquals('self', $link->attributes()->rel);
        $this->assertEquals('https://xo.am/dnb/bd/rss', $link->attributes()->href);
        $this->assertEquals('application/rss+xml', $link->attributes()->type);
    }

    public function test_adds_no_atom_link_to_channel_if_not_set()
    {
        $mp = new MyPodcast();
        $atom = $mp->addHelper(new Atom_Podcast_Helper());
        $content = $mp->generate();

        $data = simplexml_load_string($content, 'SimpleXMLElement');

        $element_count = 0;
        foreach ($data->channel->children('http://www.w3.org/2005/Atom') as $el) {
            $element_count++;
        }
        $this->assertEquals(0, $element_count);
    }
}
