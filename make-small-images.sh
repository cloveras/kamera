#!/bin/bash

# Find today's year, month and day
now="$(date +'%Y%m%d')"

# Where are we?
image_dir="/home/2/s/superelectric/www/viktun/kamera/$now"

# cd to today's directory (Ymd)
cd $image_dir

# Exit of the "today" directory does not exist.
[ ! -d $image_dir ] && exit

# Create "small" directory if ot does not exist.
[ ! -d "small" ] && mkdir "small"

# For each image starting with 2: Make small image in "small" subdirectory (unless it exists).
FILES=image-2*jpg
for f in $FILES
do
    # echo "Converting: convert $f -quality 85 -resize 160x120 small/$f 2>&1" 
    [ ! -f "small/$f" ] && convert $f -quality 85 -resize 160x120 "small/$f" 2>&1
done