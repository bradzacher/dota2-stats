<?php

if (	isset($_POST['source'])
	&& 	isset($_POST['error']) 
	&& 	isset($_POST['stack']) 
	&& 	isset($_POST['cause']) ) {
	require_once($_SERVER['DOCUMENT_ROOT'] . '/api/config.php');
	
	$db = db::obtain();
	
	$q = 'INSERT INTO errors (occurred, source, error, stack, cause) VALUES (NOW(), ?, ?, ?, ?)';
	$params = array($_POST['source'], $_POST['error'], $_POST['stack'], $_POST['cause']);
	$db->query_first_pdo($q, $params);
	
	$str = date('m/d/Y h:i:s a', time()) . ' - "' . $_POST['source'] . '" - ' . $_POST['error'] . '" - ' . $_POST['stack'] . '" - ' . $_POST['cause'] . '\n';
	file_put_contents('error_log', $str, FILE_APPEND);
}