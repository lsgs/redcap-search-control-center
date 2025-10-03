<?php
/**
 * REDCap External Module: Search Control Center
 * @author Luke Stevens, Murdoch Children's Research Institute
 */
if (!defined('SUPER_USER') || !SUPER_USER) exit;
if (is_null($module) || !($module instanceof MCRI\SearchControlCenter\SearchControlCenter)) exit;
include APP_PATH_DOCROOT . 'ControlCenter/header.php';
$module->manageContentCapturePage();
include APP_PATH_DOCROOT . 'ControlCenter/footer.php';