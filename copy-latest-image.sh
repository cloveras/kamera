#!/bin/bash

# Copies the latest *.jpg file found in current directory and subdirectories to ./latest.jpg

# Go the the directory wher the seach for *jpg will be done.
cd /home/2/s/superelectric/www/viktun/kamera

# Copy the latest *.jpg image to latest.jpg in the current directory.
find . -type f | grep jpg | grep -v latest | sort -r | head -n1 | xargs -I '{}' cp '{}' latest.jpg