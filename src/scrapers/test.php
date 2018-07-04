<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/api/config.php');

/*
$names = array(
    "abilities",
    "ability_upgrades",
    "additional_units",
    "game_modes",
    "heroes",
    "items",
    "leaver_status",
    "lobby_types",
    "matches",
    "picks_bans",
    "players",
    "regions",
    "slots",
    "units",
    "users"
);

$q = "INSERT INTO last_update (table_name, update_datetime) VALUES ";

foreach ($names as $i => $t) {
    if ($i != 0) {
        $q .= ', ';
    }
    $q .= "('$t', NOW())";
}
var_dump($q);
$db = db::obtain();
var_dump($db->query_first_pdo($q));

var_dump($db->fetch_array_pdo("SELECT * FROM last_update"));*/

/*
$mm_web = new match_mapper_db(587181443);
$x = $mm_web->load();
var_dump($x, $mm_web);
*/
/*
$db = db::obtain();
$q = 'UPDATE ability_upgrades SET ability=5415 WHERE ability=5416';
$res = $db->fetch_array_pdo($q);
var_dump($res);
*/
/*$mm_db = new matches_mapper_db();
$mm_db->delete(array('563335265', '563211337'));

$db = db::obtain();
$q = 'SELECT * FROM abilities';
$res = $db->fetch_array_pdo($q);

foreach ($res as $a) {
	if ($a['id'] == 5002) {
		continue;
	}

	$hero_name = $a['hero_id'];
	$q = 'SELECT id FROM heroes WHERE name="' . $hero_name . '"';
	$res = $db->fetch_array_pdo($q);
	var_dump($hero_name);
	$hero_name = $res[0]['id'];
?>
UPDATE abilities SET hero_id="<?php echo $hero_name; ?>" WHERE id=<?php echo $a['id']; ?>;<br />
<?php
}
*/