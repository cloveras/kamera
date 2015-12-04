<?php

/* ============================================================
//
// kamera.php
//
// Generates HTML for webcam images.
//
// Looks for directories and image files like this:
// ./20151202/image-2015120209401201.jpg
//
// Finds sunrise, sunset, dawn and dusk, only shows images taken between dawn and dusk. 
// Handles midnight sun and polar night.
//
// The script started as a simple hack, then grew into this much larger and almost 
// maintainable hack. It is a good candidate for a complete rewrite, if you have the time.
//
// Code: https://github.com/cloveras/kamera
//
// Have a look: http://superelectric.net/viktun/kamera/
//
// Things that should be changed if you want to use this:
// * Names of directories and image filenames (maybe easiest to change on filesystem).
// * Latitude and longditude (use Google Maps to find coordinates)
// * Adjust zenith, apparently a black art: https://en.wikipedia.org/wiki/Solar_zenith_angle
// * Verify the calculated sunrise and sunset with the same at yr.no.
// * Check the dates in functions midnight_sun() and polar_night().
// * Set the locale, whith is used for printing month names.
// * Change the (hardcoded) text shown on the pages (not a lot, but some).
// * Change the Google Analytics id.
// * For surprisingly verbose feedback for debugging: $debug = 1
//
============================================================ */


// Functions
// ============================================================

// Page header with title and Javascript navigation
// ------------------------------------------------------------
function page_header($title, $previous, $next, $up, $down) {

  print <<<END1
<!DOCTYPE html>
<html lang="en-US">
<head>
  <meta charset="utf-8">
  <meta name="description" content="Web camera with view towards west from Hov, Gimsøya, Lofoten, Norway.">
  <meta name="keywords" content="webcam,webcamera,hov,gimsøya,lofoten,nordland,norway">
  <meta name="robot" content="index, nofollow" />
  <meta name="generator" content="kamera.php: https://github.com/cloveras/kamera">
  <meta name="author" content="Christian Løverås">
  <link rel="stylesheet" type="text/css" href="/style.css" />
  <link rel="stylesheet" type="text/css" href="/style-viktun.css" />

END1;

  if ($previous) {
    print "  <link rel=\"prev\" title=\"Previous\" href=\"http://" . $_SERVER['SERVER_NAME'] . $_SERVER['SCRIPT_NAME'] . "$previous\" />\n";
  }
  if ($next) {
    print "  <link rel=\"next\" title=\"Next\" href=\"http://" . $_SERVER['SERVER_NAME'] . $_SERVER['SCRIPT_NAME'] . "$next\" />\n";
  }

  print <<<END2
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <title>$title</title>
END2;

  // Javascript for navigation using arrow keys. Only print the ones that do something.
  if ($previous || $next || $up || $down) {
    print "\n\n<!-- Javascript for navigation using arrow keys. -->\n";
    print "<script>\n";
    if ($previous) {
      print "  function leftArrowPressed() {\n";
      print "    window.location.href=\"$previous\";\n";
      print "  }\n\n";
    }
    if ($next) {
      print "  function rightArrowPressed() {\n";
      print "    window.location.href=\"$next\";\n";
      print "  }\n\n";
    }
    if ($up) {
      print "  function upArrowPressed() {\n";
      print "    window.location.href=\"$up\";\n";
      print "  }\n\n";
    }
    if  ($down) {
      print "  function downArrowPressed() {\n";
      print "    window.location.href=\"$down\";\n";
      print "  }\n\n";
    }
    print "  document.onkeydown = function(evt) {\n";
    print "    evt = evt || window.event;\n";
    print "      switch (evt.keyCode) {\n";
    if ($previous) {
      print "        case 37:\n";
      print "          leftArrowPressed();\n";
      print "          break;\n";
    }
    if ($up) {
      print "        case 38:\n";
      print "          upArrowPressed();\n";
      print "          break;\n";
    }
    if ($next) {
      print "        case 39:\n";
      print "          rightArrowPressed();\n";
      print "          break;\n";
    }
    if ($down) {
      print "        case 40:\n";
      print "          downArrowPressed();\n";
      print "          break;\n";
    }
    print "      }\n";
    print "    };\n";
    print "</script>\n\n";
  }

  // Print the rest of the top of the page, including page title.
  // Remember to change the Google Analytics id.
print<<<END3
</head>
<body>

<!-- You will now get ads for web cameras everywhere. -->
<script>
(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
})(window,document,'script','//www.google-analytics.com/analytics.js','ga');
ga('create', 'UA-109975-1', 'auto');
ga('send', 'pageview');
</script>

<h1>$title</h1>


END3;
}

// Debug
// ------------------------------------------------------------
function debug($txt) {
  global $debug;
  if ($debug) {
    print "$txt<br/>\n";
  }
}


