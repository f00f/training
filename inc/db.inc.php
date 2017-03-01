<?php
require_once 'config-site.inc.php'; // @uses ON_TEST_SERVER, @uses RESET_DELAY
require_once 'dbconf.inc.php';

//! Executes a db query as prepared statement and returns result
// Displays error, if any.
// ON_TEST_SERVER: Also displays query on error.
function DbQueryP($query, $types, $vars) {
	global $mysqli;

	$params = func_get_args();
	array_shift($params); // remove $query

	//Turn all values into reference since call_user_func_array
	//expects arguments of bind_param to be references
	//@see mysqli::bind_param() manpage
	foreach ($params as $key => $value) {
		if (0 != $key) {
			$params[$key] =& $params[$key];
		}
	}

	$stmt = $mysqli->prepare($query);
	call_user_func_array(array($stmt, 'bind_param'), $params);
	$stmt->execute();
	$result = $stmt->get_result();
	$stmt->close();

	if (mysqli_errno($mysqli) != 0) {
		$msg = mysqli_error($mysqli);
		if (@ON_TEST_SERVER) {
			$msg += '<br />Query was: ' . $query;
		}
		die($msg);
	}
	return $result;
}
