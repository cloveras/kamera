# kamera

Generates HTML for image directories created by a web camera: ./20151202/image-2015120221560000.jpg (etc).

Shows the following, depending on the query string:
* Today's images: empty string, or '?'
* The latest image: ?1
* All images for a date: ?type=day&date=20151202
* All images for a month: ?type=month&year=2015&month=12
* One specific image: ?type=one&image=2015120213560301 
* All images for a year (select which days in each month in the script): ?type=year&year=2015

Sunset, sunrise, dawn and dusk are found based on latitude and longditude, and only images between dusk and dawn are shown. 
Midnight sun and polar night is handled (see TODOs).

Supports navigation back/forward/up/down with the arrow keys.

Uses Google Analytics so you can see how many times you click on your page.

The script started as a simple hack, then grew into this much larger and almost maintainable hack. 
It is a good candidate for a complete rewrite, if you have the time. It does work quite well, though.

ALL HTML is hand coded, and no framework is used. The pages are somewhat responsive, but do not support swiping, etc.

Also: All text is hard coded in Norwegian. Nice.

See it in use here: http://superelectric.net/viktun/kamera/