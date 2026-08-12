<?php
require_once 'includes/config.php';
require_login();
header('Location: ' . dashboard_url_for_role());
exit;
