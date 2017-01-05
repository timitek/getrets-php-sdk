<?php

include "../GetRETS.php";

ini_set('max_execution_time', 300);  // Give enough time (5 mintues) for slow DMQL queries

$customerKey = 'aspirerealty';

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
            <li><a href="#images" data-toggle="tab">Images</a></li>
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
                <li><a href="#searchByKeyword">searchByKeyword</a> - <i>(applicable for both <strong>cached</strong> and <strong>RETS</strong>)</i></li>
                <li><a href="#search">search</a> - <i>(applicable for both <strong>cached</strong> and <strong>RETS</strong>)</i></li>
                <li><a href="#getListingsByDMQL">getListingsByDMQL</a> - <i>(<strong>RETS</strong> Only)</i></li>
                <li><a href="#executeDMQL">executeDMQL</a> - <i>(<strong>RETS</strong> only)</i></li>
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
                  <?= json_encode($rawData, JSON_PRETTY_PRINT) ?>
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
                <li role="presentation" class="active" id="searchResultsNavResults"><a href="javascript:void(0);">Results</a></li>
                <li role="presentation" id="searchResultsNavVarDump"><a href="javascript:void(0);">Var Dump</a></li>
              </ul>
              <div id="searchResultsResults">

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
                            <li><strong>Type:</strong> <?= $listing->listingTypeURLSlug ?></li>
                            <li><strong>Price:</strong> <?= $listing->listPrice ?></li>
                            <li><strong>Beds:</strong> <?= $listing->beds ?></li>
                            <li><strong>Baths:</strong> <?= $listing->baths ?></li>
                            <li><strong><abbr title="Square Feet">Sqft.</abbr>:</strong> <?= $listing->squareFeet ?></li>
                            <li><strong>Lot:</strong> <?= $listing->lot ?></li>
                            <li><strong>Price:</strong> <?= $listing->listingTypeURLSlug ?></li>
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
              <div id="searchResultsVarDump" style="display: none;">
                <pre>
                  <?php var_dump($listings); ?>
                </pre>
              </div>            
            </div>

          </div>
        </div>
      </div>

      <div class="tab-pane" id="details">
        <?php if (!empty($detail)): ?>
        <pre>
          <?= json_encode($detail, JSON_PRETTY_PRINT) ?>
        </pre>



