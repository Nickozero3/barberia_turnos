<?php
require_once dirname(__DIR__, 2) . '/app/bootstrap.php';

$_SESSION = [];
session_destroy();
redirect('/admin/login.php');
