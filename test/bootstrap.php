<?php

/**
 *  @author      Ben XO (me@ben-xo.com)
 *  @copyright   Copyright (c) 2010 Ben XO
 *  @license     MIT License (http://www.opensource.org/licenses/mit-license.html)
 *  
 *  Permission is hereby granted, free of charge, to any person obtaining a copy
 *  of this software and associated documentation files (the "Software"), to deal
 *  in the Software without restriction, including without limitation the rights
 *  to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 *  copies of the Software, and to permit persons to whom the Software is
 *  furnished to do so, subject to the following conditions:
 *  
 *  The above copyright notice and this permission notice shall be included in
 *  all copies or substantial portions of the Software.
 *  
 *  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 *  IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 *  FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 *  AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 *  LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 *  OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 *  THE SOFTWARE.
 */

error_reporting(E_ALL | E_STRICT);

function rmrf($dir) {
    // modified from https://www.php.net/manual/en/function.rmdir.php
    if(is_dir($dir) && !is_link($dir)) 
    {
        $files = array_diff(scandir($dir), array('.','..'));
        foreach ($files as $file) 
        {
          rmrf("$dir/$file");
        }
        rmdir($dir);
    }
    else
    {
        // base case: is not a dir, or is a dir but is a symlink
        unlink($dir);
    }
}

function age_dir_by($dir_or_file, $seconds)
{
    if(is_dir($dir_or_file) && !is_link($dir_or_file))
    {
        $dir = $dir_or_file;
        $files = array_diff(scandir($dir), array('.','..'));
        foreach ($files as $file) 
        {
          age_dir_by("$dir/$file", $seconds);
        }
    }

    touch($dir_or_file, filemtime($dir_or_file) - $seconds);
    clearstatcache();
}

function prepare_testing_dir()
{
    chdir(dirname(__FILE__));
    is_dir('./testdir') && rmrf('./testdir');
    mkdir('./testdir');
    chdir('./testdir');
    // if(getenv('XDEBUG_MODE'))
    // {
    //     symlink('../dir2castWithCoverage.php', './dir2cast.php');
    //     symlink('../../getID3', './getID3');
    // }
    // else
    // {
        copy('../../dir2cast.php', './dir2cast.php');
        copy('../../dir2cast.ini', './dir2cast.ini');
        $fileSystem = new Symfony\Component\Filesystem\Filesystem();
        $fileSystem->mirror('../../getID3', './getID3');
    // }
}

function temp_xml_glob()
{
    return '.' . DIRECTORY_SEPARATOR . 'temp' . DIRECTORY_SEPARATOR . '*.xml';
}

function escape_single_quoted_string($string)
{
    $string = str_replace('\\', '\\\\', $string);
    $string = str_replace('\'', '\\\'', $string);
    return $string;
}

function fake_getopt_command($argv_in, $short_options, $long_options)
{
    $argv_string = "'" . implode("', '", array_map('escape_single_quoted_string', $argv_in) ). "'";
    $argv_count = count($argv_in);
    $short_options_string = escape_single_quoted_string($short_options);
    $long_options_string = "'" . implode("', '", array_map('escape_single_quoted_string', $long_options) ). "'";

    $command_parts = array(
        'php', '-d', 'register_argc_argv=false', '-r', escapeshellarg(<<<EOSCRIPT
            \$GLOBALS["argv"]=array($argv_string);
            \$GLOBALS["argc"]=$argv_count;
            print(serialize(getopt('$short_options_string', array($long_options_string))));
        EOSCRIPT)
    );
    return implode(" ", $command_parts);
}

/**
 * Dangerous (due to exec()) and unlikely to work properly outside of testing.
 * Needed because getopt() can't have its input mocked without register_argc_argv=false
 */
function fake_getopt($argv_in, $short_options, $long_options)
{
    $command = fake_getopt_command($argv_in, $short_options, $long_options);
    $output = null;
    $result_code = null;
    exec($command, $output, $result_code);
    if(count($output) > 0)
        return unserialize($output[0]);
    return array();
}

define('NO_DISPATCHER', true);

require_once('../dir2cast.php');

// Test classes

class MyPodcast extends Podcast { }