<div id='getrets-content' class='getrets-content'>
    <div id='getrets-details' class='getrets-details'>
        <div id='getrets-details-title' class='getrets-title'>
            Details
        </div>
        <?php if ($detail->address): ?>
        <div id='getrets-detail-address' class='getrets-detail'>
            <span id='getrets-label-address' class='getrets-label'>Address:</span> <span id='getrets-value-address' class='getrets-value'><?= $detail->address; ?></span>
        </div>
        <?php endif; ?>
        <?php if ($detail->listPrice): ?>
        <div id='getrets-detail-listprice' class='getrets-detail'>
            <span id='getrets-label-listprice' class='getrets-label'>List Price:</span> <span id='getrets-value-listprice' class='getrets-value'><?= $detail->listPrice; ?></span>
        </div>
        <?php endif; ?>
        <?php if ($detail->listingTypeURLSlug): ?>
        <div id='getrets-detail-listingtype' class='getrets-listingtype'>
            <span id='getrets-label-listingtype' class='getrets-label'>Listing Type:</span> <span id='getrets-value-listingtype' class='getrets-value'><?= $detail->listingTypeURLSlug; ?></span>
        </div>
        <?php endif; ?>
        <?php if ($detail->listingID): ?>
        <div id='getrets-detail-listingid' class='getrets-listingid'>
            <span id='getrets-label-listingid' class='getrets-label'>Listing ID:</span> <span id='getrets-value-listingid' class='getrets-value'><?= $detail->listingID; ?></span>
        </div>
        <?php endif; ?>
        <?php if ($detail->squareFeet): ?>
        <div id='getrets-detail-squarefeet' class='getrets-detail'>
            <span id='getrets-label-squarefeet' class='getrets-label'>Square Feet:</span> <span id='getrets-value-squarefeet' class='getrets-value'><?= $detail->squareFeet; ?></span>
        </div>
        <?php endif; ?>
        <?php if ($detail->beds): ?>
        <div id='getrets-detail-beds' class='getrets-detail'>
            <span id='getrets-label-beds' class='getrets-label'>Beds:</span> <span id='getrets-value-beds' class='getrets-value'><?= $detail->beds; ?></span>
        </div>
        <?php endif; ?>
        <?php if ($detail->baths): ?>
        <div id='getrets-detail-baths' class='getrets-detail'>
            <span id='getrets-label-baths' class='getrets-label'>Baths:</span> <span id='getrets-value-baths' class='getrets-value'><?= $detail->baths; ?></span>
        </div>
        <?php endif; ?>
        <?php if ($detail->acres): ?>
        <div id='getrets-detail-acres' class='getrets-detail'>
            <span id='getrets-label-acres' class='getrets-label'>Acres:</span> <span id='getrets-value-acres' class='getrets-value'><?= $detail->acres; ?></span>
        </div>
        <?php endif; ?>
        <?php if ($detail->lot): ?>
        <div id='getrets-detail-lot' class='getrets-detail'>
            <span id='getrets-label-lot' class='getrets-label'>Lot:</span> <span id='getrets-value-lot' class='getrets-value'><?= $detail->lot; ?></span>
        </div>
        <?php endif; ?>
    </div>
    <div id='getrets-description' class='getrets-description'>
        <div id='getrets-description-title' class='getrets-title'>
            Description
        </div>
        <?= $description; ?>
    </div>
    <?php if ($detail->features): ?>
    <div id='getrets-features' class='getrets-features'>
        <div id='getrets-features-title' class='getrets-title'>
            Features
        </div>
        <ul>
            <?php foreach( $detail->features as $item ): ?>
            <li><?= $item ?></li>
            <?php endforeach; ?>
        </ul>        
    </div>
    <?php endif; ?>
    <?php if ($detail->photoCount > 0): ?>
    <div id='getrets-photos' class='getrets-photos'>
        <div id='getrets-photos-title' class='getrets-title'>
            Photos
        </div>
        <?php
        for ($i = 0; $i < $detail->photoCount; $i++) {
            $img = $getRets->getListing()->imageUrl($detail->listingSourceURLSlug, $detail->listingTypeURLSlug, $detail->listingID, $i);	
            echo "<div class='getrets-photo-responsive'><div class='getrets-photo-container'><a target='_blank' href='" . $img . "' class='getrets-photos-link'><img src='" . $img . "?newWidth=200&maxHeight=200' class='getrets-photo' width='200' height='200' /></a></div></div>"; 
        }
        unset($photo);
        ?>
        <div class="clearfix"></div>
    </div>
    <?php endif; ?>
    <div id='getrets-providedby' class='getrets-providedby'>
        <span id='getrets-providedby-label' class='getrets-label'>Provided By:</span> <span id='getrets-providedby-value' class='getrets-value'><?= $detail->providedBy; ?></span>
    </div>
</div>        














        <?php endif; ?>
      </div>

      <div class="tab-pane" id="images">
        <h1>Image Examples</h1>
        <p class="lead">This section demonstrates the image capabilities.</p>
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
      var searchResultsNavResults = $('#searchResultsNavResults');
      var searchResultsNavVarDump = $('#searchResultsNavVarDump');
      var handleSearchResultsNav = function (showResults) {
        var searchResultsResults = $('#searchResultsResults');
        var searchResultsVarDump = $('#searchResultsVarDump');

        searchResultsNavResults.removeClass("active");
        searchResultsNavVarDump.removeClass("active");
        searchResultsResults.hide();
        searchResultsVarDump.hide();

        if (showResults) {
          searchResultsNavResults.addClass("active");
          searchResultsResults.show();
        }
        else {
          searchResultsNavVarDump.addClass("active");
          searchResultsVarDump.show();
        }
      };
      searchResultsNavResults.click(function() { handleSearchResultsNav(true); });
      searchResultsNavVarDump.click(function() { handleSearchResultsNav(false); });


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