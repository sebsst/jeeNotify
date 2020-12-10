<?php

/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */

/* * ***************************Includes********************************* */
require_once __DIR__  . '/../../../../core/php/core.inc.php';

class template extends eqLogic {


    public static function dependancy_info() {
      $return = array();
      $return['log'] = 'jeeNotify_install';
      $mailparser = realpath(dirname(__FILE__) . '/../../resources/node_modules/jeedNotify');
      $return['progress_file'] = '/tmp/jeeNotify_dep';
      if (is_dir($mailparser)) {
        $return['state'] = 'ok';
      } else {
        $return['state'] = 'nok';
      }
      return $return;
    }

    public static function dependancy_install() {
      $install_path = dirname(__FILE__) . '/../../resources';
      //passthru('/bin/bash ' . $install_path . '/nodejs.sh ' . $install_path . ' jeeNotify >> ' . log::getPathToLog('jeeNotify_install') . ' 2>&1 &');
    }

    public static function deamon_start($_debug = false) {
      self::deamon_stop();
      $deamon_info = self::deamon_info();
     $fd = inotify_init();
     if ($deamon_info['launchable'] != 'ok') {
        throw new Exception(__('Veuillez vérifier la configuration', __FILE__));
      }
      log::add('jeeNotify', 'info', 'Lancement du démon jeeNotify');
      $watch_descriptor = inotify_add_watch($fd, $file, IN_ALL_EVENTS);
      //$url = network::getNetworkAccess('internal') . '/plugins/jeeNotify/core/api/jeeNotify.php?apikey=' . jeedom::getApiKey('jeeNotify');

      $service_path = realpath(dirname(__FILE__) . '/../../resources');
      $attach_path = $service_path . '/attachments/';

      foreach ($deamon_info['notlaunched'] as $addr) {
        $mail = self::byLogicalId($addr, 'jeeNotify');
        $cmd = 'nice -n 19 nodejs ' . $service_path . '/jeeNotify.js ' . $addr . ' ' . $url . ' "' . $mail->getConfiguration('username') . '" "' . $mail->getConfiguration('password') . '" ' . $mail->getConfiguration('server') . ' ' . $mail->getConfiguration('port') . ' ' . $mail->getConfiguration('attach') . ' ' . $attach_path;

        log::add('jeeNotify', 'debug', 'Lancement démon jeeNotify : ' . $cmd);
        $result = exec('nohup ' . $cmd . ' >> ' . log::getPathToLog('jeeNotify_node') . ' 2>&1 &');
        if (strpos(strtolower($result), 'error') !== false || strpos(strtolower($result), 'traceback') !== false) {
          log::add('jeeNotify', 'error', $result);
          return false;
        }
      }
      $i = 0;
      while ($i < 30) {
        $deamon_info = self::deamon_info();
        if ($deamon_info['state'] == 'ok') {
          break;
        }
        sleep(1);
        $i++;
      }
      if ($i >= 30) {
        log::add('jeeNotify', 'error', 'Impossible de lancer un démon jeeNotify, vérifiez le port', 'unableStartDeamon');
        return false;
      }
      message::removeAll('jeeNotify', 'unableStartDeamon');
      log::add('jeeNotify', 'info', 'Démons jeeNotify lancé');
      return true;
    }

    public static function deamon_info() {
      $return = array();
      $return['log'] = 'jeeNotify';
      $return['state'] = 'ok';
      $return['launchable'] = 'ok';
      $return['notlaunched'] = array();
      $return['launched'] = array();
      foreach (eqLogic::byType('jeeNotify') as $jeeNotify) {
          if ($jeeNotify->getIsEnable() == 1 ) {
            $pid = trim( shell_exec ('ps ax | grep "jeeNotify/resources/jeeNotify.js '. $jeeNotify->getConfiguration('addr') . '" | grep -v "grep" | wc -l') );
            if ($pid != '' && $pid != '0') {
              $return['launched'][] = $jeeNotify->getConfiguration('addr');
            } else {
              $return['state'] = 'nok';
              $return['notlaunched'][] = $jeeNotify->getConfiguration('addr');
              $return['launchable_message'] = $jeeNotify->getConfiguration('addr') . ' non lancé';
            }
            if ($jeeNotify->getConfiguration('addr') == '') {
              $return['launchable'] = 'nok';
              $return['launchable_message'] = __('Le port de ' . $jeeNotify->getName() . ' n\'est pas configuré', __FILE__);
            }
          }
      }
      return $return;
    }

