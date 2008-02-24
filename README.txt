dir2cast by Ben XO v0.3 (2008-02-24)
====================================

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

-- 
Ben

HISTORY:

0.3 2008-02-24 Fixed the auto URL detection code. Made the config clearer.
               Renamed some of the config parameters for clarity.
0.2 2008-02-23 Fixed bug with missing '/' and wrong urlencoding on spaces.
0.1 2008-02-22 Initial Release
