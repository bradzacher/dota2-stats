<?php

require_once($_SERVER['DOCUMENT_ROOT'] . '/api/config.php');
	
function standardFetchFunction($tableName) {
    cacheHeaders($tableName);
    
    $status = 0;
    $resTotal = [];

    if (isset($_GET['id']) && strlen($_GET['id']) > 0) {
        $ids = array($_GET['id']);
        if (strpos($_GET['id'], ',') !== -1) {
            $ids = explode(',', $_GET['id']);

            // limit the request
            $ids = array_slice($ids, 0, REST_ITEM_REQUEST_LIMIT);
        }

        $qmarks = '';
        for ($i = 0; $i < count($ids); $i++) {
            if ($i != 0) {
                $qmarks .= ', ';
            }
            $qmarks .= '?';
        }

        $q = "SELECT * FROM $tableName WHERE id in ($qmarks) ORDER BY id ASC";

        $db = db::obtain();
        $res = $db->fetch_array_pdo($q, $ids);
    } else {
        $q = "SELECT * FROM $tableName ORDER BY id ASC";
        $db = db::obtain();
        $res = $db->fetch_array_pdo($q);
    }

    $resTotal = count($res);
    if ($resTotal !== 0) {
        $status = 1;
    }

    $json = json_encode($res);

    $ret = new stdClass;
    $ret->status = $status;
    $ret->total = $resTotal;
    $ret->tableName = $tableName;
    $ret->json = $json;
    $ret->data = $res;

    return $ret;
}

function standardPrintFunction($res) {
    $status = $res->status;
    $resTotal = $res->total;
    $tableName = $res->tableName;
    $json = $res->json;
?>{"result":{"status":<?php print $status; ?>,"total_results":<?php print $resTotal; ?>,"<?php print $tableName; ?>":<?php print $json; ?>}}<?php
}

function cacheHeaders($name, $maxAge = 86400) {
    require_once($_SERVER['DOCUMENT_ROOT'] . '/api/config.php');
    
    $headers = apache_request_headers(); 

    $q = "SELECT UNIX_TIMESTAMP(update_datetime) AS update_timestamp, update_datetime FROM last_update WHERE table_name = ?";
    $params = array($name);
    
    $db = db::obtain();
    $res = $db->query_first_pdo($q, $params);
    
    checkAgeHeaders($res['update_timestamp'], $maxAge);
    checkCacheHeaders($res['update_timestamp']);
}

function checkCacheHeaders($updateTimestamp) {
    /*
    echo '<pre>';
    
    $updateTime = new DateTime($res['update_datetime']);
    var_dump('$res AEST                 : ' . $updateTime->format('D, d M Y H:i:s'));
    
    $updateTime->setTimeZone(new DateTimeZone('GMT'));
    var_dump('$res GMT                  : ' . $updateTime->format('D, d M Y H:i:s'));
    
    var_dump('$updateTime timeStamp     : ' . $updateTime->getTimestamp());
    var_dump('$res["update_timestamp"]  : ' . $res['update_timestamp']);
    
    var_dump('If-Modified-Since         : ' . $headers['If-Modified-Since']);
    $modifiedSince = new DateTime($headers['If-Modified-Since']);
    var_dump('$modifiedSince GMT        : ' . $modifiedSince->format('D, d M Y H:i:s'));
    var_dump('$modifiedSince timestamp  : ' . $modifiedSince->getTimestamp());
    
    var_dump(strtotime($headers['If-Modified-Since']) < $res['update_timestamp']);
    var_dump(strtotime($headers['If-Modified-Since']) == $res['update_timestamp']);
    var_dump(strtotime($headers['If-Modified-Since']) > $res['update_timestamp']);
    */
    
    $lastModifiedStr = 'Last-Modified: ' . gmdate('D, d M Y H:i:s', $updateTimestamp) . ' GMT';
    if (isset($headers['If-Modified-Since']) && (strtotime($headers['If-Modified-Since']) < $updateTimestamp)) {
        // Client's cache IS current, so we just respond '304 Not Modified'.
        header($lastModifiedStr, true, 304);
        exit();
    } else {
        // not cached or cache outdated, we respond '200 OK' and go ahead.
        header($lastModifiedStr, true, 200);
    }
}

function checkAgeHeaders($updateTimestamp, $maxAge = 43200) {
    header('Age: ' . (time() - $updateTimestamp), true);
    header('Cache-Control: max-age=' . $maxAge, true);
}
