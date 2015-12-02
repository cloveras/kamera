# kamera

Generates HTML for image directories created by a web camera: 20151202/image-2015120221560000.jpg (etc).

Shows the folowing, depending on the query string:
* Today's images: empty string, or '?'
* The latest image: ?1
* All images for a date: ?day&date=20151202
* All images for a month: ?type=month&year=2015&month=12
* One specific image: ?type=one&image=2015120213560301 

The script started as a simple hack, then grew into this.
Unfortunately, no framework like Bootstrap is used, and it is not responsive.
And all text is hardcoded in Norwegian. Nice.

See it in use here: http://superelectric.net/viktun/kamera/