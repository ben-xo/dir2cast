dir2cast by Ben XO v1.20 (2019-12-30)
================================================================================

https://github.com/ben-xo/dir2cast/


INTRODUCTION
================================================================================

Hello!

dir2cast is designed to turn a directory of MP3s into a podcast - automatically.
Perfect for, say, radio shows - upload the MP3s to a folder, and use dir2cast.php
as your PodCast URL.

Features:

* For 99% of things, NO CONFIGURATION IS NECESSARY. All you have to do is upload
  dir2cast to your web server, then start uploading media files, and it will
  pick up most of the podcast text straight from the tags in the file (such as
  the artist, title and comment tags.)

* supports MP3, MP4, and M4A files

* dir2cast will automatically use the ID3 fields from your files for the Author, 
  Title, etc. ID3v2 is supported, as are the regular tags found in MP4 and M4A
  files. (Uses getID3, which is bundled with dir2cast.)

* dir2cast will automatically use the cover art embedded in your file as well. 

* The generated feed is cached (in the supplied 'temp' folder, or anywhere else
  that you want) and only updated if something in the directory changes - so
  the feed will load fast and put minimal strain on your web server. It only
  regenerates the feed when a new episode is uploaded.

* Comprehensive support for iTunes podcast tags.

* iTunes 'image' supported: just drop a file called itunes_image.jpg in the same
  folder as your media files.

* RSS Description, iTunes Subtitle and iTunes Summary can be set by dropping
  files named description.txt, itunes_subtitle.txt and itunes_summary.txt 
  in the same folder as dir2cast.php - but they are not required. (You can
  also set these in the config).

* You can set a per-file iTunes Summary by creating a text file with the same
  name as the media file (e.g. for file.mp3, create file.txt).


QUICK HOW TO GUIDES
================================================================================

Here are links to a couple of guides on how to set up a podcast, using dir2cast.
Thanks to the people who wrote these guides!

* https://sys.re/files/itunes/ (by nilicule)
* https://www.reddit.com/r/selfhosted/comments/ae37kf/ (by u/wagesj45)


REQUIREMENTS
================================================================================

dir2cast requires PHP 5.3. It requires the XML extension to be enabled.

dir2cast makes use of getID3 by James Heinrich & Allan Hansen, although it does
not require the whole thing. A cut down version of getID3 is supplied at
https://github.com/ben-xo/dir2cast/. You will need to download this and 
install it with dir2cast.php. The full version of getID3 is available at 
http://getid3.sourceforge.net/ .


INSTALLATION
================================================================================

Please note: the config file will make more sense if you read all of this README
before trying the installation instructions.

dir2cast is quite flexible but the general idea is that you add cover art and
tags to your media files - mp3, mp4 or m4a currently supported - and then the
podcast that it generates uses the tags from your files.

Step 1. Edit dir2cast.ini to your taste.
Step 2. Upload dir2cast.php and dir2cast.ini to the web server.
Step 3. Upload getID3 to a folder called 'getID3'. (You can download getID3 from
        the same place as dir2cast.)
Step 4. Upload a media file to the same folder as dir2cast.php
Step 5. Go to the URL for dir2cast.php - e.g. http://example.com/dir2cast.php
Step 6. This is your podcast! Check it's valid at https://podba.se/validate/
        You may need to edit dir2cast.ini some more to get the text you want.
        The generated feed is cached. It will regenerate if you add a new media
        file, but if you want to force a regeneration delete the files from 
        the "temp" folder that is created.


TIPS
================================================================================


CASTING SEVERAL FOLDERS FROM ONE DIR2CAST.PHP
--------------------------------------------------------------------------------

If you have more than one folder of MP3s that you are casting, you can serve 
them all from a single install of dir2cast.php, and customise dir2cast.ini for 
each individual folder.

Assuming the following:
* your web root folder is called htdocs/ and this maps to http://www.mysite.com/
* you installed dir2cast to the folder htdocs/dir2cast 
* you have two podcasts, and the MP3s live in htdocs/dir2cast/cast1 and 
  htdocs/dir2cast/cast2

Step 1: Make 2 extra copies of dir2cast.ini (one for each cast), and then edit 
        to taste. (Any settings not specified will be taken from the main 
        dir2cast.ini - the one that is in the same folder as dir2cast.php).
Step 2: Upload these additional dir2cast.ini files to the htdocs/dir2cast/cast1/ 
        and htdocs/dir2cast/cast2/ folders, respectively.

The podcast URLs will now be:

http://www.mysite.com/dir2cast/dir2cast.php?dir=cast1 and
http://www.mysite.com/dir2cast/dir2cast.php?dir=cast2 .


"PRETTY" URLS FOR YOUR PODCASTS
--------------------------------------------------------------------------------

This hint requires your web server to be Apache with 'mod_rewrite' enabled.

From the example above, your podcast URL will be:

    http://www.mysite.com/dir2cast/dir2cast.php?dir=cast1

...but much nicer would be something along the lines of

    http://www.mysite.com/dir2cast/cast1/rss

To achieve this, you must configure apache with a rewrite rule such as: 

  RewriteEngine on
  RewriteRule (.+)/rss$ dir2cast.php?dir=$1 [L]

Put this in your VHOST configuration (inside a <Location> block) or in a 
.htaccess file alongside dir2cast.php .

