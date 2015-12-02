# kamera

Generates HTML for image directories created by a web camera: ./20151202/image-2015120221560000.jpg (etc).

Shows the following, depending on the query string:
* Today's images: empty string, or '?'
* The latest image: ?1
* All images for a date: ?day&date=20151202
* All images for a month: ?type=month&year=2015&month=12
* One specific image: ?type=one&image=2015120213560301 

Supports navigation back/forward/up/down with the arrow keys.

The script started as a simple hack, then grew into this much larger and almost maintainable hack.
ALL HTML is hand coded, and no framework is used. The pages are not responsive, do not support swiping, etc.

Also: All text is hard coded in Norwegian. Nice.

See it in use here: http://superelectric.net/viktun/kamera/