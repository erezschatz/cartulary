<? include get_cfg_var("cartulary_conf") . '/includes/env.php'; ?>
<? include "$confroot/$templates/php_bin_init.php" ?>
<?
//Let's not run twice
if ( ( $pid = cronHelper::lock() ) !== FALSE ) {

    //Track the river scan time
    $tstart = time();

    //Get the feed list
    //Checking a single feed?
    if ( isset($argv[1]) && !empty($argv[1]) ) {
        $feed = get_feed_info(feed_exists($argv[1]));
        $feeds = array( $feed );
    } else {
        $feeds = get_updated_feeds();
    }

    $totalfeeds = count($feeds);
    $totaltime = $totalfeeds * 5;
    $scancount = $totalfeeds;

    loggit(3, " ----- Start scan of [$scancount] of [$totalfeeds] feeds.");
    echo "Scanning [$scancount] of [$totalfeeds] feeds.\n\n";

    // Walk the feed list and get new items in each
    $ccount = 0;
    $ncount = 0;
    $newitems = 0;
    foreach ( $feeds as $feed ) {
        loggit(1, "Checking feed: [" . $feed['title'] . " | " . $feed['url'] . "]...");
        echo "Checking feed: [" . $feed['title'] . " | " . $feed['url'] . "]...\n";

        //Make a timestamp
        $fstart = time();

        //Parse the feed and add new items to the database
        loggit(1, "Checking feed: [ $ccount | " . $feed['title'] . " | " . $feed['url'] . "].");
        $result = get_feed_items($feed['id']);
        if ( $result == -1 ) {
            loggit(1, "Error getting items for feed: [" . $feed['title'] . " | " . $feed['url'] . "]");
            echo "    Error getting items for feed: [" . $feed['title'] . " | " . $feed['url'] . "]\n";
        } else if ( $result == -2 ) {
            loggit(1, "Feed: [" . $feed['title'] . " | " . $feed['url'] . "] has no items.");
            echo "    Feed is empty.\n";
        } else if ( $result == -3 ) {
            loggit(1, "Feed: [" . $feed['title'] . " | " . $feed['url'] . "] is current.");
            echo "    Feed is current.\n";
        } else {
            loggit(3, "Feed: [" . $feed['title'] . " | " . $feed['url'] . "] updated.");
            echo "    Feed updated.\n";
            $ncount++;
            $newitems += $result;
        }

        $ccount++;

        echo "      It took " . ( time() - $fstart ) . " seconds to scan this feed.\n";
        loggit(1, "It took [" . ( time() - $fstart ) . "] seconds to scan this feed.");
        echo "\n";

        //We stop scanning if this scan has taken longer than expected
        if ( ( time() - $tstart ) > $totaltime ) {
            loggit(3, "Stop scan because it took longer than the expected: [$totaltime] seconds.");
            break;
        }

        //We stop scanning once we hit our feed count limit for this pass
        if ( $ccount >= $scancount ) {
            break;
        }
    }
    echo "\n";

    //Calculate time took to scan the river
    loggit(3, "It took " . ( time() - $tstart ) . " seconds to scan [$ccount] of [$totalfeeds] feeds.");
    loggit(3, "Total checked: [$ccount]. Updated: [$ncount]. New items: [$newitems].");
    echo "      It took " . ( time() - $tstart ) . " seconds to scan [$ccount] of [$totalfeeds] feeds.\n";
    echo "Total checked: [$ccount]. Updated: [$ncount]. New items: [$newitems].\n";


    // Log and leave
    loggit(3, "Feedscan finished.");

    //Remove the lock file
    cronHelper::unlock();
}
exit( 0 );