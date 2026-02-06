<?php
require_once '../admin/includes/config.php';
$_SESSION = array();
session_destroy();
header('Location: user_authentication.html');
exit;
