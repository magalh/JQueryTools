<?php
#BEGIN_LICENSE
#-------------------------------------------------------------------------
# Module: JQueryTools
# Authors: Magal Hezi, with CMS Made Simple Foundation able to assign new administrators.
# Copyright: (C) 2022 Magal Hezi, h_magal@hotmail.com
#            is a fork of: JQueryTools(c) 2006 by Robert Campbell (calguy1000@cmsmadesimple.org)
#  A toolbox of conveniences to provide dynamic javascripty functionality
#  for CMS modules and website designers.
#
#-------------------------------------------------------------------------
# CMS - CMS Made Simple is (c) 2005 by Ted Kulp (wishy@cmsmadesimple.org)
# This projects homepage is: http://www.cmsmadesimple.org
#
#-------------------------------------------------------------------------
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# However, as a special exception to the GPL, this software is distributed
# as an addon module to CMS Made Simple.  You may not use this software
# in any Non GPL version of CMS Made simple, or in any version of CMS
# Made simple that does not indicate clearly and obviously in its admin
# section that the site was built with CMS Made simple.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
# Or read it online: http://www.gnu.org/licenses/licenses.html#GPL
#
#-------------------------------------------------------------------------
#END_LICENSE

class JQueryTools extends CMSModule
{

  const MANAGE_PERM = 'manage_jquerytools';

  private $_required_libs;

  public function GetName() { return 'JQueryTools'; }
  public function GetFriendlyName() { return $this->Lang('friendlyname'); }
  public function GetVersion() { return '2.0'; }
  public function GetHelp() { return file_get_contents(__DIR__.'/inc/help.inc'); }
  public function GetAuthor() { return 'Magal Hezi'; }
  public function GetAuthorEmail() { return 'h_magal@hotmail.com'; }
  public function GetChangeLog() { return @file_get_contents(__DIR__.'/inc/changelog.inc'); }
  public function IsPluginModule() { return true; }
  public function HasAdmin() { return false; }
  public function GetAdminSection() { return 'extensions'; }
  public function GetAdminDescription() { return $this->Lang('moddescription'); }
  public function VisibleToAdminUser() { return $this->CheckPermission(self::MANAGE_PERM); }
  public function GetDependencies() { return ['CMSMSExt'=>'1.2.1']; }
  public function MinimumCMSVersion() { return '2.2.9'; }
  public function InstallPostMessage() { return $this->Lang('postinstall'); }
  public function UninstallPostMessage() { return $this->Lang('postuninstall'); }
  public function UninstallPreMessage() { return $this->Lang('preuninstall'); }
  public function AllowAutoInstall() { return FALSE; }
  public function AllowAutoUpgrade() { return FALSE; }

  public function InitializeFrontend()
  {
    $this->RestrictUnknownParams();
    $this->RegisterModulePlugin();
    $this->SetParameterType('exclude',CLEAN_STRING);
    $this->SetParameterType('no_css',CLEAN_INT);
    $this->SetParameterType('no_cdn',CLEAN_INT);
    $this->SetParameterType('no_js',CLEAN_INT);
    $this->SetParameterType('no_jquery',CLEAN_INT);
    $this->SetParameterType('no_ready',CLEAN_INT);
    $this->SetParameterType('lib',CLEAN_STRING);
  }

  public function InitializeAdmin()
  {
    $this->RegisterModulePlugin();
    $this->CreateParameter('action',null,$this->Lang('param_action'));
    $this->CreateParameter('exclude',null,$this->Lang('param_exclude'));
    $this->CreateParameter('lib',null,$this->Lang('param_lib'));
    $this->CreateParameter('no_css',null,$this->Lang('param_no_css'));
    $this->CreateParameter('no_cdn',null,$this->Lang('param_no_cdn'));
    $this->CreateParameter('no_js',null,$this->Lang('param_no_js'));
    $this->CreateParameter('no_jquery',null,$this->Lang('param_no_jquery'));
    $this->CreateParameter('no_ready',null,$this->Lang('param_no_ready'));
  }

  protected function RegisterLibraries($force = false)
  {
      $_fullpath = function($dir,$in) {
          if( !is_array($in) ) $in = array($in);
          $_owd=getcwd();
          chdir($dir);
          $out = array();
          foreach( $in as $one ) {
              if( is_file($one) ) $out[] = "$dir/$one";
          }
          chdir($_owd);
          return $out;
      };

      // convert a verified fix path to a smarty template with {$root_path}
      // to handle site moves and cruft.
      $_torelpath = function($fn) {
          if( !is_array($fn) ) $in = array($fn);
          $config = cms_config::get_instance();
          $out = array();
          foreach( $fn as $one ) {
              if( startswith($one,$config['root_path']) ) {
                  $out[] = str_replace($config['root_path'],'{$root_path}',$one);
              }
          }
          return $out;
      };

      $basedir = $this->GetModulePath().'/lib';
      $dirs = glob($basedir.'/*.lib');
      $libraries = array();
      if( is_array($dirs) && count($dirs) ) {

	  //cms_utils::get_module('CMSMSExt');
      $cge = cms_utils::get_module('CMSMSExt');
          foreach( $dirs as $dir ) {
              if( !is_dir($dir) ) continue;
              if( !is_file($dir.'/info.dat') ) continue;
              $bn = basename($dir);
              $name = strtolower(substr($bn,0,-4));
              include($dir.'/info.dat');

              try {
                  $loader = new \CMSMSExt\jsloader\libdefn($name);
                  $loader->module = $this->GetName();
                  if( isset($info['js_nominify']) && $info['js_nominify'] ) $loader->js_nominify = 1;
                  if( isset($info['css_nominify']) && $info['css_nominify'] ) $loader->css_nominify = 1;
                  if( isset($info['js']) ) {
                      $tmp = $_fullpath($dir,$info['js']);
                      $tmp = $_torelpath($tmp);
                      $loader->jsfile = $tmp;
                  }
                  if( isset($info['css']) ) {
                      $tmp = $_fullpath($dir,$info['css']);
                      $loader->cssfile = $_torelpath($tmp);
                  }
                  if( isset($info['depends']) ) $loader->depends = $info['depends'];
                  $cge->get_jsloader()->register($loader, $force);
              }
              catch( \Exception $e ) {
                  debug_display($e->GetMessage());
                  debug_to_log($e->GetMessage());
              }
              unset($info);
          }
      }

      // now manually register some libraries.
      $cge = cms_utils::get_module('CMSMSExt');
      $loader = new \CMSMSExt\jsloader\libdefn('xt_datepicker');
      $loader->callback = '\JQueryTools\datepicker_plugin::load';
      $cge->get_jsloader()->register($loader, $force);
  }
} // class

?>
