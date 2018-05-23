<?php
/*
Plugin Name: Grant Application Shortcode
Plugin URI: https://github.com/JMAConsulting/biz.jmaconsulting.grantapplications
Description: Allow grant application profiles to be used as shortcodes in Wordpress pages and posts
Version: 1.0
Author: JMA Consulting
Author URI: http://jmaconsulting.biz
License: AGPL3
*/
add_filter('civicrm_shortcode_preprocess_atts', array('CiviCRM_For_WordPress_Shortcodes_Grant', 'civicrm_shortcode_preprocess_atts'), 10, 2);

// FIXME: Uncomment to allow support for multiple shortcodes on pages.
//add_filter('civicrm_shortcode_get_data', array('CiviCRM_For_WordPress_Shortcodes_Grant', 'civicrm_shortcode_get_data'), 10, 3);

/**
 * Define CiviCRM_For_WordPress_Shortcodes Class
 */
class CiviCRM_For_WordPress_Shortcodes_Grant {

  function civicrm_shortcode_preprocess_atts($args, $shortcode_atts) {
    if ($shortcode_atts['component'] == 'grant') {
      $args['q'] = 'civicrm/grant/transact';
      return $args;
    }
  }

  // FIXME: Seems like multiple shortcodes don't work on a single page. Also, 
  function civicrm_shortcode_get_data($data, $atts, $args) {
    if ($atts['component'] == 'grant') {
      // get grant application page
      $sql = "SELECT title, intro_text FROM civicrm_grant_app_page WHERE id = {$params['id']}";
      $dao = CRM_Core_DAO::executeQuery($sql);
      while ($dao->fetch()) {
        $data['title'] = $dao->title;
        $data['text'] = $dao->intro_text;
      }
      return $data;
    }
  }

}
