<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require dirname(__DIR__) . '/App/Website/db.php';

$contentCategoryQuery = 'SELECT group_id FROM `community-voices_content-categories`';
$DEFAULT_PROBABILITY = 5;
$galleries = [];

foreach ($dbHandler->query($contentCategoryQuery) as $row) {
  $galleries[$row['group_id']] = $DEFAULT_PROBABILITY;
}

$gallery_names = array_keys($galleries);
foreach ($galleries as $gallery => $numerator) {
    if (isset($_GET[$gallery])) {
        $galleries[$gallery] = $_GET[$gallery];
    }
}

if (isset($_GET['interval'])) {
  $interval = $_GET['interval'];
} else {
  $interval = '7000';
}

if (isset($_GET['search'])) {
  $param = "'%{$_GET['search']}%'";
  $search_quote_query = 'AND quote_id IN (SELECT `community-voices_quotes`.media_id FROM `community-voices_quotes` WHERE text LIKE '.($param).' OR attribution LIKE '.($param).' OR sub_attribution LIKE '.($param).')';
  $search_image_query = 'OR image_id IN (SELECT `community-voices_images`.media_id FROM `community-voices_images` WHERE title LIKE '.($param).' OR description LIKE '.($param).' OR photographer LIKE '.($param).' OR organization LIKE '.($param).')';
  $search_query = $search_quote_query . ' ' . $search_image_query;
} else {
  $search_query = '';
}
if (isset($_GET['tags'])) {
  $sanitized_tags = implode(',', array_map('intval', $_GET['tags']));
  $tag_query = 'AND (quote_id IN (SELECT media_id FROM `community-voices_media-group-map` WHERE group_id IN ('.$sanitized_tags.')) OR image_id IN (SELECT media_id FROM `community-voices_media-group-map` WHERE group_id IN ('.$sanitized_tags.')) )';
} else {
  $tag_query = '';
}
if (isset($_GET['attributions'])) {
  $attributions = implode("','", $_GET['attributions']);
  $attribution_query = "AND quote_id IN (SELECT media_id FROM `community-voices_quotes` WHERE attribution IN ('".($attributions)."'))";
} else {
  $attribution_query = '';
}
if (isset($_GET['photographers'])) {
  $photographers = implode("','", $_GET['photographers']);
  $photographer_query = "AND image_id IN (SELECT media_id FROM `community-voices_images` WHERE photographer IN ('".($photographers)."'))";
} else {
  $photographer_query = '';
}
if (isset($_GET['orgs'])) {
  $orgs = implode("','", $_GET['orgs']);
  $org_query = "AND image_id IN (SELECT media_id FROM `community-voices_images` WHERE organization IN ('".($orgs)."'))";
} else {
  $org_query = '';
}
if (isset($_GET['content_category'])) {
  $contentCategories = implode(',', array_map('intval', $_GET['content_category']));
  $content_category_query = 'AND content_category_id IN ('.($contentCategories).')';
} else {
  $content_category_query = '';
}

$sql = 'SELECT probability, content_category_id, media_id, quote_id, image_id FROM `community-voices_slides` WHERE probability > 0 ' . $content_category_query . $search_query . $tag_query . $attribution_query . $photographer_query . $org_query . ' ORDER BY probability DESC';

