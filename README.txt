dir2cast by Ben XO v0.6 (2008-02-25)
================================================================================

http://www.ben-xo.com/dir2cast


INTRODUCTION
================================================================================

Hello!

dir2cast is designed to turn a directory of MP3s into a podcast - automatically.
Perfect for, say, radio shows - upload the MP3s to a folder, and use dir2cast.php
as your PodCast URL.

Features:

* For 99% of things, NO CONFIGURATION IS NECESSARY.

* dir2cast will automatically use the ID3 fields from your MP3s for the Author, 
  Title, etc. ID3v2 is supported. (Uses bundled getID3 lib).

* The generated feed is cached (in the supplied 'temp' folder, or anywhere else
  that you want) and only updated if something in the directory changes.

* Almost-full support for iTunes podcast tags. (Not supported: block, explicit,
  new-feed-url, per-item keywords).

* iTunes 'image' supported: just drop a file called itunes_image.jpg in the same
  folder as your MP3s.

* RSS Description, iTunes Subtitle and iTunes Summary can be set by dropping
  files named description.txt, itunes_subtitle.txt and itunes_summary.txt 
  in the same folder as dir2cast.php - but they are not required. (You can
  also set these in the script).

* You can set a per-file iTunes Summary by creating a text file with the same
  name as the MP3 (e.g. for file.mp3, create file.txt).

* Works from the command line, if you want.

REQUIREMENTS
================================================================================

dir2cast requires PHP 5.1.

dir2cast makes use of getID3 by James Heinrich & Allan Hansen, although it does
not require the whole thing. A cut down version of getID3 is supplied at
http://www.ben-xo.com/dir2cast. You will need to download this and install it
with dir2cast.php. (It is not bundled in the same file for licensing reasons.
The full version of getID3 is available at http://getid3.sourceforge.net/ ).


INSTALLATION
================================================================================

Step 1. Upload dir2cast.php to the web server.
Step 2. Upload getID3 to a folder called 'getID3'. You can download getID3 from
        the same place as dir2cast.


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

0.6 2008-02-25 Updated the licensing information.
0.5 2008-02-25 Fixed losing itunes item tags when a dir changes.
0.4 2008-02-24 Added a whole error handler with pretty errors
0.3 2008-02-24 Fixed the auto URL detection code. Made the config clearer.
               Renamed some of the config parameters for clarity.
0.2 2008-02-23 Fixed bug with missing '/' and wrong urlencoding on spaces.
0.1 2008-02-22 Initial Release
