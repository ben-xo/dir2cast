[![Testing dir2cast](https://github.com/ben-xo/dir2cast/actions/workflows/testing.yml/badge.svg)](https://github.com/ben-xo/dir2cast/actions/workflows/testing.yml)


dir2cast by Ben XO v1.38 (2023-01-05)
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

* supports MP3, MP4, M4A and M4B files

* dir2cast will automatically use the ID3 fields from your files for the Author,
  Title, etc. ID3v2 is supported, as are the usual tags found in MP4 / M4A / M4B
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

* You can deploy a container the babyraptor/dir2cast Docker image


QUICK HOW TO GUIDES
================================================================================

Here are links to a couple of guides on how to set up a podcast, using dir2cast.
Thanks to the people who wrote these guides!

* https://sys.re/files/itunes/ (by nilicule)
* https://www.reddit.com/r/selfhosted/comments/ae37kf/ (by u/wagesj45)


REQUIREMENTS
================================================================================

dir2cast requires PHP 5.3 minimum. It requires the XML extension to be enabled.

It has been tested up to PHP 8. Please file a bug if you find any PHP version
specific problems: https://github.com/ben-xo/dir2cast/issues

dir2cast makes use of getID3 by James Heinrich & Allan Hansen, although it does
not require the whole thing. A cut down version of getID3 is supplied at
https://github.com/ben-xo/dir2cast/. You will need to download this and 
install it with dir2cast.php. The full version of getID3 is available at 
http://getid3.sourceforge.net/ .


INSTALLATION
================================================================================

Please note: the config file will make more sense if you read all of this `README`
before trying the installation instructions.

dir2cast is quite flexible but the general idea is that you add cover art and
tags to your media files (mp3, mp4, m4a and m4b currently supported) and then the
podcast that it generates uses the tags from your files.

1. Edit `dir2cast.ini` to your taste.
2. Upload `dir2cast.php` and `dir2cast.ini` to the web server.
3. Upload `getID3` to a folder called '`getID3`'. (You can download getID3 from
   the same place as dir2cast.)
4. Upload a media file to the same folder as `dir2cast.php`
5. Go to the URL for `dir2cast.php `- e.g. http://example.com/dir2cast.php
6. This is your podcast! Check it's valid at https://podba.se/validate/
   You may need to edit dir2cast.ini some more to get the text you want.
   The generated feed is cached. It will regenerate if you add a new media
   file, but if you want to force a regeneration delete the files from 
   the "`temp`" folder that is created.


DOCKER SETUP
================================================================================
Note: I did not create the image that I've deployed (project is in the works there), so this is a product of trial and error. Similarly, I do not use docker-compose but the "create container" tool in portainer so keep in mind these are more a guide than an exact recipe.

Local setup
1. Make sure to pull the image, even with the weird tag babyraptor/dir2cast:69adefa
2. Map the container's port 80 to whatever port you want to access it with on your local network
3. Create a volume to map with /var/www/html
4. If desired, you can bind the /var/www/html/<episode folder> to a location on some shared drive that you drop files into from your PC instead of in a docker volume
5. Your podcast feed should now exist at <docker ip>:<container port>/dir2cast.php


Remote w SWAG
1. Install SWAG and test that you can remotely access your docker server https://docs.linuxserver.io/general/swag
2. Create a <name>.subdomain.conf file for your podcast server container (again, linuxserver) with the container name and internal port (not the port you use locally to hit the host)
3. Add your dir2cast container to the network that you created with SWAG
4. Check that podcasts can be played/downloaded. If your feed is exists but files aren't available, update MP3_URL in the dir2cast.ini file for https (see comment in that file)


UNDERSTANDING HOW THE CACHING WORKS
================================================================================

dir2cast caches the feed so that it only has to renegerate the content when
something changes. It does this by looking at the file-modification times of
the media content, and of `dir2cast.php` and `dir2cast.ini`.

* The feed will be updated no more than once every 5 seconds (`MIN_CACHE_TIME`)
  This is to prevent high load if the feed is hammered by clients.
* The feed will be updated when a media file that is newer than the cache file
  appears in the folder, as long as the media file was not updated in the last
  30 seconds (`MIN_FILE_AGE`). This is so that it doesn't accidentally include
  files which are still being uploaded.
* Empty media files are ignored (nobody enjoys listening to them anyway)
* The feed will be updated when `dir2cast.php` or `dir2cast.ini` are newer
  than the cache file as a convenience when upgrading.

**Notes**:
* Files in the feed appear in order of their modification times (most recent
  first). This is usually the order in which you copy them into the folder, but
  some methods of copying preserve the original times from your upload source.
  If you aren't seeing what you expect, check that these times are in the right
  order. On Linux, you can use the command `touch` to update the modification
  time of a file.
* Extra files (such as the images and .txt descriptions) are NOT checked. If you
  modify these and need to refresh the feed, either delete the cache folder or
  use the `?force=<password>` URL (see `dir2cast.ini` `FORCE_PASSWORD`)


TIPS
================================================================================


CASTING SEVERAL FOLDERS FROM ONE DIR2CAST.PHP
--------------------------------------------------------------------------------

If you have more than one folder of MP3s that you are casting, you can serve
them all from a single install of dir2cast.php, and customise dir2cast.ini for
each individual folder.

Assuming the following:
* your web root folder is called `htdocs/` and this maps to http://www.mysite.com/
* you installed dir2cast to the folder `htdocs/dir2cast`
* you have two podcasts, and the MP3s live in `htdocs/dir2cast/cast1` and
  `htdocs/dir2cast/cast2`

1. Make 2 extra copies of `dir2cast.ini` (one for each cast), and then edit
   to taste. (Any settings not specified will be taken from the main
   `dir2cast.ini` - the one that is in the same folder as `dir2cast.php`).
2. Upload these additional dir2cast.ini files to the `htdocs/dir2cast/cast1/`
   and `htdocs/dir2cast/cast2/` folders, respectively.

The podcast URLs will now be:

http://www.mysite.com/dir2cast/dir2cast.php?dir=cast1 and
http://www.mysite.com/dir2cast/dir2cast.php?dir=cast2 .


"PRETTY" URLS FOR YOUR PODCASTS
--------------------------------------------------------------------------------

### If Your Web Server is Apache

I assume you already have PHP working with Apache.

This hint requires your web server to be Apache with '`mod_rewrite`' enabled.

From the example above, your podcast URL will be:

    http://www.mysite.com/dir2cast/dir2cast.php?dir=cast1

...but much nicer would be something along the lines of

    http://www.mysite.com/dir2cast/cast1/rss

To achieve this, you must configure apache with a rewrite rule such as:

    RewriteEngine on
    RewriteRule (.+)/rss$ dir2cast.php?dir=$1 [L]

Put this in your `VHOST` configuration (inside a `<Location>` block) or in a
`.htaccess` file alongside `dir2cast.php` .

PLEASE NOTE: just to check that you understand this section... 
* If you use the `RewriteRule` supplied, `dir2cast.php` must be in the folder above
  the MP3 folders. (If this is not the case, you will have to set MP3_BASE in
  the ini file, and change the rule for your circumstance.)


### If Your Web Server is NGINX

I assume you already have PHP working on NGINX

From the example above, your podcast URL would be:

    http://www.mysite.com/dir2cast/dir2cast.php?dir=cast1

Add some configuration like the following to your sites conf

    location = /dir2cast/rss {
      rewrite ^(/dir2cast)/rss $1/dir2cast.php last;
    }

This assumes the URL you want is /dir2cast/rss, and that the URL of dir2cast.php
is /dir2cast/dir2cast.php


TESTING
================================================================================

To run the unit tests:
1. make sure you have xdebug installed. (`pecl install xdebug`)
2. make sure you have composer installed. (`brew install composer # (or similar)`)
3. `cd test && composer install`
4. `./run.sh`


COPYRIGHT & LICENSE
================================================================================

Copyright (c) 2008-2021, Ben XO (me@ben-xo.com).

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

See CHANGELOG.txt
