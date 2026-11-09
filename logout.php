<?php
require_once __DIR__ . '/db.php';
session_unset();
session_destroy();
header('Location: index.php');
exit;
