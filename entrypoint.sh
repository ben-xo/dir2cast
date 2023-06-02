#!/bin/bash

echo MP3_DIR = $MP3_DIR \ >> /var/www/html/dir2cast.ini
echo MP3_URL= $MP3_URL \  >> /var/www/html/dir2cast.ini
echo RECURSIVE_DIRECTORY_ITERATOR = $RECURSIVE_DIRECTORY_ITERATOR \ >> /var/www/html/dir2cast.ini
echo COPYRIGHT = $COPYRIGHT \ >> /var/www/html/dir2cast.ini
echo WEBMASTER = $WEBMASTER \ >> /var/www/html/dir2cast.ini
echo ITUNES_OWNER_NAME = $ITUNES_OWNER_NAME \ >> /var/www/html/dir2cast.ini
echo ITUNES_OWNER_EMAIL = $ITUNES_OWNER_EMAIL \ >> /var/www/html/dir2cast.ini
echo LINK = $LINK \ >> /var/www/html/dir2cast.ini
echo TITLE = $TITLE \ >> /var/www/html/dir2cast.ini
echo ITUNES_AUTHOR = $ITUNES_AUTHOR \ >> /var/www/html/dir2cast.ini
echo ITUNES_CATEGORIES = $ITUNES_CATEGORIES \ >> /var/www/html/dir2cast.ini
echo ITUNES_EXPLICIT = $ITUNES_EXPLICIT \ >> /var/www/html/dir2cast.ini
echo DESCRIPTION = $DESCRIPTION \ >> /var/www/html/dir2cast.ini
echo ITUNES_SUBTITLE = $ITUNES_SUBTITLE \ >> /var/www/html/dir2cast.ini
echo ITUNES_SUMMARY = $ITUNES_SUMMARY \ >> /var/www/html/dir2cast.ini
echo ITUNES_SUBTITLE_SUFFIX = $ITUNES_SUBTITLE_SUFFIX \ >> /var/www/html/dir2cast.ini
echo ITUNES_TYPE = $ITUNES_TYPE \ >> /var/www/html/dir2cast.ini
echo LANGUAGE = $LANGUAGE \ >> /var/www/html/dir2cast.ini
echo ITEM_COUNT= $ITEM_COUNT \ >> /var/www/html/dir2cast.ini
echo TITLE = $TITLE \ >> /var/www/html/dir2cast.ini
echo AUTO_SAVE_COVER_ART = $AUTO_SAVE_COVER_ART \ >> /var/www/html/dir2cast.ini
echo MIN_FILE_AGE = $MIN_FILE_AGE \ >> /var/www/html/dir2cast.ini
echo MIN_CACHE_TIME = $MIN_CACHE_TIME \ >> /var/www/html/dir2cast.ini
echo TTL = $TTL \ >> /var/www/html/dir2cast.ini
echo FORCE_PASSWORD = $FORCE_PASSWORD \ >> /var/www/html/dir2cast.ini
echo ATOM_TYPE = $ATOM_TYPE \ >> /var/www/html/dir2cast.ini
echo DESCRIPTION_SOURCE = $DESCRIPTION_SOURCE \ >> /var/www/html/dir2cast.ini
echo DESCRIPTION_HMTL = $DESCRIPTION_HMTL \ >> /var/www/html/dir2cast.ini

exec "$@"
