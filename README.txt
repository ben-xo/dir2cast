dir2cast by Ben XO
==================

Hello!

dir2cast is designed to turn a directory of MP3s into a podcast - automatically.
Perfect for, say, radio shows - upload the MP3s to a folder, and use dir2cast.php
as your PodCast URL.

Features:

* For 99% of things, NO CONFIGURATION IS NECESSARY.

* dir2cast will automatically use the ID3 fields from your MP3s for the Author, 
  Title, etc. ID3v2 is supported. (Uses bundled getID3 lib).

* Almost-full support for iTunes podcast tags. (per-item keywords and 'explicit'
  are not supported).

* iTunes 'image' supported: just drop a file called itunes_image.jpg in the same
  folder as your MP3s.

* RSS Description, iTunes Subtitle and iTunes Summary can be set by dropping
  files named description.txt, itunes_subtitle.txt and itunes_summary.txt 
  in the same folder as dir2cast.php - but they are not required. (You can
  also set these in the script).

* You can set a per-file iTunes Summary by creating a text file with the same
  name as the MP3 (e.g. for file.mp3, create file.txt).

-- 
Ben