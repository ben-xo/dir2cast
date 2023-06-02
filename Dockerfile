FROM php:8.1-apache


# prep folders and perms
RUN mkdir -p /var/www/html/episodes

# prep env vars
ENV \
    ITEM_COUNT=10000 \
    MP3_DIR=/var/www/html/episodes \
    TITLE= \
    AUTO_SAVE_COVER_ART=

# add any dir2cast.ini options as env vars here
# todo - making this reusable for all env vars
RUN echo '; review options in dir2cast.ini source' > /var/www/html/dir2cast.ini
RUN echo "ITEM_COUNT = ${ITEM_COUNT}" >> /var/www/html/dir2cast.ini
RUN echo "MP3_DIR = ${MP3_DIR}" >> /var/www/html/dir2cast.ini
RUN if [[ $TITLE ]]; then echo "TITLE = ${TITLE}" >> /var/www/html/dir2cast.ini; fi;
RUN if [[ $AUTO_SAVE_COVER_ART ]]; then echo "AUTO_SAVE_COVER_ART = ${AUTO_SAVE_COVER_ART}" >> /var/www/html/dir2cast.ini; fi;

# copy source files to docker 
COPY ./dir2cast.php /var/www/html/
# skip creating ini. generated above
# COPY ./dir2cast.ini /var/www/html/
COPY ./getID3/ /var/www/html/getID3/

EXPOSE 80