// Footer
// ------------------------------------------------------------
function footer($count) {
  print "\n<p>Bruk piltastene for å navigere forover (&#9654;), bakover (&#9664;), opp (&#9650;) og ned (&#9660;).</p>\n\n<p>\n";
  if ($count > 0) {
    print "<a href=\"#\">Til toppen</a>.\n"; // Include link to top of page only if this is a "long" page.
  }
  print "<a href=\"http://www.lookr.com/lookout/1448496177-Lofoten\">Lookr: time-lapse</a>. \n";
  print "<a href=\"../\">Viktun</a>\n</p>\n\n";
  print "</body>\n</html>\n";
}


// Get variables from the date part of the image filename.
// ------------------------------------------------------------
function split_image_filename($image_filename) {
  $image_filename = preg_replace('/^.*image-/', '', $image_filename); // Remove everything up to and including the '/'.
  $image_filename = preg_replace('/.jpg/', '', $image_filename); // Remove the .jpg suffix.
  // 2015120209401201
  // YYYYMMDDHHMMSSFF
  $year = substr($image_filename, 0, 4);
  $month = substr($image_filename, 4, 2);
  $day = substr($image_filename, 6, 2);
  $hour = substr($image_filename, 8, 2);
  $minute = substr($image_filename, 10, 2);
  $seconds = substr($image_filename, 12, 4);
  debug("<br/>split_image_filename($image_filename): $year-$month-$day $hour:$minute:$seconds");
  return array($year, $month, $day, $hour, $minute, $seconds);
}

// Midnight sun? (tested with date_sunrise() and GPS coorinates used in this script on yr.no)
// ------------------------------------------------------------
function midnight_sun($month, $day) {
  // TODO: Base this on latitude and longditude.
  // Details: https://en.wikipedia.org/wiki/Midnight_sun
  $return = (($month == 5 && $day >= 24) || ($month == 6) || ($month == 7 && $day <= 18));
  return $return;
}

// Is it polar night (no sun)? (tested with date_sunrise() and GPS coordinates used in this script on yr.no)
// ------------------------------------------------------------
function polar_night($month, $day) {
  // TODO: Base this on latitude and longditude.
  // Details: https://en.wikipedia.org/wiki/Polar_night
  $return = (($month == 12 && $day >= 6) || ($month == 1 && $day <= 6));
  return $return;
}

// Find sunrise and sunset, return all kinds of stuff we need later.
// ------------------------------------------------------------
function find_sun_times($timestamp) {
  // Return timestamps for everything.
  $sunrise = 0;
  $sunset = 0; 
  $dawn = 0; 
  $dusk = 0; 
  $midnight_sun = false; 
  $polar_night = false;
  $polar_night_sunrise_hour = 11;
  $polar_night_sunset_hour = 12;
  $adjust_dawn_dusk = 3 * 60 * 60; // How much before/after sunrise/sunset is dawn/dusk.

  // Where: Fylkesveg 862 110, 8314 Gimsøysand: 68.329891, 14.092439
  $latitude = 68.329891; // North
  $longditude = 14.092439; // East
  $zenith = 90.58333; // Used trial and error to get this sort of right, it seems to be a black art.

  // We will need these below.
  $year = date('Y', $timestamp);
  $month = date('m', $timestamp);
  $day = date('d', $timestamp);

  if (midnight_sun($month, $day)) {
    // Sun all the time.
    $midnight_sun = true;
    $sunrise = mktime(0, 0, 0, $month, $day, $year); // Midnight
    $sunset = mktime(23, 59, 59, $month, $day, $year); // Almost midnight again
    $dawn = $sunrise;
    $dusk = $sunset;
  } else if (polar_night($month, $day)) {
    // No sun at all.
    $polar_night = true;
    // We still need to show a few images, so: faking sunrise and sunset.
    $sunrise = mktime($polar_night_sunrise_hour, 0, 0, $month, $day, $year);
    $sunset = mktime($polar_night_sunset_hour, 0, 0, $month, $day, $year); 
    $dawn = $sunrise;
    $dusk = $sunset;
  } else {
    // Do the math! Use the $timestamp passed as parameter.
    $sunrise = date_sunrise($timestamp, SUNFUNCS_RET_TIMESTAMP, $latitude, $longditude, $zenith, 1);
    $sunset = date_sunset($timestamp, SUNFUNCS_RET_TIMESTAMP, $latitude, $longditude, $zenith, 1);
    $dawn = $sunrise - $adjust_dawn_dusk;
    $dusk = $sunset + $adjust_dawn_dusk;
  }

  // At the beginning and end of the midnight sun and polar night periods, the sun may rise/set the day before/after.
  $day_start = mktime(0, 0, 0, date('m', $timestamp), date('d', $timestamp), date('Y', $timestamp)); // 00:00:00
  $day_end = mktime(23, 59, 59, date('m', $timestamp), date('d', $timestamp), date('Y', $timestamp)); // 23:59:59
  // Check if dawn/dusk are have been set too early/late above, and reset to start/end of day.
  if ($sunrise - $adjust_dawn_dusk < $day_start) {
    // The time from 00:00:00 to sunrise is less than the adjustment time. Set dawn to start of day.
    $dawn = $day_start;
  }  
  if ($sunset + $adjust_dawn_dusk > $day_end) {
    // The time from sunset to 23:59:59 is less than the adjustment time. Set dusk to end of day.
    $dusk = $day_end;
  }

  debug("<br/>find_sun_times($timestamp) (" . date('Y-m-d H:i', $timestamp) . ")");
  debug("dawn: $dawn (" . date('Y-m-d H:i', $dawn) . ")");
  debug("sunrise: $sunrise (" . date('Y-m-d H:i', $sunrise) . ")");
  debug("sunset: $sunset (" . date('Y-m-d H:i', $sunset) . ")");
  debug("dusk: $dusk (" . date('Y-m-d H:i', $dusk) . ")");
  debug("midnight_sun: $midnight_sun");
  debug("polar_night: $polar_night");
  debug("adjust_dawn_dusk: $adjust_dawn_dusk");
  return array($sunrise, $sunset, $dawn, $dusk, $midnight_sun, $polar_night);
}

