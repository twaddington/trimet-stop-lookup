<?php

require_once('../settings.php');
require_once('../libs/phpMet/src/Trimet.php');

header('Cache-Control: no-cache, must-revalidate');
header('Expires: Tue, 31 Mar 1987 05:00:00 GMT');
header('Content-Type: application/json');

if (isset($_POST['lat']) && isset($_POST['lng'])) {
    $lat = $_POST['lat'];
    $lng = $_POST['lng'];
    $radius = $_POST['radius'];
    
    if (empty($radius)) {
        // Default (in meters)
        $radius = 25;
    }

    $api = new TrimetAPIQuery();
    $stops = $api->getStops($lat, $lng, $radius);

    echo json_encode($stops);
    die();
}

?>
