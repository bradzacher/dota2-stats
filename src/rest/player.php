<?php

if (isset($_GET['account_id']) && $_GET['account_id'] != '') {
	$account_id = $_GET['account_id'];
	
	require_once($_SERVER['DOCUMENT_ROOT'] . '/api/config.php');
	require_once('standardFetchFunction.php');
	
	$outputObject = new stdClass;
	
	$db = db::obtain();
    $params = array($account_id);
	
// make sure the account_id is a valid user
	$q = "
SELECT CASE 
	WHEN COUNT(*) = 0
		THEN FALSE
	ELSE TRUE
END AS isUser
FROM users
WHERE account_id = ?";
	$res = $db->query_first_pdo($q, $params);
	if ($res['isUser'] == 0) {
?>{"result":{"status":0,"message":"Invalid account_id - user does not exist."}}<?php
		die();
	}

// make sure the caching time is correct
	$q = "
SELECT UNIX_TIMESTAMP(u.time1) as update_timestamp
FROM (
    (
        SELECT update_datetime as time1
        FROM last_update
        WHERE table_name = 'users'
    )
    UNION
    (
        SELECT m.start_time as time1
        FROM slots AS s
        LEFT JOIN matches AS m
            ON m.match_id = s.match_id
        WHERE s.account_id = 26288187
        ORDER BY m.start_time DESC
        LIMIT 0, 1
    )
) AS u
ORDER BY u.time1 DESC
LIMIT 0, 1";
	$res = $db->query_first_pdo($q, $params);
	checkCacheHeaders($res['update_timestamp']);
		 
// get profile data
	$q = "SELECT * FROM users WHERE account_id = ?";
	$res = $db->query_first_pdo($q, $params);
	$outputObject->profile = $res;
	
// get most played heroes
	$q = "
SELECT s.hero_id, COUNT(*) AS matches_played,
	SUM(CASE 
		WHEN m.radiant_win = 1 AND s.player_slot < 128
			THEN 1
		WHEN m.radiant_win = 0 AND s.player_slot >= 128
			THEN 1
		ELSE 0
	END) AS wins,
	(SUM(s.kills) + SUM(s.assists)) / SUM(s.deaths) AS kda
FROM slots AS s
LEFT JOIN matches AS m
	ON s.match_id = m.match_id
WHERE account_id = ?
GROUP BY hero_id
ORDER BY matches_played DESC, wins DESC, kda DESC
LIMIT 0, 10";
	$res = $db->fetch_array_pdo($q, $params);
	$outputObject->heroes = $res;
	
// get mode statistics
	$q = "
SELECT m.game_mode, m.lobby_type, COUNT(*) AS matches_played,
	SUM(CASE 
		WHEN m.radiant_win = 1 AND s.player_slot < 128
			THEN 1
		WHEN m.radiant_win = 0 AND s.player_slot >= 128
			THEN 1
		ELSE 0
	END) AS wins
FROM `slots` AS s
LEFT JOIN `matches` AS m
	ON s.match_id = m.match_id
WHERE account_id = ?
AND m.game_mode != 0
GROUP BY m.game_mode, m.lobby_type
ORDER BY matches_played DESC, wins DESC
LIMIT 0, 5";
	$res = $db->fetch_array_pdo($q, $params);
	$outputObject->modes = $res;
	
// get best buddies
	$q = "
SELECT q.*, u.personaname, 'true' AS isUser
FROM (
	SELECT s2.account_id, COUNT(*) AS matches_played,
		SUM(CASE 
			WHEN m.radiant_win = 1 AND s1.player_slot < 128
				THEN 1
			WHEN m.radiant_win = 0 AND s1.player_slot >= 128
				THEN 1
			ELSE 0
		END) AS wins
    FROM slots AS s1
    LEFT JOIN matches AS m
        ON s1.match_id = m.match_id
    LEFT JOIN (
        SELECT s.match_id, CASE
				WHEN u.account_id IS NULL
					THEN NULL
				ELSE u.account_id
			END as account_id
        FROM slots AS s
        LEFT JOIN users AS u
            ON s.account_id = u.account_id
    ) AS s2
    ON s1.match_id = s2.match_id
    WHERE s1.account_id = ?
    AND s2.account_id IS NOT NULL
    AND s2.account_id != ?
    GROUP BY s2.account_id
    ORDER BY matches_played DESC, wins DESC
    LIMIT 0, 6
) AS q
LEFT JOIN users AS u
ON q.account_id = u.account_id";
	$res = $db->fetch_array_pdo($q, array($account_id, $account_id));
	$outputObject->buddies = $res;
	
// get totals
	$q = "
SELECT COUNT(*) AS matches_played,
	MAX(m.start_time) as last_match,
    SUM(CASE 
      	WHEN m.radiant_win = 1 AND s.player_slot < 128
        	THEN 1
	  	WHEN m.radiant_win = 0 AND s.player_slot >= 128
			THEN 1
      	ELSE 0
    END) AS wins,
    (SUM(s.kills) + SUM(s.assists)) / SUM(s.deaths) AS kda,
    SUM(CASE
        WHEN s.player_slot < 128
        	THEN 1
        ELSE 0
    END) AS radiant_matches,
    SUM(CASE
        WHEN s.player_slot >= 128
        	THEN 1
        ELSE 0
    END) AS dire_matches,
    SUM(CASE
        WHEN s.leaver_status = 0 OR s.leaver_status = 1 OR s.leaver_status = 5
        	THEN 0
        ELSE 1
    END) AS abandoned_matches,
    SUM(kills) AS kills,
    SUM(deaths) AS deaths,
    SUM(assists) AS assists,
    SUM(last_hits) AS last_hits,
    SUM(denies) AS denies
FROM slots AS s
LEFT JOIN matches AS m
	ON s.match_id = m.match_id
WHERE s.account_id = ?";
	$res = $db->query_first_pdo($q, $params);
	$outputObject->totals = $res;
	
// get latest matches
	$q = "
SELECT m.match_id, s.hero_id, s.kills, s.deaths, s.assists, s.gold_per_min as gpm, s.xp_per_min as xppm, s.last_hits, s.denies,
	CASE 
      	WHEN m.radiant_win = 1 AND s.player_slot < 128
        	THEN 1
	  	WHEN m.radiant_win = 0 AND s.player_slot >= 128
			THEN 1
      	ELSE 0
    END AS win
FROM slots AS s
LEFT JOIN matches AS m
	ON s.match_id = m.match_id
WHERE s.account_id = ?
ORDER BY m.start_time DESC
LIMIT 0, 10";
	$res = $db->fetch_array_pdo($q, $params);
	$outputObject->matches = $res;
	
	// output the result
?>{"result":{"status":1,"player":<?php print json_encode($outputObject); ?>}}<?php

	exit();
}