// Print one image for every day in the month.
// ------------------------------------------------------------
function print_full_month($year, $month) {
  global $size;
  global $monthly_day;
  global $monthly_hour;

  debug("<br/>print_full_month($year, $month)");

  // Find previous and next month, and create the links to them.
  list($year_previous, $month_previous, $year_next, $month_next) = find_previous_and_next_month($year, $month);
  $previous = "?type=month&year=$year_previous&month=$month_previous&size=$size"; // Previous month.
  $next = "?type=month&year=$year_next&month=$month_next&size=$size"; // Next month.
  $up = "?type=year&year=$year"; // Up: SHow the full year.
  // Down goes to the first day n this month that has images.
  $first_day_with_images = find_first_day_with_images($year, $month);
  if ($first_day_with_images) {
    $down = "?type=day&date=" . find_first_day_with_images($year, $month);
  } else {
    $down = false;
  }

  // Make timestamp for this month.
  $minute = 0;
  $second = 0;
  $timestamp = mktime($monthly_hour, 0, 0, $month, $monthly_day, $year); // Using the $monthly_day as average.
  $title = "Viktun: " . ucwords(strftime("%B %Y ca kl %H hver dag", $timestamp), "§"); // § Uppercase month, but nothing else.
  page_header($title, $previous, $next, $up, $down);

  list($sunrise, $sunset, $dawn, $dusk, $midnight_sun, $polar_night) = find_sun_times($timestamp);
  print_sunrise_sunset_info($sunrise, $sunset, $dawn, $dusk, $midnight_sun, $polar_night, "average");
  print_yesterday_tomorrow_links($timestamp, true);

  $count = 0;
  for ($i = 1; $i <= 31; $i+=1) { // Works for February and 30-day months too.
    $now = mktime($hour, $minute, $second, $month, $i, $year);
    $i = sprintf("%02d", $i); // Need to pad the days with 0 first. Still works fine in for() above.
    $directory = date('Ymd', $now);
    // Get all *jpg images that start with the right year, month, day and hour.
     if (file_exists($directory)) {
       debug("Directory exists: $directory");
      // Getting the latest image in that directory for that hour.
      $image = get_latest_image_in_directory_by_date_hour($directory, $monthly_hour);
      if ($image) {
	debug("Image found: $image");
	// There was at least one image: 20151127/image-2015112700003401.jpg
	$image_datepart = get_date_part_of_image_filename($image);
	list($year, $month, $day, $hour, $minute, $seconds) = split_image_filename($image_datepart);
	// Print it!
	if ($size == "small") {
	  // Print small images.
	  print "<a href=\"?type=one&image=$year$month$day$hour$minute$seconds\">";
	  print "<img title=\"$year-$month-$day $hour:$minute\" alt=\"$year-$month-$day $hour:$minute\" width=\"160\" height=\"120\" src=\"$image\"/></a>\n";
	} else if ($size == "large") {
	  // Print large images.
	  print "<a href=\"?type=one&image=$year$month$day$hour$minute$seconds\">";
	  print "<img title=\"$year-$month-$day $hour:$minute\" alt=\"$year-$month-$day $hour:$minute\" width=\"640\" height=\"480\" src=\"$image\"/></a><br/>\n";
	}
	$count += 1; // Count the image just printed.
      }
     } else {
       debug("Directory does not exist: $directory");
     }
  }
  if ($count == 0) {
    print "<p>(Ingen bilder å vise for " .  strftime("%B %Y", $timestamp) . ")</p>\n"; // No pictures found for this month.
  }
  footer($count);
}

