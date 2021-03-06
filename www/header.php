<?php
/*
 * Copyright 2005-2015 Centreon
 * Centreon is developped by : Julien Mathis and Romain Le Merlus under
 * GPL Licence 2.0.
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License as published by the Free Software
 * Foundation ; either version 2 of the License.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
 * PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with
 * this program; if not, see <http://www.gnu.org/licenses>.
 *
 * Linking this program statically or dynamically with other modules is making a
 * combined work based on this program. Thus, the terms and conditions of the GNU
 * General Public License cover the whole combination.
 *
 * As a special exception, the copyright holders of this program give Centreon
 * permission to link this program with independent modules to produce an executable,
 * regardless of the license terms of these independent modules, and to copy and
 * distribute the resulting executable under terms of Centreon choice, provided that
 * Centreon also meet, for each linked independent module, the terms  and conditions
 * of the license of that module. An independent module is a module which is not
 * derived from this program. If you modify this program, you may extend this
 * exception to your version of the program, but you are not obliged to do so. If you
 * do not wish to do so, delete this exception statement from your version.
 *
 * For more information : contact@centreon.com
 *
 */

if (!defined('SMARTY_DIR')) {
    define('SMARTY_DIR', realpath('../GPL_LIB/Smarty/libs/') . '/');
}

/*
 * Bench
 */
function microtime_float()  {
    list($usec, $sec) = explode(" ", microtime());
    return ((float)$usec + (float)$sec);
}

set_time_limit(60);
$time_start = microtime_float();

$advanced_search = 0;

/*
 * Include
 */
include_once(realpath(dirname(__FILE__) . "/../config/centreon.config.php"));

require_once "$classdir/centreonDB.class.php";
require_once "$classdir/centreonLang.class.php";
require_once "$classdir/centreonSession.class.php";
require_once "$classdir/centreon.class.php";
require_once SMARTY_DIR."Smarty.class.php";

/*
 * Create DB Connection
 *  - centreon
 *  - centstorage
 */
$pearDB     = new CentreonDB();
$pearDBO    = new CentreonDB("centstorage");

ini_set("session.gc_maxlifetime", "31536000");

CentreonSession::start();

/*
 * Delete Session Expired
 */
$DBRESULT = $pearDB->query("SELECT * FROM `options` WHERE `key` = 'session_expire' LIMIT 1");
$session_expire = $DBRESULT->fetchRow();
if (!isset($session_expire["value"]) || !$session_expire["value"]) {
    $session_expire["value"] = 2;
}
$time_limit = time() - ($session_expire["value"] * 60);

$DBRESULT = $pearDB->query("DELETE FROM `session` WHERE `last_reload` < '".$time_limit."'");

/*
 * Get session and Check if session is not expired
 */
$DBRESULT = $pearDB->query("SELECT `user_id` FROM `session` WHERE `session_id` = '".session_id()."'");
if (!$DBRESULT->numRows()) {
    header("Location: index.php?disconnect=2");
}

/*
 * Check autologin here
 */
if (!isset($_SESSION["centreon"])) {
    if (!isset($_GET['autologin'])) {
        header("Location: index.php?disconnect=1");
    } else {
        $args = NULL;
        foreach ($_GET as $key=>$value) { 
            $args ? $args .= "&".$key."=".$value : $args = $key."=".$value;
        }
        header("Location: index.php?".$args."");
    }
}

/*
 * Define Oreon var alias
 */
if (isset($_SESSION["centreon"])) {
    $centreon = $_SESSION["centreon"];
    $oreon = $centreon;
}
if (!isset($centreon) || !is_object($centreon)) {
    exit();
}

/*
 * Init differents elements we need in a lot of pages
 */
unset($centreon->Nagioscfg);
$centreon->initNagiosCFG($pearDB);
unset($centreon->optGen);
$centreon->initOptGen($pearDB);

if (!$p) {
    $root_menu = get_my_first_allowed_root_menu($centreon->user->access->topologyStr);
    if (isset($root_menu["topology_page"])) {
        $p = $root_menu["topology_page"];
    } else {
        $p = NULL;
    }
    if (isset($root_menu["topology_url_opt"])) {
        $tab = preg_split("/\=/", $root_menu["topology_url_opt"]);
        if (isset($tab[1])) {
            $o = $tab[1];
        }
    }
}

/*
 * Cut Page ID
 */
$level1 = NULL;
$level2 = NULL;
$level3 = NULL;
$level4 = NULL;
switch (strlen($p)) {
    case 1 :  $level1= $p; break;
    case 3 :  $level1 = substr($p, 0, 1); $level2 = substr($p, 1, 2); $level3 = substr($p, 3, 2); break;
    case 5 :  $level1 = substr($p, 0, 1); $level2 = substr($p, 1, 2); $level3 = substr($p, 3, 2); break;
    case 6 :  $level1 = substr($p, 0, 2); $level2 = substr($p, 2, 2); $level3 = substr($p, 3, 2); break;
    case 7 :  $level1 = substr($p, 0, 1); $level2 = substr($p, 1, 2); $level3 = substr($p, 3, 2); $level4 = substr($p, 5, 2); break;
    default : $level1= $p; break;
}

/*
 * Skin path
 */
$DBRESULT = $pearDB->query("SELECT value FROM `options` WHERE `key` = 'template' LIMIT 1");
$data = $DBRESULT->fetchRow();
$skin = "./Themes/".$data["value"]."/";

$tab_file_css = array();
$i = 0;
if ($handle  = @opendir($skin."Color")) {
    while ($file = @readdir($handle)) {
        if (is_file($skin."Color"."/$file"))
            $tab_file_css[$i++] = $file;
    }
    @closedir($handle);
}

$colorfile = "Color/". $tab_file_css[0];

/*
 * Get CSS Order and color
 */
$DBRESULT = $pearDB->query("SELECT `css_name` FROM `css_color_menu` WHERE `menu_nb` = '".$level1."'");
if ($DBRESULT->numRows() && ($elem = $DBRESULT->fetchRow())) {
    $colorfile = "Color/".$elem["css_name"];
}

/*
 * Update Session Table For last_reload and current_page row
 */
$DBRESULT = $pearDB->query("UPDATE `session` SET `current_page` = '".$level1.$level2.$level3.$level4."', `last_reload` = '".time()."', `ip_address` = '".$_SERVER["REMOTE_ADDR"]."' WHERE CONVERT(`session_id` USING utf8) = '".session_id()."' AND `user_id` = '".$centreon->user->user_id."'");

/*
 * Init Language
 */
$centreonLang = new CentreonLang(_CENTREON_PATH_, $centreon);
$centreonLang->bindLang();
$centreonLang->bindLang('help');
