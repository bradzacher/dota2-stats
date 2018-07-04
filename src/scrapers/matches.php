                                                                <?php

header('Content-type: text/html; charset=utf-8');
ignore_user_abort(true);

date_default_timezone_set('Australia/Adelaide');

require_once('/home/zacherco/public_html/dota/api/config.php');

$db = db::obtain();

$outfile_name = 'match_fetch_output.html';

// only latest games?
if (isset($_GET['latest'])) {
    $latest_match_id = $db->query_first_pdo('SELECT match_id FROM matches ORDER BY match_id DESC');
    $latest_match_id = $latest_match_id['match_id'];
} else {
    $latest_match_id = -1;
    $outfile_name = 'match_fetch_output_full.html';
}

file_put_contents($outfile_name, '<h1>Starting at: ' . date('m/d/Y h:i:s a', time()) . '</h1>');

// for a certain player?
if (isset($_GET['account_id'])) {
    if (strlen($_GET['account_id']) <= 10) {
        $id64 = player::convert_id($_GET['account_id']);
    } else {
        $id64 = $_GET['account_id'];
    }
    $players = $db->fetch_array_pdo('SELECT account_id, personaname FROM users WHERE steamid = ?', array($id64));
} else {
    $players = $db->fetch_array_pdo('SELECT account_id, personaname FROM users');
}

$totalNewMatches = 0;
$totalResultsProcessed = 0;

foreach ($players as $player) {
    set_time_limit(6000);
    $matches_mapper_web = new matches_mapper_web();
    $matches_mapper_web->set_account_id($player['account_id']);

    $newMatches = 0;
    $resultsProcessed = 0;

    write_to_file('<h3>Starting user ' . $player['personaname'] . ': ' . date('m/d/Y h:i:s a', time()) . '</h3>', $outfile_name);

    while (true) {
    	set_time_limit(6000);
        $stop_search = false;

        write_to_file("loading history page.<br />\n", $outfile_name);

        // try and load a page from the api
        $matches_short_info = $matches_mapper_web->load();

        if (empty($matches_short_info)) {
            write_to_file("no matches found.<br />\n", $outfile_name);
            $stop_search = true;
        } else {
            write_to_file("loading matches.<br />\n", $outfile_name);
        }

        // get the full details for each match from the API
        foreach ($matches_short_info AS $key => $match_short_info) {
    	    set_time_limit(6000);
            if ($key < $latest_match_id) {
                $stop_search = true;
                break;
            }

            $colourPre = '<span style="color: red;">';
            $colourPost = '</span>';

            // only load matches that are of lobby type:
            //      Public matchmaking, Team match, Solo queue or ranked
            $lobby_type_valid = in_array($match_short_info->get('lobby_type'), array(0, 5, 6, 7));

            // make sure everyone picked a hero
            $heroes_valid = true;
            // make sure there are 10 human players
            $player_count_valid = count($match_short_info->get('slots')) == 10;
            foreach ($match_short_info->get('slots') as $slot) {
                if ($slot->get('hero_id') == 0) {
                    $heroes_valid = false;
                }
                if ($player_count_valid) {
                    $player_count_valid = $player_count_valid && ($slot->get('account_id') != null);
                }
            }

            if ($lobby_type_valid && $heroes_valid && $player_count_valid) {
                $match_mapper = new match_mapper_web($key);

                // don't load matches we already have
                if (!match_mapper_db::match_exists($key)) {
    	    	    set_time_limit(6000);
                    $match = $match_mapper->load();

                    // only load matches that are of the game mode:
                    //      None (for games before 25/10/2012), All Pick, Captains Mode, Random Draft, Single Draft, All Random, Least Played, Captains Draft, ALL PICK??
                    $game_mode_valid = in_array($match->get('game_mode'), array(0, 1, 2, 3, 4, 5, 12, 13, 16, 22));
                    // only load matches that have duration > 10 min
                    $duration_valid = $match->get('duration') > 600;

                    if ($game_mode_valid && $duration_valid) {
                        $mm = new match_mapper_db();
                        $mm->save($match);
                        $colourPre = '<span style="color: green;">';
                        $newMatches++;
                    }
                }
            }

            $resultsProcessed++;
            if ($resultsProcessed % 10 === 0) {
                write_to_file($colourPre."|".$colourPost, $outfile_name);
            } else {
                write_to_file($colourPre.".".$colourPost, $outfile_name);
            }
        }

        $totalNewMatches += $newMatches;
        $totalResultsProcessed += $resultsProcessed;

        // stop searching if there are no results left
        write_to_file('<br /><b>Progress for ' . $player['personaname'] . ': ' . $resultsProcessed . '</b><br />', $outfile_name);
        if ( ($resultsProcessed >= $matches_mapper_web->get_total_matches()) || $stop_search ) {
            write_to_file('<h3>Finished processing user ' . $player['personaname'] . ' (' . $player['account_id'] . '): ' . $newMatches . ' new matches - ' . date('m/d/Y h:i:s a', time()) . '</h3>', $outfile_name);

            break;
        }

        // advance to the next page
        $matches_mapper_web->set_start_at_match_id(end($matches_short_info)->get('match_id'));

    }
}

// so that clients know to update their cache
if ($totalNewMatches != 0) {
    $q = "UPDATE last_update SET update_datetime = NOW() WHERE table_name = 'matches'";
    $db->query_first_pdo($q);
}

write_to_file('<h1>Finished at: ' . date('m/d/Y h:i:s a', time()) . ' - ' . $totalNewMatches .' new matches, ' . $totalResultsProcessed . ' results processed.</h1>', $outfile_name);

function write_to_file($str, $outfile_name) {
    echo $str;
    flush();
    file_put_contents($outfile_name, $str, FILE_APPEND);
}
                            
                            