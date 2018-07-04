<?php

// make sure the client knows if there are new matches in the request
require_once('standardFetchFunction.php');
// set the max age for 3 minutes (the update time) so that proxies and browsers don't rely on
//  just the cached file and actually make a request to see if the data has changed
//  ##workaround to problem behind a caching proxy which didn't allow fetching of a new copy if the max-age was 0
cacheHeaders('matches', 180);//21600);

require_once($_SERVER['DOCUMENT_ROOT'] . '/api/config.php');

$match_data = array();

$db = db::obtain();

// pageinate
$page = 0;
if (isset($_GET['page']) && strlen($_GET['page']) > 0) {
    $page = $_GET['page'];
}

$limit = $page * PAGEINATE_LIMIT;

// for selecting matches from only certain players
$whereClauses = [];
$whereParams = [];
if (isset($_GET['hero_id']) && $_GET['hero_id'] > 0) {
    $ids = explode(',', $_GET['hero_id']);

    $str = 'hero_id IN (';
    foreach ($ids as $key => $acct) {
        $whereParams[] = trim($acct);
        if ($key != 0) {
            $str .= ',';
        }
        $str .= '?';
    }
    $str .= ')';
    
    $whereClauses[] = $str;
}

// count how many matches there are
$q = "SELECT COUNT(*) as total FROM matches";
$res = $db->query_first_pdo($q);
$resTotal = ceil($res['total'] / PAGEINATE_LIMIT);
$resRemaining = max($resTotal - (1 + $page), 0);

// get the basic match info
$q = "SELECT match_id, radiant_win, game_mode, lobby_type, start_time FROM matches ORDER BY match_id DESC LIMIT ?, " . PAGEINATE_LIMIT;
$res = $db->fetch_array_pdo($q, array($limit));

$matches = $res;

// get the slot data
$q = "SELECT DISTINCT s.match_id, s.player_slot, s.hero_id, u.personaname, CASE WHEN u.account_id IS NULL THEN 'false' ELSE 'true' END as isUser FROM slots AS s LEFT JOIN users AS u ON s.account_id=u.account_id WHERE match_id in (";
$match_ids = array();
$matches_by_id = array();
foreach ($matches as $i => $match) {
    $match_ids[] = $match['match_id'];
    if ($i !== 0) {
        $q .= ', ';
    }
    $q .= '?';
    $matches_by_id[$match['match_id']] = $match;
    $matches_by_id[$match['match_id']]['players'] = array();
}
$q .= ') ORDER BY match_id, player_slot';
$res = $db->fetch_array_pdo($q, $match_ids);

foreach ($res as $slot) {
    $id = $slot['match_id'];
    unset($slot['match_id']);
    $matches_by_id[$id]['players'][] = $slot;
}

$matches = array_values($matches_by_id);

$json = json_encode($matches);
$resLength = count($matches);

?>
{"result":{"status":1,"num_results":<?php print $resLength; ?>,"total_pages":<?php print $resTotal; ?>,"pages_remaining":<?php print $resRemaining; ?>,"matches":<?php print $json; ?>}}
<?php