// Print images for a whole year.
// ------------------------------------------------------------
function print_full_year($year) {
  global $size;
  //$days = array(1, 8, 15, 23); 
  $days = array(1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31); // I don't have that many images yet..
  $hour = 11;

  debug("<br/>print_full_year($year)");

  // Find previous and next year, and create the links to them.
  $previous = "?type=year&year=" . ($year - 1);
  if ($year < date('Y')) {
    $next = "?type=year&year=" . ($year + 1);
  } else {
    $next = false;
  }
  $up = false; 
  // Down goes to the first month that has images.
  $down = false;
  $first_day_with_images = "";
  for ($month = 1; $month <= 12; $month++) {
    $month = sprintf("%02d", $month);
    $first_day_with_images = find_first_day_with_images($year, $month);
    if ($first_day_with_images) {
      // We found a month (and also a day, which we don't need now).
      $down = "?type=month&year=$year&month=$month";
      break;
    }
  }

  //page_header("Viktun: " . count($days) . " bilder for hver måned i hele $year", $previous, $next, $up, $down);
  page_header("Viktun: $year", $previous, $next, $up, $down);
  print_previous_next_year_links($year);

  // Loop through all months 1-12 and print images for the $days if they exist.
  $count = 0;
  $image_datepart = "";
  $image_filename = "";
  for ($month = 1; $month <= 12; $month++) {
    $month = sprintf("%02d", $month);
    debug("<br/>Checking month: $month");
    // Check for each of the days in the $days array
    foreach ($days as $day) {
      $day = sprintf("%02d", $day);
      // Find first image for that day taken after $hour
      $image_datepart = find_first_image_after_time($year, $month, $day, $hour, 0, 0);
      if ($image_datepart) {
	// Something was found.
	debug("Found image: $image_datepart");
	$image_filename = $year . "$month$day/" . "image-" . $image_datepart . ".jpg";
	debug("Filename: $image_filename");
	// Print it!
	if ($size == "small") {
	  // Print small images.
	  print "<a href=\"?type=one&image=$year$month$day$hour$minute$seconds\">";
	  print "<img title=\"$year-$month-$day kl $hour\" alt=\"$year-$month-$day kl $hour\" width=\"160\" height=\"120\" src=\"$image_filename\"/></a>\n";
	} else if ($size == "large") {
	  // Print large images.
	  print "<a href=\"?type=one&image=$year$month$day$hour$minute$seconds\">";
	  print "<img title=\"$year-$month-$day kl $hour:$minute\" alt=\"$year-$month-$day kl $hour\" width=\"640\" height=\"480\" src=\"$image\_filename\"/></a><br/>\n";
	}
	$count += 1;
      }
    }
  }
  if ($count == 0) {
    print "<p>(Ingen bilder å vise for $year)</p>\n"; // No pictures found for this year.
  }
  footer($count);
}

// Print links to small and large images
// ------------------------------------------------------------
function print_small_large_links($timestamp, $size) {
  $year = date('Y', $timestamp);
  $month = date('m', $timestamp);
  $day = date('d', $timestamp);
  print "<p>\n";
  if ($size == "large" || $size = "") { // Link to small if we showed large, or don't know.
    print "<a href=\"?type=day&date=$year$month$day&size=small\">Små bilder</a>.";
  }
  if ($size == "small" || $size == "") { // Links to large if we showed small, or don't know.
    print "<a href=\"?type=day&date=$year$month$day&size=large\">Store bilder</a>.";
  }
  print "</p>\n\n";
}

// Returns only the date part of an image filename (removes directory "image-" and ".jpg").
// ------------------------------------------------------------
function get_date_part_of_image_filename($image_filename) {
  debug("get_date_part_of_image_filename($image_filename)");
  // Full image filename: 20151202/image-2015120210451101.jpg
  $image_filename = preg_replace("/^.*image-/", '', $image_filename); // Remove everything up to and including the '/'.
  $image_filename = preg_replace('/.jpg/', '', $image_filename); // Remove the .jpg suffix.
  debug("datepart: $image_filename");
  return $image_filename;
}

// Finds the latest "*jpg" file in the newsst "2*" directory. Returns only date part of filename.
// ------------------------------------------------------------
function find_latest_image() {
  // Find newest directory with the right name format
  $directories = array_reverse(glob("2*")); // Get the latest first. 2* works until the year 3000.
  $directory = $directories[0];
  // Find newest image in the newest directory
  $images = array_reverse(glob("$directory/image*jpg")); // Get the latest *jpg file in the directory.
  // Getting 20151202/image-2015120209401201.jpg
  $image = $images[0];
  debug("<br>find_latest_image()<br/>directory: $directory<br/>image: $image");
  $image = get_date_part_of_image_filename($image);
  debug("image (datepart): $image");
   // Now: 2015120209401201
  return $image;
}

// Finds the first day with images for a specific year and month. Returns only date part of filename.
// ------------------------------------------------------------
function find_first_day_with_images($year, $month) {
  // Find newest directory with the right name format
  debug("<br/>find_first_day_with_images($year, $month)");
  $directories = glob("$year$month*"); // Get the first first. 2* works until the year 3000.
  $directory = $directories[0]; // This is the first one in that month.
  debug("First day with images: $directory");
  return $directory;
}

// Gets all images in the directory for a specific day (20151202).
// ------------------------------------------------------------
function get_all_images_in_directory($directory) {
  $images = glob("$directory/image-*.jpg");
  debug("<br/>get_all_images_in_directory($directory/image-*.jpg): " . count($images) . " images found.");
  return $images;
}

