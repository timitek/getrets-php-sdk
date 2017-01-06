<?php

include "../GetRETS.php";

ini_set('max_execution_time', 300);  // Give enough time (5 mintues) for slow DMQL queries

$customerKey = '';

$isPublic = false;
$exampleAddress = "sheridan, ar";
$exampleSource = "CARMLS";

$disableCache = !empty($_POST["disableCache"]);
$keywords = (array_key_exists("keywords", $_POST) ? $_POST["keywords"] : $exampleAddress );
$maxPrice = (array_key_exists("maxPrice", $_POST) ? $_POST["maxPrice"] : null );
$minPrice = (array_key_exists("minPrice", $_POST) ? $_POST["minPrice"] : null );
$includeResidential = array_key_exists("includeResidential", $_POST);
$includeLand = array_key_exists("includeLand", $_POST);
$includeCommercial = array_key_exists("includeCommercial", $_POST);
$sortBy = (array_key_exists("sortBy", $_POST) ? $_POST["sortBy"] : "rawListPrice" );
$reverseSort = array_key_exists("reverseSort", $_POST);

// Only let the public search the last days worth of modified residential listings (to prevent abusive queries)
$publicDMQL = '(L_UpdateDate=' . date('Y-m-d',(strtotime('-1 day', time()))) . '-' . date('Y-m-d') . ')';
$dmql =((array_key_exists("dmql", $_POST) && !$isPublic) ? $_POST["dmql"] : $publicDMQL);

$newWidth = (array_key_exists("image", $_POST) ? $_POST["newWidth"] : 400);
$maxHeight = (array_key_exists("image", $_POST) ? $_POST["maxHeight"] : 400);

$address = (array_key_exists("address", $_POST) ? $_POST["address"] : $exampleAddress );

$image = null;
$thumbnail = null;
$detail = NULL;
$rawData = NULL;
$listings = null;

$getRets = new GetRETS($customerKey);

if (!empty($_POST)) {

  // Keyword Search
  if (array_key_exists("searchByKeyword", $_POST)) {
    $preparedKeywords = htmlspecialchars($_POST["keywords"]);
    if ($disableCache) {
      $listings = $getRets->getRETSListing()
                          ->setSortBy($sortBy)->setReverseSort($reverseSort)
                          ->searchByKeyword($preparedKeywords);
    }
    else {
      $listings = $getRets->getListing()
                          ->setSortBy($sortBy)->setReverseSort($reverseSort)
                          ->searchByKeyword($preparedKeywords);
    }
  }
  // Advanced Search
  else if (array_key_exists("search", $_POST)) {
    if ($disableCache) {
      $listings = $getRets->getRETSListing()
                          ->setSortBy($sortBy)->setReverseSort($reverseSort)
                          ->search($keywords, $maxPrice, $minPrice, $includeResidential, $includeLand, $includeCommercial);
    }
    else {
      $listings = $getRets->getListing()
                          ->setSortBy($sortBy)->setReverseSort($reverseSort)
                          ->search($keywords, $maxPrice, $minPrice, $includeResidential, $includeLand, $includeCommercial);
    }
  }
  // Image
  else if (!empty($_POST['image'])) {
    $image = ($disableCache ? 
          $getRets->getRETSListing()->imageUrl($exampleSource, "Residential", "R66981", 0) : 
          $getRets->getListing()->imageUrl($exampleSource, "Residential", "R66981", 0));
    $thumbnail = $image . "?newWidth=" . $newWidth . "&maxHeight=" . $maxHeight;
  }
  // Return Listings by DMQL
  else if (array_key_exists("getListingsByDMQL", $_POST)) {
    $results = $getRets->getRETSListing()
                       ->setSortBy($sortBy)->setReverseSort($reverseSort)
                       ->getListingsByDMQL($dmql, $exampleSource, "Residential");
    if (!empty($results)) {
      if ($results->success && !empty($results->data)) {
        $listings = $results->data;
      }
    }
  }
  // Return raw DMQL results
  else if (array_key_exists("executeDMQL", $_POST)) {
      $rawData = $getRets->getRETSListing()->executeDMQL($dmql, $exampleSource, "Residential");
  }
  // Geocode
  else if (!empty($_POST['geocode'])) {
    $preparedAddress = htmlspecialchars($_POST["address"]);
    $rawData = $getRets->getGeocoding()->googleGeocode($preparedAddress);
  }
  // Sanitize Google Results
  else if (!empty($_POST['parseGoogleResults'])) {
    $googlResults = $_POST['googleResults'];
    $rawData = $getRets->getGeocoding()->parseGoogleResults($googlResults);
  }
}

