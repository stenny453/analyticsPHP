<?php

// Load the Google API PHP Client Library.
require_once __DIR__ . '/vendor/autoload.php';

$analytics = initializeAnalytics();
$profile = getFirstProfileId($analytics);
// $results = getResults($analytics, $profile, '7daysAgo', 'today');
// printResults($results);

// --------------- Get chart result ----------------
$result = getChartResults($analytics, $profile, 'ga:pageviews, ga:users, ga:sessions', '30daysAgo', 'today');
$list = $result->rows;


function initializeAnalytics()
{
  $KEY_FILE_LOCATION = __DIR__ . '/service-account-credentials.json';

  $client = new Google_Client();
  $client->setApplicationName("Analytics Reporting");
  $client->setAuthConfig($KEY_FILE_LOCATION);
  $client->setScopes(['https://www.googleapis.com/auth/analytics.readonly']);
  $analytics = new Google_Service_Analytics($client);
  return $analytics;
}

function getFirstProfileId($analytics) {
  // Get the list of accounts for the authorized user.
  $accounts = $analytics->management_accounts->listManagementAccounts();

  if (count($accounts->getItems()) > 0) {
    $items = $accounts->getItems();
    $firstAccountId = $items[0]->getId();

    // Get the list of properties for the authorized user.
    $properties = $analytics->management_webproperties
        ->listManagementWebproperties($firstAccountId);

    if (count($properties->getItems()) > 0) {
      $items = $properties->getItems();
      $firstPropertyId = $items[0]->getId();

      // Get the list of views (profiles) for the authorized user.
      $profiles = $analytics->management_profiles
          ->listManagementProfiles($firstAccountId, $firstPropertyId);

      if (count($profiles->getItems()) > 0) {
        $items = $profiles->getItems();

        // Return the first view (profile) ID.
        return $items[0]->getId();

      } else {
        throw new Exception('No views (profiles) found for this user.');
      }
    } else {
      throw new Exception('No properties found for this user.');
    }
  } else {
    throw new Exception('No accounts found for this user.');
  }
}

function getResults($analytics, $profileId, $begin, $end) {
  // Calls the Core Reporting API and queries for the number of sessions
   return $analytics->data_ga->get(
       'ga:' . $profileId,
       $begin, 
       $end,
       'ga:sessions');
}

function printResults($results) {
  if (count($results->getRows()) > 0) {

    // Get the profile name.
    $profileName = $results->getProfileInfo()->getProfileName();

    // Get the entry for the first entry in the first row.
    $rows = $results->getRows();
    $sessions = $rows[0][0];

    // Print the results.
    print "First view (profile) found: $profileName\n";
    print "Total sessions: $sessions\n";
  } else {
    print "No results found.\n";
  }
}

function getChartResults($analytics, $profileId, $metrics, $begin, $end) {
  return $analytics->data_ga->get(
      'ga:' . $profileId,
      $begin,
      $end,
      $metrics,
      [
          'dimensions' => 'ga:Date'
      ]
  );
}

function buildChartArray($results) {
  if (count($results) > 0) {
      $rows = $results;
      $array = [['Date', 'Pages Vues', 'Visiteurs', 'Visites']];
      foreach($rows as $date) {
          $dateJour = substr($date[0], -2, 2).'/'.substr($date[0], -4, 2).'/'.substr($date[0], 0, 4);
          $array[] = [$dateJour, (int)$date[1], (int)$date[2], (int)$date[3]];
      }
      return json_encode($array);
  } else {
      return 'No result found';
  }
}

function console_log($output, $with_script_tags = true) {
  $js_code = 'console.log(' . json_encode($output, JSON_HEX_TAG) . ');';
  if ($with_script_tags) {
      $js_code = '<script>' . $js_code . '</script>';
  }
  echo $js_code;
}

?>

<div style="width: 100%; text-align: center;font-weight: bold;">
  <h1>Data Analytics</h1>
</div>

<div id="visites" style="height: 400px; padding: 0;margin: 20px;">
</div>

<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
<script type="text/javascript">
      google.charts.load('current', {'packages':['line']});
      google.charts.setOnLoadCallback(drawChart);

    function drawChart() {
      var data = google.visualization.arrayToDataTable(
            <?= buildChartArray($list) ?>
      );

      var options = {
        curveType: 'function',
            series: {
                0: {targetAxisIndex: 0},
                1: {targetAxisIndex: 1},
                2: {targetAxisIndex: 1},
            },
            hAxis: {
              textStyle: {
                fontSize: 10,
                fontName: 'Nunito'
              }
            },
            vAxes: {
              0: {
                gridlines: {color: 'transparent'},
                textStyle: {
                  fontSize: 10,
                  fontName: 'Nunito'
                }
              },
              1: {
                gridlines: {color: 'transparent'},
                textStyle: {
                  fontSize: 10,
                  fontName: 'Nunito'
                }
              }
            },
            legend: {
              position: 'bottom',
              textStyle: {
                  fontSize: 10,
                  fontName: 'Nunito'
              }
            }
      };

      var chart = new google.charts.Line(document.getElementById('visites'));

      chart.draw(data, google.charts.Line.convertOptions(options));
    }
</script>