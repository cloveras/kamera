#!/bin/bash

# Copies the latest *.jpg file in the "today" directory to latest.jpg in the current directory.

# Find today's year, month and day
today="$(date +'%Y%m%d')"

# Go the the directory wher the seach for *jpg will be done.
cd /home/2/s/superelectric/www/viktun/kamera

# If the "today" directory exists, copy the latest *.jpg image to latest.jpg in the current directory.
[ -d $today ] && find $today -type f | grep jpg | grep -v small | sort -r | head -n1 | xargs -I '{}' cp '{}' latest.jpg

# Update timestamp on latest.jpg
touch /home/2/s/superelectric/www/viktun/kamera/latest.jpg
