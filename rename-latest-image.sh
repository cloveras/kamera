#!/bin/bash

cd /home/2/s/superelectric/www/viktun/kamera
find . -type f | grep jpg | grep -v latest | sort -r | head -n1 | xargs -I '{}' cp '{}' latest.jpg