// Listing Details
$detail = null;
if (array_key_exists("source", $_GET) && array_key_exists("type", $_GET) && array_key_exists("id", $_GET)) {
  $listingSource = $_GET['source'];
  $listingType = $_GET['type'];
  $listingId = $_GET['id'];

  $disableCache = !empty($_GET["disableCache"]);

  if ($disableCache) {
    $detail = $getRets->getRETSListing()->details($listingSource, $listingType, $listingId);
  }
  else {
    $detail = $getRets->getListing()->details($listingSource, $listingType, $listingId);
  }
}

?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">
    <link rel="shortcut icon" href="favicon.ico">

    <title>GetRETS PHP SDK Example</title>

    <!-- Latest compiled and minified CSS -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
    
    <!-- Just for debugging purposes. Don't actually copy this line! -->
    <!--[if lt IE 9]><script src="../../assets/js/ie8-responsive-file-warning.js"></script><![endif]-->

    <!-- HTML5 shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
      <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
    <![endif]-->
    <style>
    .content {padding-top: 80px ;padding-bottom: 20px}
    @media (min-width:768px) { #searching .list-group {position: fixed; width:18%;} }
    @media (min-width:768px) { #details .list-group {position: fixed; width:18%;} }
    .getrets-features ul { column-count: 2; -moz-column-count: 2; -webkit-column-count: 2; }
    .getrets-features ul li { list-style-type: none; }
    </style>

  </head>

  <body>

    <div class="navbar navbar-inverse navbar-fixed-top" role="navigation">
      <div class="container">
        <div class="navbar-header">
          <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
            <span class="sr-only">Toggle navigation</span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
          </button>
          <a class="navbar-brand" href="#">GetRETS Example</a>
        </div>
        <div class="collapse navbar-collapse">
          <ul class="nav navbar-nav" id="tabs">
            <li><a href="#searching" data-toggle="tab">Searching</a></li>
            <li><a href="#details" data-toggle="tab">Details</a></li>
          </ul>
        </div><!--/.nav-collapse -->
      </div>
    </div>

    <div class="content container tab-content">

      <!--================================
      Home
      ================================-->
      <div class="tab-pane active" id="home">
        <div class="jumbotron">
          <h1>GetRETS&reg; PHP SDK Example</h1>
          <p>This is a demonstration of the GetRETS PHP SDK.</p>
          <p><a href="https://github.com/timitek/getrets-php-sdk" target="_blank" class="btn btn-primary btn-lg" role="button">Download &darr;</a></p>
        </div>
        <div class="row">
          <?php if (empty($customerKey)): ?>
          <div class="col-xs-12">
            <div class="alert alert-danger" role="alert">
              <h1>Customer Key Required!</h1>
              <p class="lead">
                To run this example, you must have a valid customer key.<br />
                Set the customer key on line 7 of this file.<br />
                If you do not have a customer key, you can obtain an evaluation key from <a href="http://www.timitek.com/" target="_blank">www.timitek.com</a><br />
                If you want to see a running instance of this example please visit <a href="http://www.timitek.com/phpsdk/" target="_blank">www.timitek.com/phpsdk/</a>
              </p>
            </div>
          </div>
          <?php endif; ?>
        
          <div class="col-xs-12">
            <h1>About</h1>
            <p class="lead">
              <strong>GetRETS&reg;</strong> is a product / service developed by <a href="http://www.timitek.com/" target="_blank">timitek</a>
              that makes it possible to quickly build real estate related applications for pulling listing data from
              sevreal MLS's without having to know anything about RETS or IDX or worry about the pains of mapping and 
              storing listing data from these various sources.
              <br /><br />

              <strong>GetRETS&reg;</strong> as a service provides a RESTful API endpoint for consuming the data, and although it's not
              limited to only being used in PHP applications, and users aren't required to use our SDK, we have 
              provided a simple PHP SDK for the API and set of documentation for it's use, which can be downloaded at 
              <a href="https://github.com/timitek/getrets-php-sdk" target="_blank">github</a>.  Further documenation
              may be found at our website <a href="http://www.timitek.com" target="_blank">www.timitek.com</a>, or at our 
              <a href="http://swagger.io/">Swagger Interface</a> located at <a href="http://getrets.net/swagger/ui/index">getrets.net/swagger</a>.
              <br /><br />

              Use the menu at the top of the page to explore the various peices of functionality provided by the SDK.
              <br /><br />
            </p>
          </div>
        </div>
      </div>

      <!-- Exit the script if there is no Customer Key -->
      <?php if (empty($customerKey)): ?>
      </div>
      </body>
      </html>
      <?php exit; ?>
      <?php endif; ?>
      

      <!--================================
      Searching - Menu Option
      ================================-->
      <div class="tab-pane" id="searching">
        <div class="row">
          
          <div class="col-sm-3">
            <div class="list-group">
              <a class="list-group-item" href="#searchByKeyword">searchByKeyword</a>
              <a class="list-group-item" href="#search">search</a>
              <a class="list-group-item" href="#getListingsByDMQL">getListingsByDMQL</a>
              <a class="list-group-item" href="#executeDMQL">executeDMQL</a>
              <a class="list-group-item" href="#searchResults">Search Results</a>
            </div>
          </div>

          <div class="col-sm-9">
            <h1>Searching</h1>
            <p class="lead">
              There are 4 ways to search for listings.
              <ol>
                <li><a href="#searchByKeyword">searchByKeyword</a> - <em>(applicable for both <strong>cached</strong> and <strong>RETS</strong>)</em></li>
                <li><a href="#search">search</a> - <em>(applicable for both <strong>cached</strong> and <strong>RETS</strong>)</em></li>
                <li><a href="#getListingsByDMQL">getListingsByDMQL</a> - <em>(<strong>RETS</strong> Only)</em></li>
                <li><a href="#executeDMQL">executeDMQL</a> - <em>(<strong>RETS</strong> only)</em></li>
              </ol>
            </p>

            <!--================================
            searchByKeyword
            ================================-->
            <hr>
            <div id="searchByKeyword" class="content section">

              <div class="panel panel-primary">
                <div class="panel-heading"><h3 class="panel-title">searchByKeyword</h3></div>
                <div class="panel-body">
                  <p>Available for both cached (<a href="https://github.com/timitek/getrets-php-sdk#searchbykeyword" target="_blank">documentation</a>) and RETS (<a href="https://github.com/timitek/getrets-php-sdk#searchbykeyword-1" target="_blank">documentation</a>).</p>
                  <blockquote><p>Search for listings by keyword</p></blockquote>
                  <p><a href="http://getrets.net/swagger/ui/index#!/Listing/Listing_SearchByKeyword" target="_blank">Swagger Documentation</a></p>
                  <pre>(new GetRETS($customerKey))->getListing()->searchByKeyword($preparedKeywords);</pre>
                  <p>A simple search that will retrieve listings by a keyword search.</p>
                </div>
              </div>

              <div class="well well-lg">
                <form action="<?= htmlspecialchars($_SERVER["PHP_SELF"]) . '#searchResults' ?>"  method="post">
                  <div class="form-group">
                    <label for="keywords">Keywords</label>
                    <input class="form-control" id="keywords" name="keywords" placeholder="Enter keywords (address, listing id, etc..)" value="<?= $keywords ?>">
                  </div>
                  <div class="checkbox">
                    <label>
                      <input type="checkbox" name="disableCache" value="true" <?= ($disableCache ? 'checked' : '') ?> /> 
                      Use non cached direct RETS Data
                    </label>
                  </div>
                  <button type="submit" class="btn btn-primary" name="searchByKeyword">Search</button>
                </form>
              </div>

            </div>

            <!--================================
            search
            ================================-->
            <hr>
            <div id="search" class="content section">

              <div class="panel panel-primary">
                <div class="panel-heading"><h3 class="panel-title">search</h3></div>
                <div class="panel-body">
                  <p>Available for both cached (<a href="https://github.com/timitek/getrets-php-sdk#search" target="_blank">documentation</a>) and RETS (<a href="https://github.com/timitek/getrets-php-sdk#search-1" target="_blank">documentation</a>).</p>
                  <blockquote><p>Advanced search</p></blockquote>
                  <p><a href="http://getrets.net/swagger/ui/index#!/Listing/Listing_Search" target="_blank">Swagger Documentation</a></p>
                  <pre>(new GetRETS($customerKey))->getListing()->search($keywords, $maxPrice, $minPrice, $includeResidential, $includeLand, $includeCommercial);</pre>
                  <p>A more advanced search that retrieves listings constrained by the optional parameters.</p>
                </div>
              </div>

              <div class="well well-lg">
                <form action="<?= htmlspecialchars($_SERVER["PHP_SELF"]) . '#searchResults' ?>"  method="post">
                  <div class="form-group">
                    <label for="keywords">Keywords</label>
                    <input class="form-control" id="keywords" name="keywords" placeholder="Enter keywords (address, listing id, etc..)" value="<?= $keywords ?>">
                  </div>
                  <div class="form-group">
                    <label for="maxPrice">Max Price</label>
                    <input class="form-control" id="maxPrice" name="maxPrice" placeholder="Max Price" value="<?= $maxPrice ?>">
                  </div>
                  <div class="form-group">
                    <label for="minPrice">Min Price</label>
                    <input class="form-control" id="minPrice" name="minPrice" placeholder="Min Price" value="<?= $minPrice ?>">
                  </div>
                  <div class="checkbox">
                    <label>
                      <input type="checkbox" name="includeResidential" value="true" <?= ($includeResidential ? 'checked' : '') ?> /> 
                      Include Residential
                    </label>
                  </div>
                  <div class="checkbox">
                    <label>
                      <input type="checkbox" name="includeLand" value="true" <?= ($includeLand ? 'checked' : '') ?> /> 
                      Include Land
                    </label>
                  </div>
                  <div class="checkbox">
                    <label>
                      <input type="checkbox" name="includeCommercial" value="true" <?= ($includeCommercial ? 'checked' : '') ?> /> 
                      Include Commercial
                    </label>
                  </div>
                  <div class="checkbox">
                    <label>
                      <input type="checkbox" name="disableCache" value="true" <?= ($disableCache ? 'checked' : '') ?> /> 
                      Use non cached direct RETS Data
                    </label>
                  </div>
                  <button type="submit" class="btn btn-primary" name="search">Search</button>
                </form>
              </div>

            </div>


            <!--================================
            getListingsByDMQL
            ================================-->
            <hr>
            <div id="getListingsByDMQL" class="content section">

              <div class="panel panel-primary">
                <div class="panel-heading"><h3 class="panel-title">getListingsByDMQL</h3></div>
                <div class="panel-body">
                  <p>RETS Only (<a href="https://github.com/timitek/getrets-php-sdk#getlistingsbydmql" target="_blank">documentation</a>).</p>
                  <blockquote><p>Get translated listings by DMQL query</p></blockquote>
                  <p><a href="http://getrets.net/swagger/ui/index#!/RETSListing/RETSListing_GetListingsByDMQL" target="_blank">Swagger Documentation</a></p>
                  <pre>(new GetRETS($customerKey))->getRETSListing()->getListingsByDMQL($query, $feedName, $listingType);</pre>
                  <p>This is a powerful function that will execute raw DMQL against the RETS MLS server and will return the results as a serialized object.  It is similar to executeDMQL, however this function will <strong>translate</strong> data to be in the same format as returned by other methods that retrieve listing details.</p>
                </div>
              </div>

              <div class="well well-lg">
                <form action="<?= htmlspecialchars($_SERVER["PHP_SELF"]) . '#searchResults' ?>"  method="post">
                  <div class="form-group">
                    <label for="dmql">DMQL</label>
                    <textarea class="form-control" id="dmql" name="dmql" rows="3" <?= ($isPublic ? 'disabled' : '') ?>><?= $dmql ?></textarea>
                  </div>
                  <button type="submit" class="btn btn-primary" name="getListingsByDMQL">Get Listings By DMQL</button>
                </form>
              </div>

            </div>

            <!--================================
            executeDMQL
            ================================-->
            <hr>
            <div id="executeDMQL" class="content section">

              <div class="panel panel-primary">
                <div class="panel-heading"><h3 class="panel-title">executeDMQL</h3></div>
                <div class="panel-body">
                  <p>RETS Only (<a href="https://github.com/timitek/getrets-php-sdk#executedmql" target="_blank">documentation</a>).</p>
                  <blockquote><p>Return MLS results via a DMQL query</p></blockquote>
                  <p><a href="http://getrets.net/swagger/ui/index#!/RETSListing/RETSListing_ExecuteDMQL" target="_blank">Swagger Documentation</a></p>
                  <pre>(new GetRETS($customerKey))->getRETSListing()->executeDMQL($query, $feedName, $listingType);</pre>
                  <p>This is a powerful function that will execute raw DMQL against the RETS MLS server and will return the results as a serialized object.</p>
                  <p><strong><em>Special Note</em></strong> - These results will not be returned in a translated fashion similiar to the other listing detail searches.  These results are in the format as returned from the MLS RETS server.  If you wish to retrieve listings in a <strong>translated</strong> format use getListingsByDMQL.</p>
                </div>
              </div>

              <div class="well well-lg">
                <form action="<?= htmlspecialchars($_SERVER["PHP_SELF"]) . '#executeDMQL' ?>"  method="post">
                  <div class="form-group">
                    <label for="dmql">DMQL</label>
                    <textarea class="form-control" id="dmql" name="dmql" rows="3" <?= ($isPublic ? 'disabled' : '') ?>><?= $dmql ?></textarea>
                  </div>
                  <button type="submit" class="btn btn-primary" name="executeDMQL">Execute DMQL (returns raw serialized results)</button>
                </form>

                <?php if (!empty($rawData)): ?>
                <pre>
                  <?= var_dump($rawData); ?>
                </pre>
                <?php endif; ?>
              </div>

            </div>
            
            <!--================================
            searchResults
            ================================-->
            <hr>
            <div id="searchResults" class="content section">
              <h3>Search Results</h3>
              <ul class="nav nav-tabs">
                <li role="presentation" class="active" id="searchResultsNavMain"><a href="javascript:void(0);">Results</a></li>
                <li role="presentation" id="searchResultsNavData"><a href="javascript:void(0);">Data</a></li>
              </ul>

              <div id="searchResultsMain">

                <!--================================
                Sorting
                ================================-->
                <div class="row">
                  <div class="col-xs-12">
                    <?php if (empty($listings)): ?>
                      <div class="well well-sm text-center">
                        <h3><strong>No</strong> Results</h3>
                      </div>
                    <?php else: ?>
                      <br />
                      <div class="panel panel-primary">
                        <div class="panel-heading"><h3 class="panel-title">setSortBy / setReverseSort</h3></div>
                        <div class="panel-body">
                          <blockquote><p>Used for sorting / ordering the results that are returned</p></blockquote>
                          <p><a href="https://github.com/timitek/getrets-php-sdk#setsortby--setreversesort" target="_blank">Documentation</a></p>
                          <pre>(new GetRETS($customerKey))->getListing()->setSortBy("providedBy")->setReverseSort(true)->searchByKeyword($preparedKeywords);</pre>
                        </div>
                      </div>

                      <div class="well well-lg">
                        <form action="<?= htmlspecialchars($_SERVER["PHP_SELF"]) . '#searchResults' ?>"  method="post" class="form-inline">
                          <?php foreach ($_POST as $key => $value): ?>
                            <input type="hidden" name="<?= $key ?>" value="<?= $value ?>" />
                          <?php endforeach; ?>

                          <div class="form-group">
                            <p class="form-control-static"><strong>Sort By:</strong></p>
                          </div>
                          <div class="form-group">
                            <select class="form-control" name="sortBy">
                              <?php foreach ($listings[0] as $key => $value): ?>
                                <option<?= $key === $sortBy ? ' selected="true"' : '' ?>><?= $key ?></option>
                              <?php endforeach; ?>
                            </select>
                          </div>

                          <div class="form-group">
                            <p class="form-control-static"><strong>Reverse Sort:</strong></p>
                          </div>
                          <div class="form-group">
                            <input type="checkbox" name="reverseSort" value="true" <?= ($reverseSort ? 'checked' : '') ?> />
                          </div>
                          
                          <button type="submit" class="btn btn-primary" name="applySort">Apply Sort</button>
                        </form>
                      </div>
                    <?php endif; ?>

                  </div>
                </div>

                <!--================================
                Listings
                ================================-->
                <div class="row">
                  <?php foreach ($listings as $listing): ?>
                  <div class="col-xs-12 col-md-6">
                    <div class="thumbnail" style="min-height: 450px;">
                      <img src="<?= ($disableCache ? 
										   $getRets->getRETSListing()->imageUrl($listing->listingSourceURLSlug, $listing->listingTypeURLSlug, $listing->listingID, 0) : 
										   $getRets->getListing()->imageUrl($listing->listingSourceURLSlug, $listing->listingTypeURLSlug, $listing->listingID, 0)) . '?newWidth=242&maxHeight=200' ?>" alt="...">
                      <div class="caption">
                        <small><strong>Provided By:</strong> <?= $listing->providedBy ?></small><br />
                        <h3><?= $listing->address ?></h3>
                        <div class="getrets-features">
                          <ul>
                            <li><strong>Type:</strong> <?= $listing->listingTypeURLSlug; ?></li>
                            <li><strong>Price:</strong> <?= $listing->listPrice; ?></li>
                            <li><strong>Beds:</strong> <?= $listing->beds; ?></li>
                            <li><strong>Baths:</strong> <?= $listing->baths; ?></li>
                            <li><strong><abbr title="Square Feet">Sqft.</abbr>:</strong> <?= $listing->squareFeet; ?></li>
                            <li><strong>Lot:</strong> <?= $listing->lot; ?></li>
                            <li><strong>Acres:</strong> <?= $listing->acres; ?></li>
                          </ul>
                        </div>
                        <div class="pull-right">
                          <a class="btn btn-primary" role="button"
                            href="?<?= http_build_query(['source'=>$listing->listingSourceURLSlug,'type'=>$listing->listingTypeURLSlug,'id'=>$listing->listingID], null, '&', PHP_QUERY_RFC3986) ?><?= $disableCache ? '&disableCache=1' : '' ?>#details">
                            Details
                          </a>
                        </div>
                      </div>
                    </div>
                  </div>
                  <?php endforeach; ?>
                </div>              
              </div>
              <div id="searchResultsData" style="display: none;">
                <pre>
                  <?php var_dump($listings); ?>
                </pre>
              </div>            
            </div>

          </div>
        </div>
      </div>


      <!--================================
      Details
      ================================-->
      <div class="tab-pane" id="details">

        <div class="row">
          
          <div class="col-sm-3">
            <div class="list-group">
              <a class="list-group-item" href="#detailsSection">details</a>
              <a class="list-group-item" href="#imageUrl">imageUrl</a>
            </div>
          </div>

          <div class="col-sm-9">
            <h1>Details</h1>
            <p class="lead">
              Listings contain more advanced details / meta data and images.
              <ol>
                <li><a href="#detailsSection">details</a> - <em>(applicable for both <strong>cached</strong> and <strong>RETS</strong>)</em></li>
                <li><a href="#imageUrl">imageUrl</a> - <em>(applicable for both <strong>cached</strong> and <strong>RETS</strong>)</em></li>
              </ol>
            </p>

            <!--================================
            details
            ================================-->
            <hr>
            <div id="detailsSection" class="content section">

              <div class="panel panel-primary">
                <div class="panel-heading"><h3 class="panel-title">details</h3></div>
                <div class="panel-body">
                  <p>Available for both cached (<a href="https://github.com/timitek/getrets-php-sdk#details" target="_blank">documentation</a>) and RETS (<a href="https://github.com/timitek/getrets-php-sdk#details-1" target="_blank">documentation</a>).</p>
                  <blockquote><p>Get details for a specific listing</p></blockquote>
                  <p><a href="http://getrets.net/swagger/ui/index#!/Listing/Listing_GetListingDetails" target="_blank">Swagger Documentation</a></p>
                  <pre>(new GetRETS($customerKey))->getListing()->details($listingSource, $listingType, $listingId);</pre>
                  <p>Retrieves the more specific / non condensed details for a listing. You will typically use the values returned from search functions as the parameters.</p>
                </div>
              </div>

              <?php if (empty($detail)): ?>
              <div class="well well-lg">
                <p class="lead">Perform a search first and then select a listing to see the details.</p>
              </div>

              <?php else: ?>
              <ul class="nav nav-tabs">
                <li role="presentation" class="active" id="detailsNavMain"><a href="javascript:void(0);">Results</a></li>
                <li role="presentation" id="detailsNavData"><a href="javascript:void(0);">Data</a></li>
              </ul>

              <div id="detailsMain">

                <h1><?= $detail->address; ?><br /><small><?= $detail->listPrice; ?></small></h1>
                <div class="row">

                  <!--================================
                  Detail Summary
                  ================================-->
                  <div class="col-xs-12 col-md-6">
                    <div class="row">
                      <div class="col-xs-6"><small class="pull-left"><strong>Provided By:</strong> <?= $detail->providedBy; ?></small></div>
                      <div class="col-xs-6"><small class="pull-right"><strong>Listing ID:</strong> <?= $detail->listingID; ?></small></div>
                      <div class="col-xs-12">
                        <hr />
                        <p><?= $detail->description; ?></p>
                        <div class="getrets-features">
                          <ul>
                            <li><strong>Type:</strong> <?= $detail->listingTypeURLSlug; ?></li>
                            <li><strong>Price:</strong> <?= $detail->listPrice; ?></li>
                            <li><strong>Beds:</strong> <?= $detail->beds; ?></li>
                            <li><strong>Baths:</strong> <?= $detail->baths; ?></li>
                            <li><strong><abbr title="Square Feet">Sqft.</abbr>:</strong> <?= $detail->squareFeet; ?></li>
                            <li><strong>Lot:</strong> <?= $listing->lot; ?></li>
                            <li><strong>Acres:</strong> <?= $detail->acres; ?></li>
                          </ul>
                        </div>
                      </div>
                    </div>
                  </div>


                  <!--================================
                  Features
                  ================================-->
                  <div class="col-xs-12 col-md-6">
                    <div class="panel panel-default">
                      <div class="panel-heading"><h3 class="panel-title">Features</h3></div>
                      <div class="panel-body">
                        <div class="getrets-features">
                          <ul>
                            <?php foreach($detail->features as $feature): ?>
                              <li><strong><?= $feature; ?></strong></li>
                            <?php endforeach; ?>
                          </ul>
                        </div>
                      </div>
                    </div>
                  </div>
                  
                </div>

              </div>

              <div id="detailsData" style="display: none;">
                <pre>
                  <?= var_dump($detail); ?>
                </pre>
              </div>
              <?php endif; ?>

            </div>


            <!--================================
            imageUrl
            ================================-->
            <hr>
            <div id="imageUrl" class="content section">

              <div class="panel panel-primary">
                <div class="panel-heading"><h3 class="panel-title">imageUrl</h3></div>
                <div class="panel-body">
                  <p>Available for both cached (<a href="https://github.com/timitek/getrets-php-sdk#imageurl" target="_blank">documentation</a>) and RETS (<a href="https://github.com/timitek/getrets-php-sdk#imageurl-1" target="_blank">documentation</a>).</p>
                  <blockquote><p>Get details for a specific listing</p></blockquote>
                  <p><a href="http://getrets.net/swagger/ui/index#!/Listing/Listing_Image" target="_blank">Swagger Documentation</a></p>
                  <pre>(new GetRETS($customerKey))->getListing()->imageUrl($listingSource, $listingType, $listingId, $photoId, $width = null, $height = null);</pre>
                  <p>
                    Retrieves an image(s) associated with a specific listing.<br /><br />
                    <strong><em>Special Note</em></strong> - While the width and height parameters are optional, using them to specify an appropriate image size will increase the speed in which your site renders by lowering the need to download a full size image.<br /><br />
                    Also, fetching the first photo ($photoId = 0) is a suggested strategy for displaying a thumbnail image.
                  </p>
                </div>
              </div>

              <?php if (empty($detail)): ?>
              <div class="well well-lg">
                <p class="lead">Perform a search first and then select a listing to see the images.</p>
              </div>

              <?php else: ?>
              <div class="row">
                <!--================================
                Image Carousel
                ================================-->
                <div class="col-xs-12 col-md-6">
                  <div id="carousel-example-generic" class="carousel slide" data-ride="carousel">
                    <!-- Indicators -->
                    <ol class="carousel-indicators">
                      <?php for ($i = 0; $i < $detail->photoCount; $i++): ?>
                      <li data-target="#carousel-example-generic" data-slide-to="0" <?= $i===0 ? 'class="active"' : '' ?>></li>
                      <?php endfor; ?>
                    </ol>

                    <!-- Wrapper for slides -->
                    <div class="carousel-inner" role="listbox">
                      <?php for ($i = 0; $i < $detail->photoCount; $i++): ?>
                      <div class="item<?= $i===0 ? ' active' : '' ?>">
                        <img src="<?= $getRets->getListing()->imageUrl($detail->listingSourceURLSlug, $detail->listingTypeURLSlug, $detail->listingID, $i); ?>" class="img-responsive" alt="..." style="width: 100%;">
                        <div class="carousel-caption">
                          Photo <?= $i+1; ?>
                        </div>
                      </div>
                      <?php endfor; ?>
                    </div>

                    <!-- Controls -->
                    <a class="left carousel-control" href="#carousel-example-generic" role="button" data-slide="prev">
                      <span class="glyphicon glyphicon-chevron-left" aria-hidden="true"></span>
                      <span class="sr-only">Previous</span>
                    </a>
                    <a class="right carousel-control" href="#carousel-example-generic" role="button" data-slide="next">
                      <span class="glyphicon glyphicon-chevron-right" aria-hidden="true"></span>
                      <span class="sr-only">Next</span>
                    </a>
                  </div>
                </div>

                <!--================================
                Thumbnails
                ================================-->
                <div class="col-xs-12 col-md-6">
                  <div class="panel panel-default">
                    <div class="panel-heading"><h3 class="panel-title">Thumbnails</h3></div>
                    <div class="panel-body">
                      <div class="row">
                      <?php for ($i = 0; $i < $detail->photoCount; $i++): ?>
                        <?php $imgSrc = $getRets->getListing()->imageUrl($detail->listingSourceURLSlug, $detail->listingTypeURLSlug, $detail->listingID, $i); ?>
                        <div class="col-xs-6">
                          <a href="<?= $imgSrc; ?>" target="_blank" class="thumbnail">
                            <img src="<?= $imgSrc; ?>" alt="...">
                          </a>
                        </div>
                      <?php endfor; ?>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <?php endif; ?>

            </div>

          </div>
        </div>

      </div>

    </div><!-- /.container -->


    <!-- Bootstrap core JavaScript
    ================================================== -->
    <!-- Placed at the end of the document so the pages load faster -->
    <script src="https://code.jquery.com/jquery-1.10.2.min.js"></script>
    
    <!-- Latest compiled and minified JavaScript -->
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>
    
    <script>

      /* scroll to top when clicked on navbar */
      $(".navbar > .container").on("click", function(e){
        $('html,body').animate({scrollTop:0},'slow');
      });

      /* brand logo click */
      $(".navbar-brand").on("click",function(){
        $("#home").addClass("active").siblings().removeClass("active");
        $("#tabs > li").removeClass("active");
      });

      /* make list-group selectable */
      $(".list-group>a").on("click", function(){
        $(this).addClass("active").siblings().removeClass("active");
      });

      /* auto select list-group item on scroll */
      $(document).scroll(function(){
        $('.section').each(function(){
          var position = $(document).scrollTop() - $(this).offset().top;
          if(position < 30 && position > -115) {
            $($('a[href$='+$(this).attr("id")+']')).click();
          }
        });      
      });

      /* set hash on tab select */
      $('#tabs a').click(function() {
        window.location.hash = $(this).attr("href");
        $(document).scrollTop(0);
        $(".list-group > a").removeClass("active");
      });

      /* search results tabs */
      var searchResultsNavMain = $('#searchResultsNavMain');
      var searchResultsNavData = $('#searchResultsNavData');
      var handleSearchResultsNav = function (showResults) {
        var searchResultsMain = $('#searchResultsMain');
        var searchResultsData = $('#searchResultsData');

        searchResultsNavMain.removeClass("active");
        searchResultsNavData.removeClass("active");
        searchResultsMain.hide();
        searchResultsData.hide();

        if (showResults) {
          searchResultsNavMain.addClass("active");
          searchResultsMain.show();
        }
        else {
          searchResultsNavData.addClass("active");
          searchResultsData.show();
        }
      };
      searchResultsNavMain.click(function() { handleSearchResultsNav(true); });
      searchResultsNavData.click(function() { handleSearchResultsNav(false); });


      /* details tabs */
      var detailsNavMain = $('#detailsNavMain');
      var detailsNavData = $('#detailsNavData');
      var handledetailsNav = function (showResults) {
        var detailsMain = $('#detailsMain');
        var detailsData = $('#detailsData');

        detailsNavMain.removeClass("active");
        detailsNavData.removeClass("active");
        detailsMain.hide();
        detailsData.hide();

        if (showResults) {
          detailsNavMain.addClass("active");
          detailsMain.show();
        }
        else {
          detailsNavData.addClass("active");
          detailsData.show();
        }
      };
      detailsNavMain.click(function() { handledetailsNav(true); });
      detailsNavData.click(function() { handledetailsNav(false); });


      $(document).ready(function(){

        if(window.location.hash !== ""){
          /* open tab based on hash value */
          if($('#tabs > li > a[href="'+window.location.hash+'"]').length){
            $('#tabs > li > a[href="'+window.location.hash+'"]').tab('show');
            setTimeout(function(){$(document).scrollTop(0)}, 200);
          }
          /* open tab and section based on hash value */
          if($('.list-group > a[href="'+window.location.hash+'"]').length){
            var tab = $('.list-group > a[href="'+window.location.hash+'"]').closest(".tab-pane").attr("id");
            $('a[href="#'+tab+'"]').tab('show');
            $('.list-group > a[href="'+window.location.hash+'"]').click();
            setTimeout(function(){$(document).scrollTop($(window.location.hash).offset().top)}, 200);
          }
        }
      });
    </script>
  </body>
</html>