<?php

require_once($_SERVER['DOCUMENT_ROOT'] . '/api/config.php');

$match_data = array();

$status = 0;

$newestStartTimestamp = 0;

// if the user asked for certain match(es) then give them only those
if (isset($_GET['match_id']) && strlen($_GET['match_id']) > 0) {
    $match_id_get = $_GET['match_id'];
    $match_ids = array($match_id_get);
    if (strpos($match_id_get, ',') !== -1) {
        $match_ids = explode(',', $match_id_get);

        // limit the request
        $match_ids = array_slice($match_ids, 0, REST_MATCH_REQUEST_LIMIT);
    }

    $match_mapper = new match_mapper_db();

    $invalidMatchCount = 0;

    foreach ($match_ids as $id) {
        $match = $match_mapper->load($id);
        if ($match->get('match_id') === NULL) {
            $invalidMatchCount++;
        } else {
            $matchTmp = $match->get_data_array();

            // get the player data out
            $slots = $match->get('slots');
            $matchTmp['players'] = array();
            foreach ($slots as $player) {
                $playerTmp = $player->get_data_array(false);

                // get the ability data out
                $playerTmp['abilities_upgrades'] = $player->get('abilities_upgrade');

                $playerTmp['additional_units'] = $player->get('additional_unit_items');

                $matchTmp['players'][] = $playerTmp;
            }
            
			$pickbans = $match->get_all_picks_bans();
			$matchTmp['pickbans'] = array('radiant' => array(), 'dire' => array());
			foreach ($pickbans as $pb) {
				$pb['order'] = intval($pb['order']);
				if ($pb['team']) {
					$matchTmp['pickbans']['dire'][] = $pb;
				} else {
					$matchTmp['pickbans']['radiant'][] = $pb;
				}
			}
			
            $newestStartTimestamp = max(strtotime($matchTmp['start_time']), $newestStartTimestamp);
            
            $match_data[] = $matchTmp;
        }
    }
}

$resTotal = count($match_data);
if ($resTotal !== 0) {
    $status = 1;
}

$json = json_encode($match_data);

// make sure the client knows if there are new matches in the request
require_once('standardFetchFunction.php');
checkCacheHeaders($newestStartTimestamp);

?>
{"result":{"status":<?php print $status; ?>,"total_results":<?php print $resTotal; ?>,"matches":<?php print $json; ?>}}
<?php