PLEASE NOTE: just to check that you understand this section... 
* If you use the RewriteRule supplied, dir2cast.php must be in the folder above
  the MP3 folders. (If this is not the case, you will have to set MP3_BASE in 
  the ini file, and change the rule for your circumstance.)


COPYRIGHT & LICENSE
================================================================================

Copyright (c) 2008, Ben XO (me@ben-xo.com).

The software is released under the BSD License.

All rights reserved.

Redistribution and use in source and binary forms, with or without modification,
are permitted provided that the following conditions are met:

    * Redistributions of source code must retain the above copyright notice, 
      this list of conditions and the following disclaimer.
    * Redistributions in binary form must reproduce the above copyright notice, 
      this list of conditions and the following disclaimer in the documentation
      and/or other materials provided with the distribution.
    * Neither the name of dir2cast nor the names of its contributors
      may be used to endorse or promote products derived from this software 
      without specific prior written permission.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
"AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR
CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL,
EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO,
PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR
PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF
LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.


HISTORY
================================================================================

1.20 2019-12-30 Cache files per media-file, which speeds up large podcasts
                Upgrade bundled getID3 with better MP4 support
                Support for <itunes:explicit> tag
                General speed improvements
1.19 2019-12-21 Extract cover art from MP4 and M4A files.
1.18 2019-12-21 Bugfix for image URLs. Better CLI options.
1.17 2019-12-21 Automatically extract cover art from the files
1.16 2019-12-16 Other fixes for PHP7.x
1.15 2019-12-16 Other fixes for PHP7.x
1.15 2019-12-15 Upgrade getID3 to 1.9.18-201911300717 - fixes PHP7.4 support
1.14 2019-12-12 Support for mp4 videos
1.13 2019-12-11 Suppress deprecation warnings, as both dir2cast and getID3
                trigger the error handler on newer PHPs
1.12 2018-03-22 Switched to de-facto text/xml content-type
                Suggested by RobertBozic
1.11 2018-02-28 Added charset=utf-8 to the Content-type header.
                Suggested by RobertBozic
1.10 2018-01-21 Added RECURSIVE_DIRECTORY_ITERATOR option for scanning nested
                folders. Suggested by ognjiscar
1.9.1 18-01-21 Small PHP7 fix.
1.9 2017-12-27 Merged changes from cbz:
               * Upgraded getID3
               * Added support for M4A files
1.8 2016-07-04 Merged changes from Jeff Bearer:
               * Added support for PNG podcast cover art
               * create tempdir if it does not already exist
               * support for item specific images
               * support for writing output to an RSS file
               * directory specific MP3_URL
               * customizable content type
               * add option to have <description> populated by description.txt
1.7.2 14-03-06 Update homepage information in file to the GitHub.
1.7.1 11-02-17 Remove unused mpc_old code from getID3, which false-positived
               on one of my MP3s.
1.7 2010-12-10 <itunes:summary> is now excluded if it's not explicitly set, as
               iTunes will happily fall back to the <description> and there's
               no point duplicating this. Changed <itunes:subtitle> to pull from
               ID3 Artist field, rather than ID3 Album field, as this is more
               useful for me. <itunes:subtitle> can now be set with a file in
               the same was as <itunes:summary>. Also added new INI parameter
               ITUNES_SUBTITLE_SUFFIX, which is appended to the subtitle of 
               every item. I suggest using this for an appropriate 'Click here
               for more info!' message, to lead people to the description or
               summary.
1.6 2010-11-27 Fix bug including summary info from either ID3 album field
               or filename.txt reported by Nilicule. Thanks!
1.5 2010-07-31 Add optional RSS <image> tag. This is not the same as the 
               <itunes:image> tag, as it has size restrictions. 
1.4 2010-03-10 Make <description> in a CDATA section
1.3 2009-05-28 Fixed nasty regeneration bug where no items in the feed would
               get any metadata after adding a file to the feed. The workaround
               was to clear the cache, but this update fixes it. Also, make
               the feed more robust against getID3's handling of broken MP3s.
1.2 2009-05-04 Changed the contents of the <title> tag per item. Added a new 
               configuration option LONG_TITLE to enable the old behaviour.
               Added new URL parameter ?force=password to enable clearing of
               the cache if, for some reason, the URL is not generated
               correctly.
1.1 2009-04-23 Fix an error in the default dir2cast.ini
1.0 2008-03-17 Fixed a couple of bugs with incomplete ID3 tags.
               The most-common case, that of hitting the cache, has been 
               streamlined so that it doesn't hit the defaults-setup code
               or parse the .ini files unless necessary.
0.9 2008-03-02 Added .ini file configuration, added MP3_BASE option.
0.8 2008-02-27 Fixed ?dir= so it works intuitively, and much more safely.
0.7 2008-02-25 W3 feed validator conformance fixes and fixed itunes:image.
0.6 2008-02-25 Updated the licensing information.
0.5 2008-02-25 Fixed losing itunes item tags when a dir changes.
0.4 2008-02-24 Added a whole error handler with pretty errors
0.3 2008-02-24 Fixed the auto URL detection code. Made the config clearer.
               Renamed some of the config parameters for clarity.
0.2 2008-02-23 Fixed bug with missing '/' and wrong urlencoding on spaces.
0.1 2008-02-22 Initial Release