// Gets all images in the directory for a specific day. Returns date part: 2015120209401201 .
// ------------------------------------------------------------
function get_latest_image_in_directory_by_date_hour($directory, $hour) {
  $images = glob("$directory/image-$directory$hour*.jpg");
  debug("<br/>get_latest_image_in_directory_by_date_hour($directory, $hour)<br/>Found " . count($images) . "images, returning " . $images[0]);
  return $images[0];
}

// Find the first image after a given time. Used when going to the first image in a day.
// ------------------------------------------------------------
function find_first_image_after_time($year, $month, $day, $hour, $minute, $seconds) {  
  if ($minute < 10) {
    $minute = sprintf("%02d", $minute);
  }
  if ($seconds < 10) {
    $seconds = sprintf("%02d", $seconds);
  }
  debug("<br/>find_first_image_after_time($year, $month, $day, $hour, $minute, $seconds)"); 
  // Find all images for the specified date and hour (the minutes are checked further below).
  $images = glob("$year$month$day/image-$year$month$day$hour*");
  debug("Looking in directory: $year$month$day/image-$year$month$day$hour*");
  // Check if minutes are after the minutes passed as parameter (do not return a "too early" image).
  foreach ($images as $image) {
    debug("Now checking $image");
    // Get the date info for this image.
    list($year_split, $month_split, $day_split, $hour_split, $minute_split, $seconds_split) = split_image_filename($image);
    $seconds_split_compare = substr($seconds_split, 0, 2); // Not comparing with subseconds.
    if ("$hour$minute_split$seconds_split_compare" >= "$hour$minute$seconds") { 
      // The image we are checking is taken after the time passed as parameter.
      $image = "$year$month$day$hour$minute_split$seconds_split"; // Now we need the subseconds.
      debug("Success ($hour:$minute_split:$seconds_split >= $hour:$minute:$seconds): New image name: $image");
      break; // Success! This image was taken after the hour and minute passed as parameter.
    } else if ($hour_split > $hour) { 
      $image = ""; // We have tried all images taken that hour.
      debug("No image found for that hour, and all have been checked: $hour_split > $hour");
      break;
    }
  }
  if ($image) {
    $image = get_date_part_of_image_filename($image);
    // Now have 2015120209401201
  } else {
    debug("No image found for $year$month$day/image-$year$month$day$hour$minute");
  }
  return $image;
}

// Print a single image, specified by the date part of the filename (no .jpg suffix, no path)
// ------------------------------------------------------------
function print_single_image($image_filename) {
  // Works for 201511281504 and 2015112815 (minutes missing if this was arrow-down to get the first image)

  debug("<br/>print_single_image($image_filename)");

  if (strlen($image_filename) < strlen("YYYYMMDDHHMMSSSS")) {
    // We do not have the hour or the minutes. This is the first image in a day (arrow-down from full day).
    debug("Short filename! No seconds. Will find dawn and use minutes from there.");
    // Making timestamp, then finding dawn for this day.
    list($year, $month, $day, $hour, $minute, $seconds) = split_image_filename($image_filename);
    $timestamp = mktime($hour, $minute, 0, $month, $day, $year); // Using 0 for minutes to get the one(s) before too.
    // Find out when dawn is.
    list($sunrise, $sunset, $dawn, $dusk, $midnight_sun, $polar_night) = find_sun_times($timestamp);
    // We now have dawn. Find the first image after dawn, using the hours and minutes and even seconds.
    $image_filename = find_first_image_after_time($year, $month, $day, $hour, date('i', $dawn), date('s', $dawn));
    debug("Filename fixed (added minutes:" . date('i', $dawn) . " and seconds:" . date('s', $dawn) . "): $image_filename");
  } else {
    debug("Filename was ok (minutes in filename): $image_filename");
  }
  // We have the full filename, with minutes.
  list($year, $month, $day, $hour, $minute, $seconds) = split_image_filename($image_filename);
  $timestamp = mktime($hour, $minute, 0, $month, $day, $year);

  // Get previous and next image: First get all images for the same day as the images passed as parameter.
  $directory = "$year$month$day";
  // Loop through all images in this day's directory and look for the one passed as parameter.
  $images = get_all_images_in_directory($directory);
  $previous_image = false;
  $next_image = false;
  $i = 0;
  foreach($images as $image) {
    if (preg_match("/$image_filename/", $images[$i])) {
      // We found the one passed as paramter, now get previous and next.
      debug("MATCH: $image_filename == $images[$i]");
      $image_filename = "image-" . get_date_part_of_image_filename($images[$i]) . ".jpg";
      debug("Full name of found file: $image_filename");
      // We found the image that was passed as a parameter.
      if ($i != 0) {
	// This was not the first image in the array, get the previous one.
	$previous_image = $images[$i - 1];
      }
      if ($i != count($images)) {
	// This was not the last image in the array, get the next one.
	$next_image = $images[$i + 1];
      }
      break;
    }
    $i += 1;
  }

  // Links to previous, next, up, down.
  if ($previous_image) {
    $previous_datepart = get_date_part_of_image_filename($previous_image);
    $previous = "?type=one&image=$previous_datepart"; // Only date for the link.
  }
  if ($next_image) {
    $next_datepart = get_date_part_of_image_filename($next_image);
    $next = "?type=one&image=$next_datepart"; // Only date for the link.
  }
  $up_datepart = get_date_part_of_image_filename($image_filename);
  $up = "?type=day&date=$up_datepart"; // The full day.
  $down = false; // Already showing a single image, not possible to go lower.

  // Print!
  $title = "Viktun: " . strtolower(strftime("%e. %B %Y kl %H:%M", $timestamp));
  page_header($title, $previous, $next, $up, $down);
  list($sunrise, $sunset, $dawn, $dusk, $midnight_sun, $polar_night) = find_sun_times($timestamp);
  print_sunrise_sunset_info($sunrise, $sunset, $dawn, $dusk, $midnight_sun, $polar_night, false);
  print_full_day_link($timestamp);
  print "<p>";
  print "<a href=\"$previous\">Forrige (" . substr($previous_datepart, 8, 2) . ":" . substr($previous_datepart, 10, 2) . ")</a>.\n";
  if ($next_datepart) {
    print "<a href=\"$next\">Neste (" . substr($next_datepart, 8, 2) . ":" . substr($next_datepart, 10, 2) . ")</a>.\n";
  }
  debug("Showing image: $year$month$day/$image_filename");
  print "<p>";
  print "<a href=\"?type=day&date=$year$month$day\">";
  print "<img title=\"$year-$month-$day $hour:$minute\" alt=\"$year-$month-$day $hour:$minute\" width=\"640\" height=\"480\" src=\"$year$month$day/$image_filename\"/>";
  print "</a>";
  print "</p>\n";
  footer();
}

