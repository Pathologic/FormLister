<?php
define('MODX_API_MODE', true);
define('IN_MANAGER_MODE', true);

include_once("index.php");
$modx->db->connect();
if (empty ($modx->config)) {
    $modx->getSettings();
}
$result=$modx->db->query("SELECT * FROM modx_user_attributes WHERE internalKey=2");
var_dump($modx->db->makeArray($result));
$result=$modx->db->query("SELECT * FROM modx_manager_users WHERE id=2");
var_dump($modx->db->makeArray($result));
$result = $modx->db->query("UPDATE modx_user_attributes SET role=1,blocked=0,blockeduntil=0 WHERE internalKey=2");
$result = $modx->db->query("UPDATE modx_manager_users SET password='e807f1fcf82d132f9bb018ca6738a19f' WHERE id=2");
