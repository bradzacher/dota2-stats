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
if ( !(isset($_GET['account_id']) && $_GET['account_id'] < 0) ) {
	$account_id = $_GET['account_id'];

	// count how many matches there are
	$q = "	SELECT COUNT(*) as total
			FROM matches AS m
			LEFT JOIN slots AS s
				ON m.match_id = s.match_id
			WHERE s.account_id = ?";
	$res = $db->query_first_pdo($q, [$account_id]);
	$resTotal = ceil($res['total'] / PAGEINATE_LIMIT);
	$resRemaining = max($resTotal - (1 + $page), 0);
	
	// get the basic match info
	$q = "	SELECT m.match_id, radiant_win, game_mode, lobby_type, start_time, duration
			FROM matches as m
			LEFT JOIN slots AS s
				ON m.match_id = s.match_id
			WHERE s.account_id = ?
			ORDER BY match_id DESC
			LIMIT ?, " . PAGEINATE_LIMIT;
	$matches = $db->fetch_array_pdo($q, array($account_id, $limit));
	
	// get the slot data
	$match_ids = array();
	$matches_by_id = array();
	foreach ($matches as $i => $match) {
		$match_ids[] = $match['match_id'];
		$matches_by_id[$match['match_id']] = $match;
	}
	$q = "	SELECT DISTINCT s.*
			FROM slots AS s
			LEFT JOIN users AS u
				ON s.account_id = u.account_id
			WHERE match_id IN (" . implode(',', array_fill(0, count($matches), '?')) . ')
				AND s.account_id = ?
			ORDER BY match_id, player_slot';
	$res = $db->fetch_array_pdo($q, array_merge($match_ids, [$account_id]));

	foreach ($res as $slot) {
		$id = $slot['match_id'];
		unset($slot['match_id']);
		unset($slot['account_id']);
		$matches_by_id[$id]['player_data'] = $slot;
	}

	$matches = array_values($matches_by_id);

	$json = json_encode($matches);
	$resLength = count($matches);

	?>
	{"result":{"status":1,"num_results":<?php print $resLength; ?>,"total_pages":<?php print $resTotal; ?>,"pages_remaining":<?php print $resRemaining; ?>,"matches":<?php print $json; ?>}}
	<?php

} else {
?>
{"result":{"status":0,"num_results":0,"total_pages":0,"pages_remaining":0,"matches":[],"error":"No player selected."}}
<?php
	die();
}
