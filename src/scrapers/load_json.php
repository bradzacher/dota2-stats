<?php

require_once($_SERVER['DOCUMENT_ROOT'] . '/api/config.php');

$db = db::obtain();

if (isset($_GET['type'])) {
    switch ($_GET['type']) {
        case 'abilities':
                $fileName = 'abilities';
                $table = 'abilities';
                $jsonRoot = $fileName;
            break;

        case 'heroes':
                $fileName = 'heroes';
                $table = 'heroes';
                $jsonRoot = $fileName;
            break;

        case 'items':
                $fileName = 'items';
                $table = 'items';
                $jsonRoot = $fileName;
            break;

        case 'lobby_types':
                $fileName = 'lobbies';
                $table = 'lobby_types';
                $jsonRoot = $fileName;
            break;

        case 'game_modes':
                $fileName = 'mods';
                $table = 'game_modes';
                $jsonRoot = $fileName;
            break;

        case 'regions':
                $fileName = 'regions';
                $table = 'regions';
                $jsonRoot = $fileName;
            break;
            
        default:
            die();
    }

    $file = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/api/data/' . $fileName . '.json'))->$jsonRoot;

    // figure out what needs to be updated, and what needs to be inserted
    $insert = [];
    $update = [];
    foreach ($file as $item) {
        if (row_exists($table, $item->id)) {
            $update[] = $item;
        } else {
            $insert[] = $item;
        }
    }

    // figure out the fields and how many question marks there should be
    $fields = array();
    $vals = array();
    foreach ($insert[0] as $key => $item) {
        $fields[] = $key;
        $vals[] = '?';
    }

    // do the insert
    $data = array();
    foreach ($insert as $item) {
        $params = array();
        foreach ($item as $key => $item) {
            $params[] = $item;
        }

        $data[] = $params;
    }
    $db->insert_many_pdo($table, $fields, $data);
    // do the updates
}

function row_exists($table, $id) {
    global $db;

    $q = "SELECT id FROM $table WHERE id = ?";
    $params = array($id);
    $res = $db->query_first_pdo($q, $params);
    return $res;
}
