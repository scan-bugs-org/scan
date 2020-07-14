<?php

include_once('../../config/dbconnection.php');

$NON_SOLR_FIELDS = [
    "genus",
    "specificEpithet",
    "locality",
    "municipality",
    "decimalLatitude",
    "decimalLongitude"
];

$NON_SOLR_FIELDS_READABLE = [
    "Genus" => "genus",
    "Specific Epithet" => "specificEpithet",
    "Locality" => "locality",
    "Municipality" => "municipality",
    "Latitude" => "decimalLatitude",
    "Longitude" => "decimalLongitude"
];

function getNonSOLRFields($occid) {
    global $NON_SOLR_FIELDS;
    $RANKID_GENUS = 180;

    $con = MySQLiConnectionFactory::getCon("readonly");
    $result = [];
    foreach ($NON_SOLR_FIELDS as $f) {
        $result[$f] = "";
    }

    try {
        $sql = <<<EOF
        SELECT
            genus.sciName as genus,
            o.specificEpithet,
            o.locality,
            o.municipality,
            o.decimalLatitude,
            o.decimalLongitude
        FROM omoccurrences o
        INNER JOIN taxaenumtree te ON o.tidinterpreted = te.tid
        INNER JOIN taxa genus ON genus.tid = te.parenttid
        WHERE occid = $occid
        AND genus.rankID = $RANKID_GENUS
        GROUP BY o.occid
        LIMIT 1
    ;
EOF;
        $sql_result = $con->query($sql)->fetch_assoc();
        if ($sql_result !== null) {
            $result = $sql_result;
        }
    }
    catch (Error $e) {
        error_log("Error pulling non-solr fields: '$e'");
    }
    finally {
        $con->close();
    }

    return $result;
}

//try {
//    header("Content-Type: application/json; charset=utf-8");
//
//    $results = [];
//
//    if (array_key_exists("occid", $_GET) && is_numeric($_GET["occid"])) {
//        $occid = intval($_GET["occid"]);
//
//        $sql = getMissingFieldsSql($occid);
//        $sql_results = $con->query($sql);
//
//        if ($sql_results) {
//            while ($r = $sql_results->fetch_assoc()) {
//                array_push($results, $r);
//            }
//        }
//
//        echo json_encode($results, JSON_NUMERIC_CHECK);
//    }
//    else {
//        http_response_code(400);
//        echo json_encode(["error" => "Query must include 'occid'"]);
//    }
//}
//catch (Error $e) {
//    http_response_code(500);
//    error_log("Error looking up taxon details: '$e'");
//    echo json_encode(["error" => $e]);
//}
//finally {
//    $con->close();
//}

