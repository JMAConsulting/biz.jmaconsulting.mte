<?php
/**
 * Mandrill Transactional Email extension integrates CiviCRM's non-bulk email 
 * with the Mandrill service
 * 
 * Copyright (C) 2012 JMA Consulting
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 * 
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 * 
 * Support: https://github.com/JMAConsulting/biz.jmaconsulting.mte/issues
 * 
 * Contact: info@jmaconsulting.biz
 *          JMA Consulting
 *          215 Spadina Ave, Ste 400
 *          Toronto, ON  
 *          Canada   M5T 2C7
 */

// AUTO-GENERATED FILE -- This may be overwritten!

/**
 * (Delegated) Implementation of hook_civicrm_config
 */
function _mte_civix_civicrm_config(&$config = NULL) {
  static $configured = FALSE;
  if ($configured) return;
  $configured = TRUE;

  $template =& CRM_Core_Smarty::singleton();

  $extRoot = dirname( __FILE__ ) . DIRECTORY_SEPARATOR;
  $extDir = $extRoot . 'templates';

  if ( is_array( $template->template_dir ) ) {
      array_unshift( $template->template_dir, $extDir );
  } else {
      $template->template_dir = array( $extDir, $template->template_dir );
  }

  $include_path = $extRoot . PATH_SEPARATOR . get_include_path( );
  set_include_path( $include_path );
}

/**
 * (Delegated) Implementation of hook_civicrm_xmlMenu
 *
 * @param $files array(string)
 */
function _mte_civix_civicrm_xmlMenu(&$files) {
  foreach (_mte_civix_glob(__DIR__ . '/xml/Menu/*.xml') as $file) {
    $files[] = $file;
  }
}

/**
 * Implementation of hook_civicrm_install
 */
function _mte_civix_civicrm_install() {
  _mte_civix_civicrm_config();
  if ($upgrader = _mte_civix_upgrader()) {
    return $upgrader->onInstall();
  }
}

/**
 * Implementation of hook_civicrm_uninstall
 */
function _mte_civix_civicrm_uninstall() {
  _mte_civix_civicrm_config();
  if ($upgrader = _mte_civix_upgrader()) {
    return $upgrader->onUninstall();
  }
}

/**
 * (Delegated) Implementation of hook_civicrm_enable
 */
function _mte_civix_civicrm_enable() {
  _mte_civix_civicrm_config();
  if ($upgrader = _mte_civix_upgrader()) {
    if (is_callable(array($upgrader, 'onEnable'))) {
      return $upgrader->onEnable();
    }
  }
}

/**
 * (Delegated) Implementation of hook_civicrm_disable
 */
function _mte_civix_civicrm_disable() {
  _mte_civix_civicrm_config();
  if ($upgrader = _mte_civix_upgrader()) {
    if (is_callable(array($upgrader, 'onDisable'))) {
      return $upgrader->onDisable();
    }
  }
}

/**
 * (Delegated) Implementation of hook_civicrm_upgrade
 *
 * @param $op string, the type of operation being performed; 'check' or 'enqueue'
 * @param $queue CRM_Queue_Queue, (for 'enqueue') the modifiable list of pending up upgrade tasks
 *
 * @return mixed  based on op. for 'check', returns array(boolean) (TRUE if upgrades are pending)
 *                for 'enqueue', returns void
 */
function _mte_civix_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  if ($upgrader = _mte_civix_upgrader()) {
    return $upgrader->onUpgrade($op, $queue);
  }
}

function _mte_civix_upgrader() {
  if (!file_exists(__DIR__.'/CRM/Mte/Upgrader.php')) {
    return NULL;
  } else {
    return CRM_Mte_Upgrader_Base::instance();
  }
}

/**
 * Search directory tree for files which match a glob pattern
 *
 * Note: Dot-directories (like "..", ".git", or ".svn") will be ignored.
 * Note: In Civi 4.3+, delegate to CRM_Utils_File::findFiles()
 *
 * @param $dir string, base dir
 * @param $pattern string, glob pattern, eg "*.txt"
 * @return array(string)
 */
function _mte_civix_find_files($dir, $pattern) {
  if (is_callable(array('CRM_Utils_File', 'findFiles'))) {
    return CRM_Utils_File::findFiles($dir, $pattern);
  }

  $todos = array($dir);
  $result = array();
  while (!empty($todos)) {
    $subdir = array_shift($todos);
    foreach (_mte_civix_glob("$subdir/$pattern") as $match) {
      if (!is_dir($match)) {
        $result[] = $match;
      }
    }
    if ($dh = opendir($subdir)) {
      while (FALSE !== ($entry = readdir($dh))) {
        $path = $subdir . DIRECTORY_SEPARATOR . $entry;
        if ($entry{0} == '.') {
        } elseif (is_dir($path)) {
          $todos[] = $path;
        }
      }
      closedir($dh);
    }
  }
  return $result;
}
/**
 * (Delegated) Implementation of hook_civicrm_managed
 *
 * Find any *.mgd.php files, merge their content, and return.
 */
function _mte_civix_civicrm_managed(&$entities) {
  $mgdFiles = _mte_civix_find_files(__DIR__, '*.mgd.php');
  foreach ($mgdFiles as $file) {
    $es = include $file;
    foreach ($es as $e) {
      if (empty($e['module'])) {
        $e['module'] = 'biz.jmaconsulting.mte';
      }
      $entities[] = $e;
    }
  }
}

/**
 * Glob wrapper which is guaranteed to return an array.
 *
 * The documentation for glob() says, "On some systems it is impossible to
 * distinguish between empty match and an error." Anecdotally, the return
 * result for an empty match is sometimes array() and sometimes FALSE.
 * This wrapper provides consistency.
 *
 * @see http://php.net/glob
 * @param string $pattern
 * @return array, possibly empty
 */
function _mte_civix_glob($pattern) {
  $result = glob($pattern);
  return is_array($result) ? $result : array();
}

/**
 * Inserts a navigation menu item at a given place in the hierarchy
 *
 * $menu - menu hierarchy
 * $path - path where insertion should happen (ie. Administer/System Settings)
 * $item - menu you need to insert (parent/child attributes will be filled for you)
 * $parentId - used internally to recurse in the menu structure
 */
function _mte_civix_insert_navigation_menu(&$menu, $path, $item, $parentId = NULL) {
  static $navId;

  // If we are done going down the path, insert menu
  if (empty($path)) {
    if (!$navId) $navId = CRM_Core_DAO::singleValueQuery("SELECT max(id) FROM civicrm_navigation");
    $navId ++;
    $menu[$navId] = array (
      'attributes' => array_merge($item, array(
        'label'      => CRM_Utils_Array::value('name', $item),
        'active'     => 1,
        'parentID'   => $parentId,
        'navID'      => $navId,
      ))
    );
    return true;
  } else {
    // Find an recurse into the next level down
    $found = false;
    $path = explode('/', $path);
    $first = array_shift($path);
    foreach ($menu as $key => &$entry) {
      if ($entry['attributes']['name'] == $first) {
        if (!$entry['child']) $entry['child'] = array();
        $found = _mte_civix_insert_navigation_menu($entry['child'], implode('/', $path), $item, $key);
      }
    }
    return $found;
  }
}
