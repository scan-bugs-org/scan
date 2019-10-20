<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/config/dbconnection.php');

function run_sql($sql, $readOnly=true) {
    if ($readOnly) {
        $sqlConn = MySQLiConnectionFactory::getCon("readonly");
    } else {
        $sqlConn = MySQLiConnectionFactory::getCon("readwrite");
    }
    $results = [];

    if ($sqlConn !== null) {
        if ($res = $sqlConn->query($sql)) {
            while ($resItem = $res->fetch_assoc()) {
                array_push($results, $resItem);
            }
            $res->close();
        }
        $sqlConn->close();
    }

    return $results;
}

function getCollectionsWithPermission($uid) {
    $collectionPermissionSql = <<< EOD
        select distinct c.collid as collid, c.collectionname as collectionname
        from omcollections c
        inner join userroles r on c.collid = r.tablepk
        inner join users u on r.uid = u.uid
        where (r.role = 'CollEditor' or r.role = 'CollAdmin') and u.uid = $uid
        order by c.collectionname;
EOD;
    return run_sql($collectionPermissionSql);
}

function getCollectionRegex($collid) {
    $collectionRegexSql = <<< EOD

EOD;

}


$results = [];

if (!$SYMB_UID) {
    http_response_code(403);
    header("Content-Type: text/plain; charset=".$CHARSET);
    echo "403 Forbidden";
} else {
    if (key_exists("allowedCollections", $_GET) and $_GET["allowedCollections"] === "true") {
        $results = getCollectionsWithPermission($SYMB_UID);
    }

    header("Content-Type: application/json; charset=".$CHARSET);
    echo json_encode($results);
}
?>