    public static function deamon_stop() {

      inotify_rm_watch($fd, $watch_descriptor);
      log::add('jeeNotify', 'info', 'Arrêt du service jeeNotify');
      $deamon_info = self::deamon_info();
      if (count($deamon_info['launched']) != 0) {
      }
    }

    /*     * *************************Attributs****************************** */

  /*
   * Permet de définir les possibilités de personnalisation du widget (en cas d'utilisation de la fonction 'toHtml' par exemple)
   * Tableau multidimensionnel - exemple: array('custom' => true, 'custom::layout' => false)
	public static $_widgetPossibility = array();
   */

    /*     * ***********************Methode static*************************** */

    /*
     * Fonction exécutée automatiquement toutes les minutes par Jeedom
      public static function cron() {
      }
     */

    /*
     * Fonction exécutée automatiquement toutes les 5 minutes par Jeedom
      public static function cron5() {
      }
     */

    /*
     * Fonction exécutée automatiquement toutes les 10 minutes par Jeedom
      public static function cron10() {
      }
     */

    /*
     * Fonction exécutée automatiquement toutes les 15 minutes par Jeedom
      public static function cron15() {
      }
     */

    /*
     * Fonction exécutée automatiquement toutes les 30 minutes par Jeedom
      public static function cron30() {
      }
     */

    /*
     * Fonction exécutée automatiquement toutes les heures par Jeedom
      public static function cronHourly() {
      }
     */

    /*
     * Fonction exécutée automatiquement tous les jours par Jeedom
      public static function cronDaily() {
      }
     */



    /*     * *********************Méthodes d'instance************************* */

 // Fonction exécutée automatiquement avant la création de l'équipement
    public function preInsert() {

    }

 // Fonction exécutée automatiquement après la création de l'équipement
    public function postInsert() {

    }

 // Fonction exécutée automatiquement avant la mise à jour de l'équipement
    public function preUpdate() {

    }

 // Fonction exécutée automatiquement après la mise à jour de l'équipement
    public function postUpdate() {

    }

 // Fonction exécutée automatiquement avant la sauvegarde (création ou mise à jour) de l'équipement
    public function preSave() {

    }

 // Fonction exécutée automatiquement après la sauvegarde (création ou mise à jour) de l'équipement
    public function postSave() {

    }

 // Fonction exécutée automatiquement avant la suppression de l'équipement
    public function preRemove() {

    }

 // Fonction exécutée automatiquement après la suppression de l'équipement
    public function postRemove() {

    }

    /*
     * Non obligatoire : permet de modifier l'affichage du widget (également utilisable par les commandes)
      public function toHtml($_version = 'dashboard') {

      }
     */

    /*
     * Non obligatoire : permet de déclencher une action après modification de variable de configuration
    public static function postConfig_<Variable>() {
    }
     */

    /*
     * Non obligatoire : permet de déclencher une action avant modification de variable de configuration
    public static function preConfig_<Variable>() {
    }
     */

    /*     * **********************Getteur Setteur*************************** */
}

class templateCmd extends cmd {
    /*     * *************************Attributs****************************** */

    /*
      public static $_widgetPossibility = array();
    */

    /*     * ***********************Methode static*************************** */


    /*     * *********************Methode d'instance************************* */

    /*
     * Non obligatoire permet de demander de ne pas supprimer les commandes même si elles ne sont pas dans la nouvelle configuration de l'équipement envoyé en JS
      public function dontRemoveCmd() {
      return true;
      }
     */

  // Exécution d'une commande
     public function execute($_options = array()) {

     }

    /*     * **********************Getteur Setteur*************************** */
}