// Print details about the sun, and what images are shown.
// ------------------------------------------------------------
function print_sunrise_sunset_info($sunrise, $sunset, $dawn, $dusk, $midnight_sun, $polar_night, $include_interval) {
  global $monthly_day;
  print "<p>";
  if ($midnight_sun) {
    print "Midnattsol &#9728;";
  } else if ($polar_night) {
    print "Mørketid";
  } else {
    print "Soloppgang: " . date('H:i', $sunrise) . ". Solnedgang: " . date('H:i', $sunset);
  }
  if ($include_interval == "day") {
    print ". Viser bilder tatt mellom " . date('H:i', $dawn) . " og " . date('H:i', $dusk);
  } else if ($include_interval == "average") {
    print " (beregnet for den $monthly_day. i måneden)";
  }
  print ".</p>\n\n";
}

// Find the previous and next month, even for January and December.
// ------------------------------------------------------------
function find_previous_and_next_month($year, $month) {
  $month_previous = "";
  $year_previous = $year;
  $month_next = "";
  $year_next = $year;
  // Find previous month
  if ($month == 1) {
    $month_previous = 12;
    $year_previous = sprintf("%4d", $year - 1);
  } else {
    $month_previous = sprintf("%02d", $month - 1);
  }
  // Find next month
  if ($month == 12) {
    $month_next = "01";
    $year_next = sprintf("%4d", $year + 1);
  } else {
    $month_next = sprintf("%02d", $month  + 1);
  }
  debug("<br/>find_previous_and_next_month($year, $month)<br/>year_previous: $year_previous<br/>month_previous: $month_previous<br/>year_next: $year_next<br/>month_next: $month_next");
  return array($year_previous, $month_previous, $year_next, $month_next);
}

// Links to previsou and next year.
// ------------------------------------------------------------
function print_previous_next_year_links($year) {
  print "<p><a href=\"?type=year&year=" . ($year - 1) . "\">Forrige (" . ($year - 1) . ")</a>.\n";
  if ($year < date('Y')) {
    print "<a href=\"?type=year&year=" . ($year + 1) . "\">Neste (" . ($year + 1) . ")</a>.\n";
  }
  print "<p>\n";
}


