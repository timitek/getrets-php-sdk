<?php

include "../GetRETS.php";

ini_set('max_execution_time', 300);  // Give enough time (5 mintues) for slow DMQL queries

$customerKey = '';

$isPublic = false;
$exampleAddress = "sheridan, ar";
$exampleSource = "CARMLS";

$keywords = (array_key_exists("keywords", $_POST) ? $_POST["keywords"] : $exampleAddress );
$disableCache = !empty($_POST["disableCache"]);
$maxPrice = (array_key_exists("maxPrice", $_POST) ? $_POST["maxPrice"] : null );
$minPrice = (array_key_exists("minPrice", $_POST) ? $_POST["minPrice"] : null );
$includeResidential = array_key_exists("includeResidential", $_POST);
$includeLand = array_key_exists("includeLand", $_POST);
$includeCommercial = array_key_exists("includeCommercial", $_POST);

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

if (!empty($_POST)) {
  $getRets = new GetRETS($customerKey);
  // Keyword Search
  if (array_key_exists("searchByKeyword", $_POST)) {
    $preparedKeywords = htmlspecialchars($_POST["keywords"]);
    if ($disableCache) {
      $listings = $getRets->getRETSListing()->searchByKeyword($preparedKeywords);
    }
    else {
      $listings = $getRets->getListing()->searchByKeyword($preparedKeywords);
    }
  }
  // Advanced Search
  else if (array_key_exists("search", $_POST)) {
    if ($disableCache) {
      $listings = $getRets->getRETSListing()->search($keywords, $maxPrice, $minPrice, $includeResidential, $includeLand, $includeCommercial);
    }
    else {
      $listings = $getRets->getListing()->search($keywords, $maxPrice, $minPrice, $includeResidential, $includeLand, $includeCommercial);
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
    $results = $getRets->getRETSListing()->getListingsByDMQL($dmql, $exampleSource, "Residential");
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
              <h3>searchByKeyword</h3>
              <p>
                Avaialble for both cached (<a href="https://github.com/timitek/getrets-php-sdk#searchbykeyword" target="_blank">getListing</a>) and RETS (<a href="https://github.com/timitek/getrets-php-sdk#searchbykeyword-1" target="_blank">getRETSListing</a>).
              </p>
              <blockquote>
              <p>Search for listings by keyword</p>
              </blockquote>

              <p><a href="http://getrets.net/swagger/ui/index#!/Listing/Listing_SearchByKeyword">Swagger Documentation</a></p>

              <div class="highlight highlight-text-html-php"><pre><span class="pl-s1">(<span class="pl-k">new</span> <span class="pl-c1">GetRETS</span>(<span class="pl-smi">$customerKey</span>))<span class="pl-k">-&gt;</span>getListing()<span class="pl-k">-&gt;</span>searchByKeyword(<span class="pl-smi">$preparedKeywords</span>);</span></pre></div>

              <p>A simple search that will retrieve listings by a keyword search.</p>
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
                <button type="submit" class="btn btn-default" name="searchByKeyword">Search</button>
              </form>              
            </div>

            <!--================================
            search
            ================================-->
            <hr>
            <div id="search" class="content section">
              <h3>search</h3>
              <p>
                Avaialble for both cached (<a href="https://github.com/timitek/getrets-php-sdk#search" target="_blank">getListing</a>) and RETS (<a href="https://github.com/timitek/getrets-php-sdk#search-1" target="_blank">getRETSListing</a>).
              </p>
              <blockquote>
              <p>Advanced search</p>
              </blockquote>

              <p><a href="http://getrets.net/swagger/ui/index#!/Listing/Listing_Search">Swagger Documentation</a></p>

              <div class="highlight highlight-text-html-php"><pre><span class="pl-s1">(<span class="pl-k">new</span> <span class="pl-c1">GetRETS</span>(<span class="pl-smi">$customerKey</span>))<span class="pl-k">-&gt;</span>getListing()<span class="pl-k">-&gt;</span>search(<span class="pl-smi">$keywords</span>, <span class="pl-smi">$maxPrice</span>, <span class="pl-smi">$minPrice</span>, <span class="pl-smi">$includeResidential</span>, <span class="pl-smi">$includeLand</span>, <span class="pl-smi">$includeCommercial</span>);</span></pre></div>

              <p>A more advanced search that retrieves listings constrained by the optional parameters.</p>              
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
                <button type="submit" class="btn btn-default" name="search">Search</button>
              </form>              
            </div>


            <!--================================
            getListingsByDMQL
            ================================-->
            <hr>
            <div id="getListingsByDMQL" class="content section">
              <h3>getListingsByDMQL</h3>
              <p>
                RETS Only (<a href="https://github.com/timitek/getrets-php-sdk#getlistingsbydmql" target="_blank">getRETSListing</a>).
              </p>
              <blockquote>
                <p>Get translated listings by DMQL query</p>
              </blockquote>
              <p><a href="http://getrets.net/swagger/ui/index#!/RETSListing/RETSListing_GetListingsByDMQL">Swagger Documentation</a></p>
              <div class="highlight highlight-text-html-php"><pre><span class="pl-s1">(<span class="pl-k">new</span> <span class="pl-c1">GetRETS</span>(<span class="pl-smi">$customerKey</span>))<span class="pl-k">-&gt;</span>getRETSListing()<span class="pl-k">-&gt;</span>getListingsByDMQL(<span class="pl-smi">$query</span>, <span class="pl-smi">$feedName</span>, <span class="pl-smi">$listingType</span>);</span></pre></div>              
              <p>This is a powerful function that will execute raw DMQL against the RETS MLS server and will return the results as a serialized object.  It is similar to executeDMQL, however this function will <strong>translate</strong> data to be in the same format as returned by other methods that retrieve listing details.</p>
              <form action="<?= htmlspecialchars($_SERVER["PHP_SELF"]) . '#searchResults' ?>"  method="post">
                <div class="form-group">
                  <label for="dmql">DMQL</label>
                  <textarea class="form-control" id="dmql" name="dmql" rows="3" <?= ($isPublic ? 'disabled' : '') ?>><?= $dmql ?></textarea>
                </div>
                <button type="submit" class="btn btn-default" name="getListingsByDMQL">Get Listings By DMQL</button>
              </form>              
            </div>

            <!--================================
            executeDMQL
            ================================-->
            <hr>
            <div id="executeDMQL" class="content section">
              <h3>executeDMQL</h3>
              <p>
                RETS Only (<a href="https://github.com/timitek/getrets-php-sdk#executedmql" target="_blank">getRETSListing</a>).
              </p>
              <blockquote>
              <p>Return MLS results via a DMQL query</p>
              </blockquote>

              <p><a href="http://getrets.net/swagger/ui/index#!/RETSListing/RETSListing_ExecuteDMQL">Swagger Documentation</a></p>

              <div class="highlight highlight-text-html-php"><pre><span class="pl-s1">(<span class="pl-k">new</span> <span class="pl-c1">GetRETS</span>(<span class="pl-smi">$customerKey</span>))<span class="pl-k">-&gt;</span>getRETSListing()<span class="pl-k">-&gt;</span>executeDMQL(<span class="pl-smi">$query</span>, <span class="pl-smi">$feedName</span>, <span class="pl-smi">$listingType</span>);</span></pre></div>

              <p>This is a powerful function that will execute raw DMQL against the RETS MLS server and will return the results as a serialized object.</p>

              <p><strong><em>Special Note</em></strong> - These results will not be returned in a translated fashion similiar to the other listing detail searches.  These results are in the format as returned from the MLS RETS server.  If you wish to retrieve listings in a <strong>translated</strong> format use getListingsByDMQL.</p>
              <form action="<?= htmlspecialchars($_SERVER["PHP_SELF"]) . '#executeDMQL' ?>"  method="post">
                <div class="form-group">
                  <label for="dmql">DMQL</label>
                  <textarea class="form-control" id="dmql" name="dmql" rows="3" <?= ($isPublic ? 'disabled' : '') ?>><?= $dmql ?></textarea>
                </div>
                <button type="submit" class="btn btn-default" name="executeDMQL">Execute DMQL (returns raw serialized results)</button>
              </form>

              <?php if (!empty($rawData)): ?>
              <pre>
                <?= json_encode($rawData, JSON_PRETTY_PRINT) ?>
              </pre>
              <?php endif; ?>
            </div>
            
            <!--================================
            searchResults
            ================================-->
            <hr>
            <div id="searchResults" class="content section">
              <h3>Search Results</h3>
              <ul class="nav nav-tabs">
                <li role="presentation" class="active" id="searchResultsNavRendered"><a href="javascript:void(0);">Rendered</a></li>
                <li role="presentation" id="searchResultsNavVarDump"><a href="javascript:void(0);">Var Dump</a></li>
              </ul>
              <div id="searchResultsRendered">
                <div class="row">
                  <?php if (empty($listings)): ?>
                    <div class="col-xs-12">
                      <h3>No Results</h3>
                    </div>
                  <?php endif; ?>
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
                            href="#details?<?= http_build_query(['source'=>$listing->listingSourceURLSlug,'type'=>$listing->listingTypeURLSlug,'id'=>$listing->listingID], null, '&', PHP_QUERY_RFC3986) ?>">
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
      var searchResultsNavRendered = $('#searchResultsNavRendered');
      var searchResultsNavVarDump = $('#searchResultsNavVarDump');
      var handleSearchResultsNav = function (showRendered) {
        var searchResultsRendered = $('#searchResultsRendered');
        var searchResultsVarDump = $('#searchResultsVarDump');

        searchResultsNavRendered.removeClass("active");
        searchResultsNavVarDump.removeClass("active");
        searchResultsRendered.hide();
        searchResultsVarDump.hide();

        if (showRendered) {
          searchResultsNavRendered.addClass("active");
          searchResultsRendered.show();
        }
        else {
          searchResultsNavVarDump.addClass("active");
          searchResultsVarDump.show();
        }
      };
      searchResultsNavRendered.click(function() { handleSearchResultsNav(true); });
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