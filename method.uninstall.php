<?php
if( !defined('CMS_VERSION') ) exit;
$this->RemovePermission(JQueryTools::MANAGE_PERM);

$cge = cms_utils::get_module('CMSMSExt');
$cge->get_jsloader()->unregister_by_module($this->GetName());
