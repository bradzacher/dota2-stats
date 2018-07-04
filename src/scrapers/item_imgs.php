<?php

$base = "http://cdn.dota2.com/apps/dota2/images/items/";

require_once($_SERVER['DOCUMENT_ROOT'] . '/api/config.php');

$q = "SELECT * FROM items";
$db = db::obtain();
$res = $db->fetch_array_pdo($q);

foreach ($res as $item) {
$name = str_replace("item_", "", $item["name"]);
?>
<img src="<?php echo $base . $name . "_lg.png"; ?>" />
<?php
}