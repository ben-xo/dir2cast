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

function prepare_testing_dir()
{
    is_dir('./testdir') && rmrf('./testdir');
    mkdir('./testdir');
    symlink('../dir2castWithCoverage.php', './testdir/dir2cast.php');
    // symlink('../../dir2cast.php', './testdir/dir2cast.php');
    symlink('../../getID3', './testdir/getID3');
    chdir('./testdir');
}

// chdir(dirname(__FILE__));
define('NO_DISPATCHER', true);
define('CLI_ONLY', true);

require_once('../dir2cast.php');

// Test classes

class MyPodcast extends Podcast { }

