<?php
if(!defined("opoiLoaded")) die('Incorrect or unknown use of application');
$authMgr->requireAdmin();

if (isset($_GET ["id"])) {
    $id = intval($_GET ["id"]);
    $driver->removeOpziener($id);
}

header("Location: ".WEBSITE_URL."opzieners");
die();