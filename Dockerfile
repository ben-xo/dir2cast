FROM php:8.1-apache


# prep folders and perms
RUN mkdir -p /var/www/html/episodes

# initialize env vars with defaults
# these can be changed with -e at docker run
ENV MP3_DIR=/var/www/html/episodes \
    MP3_URL=http://var/www/html/episodes \
    RECURSIVE_DIRECTORY_ITERATOR=true \
    COPYRIGHT= \
    WEBMASTER= \
    ITUNES_OWNER_NAME= \
    ITUNES_OWNER_EMAIL= \
    LINK= \
    TITLE= \
    ITUNES_AUTHOR= \
    ITUNES_CATEGORIES= \
    ITUNES_EXPLICIT=false \
    DESCRIPTION= \
    ITUNES_SUBTITLE= \
    ITUNES_SUMMARY= \
    ITUNES_SUBTITLE_SUFFIX= \
    ITUNES_TYPE= \
    LANGUAGE="en-us" \
    ITEM_COUNT=10000 \
    TITLE= \
    AUTO_SAVE_COVER_ART=false \
    MIN_FILE_AGE=30 \
    MIN_CACHE_TIME=5 \
    TTL=60 \
    FORCE_PASSWORD= \
    ATOM_TYPE= \
    DESCRIPTION_SOURCE= \
    DESCRIPTION_HMTL=

# copy source files to docker 
COPY ./.htaccess/ /var/www/html/
COPY ./dir2cast.php /var/www/html/
COPY ./getID3/ /var/www/html/getID3/
COPY entrypoint.sh /usr/local/bin/
RUN a2enmod rewrite
ENTRYPOINT ["/bin/sh", "/usr/local/bin/entrypoint.sh"]
EXPOSE 80
CMD ["apache2-foreground"]