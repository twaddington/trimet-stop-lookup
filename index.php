<?php

require_once('./settings.php');
require_once('./libs/phpMet/src/Trimet.php');

$stop_ids = '';
$error_msg = '';
$arrivals = array();
if (isset($_GET['LocIDs'])) {
    $stop_ids = $_GET['LocIDs'];
    $LocIDs = explode(',', $stop_ids);

    $LocIDs = array_filter($LocIDs, 'TrimetAPIQuery::sanitizeLocationID');

    if (!empty($LocIDs)) {
        $api = new TrimetAPIQuery();
        $arrivals = $api->getArrivals($LocIDs);
    }
    else {
        $error_msg = 'Invalid stop! Try a numeric stop ID like "1448".';
    }
}

?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">

        <title>Where's My (TriMet) Bus?</title>

        <meta name="description" content="Enter your TriMet stop number to find out when your bus will arrive in real time!">
        <meta name="author" content="Tristan Waddington">

        <style>
            html,
            body {
                margin: 0px;
                padding: 0px;
                font-size: 14px;
                font-family: "Lucida Grande", "Lucida Sans", Arial, Verdana, sans-serif;
            }
            html {
                background-color: #fff;
            }
            body {
                background-color: transparent;
            }
            .wrap {
                position: relative;
                min-width: 300px;
                max-width: 600px;
                margin: 0px auto;
                padding: 10px;
            }
            h1,h2,h3,h4,h5,h6 {
                margin: 0px;
                padding: 0px;
                color: #084C8D; 
                clear: both;
            }
            h1 {
                margin-top: 20px;
                font-size: 24px;
            }
            h2 {
                font-size: 22px; 
            }
            h3 {
                font-size: 18px;
            }
            fieldset {
                margin: 0px;
                padding: 5px 10px;
                color: #fff;
                border: none;
                background-color: #084C8D;
            }
            fieldset ol,
            fieldset li {
                margin: 0px;
                padding: 0px;
                list-style-type: none;
            }
            fieldset li {
                position: relative;
                margin: 5px 0px;
                background-color: #fff;
                overflow: hidden;
            }
            fieldset span {
                display: block;
                margin-top: 5px;
                font-size: 12px;
            }
            input {
                position: relative;
                margin: 0px;
                padding: 0px;
            }
            input[type="text"] {
                position: absolute;
                width: 100%;
                margin: 0;
                padding: 10px;
                color: #999;
                font-size: 24px;
                border: none;
                outline: none;
                z-index: 1;
            }
            input[type="submit"] {
                display: block;
                width: 100px;
                margin: 5px;
                padding: 8px 0px;
                color: #084C8D;
                font-size: 16px;
                text-transform: uppercase;
                border: none;
                background: #DADFE1;
                cursor: pointer;
                z-index: 2;
                float: right;
            }
            #find-stops {
                display: none;
                text-align: right;
                background-color: #084C8D;
            }
            #find-stops .wrap {
                padding: 0;
            }
            #find-stops a:link,
            #find-stops a:visited,
            #find-stops a:hover,
            #find-stops a:active {
                display: block;
                margin: 0;
                padding: 15px 10px;
                color: #fff;
                text-decoration: none;
            }
            #loading {
                display: none;
                width: 16px;
                height: 16px;
                margin: 0;
                padding: 0;
                background: transparent url('images/spinner.gif') no-repeat center;
                float: left;
            }
            #debug {
                color: #999;
                font-size: 10px;
                text-align: center;
            }
            #results {
                margin: 20px 10px;
                padding: 0px;
            }
            #results ul {
                display: block;
                margin: 10px 0 0 0;
                padding: 0;
                list-style-type: none;
            }
            #results li:first-child {
                border: none;
            }
            #results li {
                display: block;
                margin: 10px 0;
                padding: 10px 0 0 0;
                color: #666;
                font-size: 16px;
                border-top: 1px solid #DADFE1;
                list-style-type: none;
            }
            .arrival {
                margin: 0 0 2px 0;
                color: #333;
            }
            .arrival-line {
                /* stub */
            }
            .arrival-time {
                color: #084C8D;
                font-weight: bold;
            }
            .arrival-location {
                color: #A9A9A9;
                font-size: 13px;
            }
            #stop-list {
                display: none;
                overflow: hidden;
            }
            #stop-list ul,
            #stop-list li {
                display: block;
                margin: 0;
                padding: 0;
                list-style-type: none;
            }
            #stop-list li a:link,
            #stop-list li a:visited {
                display: block;
                margin: 5px;
                padding: 15px 10px;
                color: #084C8D;
                text-decoration: none;
                text-overflow: ellipsis;
                white-space: nowrap;
                background-color: #DADFE1;
            }
            #footer {
                position: relative;
                margin: 20px 0px;
                padding: 10px 0;
                color: #999;
                text-align: right;
                border-top: 1px solid #A9A9A9;
            }
            a:link,
            a:visited {
                color: #999;
            }
            a:hover,
            a:active {
                text-decoration: none;
            }
            .error {
                margin: 20px 10px;
                padding: 0px;
                color: red;
            }
        </style>

        <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>
        <script>
            $(document).ready(function() {
                if (navigator.geolocation) {
                    var find_stops = $('#find-stops');
                    var stop_list = $('#stop-list');
                    var loading = $('#loading');

                    find_stops.show();
                    find_stops.find('a:first').click(function(e) {
                        // Show loading indicator
                        loading.show();

                        // Get the user's location
                        navigator.geolocation.getCurrentPosition(function(pos) {
                            // Debug
                            $('#debug').text(
                                pos.coords.latitude + ',' +
                                pos.coords.longitude + ',' +
                                pos.coords.accuracy);

                            // Make our request
                            $.post('/ajax/stops.php',
                                {'lat': pos.coords.latitude, 'lng': pos.coords.longitude, 'radius': 75},
                                function(data) {
                                    if (data && data.length > 0) {
                                        $.each(data, function(k,l) {
                                            var a = $('<a/>', {
                                                'href': '/?LocIDs=' + l.locid
                                            }).text('[' + l.locid + '] ' + l.desc + ' ' + l.dir);

                                            var item = $('<li/>').append(a);
                                            stop_list.find('ul:first').append(item);
                                        });
                                        stop_list.show();
                                    } else {
                                        find_stops.find('a').text('No stops found! Try again?');
                                    }

                                    // Hide the loading indicator
                                    loading.hide();
                                }
                            );
                        }, function(e) {
                            loading.hide();
                        },
                        {
                            enableHighAccuracy: true,
                            timeout: 10000,
                            maximumAge: 0
                        });
                        return false;
                    });
                }
            });
        </script>
    </head>
    <body>
        <div id="find-stops">
            <div class="wrap">
                <a href="#">
                    <div id="loading"></div>
                    Tap here to find stops near you!
                </a>
            </div>
        </div>
        <div class="wrap">
            <div id="stop-list">
                <ul></ul>
            </div>
            <h1>Where's My Bus?</h1>
            <form action="" method="GET">
                <fieldset>
                    <ol>
                        <li>
                            <label for=""></label>
                            <input type="number" name="LocIDs" placeholder="Stop number..." value="<?php echo $stop_ids; ?>" />
                            <input type="submit" value="Submit" />
                        </li>
                    </ol>
                </fieldset>
            </form>
            <?php if (!empty($error_msg)) : ?>
            <div class="error">
                <p><?php echo $error_msg; ?></p>
            </div>
            <?php endif; ?>
            <?php if (!empty($arrivals)) : ?>
            <div id="results">
                <ul>
                    <?php foreach ($arrivals as $a) : ?>
                        <li>
                            <div class="arrival">
                                <span class="arrival-line"><?php echo $a->shortSign; ?></span>
                                arriving in
                                <span class="arrival-time"><?php echo abs($a->getArrivalTime()); ?> minutes</span>
                            </div>
                            <span class="arrival-location"><?php echo $a->location; ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
            <div id="footer">
                <a href="https://github.com/twaddington/trimet-stop-lookup">View Source</a> |
                <a href="mailto:consulting@tristanwaddington.com?subject=Where's My Bus Feedback">Send Feedback</a>
            </div>
            <div id="debug"></div>
        </div>
        <!-- end .wrap -->
<script type="text/javascript">

var _gaq = _gaq || [];
_gaq.push(['_setAccount', 'UA-2012911-13']);
_gaq.push(['_trackPageview']);

(function() {
    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
})();

</script>
    </body>
</html>
