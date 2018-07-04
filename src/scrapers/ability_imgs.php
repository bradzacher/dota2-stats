<?php

$base = "http://cdn.dota2.com/apps/dota2/images/abilities/";

require_once($_SERVER['DOCUMENT_ROOT'] . '/api/config.php');

$q = "SELECT * FROM abilities";
$db = db::obtain();
$res = $db->fetch_array_pdo($q);

foreach ($res as $ability) {
?>
<img src="<?php echo $base . $ability["name"] . "_md.png"; ?>" />
<img src="<?php echo $base . $ability["name"] . "_hp1.png"; ?>" />
<img src="<?php echo $base . $ability["name"] . "_hp2.png"; ?>" />
<?php
}
?>
<img src="http://cdn.dota2.com/apps/dota2/images/workshop/itembuilder/stats.png" />
<?php