$weight_sum = array_sum($galleries);
$sorted_rows = array_fill_keys($gallery_names, []); // list of urls, each duplicated to match its prob/weight
$num_urls = 0;
foreach ($dbHandler->query($sql) as $row) {
    for ($i=0; $i < $row['probability']; $i++) {
        $sorted_rows[$row['content_category_id']][] = "/community-voices/slides/{$row['media_id']}";
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
    <link rel="stylesheet" href="https://environmentaldashboard.org/css/bootstrap.css?v=2">
    <link rel="stylesheet" href="/community-voices/public/css/landing.css">
    <link rel="stylesheet" href="/community-voices/public/css/embeddable.css">
    <meta name="theme-color" content="#000000">
    <title>Community Voices</title>
  </head>

  <body style="background: #000">
    <div id="carouselIndicators" class="carousel slide" data-ride="carousel" data-interval="<?php echo $interval; ?>">
      <div class="carousel-inner" ontransitionend="loadMore()">
        <div class="carousel-item active"><div class="embed-responsive embed-responsive-16by9"><iframe class="embed-responsive-item" id="slide1" style="pointer-events: none;" src="<?php echo $files[0]; ?>"></iframe></div></div>
      </div>
      <a class="carousel-control-prev" href="#carouselIndicators" role="button" data-slide="prev">
        <span class="carousel-control-prev-icon" aria-hidden="true"></span><span class="sr-only">Previous</span>
      </a>
      <a class="carousel-control-next" href="#carouselIndicators" role="button" data-slide="next">
          <span class="carousel-control-next-icon" aria-hidden="true"></span><span class="sr-only">Next</span>
      </a>
    </div>

    <div id="buttons" class="row" style="padding: 15px">
      <div style="display: flex; flex-wrap: wrap; padding: 0px 15px; width: 100%" id="carousel-selection-flex-container">
        <div style="display: flex; flex-direction: column; width: 130px" class="carousel-selection-flex-item">
          <div style="display: flex; justify-content: center; align-content: center; background-color: #008cb4; border-radius: 10px; height: 105px; width: 130px"><img data-cc="111" onclick="setCategory(this)" src="/community-voices/uploads/4530" class="selector-img" style="cursor: pointer; margin: auto; max-width: 130px; max-height: 95px"></div>
          <div style="text-align:center; font-weight:bold; color:white">Climate Action</div>
        </div>
        <div style="display: flex; flex-direction: column; width: 130px" class="carousel-selection-flex-item">
          <div style="display: flex; justify-content: center; align-content: center; background-color: #371a95; border-radius: 10px; height: 105px; width: 130px"><img data-cc="4" onclick="setCategory(this)" src="/community-voices/uploads/4711" class="selector-img" style="cursor: pointer; margin: auto; max-width: 130px; max-height: 95px"></div>
          <div style="text-align:center; font-weight:bold; color:white">Heritage</div>
        </div>
        <div style="display: flex; flex-direction: column; width: 130px" class="carousel-selection-flex-item">
          <div style="display: flex; justify-content: center; align-content: center; background-color: #43762d; border-radius: 10px; height: 105px; width: 130px"><img data-cc="5" onclick="setCategory(this)" src="/community-voices/uploads/4712" class="selector-img" style="cursor: pointer; margin: auto; max-width: 130px; max-height: 95px"></div>
          <div style="text-align:center; font-weight:bold; color:white">Natural Oberlin</div>
        </div>
        <div style="display: flex; flex-direction: column; width: 130px" class="carousel-selection-flex-item">
          <div style="display: flex; justify-content: center; align-content: center; background-color: #b92d5d; border-radius: 10px; height: 105px; width: 130px"><img data-cc="6" onclick="setCategory(this)" src="/community-voices/uploads/4713" class="selector-img" style="cursor: pointer; margin: auto; max-width: 130px; max-height: 95px"></div>
          <div style="text-align:center; font-weight:bold; color:white">Neighbors</div>
        </div>
        <div style="display: flex; flex-direction: column; width: 130px" class="carousel-selection-flex-item">
          <div style="display: flex; justify-content: center; align-content: center; background-color: #002e7a; border-radius: 10px; height: 105px; width: 130px"><img data-cc="3" onclick="setCategory(this)" src="/community-voices/uploads/4714" class="selector-img" style="cursor: pointer; margin: auto; max-width: 130px; max-height: 95px"></div>
          <div style="text-align:center; font-weight:bold; color:white">Next Generation</div>
        </div>
        <div style="display: flex; flex-direction: column; width: 130px" class="carousel-selection-flex-item">
          <div style="display: flex; justify-content: center; align-content: center; background-color: #5c5c5c; border-radius: 10px; height: 105px; width: 130px"><img data-cc="2" onclick="setCategory(this)" src="/community-voices/uploads/4710" class="selector-img" style="cursor: pointer; margin: auto; max-width: 130px; max-height: 95px"></div>
          <div style="text-align:center; font-weight:bold; color:white">Our Downtown</div>
        </div>
        <div style="display: flex; flex-direction: column; width: 130px" class="carousel-selection-flex-item">
          <div style="display: flex; justify-content: center; align-content: center; background-color: #965117; border-radius: 10px; height: 105px; width: 130px"><img data-cc="1" onclick="setCategory(this)" src="/community-voices/uploads/4715" class="selector-img" style="cursor: pointer; margin: auto; max-width: 130px; max-height: 95px"></div>
          <div style="text-align:center; font-weight:bold; color:white">Serving Our Community</div>
        </div>
      </div>
    </div>

    <div id="search" class="input-group input-group-lg">
      <input id="search_input" name="search" type="text" class="form-control" aria-label="Search Community Voices" placeholder="Search for slides" style="background: url(/community-voices/public/images/search.svg) no-repeat left 1rem center;background-size: 20px 20px;padding-left: 3rem;background-color: white;">
      <button type="submit" class="btn btn-outline-primary form-control" style="max-width:15%;min-width: 100px;background-color: white;" onclick="setSearch(this)">Search</button>
    </div>

    <script src="https://code.jquery.com/jquery-3.2.1.min.js" integrity="sha256-hwg4gsxgFZhOsEEamdOYGBf13FyQuiTwlAQgxVSNgt4=" crossorigin="anonymous"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>

    <script>
    var paths = <?php echo json_encode($files); ?>;
    var currentMax = paths.length < 10 ? paths.length : 10;

    function setCategory(category) {
      window.location.search = `content_category[]=${category.dataset.cc}`
    }

    function setSearch(query) {
      window.location.search = `search=${document.getElementById('search_input').value}`
    }

    function loadMore() {
      if (Number(document.getElementsByClassName('carousel-item active')[0].getElementsByClassName('embed-responsive-item')[0].id.substring(5,)) === currentMax) {
        for(var i = currentMax; i < (paths.length < currentMax + 10 ? paths.length - currentMax : currentMax + 10); i++) {
          $('<div class="carousel-item"><div class="embed-responsive embed-responsive-16by9"><iframe class="embed-responsive-item" id="slide' + (i + 1) + '" style="pointer-events: none;" src="' + paths[i]+ '"></iframe></div></div>').appendTo('.carousel-inner');
        }
        currentMax += paths.length < currentMax + 10 ? paths.length - currentMax : 10;
      }
    }

    $(document).ready(function(){
      const hash = window.location.hash.split("&");
      const search = window.location.search;

      let adjust = 74;
      if (!hash.includes("buttons") && !hash.includes("#buttons")) {
        document.getElementById("buttons").style.display = "none";
        adjust += 20;
      }
      if (!hash.includes("search") && !hash.includes("#search")) {
        document.getElementById("search").style.display = "none";
        adjust += 6;
      }
      for(var i=1; i < (paths.length < 10 ? paths.length : 10); i++) {
        $('<div class="carousel-item"><div class="embed-responsive embed-responsive-16by9"><iframe class="embed-responsive-item" id="slide' + (i + 1) + '" style="pointer-events: none;" src="' + paths[i]+ '"></iframe></div></div>').appendTo('.carousel-inner');
      }

      if (search.includes('search=')) {
        search_query = search.substring(search.indexOf('search='),);
        search_query = ((search_query.indexOf('&') != -1) ? search_query.substring(7, search_query.indexOf('&')) : search_query.substring(7,));
        document.getElementById('search_input').value = search_query;
      }

      var r = document.querySelector(':root');
      r.style.setProperty('--adjustment', `${adjust}vh`);
    });
    </script>

  </body>
</html>
