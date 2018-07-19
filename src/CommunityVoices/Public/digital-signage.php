<?php

/**
 *
 * Route for digital signage developed outside main app
 * to make development faster
 *
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require dirname(__DIR__) . '/App/Website/db.php';

$galleries = [1 => 5, // serving-our-community (id of content category) => probability
							2 => 5, // our-downtown
							3 => 5, // next-generation
							6 => 5, // neighbors
							5 => 5, // nature_photos
							4 => 5]; // heritage
$gallery_names = array_keys($galleries);
foreach ($galleries as $gallery => $numerator) {
  if (isset($_GET[$gallery])) {
    $galleries[$gallery] = $_GET[$gallery];
  }
}
$weight_sum = array_sum($galleries);
$sorted_rows = array_fill_keys($gallery_names, []); // list of urls, each duplicated to match its prob/weight
$num_urls = 0;
foreach ($dbHandler->query('SELECT probability, content_category_id, media_id FROM `community-voices_slides` WHERE probability > 0 ORDER BY probability DESC') as $row) {
  for ($i=0; $i < $row['probability']; $i++) { 
    $sorted_rows[$row['content_category_id']][] = "https://environmentaldashboard.org/cv/slides/{$row['media_id']}";
  }
  $num_urls += $row['probability'];
}
$files = [];
foreach ($galleries as $gallery => $weight) {
  shuffle($sorted_rows[$gallery]);
  $allowed_space = ($galleries[$gallery]/$weight_sum);
  $space_so_far = 0;
  foreach ($sorted_rows[$gallery] as $url) {
    $files[] = $url;
    if ($allowed_space <= (($space_so_far++)/$num_urls)) {
      break;
    }
  }
}
shuffle($files);
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png?v=9ByOqqx0o3">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png?v=9ByOqqx0o3">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png?v=9ByOqqx0o3">
    <link rel="manifest" href="/manifest.json?v=9ByOqqx0o3">
    <link rel="mask-icon" href="/safari-pinned-tab.svg?v=9ByOqqx0o3" color="#00a300">
    <link rel="shortcut icon" href="/favicon.ico?v=9ByOqqx0o3">
    <meta name="theme-color" content="#000000">
    <title>Environmental Dashboard</title>
    <style>
      @keyframes fadeIn {
        0% {
          display: none;
          opacity: 0;
        }
        1% {
          display: block;
          opacity: 0;
        }
        100% {
          display: block;
          opacity: 1;
        }
      }
      @keyframes fadeOut {
        0% {
          display: block;
          opacity: 1;
        }
        99% {
          display: block;
          opacity: 0;
        }
        100% {
          display: none;
          opacity: 0;
        }
      }
      .fade-in {
        -webkit-animation: fadeIn 2s linear 0s 1 normal forwards;
        -o-animation: fadeIn 2s linear 0s 1 normal forwards;
        animation: fadeIn 2s linear 0s 1 normal forwards;
      }
      .fade-out {
        -webkit-animation: fadeOut 2s linear 0s 1 normal forwards;
        -o-animation: fadeOut 2s linear 0s 1 normal forwards;
        animation: fadeOut 2s linear 0s 1 normal forwards;
      }
    </style>
  </head>
  <body style="background: #000">
    <img id='img1' src="<?php echo $files[0]; ?>" alt="" style="width: 100%;height: auto;position: absolute;top: 0;left: 0;right: 0">
    <img id="img2" src="<?php echo $files[1]; ?>" alt="" style="width: 100%;height: auto;position: absolute;top: 0;left: 0;right: 0">
  </body>
  <script>
    var paths = <?php echo json_encode($files); ?>;
    var images = [document.getElementById('img1'), document.getElementById('img2')];
    var current_path = 2,
        current_img = 1;
    setInterval(function() {
      if (current_img === 0) {
        images[current_img].className = 'fade-out';
        setTimeout(function() { images[0].setAttribute('src', paths[current_path++]); }, 2000);
        current_img = 1;
        images[current_img].className = 'fade-in';
        if (current_path === paths.length) {
          current_path = 0;
        }
      } else {
        images[current_img].className = 'fade-out';
        setTimeout(function() { images[1].setAttribute('src', paths[current_path++]); }, 2000);
        current_img = 0;
        images[current_img].className = 'fade-in';
        if (current_path === paths.length) {
          current_path = 0;
        }
      }
    }, <?php echo (isset($_GET['ms'])) ? $_GET['ms'] : 5000 ?>);
  </script>
</html>