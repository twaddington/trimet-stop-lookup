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
        $error_msg = 'The stop ID you entered was not valid. Please try again.';
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
                border-radius: 5px;
                -webkit-border-radius: 5px;
                -moz-border-radius: 5px;
                background-color: #084C8D; 
            }
            fieldset ol,
            fieldset li {
                margin: 0px;
                padding: 0px;
                list-style-type: none;
            }
            fieldset li {
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
                margin: 0px;
                padding: 0px;
            }
            input[type="text"] {
                width: 64%;
                margin-left: 5px;
                padding: 5px 0px;
                color: #999;
                font-size: 28px;
                border: none;
                outline: none;
            }
            input[type="submit"] {
                width: 80px;
                margin: 5px;
                padding: 4px 0px;
                color: #fff;
                font-size: 18px;
                text-shadow: 0 1px 1px rgba(0, 0, 0, 0.3);
                border: 1px solid #2E2D2A;
                border-radius: 5px;
                -webkit-border-radius: 5px;
                -moz-border-radius: 5px;
                background: #0C457A;
                background: -webkit-gradient(linear, left top, left bottom, from(#555), to(#222));
                background: -moz-linear-gradient(top, #555, #222);
                float: right;
                cursor: pointer;
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
            #results {
                margin: 20px 10px;
                padding: 0px;
            }
            #results li {
                margin: 5px 0px;
                color: #666;
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
                margin: 20px 0px;
                padding: 10px;
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

                            // Make our request
                            $.post('/ajax/stops.php',
                                {'lat': pos.coords.latitude, 'lng': pos.coords.longitude, 'radius': 50},
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
                            <input type="text" name="LocIDs" placeholder="Enter your stop number..." value="<?php echo $stop_ids; ?>" />
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
                <h2>Your bus will arrive...</h2>
                <ul>
                    <?php foreach ($arrivals as $a) : ?>
                        <li><?php echo $a; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
            <div id="footer">
                <a href="https://github.com/twaddington/trimet-stop-lookup">Source Code</a> |
                <a href="mailto:consulting@tristanwaddington.com">Feedback? Results not accurate?</a>
            </div>
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