// Links to yesterday and (possibly) tomorrow.
// ------------------------------------------------------------
function print_yesterday_tomorrow_links($timestamp, $is_full_month) {

  if ($is_full_month) {
    // Not links to yesterday and tomorrow, but the the previous and next months. Easy.
    list($year_previous, $month_previous, $year_next, $month_next) = find_previous_and_next_month(date('Y', $timestamp), date('m', $timestamp));
    print "<p>\nForrige: <a href=\"?type=month&year=$year_previous&month=$month_previous\">$year_previous-$month_previous</a>. \n";
    print "Neste: <a href=\"?type=month&year=$year_next&month=$month_next\">$year_next-$month_next</a>. \n";
    $this_month = date('Y-m'); // 2015-12
    $previous_month = date('Y-m', time() - 60 * 60 * 24 * 30); // 2015-11
    $requested_month = date('Y-m', $timestamp);
    //if (($requested_month != $this_month) && ($requested_month != $previous_month)) {
    if ($requested_month != $this_month) {
      print "<a href=\"?type=month&year=" . date('Y') . "&month=" . date('m') . "\">Denne: $this_month</a>. \n";
    }
    print "<a href=\"?\">I dag</a>. \n";
  } else {
    // Work hard to find the days.
    // Yesterday always exists.
    $yesterday_timestamp = $timestamp - 60 * 60 * 24;
    print "<p>\nForrige: <a href=\"?type=day&date=" . date('Ymd', $yesterday_timestamp) . "&size=$size\">" . date('Y-m-d', $yesterday_timestamp) . "</a>.\n";
    // Is there a tomorrow?
    $tomorrow_timestamp = $timestamp + 60 * 60 * 24;
    if (date('Y-m-d', $tomorrow_timestamp) > date('Y-m-d')) {
      // The next day is after the current day, so there will be no images to show.
    } else if (date('Ymd', $tomorrow_timestamp) != date('Y-m-d', $timestamp)) {
      // The next day is a day that we have images for.
      print "Neste: <a href=\"?type=day&date=" . date('Ymd', $tomorrow_timestamp) . "\">" . date('Y-m-d', $tomorrow_timestamp) . "</a>.\n";
    }
    
    // Link to "today" if we are further back than the day before yesterday.
    $yesterday = strtotime("-1 day", time());
    $yesterday_formatted = date('Y-m-d', $yesterday);
    debug("yesterday_formatted: $yesterday_formatted");
    // Should we show both "next" and "today" links? Extra detailed, since this was confusing at the time.
    if ($yesterday_formatted == date('Y-m-d', $timestamp)) {
      // The day shown was the day before the current day (meaning: yesterday).
    } else if (date('Y-m-d') == date('Y-m-d', $timestamp)) {
      // The day shown was the current date.
    } else {
      // The day shown was the day before yesterday, or earlier.
      print "<a href=\"?\">I dag</a>.\n";
    }
    // Link to the full month at 13:00
    //------------------------------------------------------------
    print "<a href=\"?type=month&year=" . date('Y', $timestamp) . "&month=" . date('m', $timestamp) . "\">Hele måneden (" . date('m', $timestamp) . ")</a>.\n";
    print "<a href=\"?type=year&year=" . date('Y', $timestamp) . "\">Hele året (" . date('Y', $timestamp) . ")</a>.\n";
  }
  print "</p>\n\n";
}

// Print link to alle images for the day specified with a timestamp.
// ------------------------------------------------------------
function print_full_day_link($timestamp) {
  $year= date('Y', $timestamp);
  $month = date('m', $timestamp);
  $day = date('d', $timestamp);
  print "<p><a href=\"?type=day&date=$year$month$day\">Hele dagen (" . trim(strftime("%e. %B %Y", $timestamp)) . ")</a>.</p>\n\n"; 
}

// Print all images in a diretory, between dawn and dusk, with small/large size, optionally limited by a number.
// ------------------------------------------------------------
function print_full_day($timestamp, $image_size, $number_of_images) {
  global $size;
  
  // Get all *jpg images in "today's" image directory.
  $directory = date('Ymd', $timestamp);
  debug("print_full_day($timestamp, $image_size, $number_of_images)");
  
  list($sunrise, $sunset, $dawn, $dusk, $midnight_sun, $polar_night) = find_sun_times($timestamp);
  
  // Set the navigation (we need $dusk from above).
  $previous = "?type=day&date=" . date('Ymd', $timestamp - 60 * 60 * 24) . "&size=$size"; // The previous day.
  $next = "?type=day&date=" . date('Ymd', $timestamp + 60 * 60 * 24) . "&size=$size"; // The next day.
  $up = "?type=month&year=" . date('Y', $timestamp) . "&month=" . date('m', $timestamp); // Full month.
  $down = "?type=one&image=" . date('Ymd', $timestamp) . date('H', $dawn); // First image this day (no minutes, as image may not be taken exactly at dawn).
  
  // Print header now that we have the details for it.
  $title = "Viktun: " . strtolower(strftime("%e. %B %Y", $timestamp));
  if ($number_of_images == 1) {
    // Just the latest image, so include hour and minute too.
    $title .= " " . date('H', $timestamp) . ":" . date('i', $timestamp);
  }
  page_header($title, $previous, $next, $up, $down);
  print_sunrise_sunset_info($sunrise, $sunset, $dawn, $dusk, $midnight_sun, $polar_night, $number_of_images != 1);
  print_small_large_links($timestamp, $size);
  print_yesterday_tomorrow_links($timestamp);
  
  $count = 0;
  debug("Getting images from directory: <a href=\"$directory\">$directory</a>");
  if (file_exists($directory)) {
    $images = glob("$directory/*.jpg");
    // Loop through all images. Reverse sort to start with the latest image at the top.
    foreach(array_reverse($images) as $image) {
      // Each filename is of this type: 20151123/image-2015112319140001.jpg
      $image_datepart = get_date_part_of_image_filename($image); // Get the "2015112319140001" part.
      list($year, $month, $day, $hour, $minute, $seconds) = split_image_filename($image_datepart); // Split into variables.
      // Create timestamp top check if this image is from between dawn and dusk.
      $image_timestamp = mktime($hour, $minute, substr($seconds, 0, 2), $month, $day, $year); // Skip the subseconds.
      debug("image_timestamp = mktime($hour, $minute, " . substr($seconds, 0, 2) . ", $month, $day, $year)");
      debug("image_timestamp: $image_timestamp<br/>dawn: $dawn<br/>dusk: $dusk");
      if (($image_timestamp <= $dusk) && ($image_timestamp >= $dawn)) {
	debug("INSIDE: " . date('H:i:s', $dawn) . " / " . date('H:i:s', $image_timestamp) . " / " . date('H:i:s', $dusk));
	if ($image_size == "large") {
	  // Print full size with linebreaks.
	  print "<p>";
	  print "$hour:$minute<br/>";
	  print "<a href=\"?type=one&image=$year$month$day$hour$minute$seconds\">";
	  print "<img title=\"$hour:$minute\" alt=\"$year-$month-$day $hour:$minute\" width=\"640\" height=\"480\" src=\"$image\"/></a>";
	  print "</p>\n";
	} else {
	  // Default: Small (25%) without linebreaks.
	  print "<a href=\"?type=one&image=$year$month$day$hour$minute$seconds\">";
	  print "<img title=\"$hour:$minute\" alt=\"$year-$month-$day $hour:$minute\" width=\"160\" height=\"120\" src=\"$image\"/></a>\n";
	}
	
	if ($number_of_images == 1) {
	  // We only wanted the latest image.
	  break;
	}
	
	$count += 1;	
	if ($count >= $number_of_images) {
	  break;
	}
      } else {
	debug("OUTSIDE: " . date('H:i:s', $dawn) . " / " . date('H:i:s', $image_timestamp) . " / " . date('H:i:s', $dusk));
      }
    }
  }
  if ($count == 0) {
    print "<p>(Ingen bilder å vise for " .  strftime("%e. %B %Y", $timestamp) . ")</p>\n"; // No pictures found for this day.
  }
  footer($count);
}
  
