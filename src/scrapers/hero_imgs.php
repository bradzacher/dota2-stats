<?php

$base = "http://cdn.dota2.com/apps/dota2/images/heroes/";

require_once($_SERVER['DOCUMENT_ROOT'] . '/api/config.php');

$q = "SELECT * FROM heroes";
$db = db::obtain();
$res = $db->fetch_array_pdo($q);

foreach ($res as $hero) {
?>
<img src="<?php echo $base . $hero["name"] . "_sb.png"; ?>" />
<img src="<?php echo $base . $hero["name"] . "_lg.png"; ?>" />
<img src="<?php echo $base . $hero["name"] . "_full.png"; ?>" />
<img src="<?php echo $base . $hero["name"] . "_vert.jpg"; ?>" />
<?php
}
