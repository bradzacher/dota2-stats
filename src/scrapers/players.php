                                <?php

require_once('/home/zacherco/public_html/dota/api/config.php');

$ids = array(
    '76561197986553915',
    '76561197989835236',
    '76561197991048055',
    '76561197991459113',
    '76561197992730791',
    '76561197995870744',
    '76561197996503970',
    '76561197997580885',
    '76561197998226187',
    '76561198001278759',
    '76561198005226264',
    '76561198012133556',
    '76561198017550896',
    '76561198029485713',
    '76561198031620127',
    '76561198071011813',
    '76561197993561990',
    '76561198083938952', // cam
    '76561198038575501'  // skag
);

$players_web = new players_mapper_web();
$player_mapper = new player_mapper_db();

foreach ($ids as $i) {
    $players_web->add_id($i);
}

$playerdata = $players_web->load();

$db = db::obtain();

$fields = array('account_id', 'personaname', 'steamid', 'avatarfull');
$insertData = array();
$updateData = array();
foreach ($playerdata as $player) {
    $player_mapper->save($player, true);

    var_dump($db->get_error());
}

$q = "UPDATE last_update SET update_datetime = NOW() WHERE table_name = 'users'";
$db->query_first_pdo($q);
                            
                            