// Action below
// ============================================================

// Important variables and defaults.
// ------------------------------------------------------------
setlocale(LC_ALL,'no_NO');
date_default_timezone_set("Europe/Oslo"); 
$timestamp = time();
$debug = 0;
$size = "small";
$type = "day";
$monthly_day = 1; // The day to use for full month view.
$monthly_hour = 11; // Time to use when showing full months.
$max_images = 1000; // Unless we are showing less.

// Debug: Set the date to something else than today.
// ------------------------------------------------------------
if (false) {
  $debug_year = "2015";
  $debug_month = "11";
  $debug_day = "28";
  $timestamp = mktime(0, 0, 0, $debug_month, $debug_day, $debug_year); 
  print "Today (set in debug): " . date('Y-m-d H:i', $timestamp) . "<br/>\n";
}

// Sort out the QUERY_STRING
// ------------------------------------------------------------
if ($_SERVER['QUERY_STRING'] == 1) {
  $type = "last";
  debug("LAST");
} else if ($_SERVER['QUERY_STRING'] == "") {
  $type = "day";
  debug("DAY");
} else {
  parse_str($_SERVER['QUERY_STRING']); // Scary, but efficient.
  debug("PARSE");
}
debug("QUERY_STRING: " . $_SERVER['QUERY_STRING']);
debug("type: $type<br/>date: $date<br/>year: $year</br>month: $month</br>size: $size<br/>image: $image<br/>last_image: $last_image");


// Check the type, do the right thing
// ------------------------------------------------------------
if ($type == "last") {
  // Only the last image, even if it is after both sunset and dusk.
  $latest_image = find_latest_image();
  $latest_image_filename = get_date_part_of_image_filename($latest_image);
  print_single_image($latest_image_filename);
} else if ($type == "one") {
  // One specific image, the datepart is in the $image parameter (no path or .jpg): 2015112613051901
  print_single_image($image);
} else if ($type == "day") {
  // All images for the specified date either in $date parameter or created below: 20151130.
  if ($date) {
    $timestamp = mktime(0, 0, 0, substr($date, 4, 2), substr($date, 6, 2), $year = substr($date, 0, 4));    
  } // If $date is undefined, we use existing $timestamp.
  print_full_day($timestamp, $size, $max_images);
} else if ($type == "month") {
  // All images for this month, spåecified with $year and $month parameters.
  if ($month && $year) {
    print_full_month($year, sprintf("%02d", $month), $monthly_hour, $size); 
  }
} else if ($type == "year") {
  // The full year, actually. Not all images, though.
  print_full_year($year);
} else {
  // Unknown type.
  page_header("Feil", false, false, false, false);
  print "<p>Ukjent type: \"$type\".</p>";
  print "<p><a href=\"javascript:history.back()\">Tilbake</a>.</p>\n";
  footer(0);
}

?>
