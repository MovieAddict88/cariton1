<?php
/**
 * Logout Handler
 */

require_once 'includes/config.php';

session_destroy();
redirect('login.php');
