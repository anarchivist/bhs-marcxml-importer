<?php
/*
Plugin Name: BHS MARC Importer
Plugin URI: 
Description: Imports data from MARCXML records and generates WordPress posts.
Author: Mark A. Matienzo
Author URI: http://matienzo.org/
Version: 0.1
Stable tag: 1.0
License: GPL v2 - http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
*/

require_once('File/MARCXML.php');

/**
 * BHS MARC Importer
 *
 * @package WordPress
 * @subpackage Importer
 */
 
if ( !defined('WP_LOAD_IMPORTERS') )
	return;

// Load Importer API
require_once ABSPATH . 'wp-admin/includes/import.php';

if ( !class_exists( 'WP_Importer' ) ) {
	$class_wp_importer = ABSPATH . 'wp-admin/includes/class-wp-importer.php';
	if ( file_exists( $class_wp_importer ) )
		require_once $class_wp_importer;
}

/**
  * Retrieve an array of formatted fields from a File_MARC_Record object,
  * specified by tag
  *
  * @param File_MARC_Record $record
  *   record
  * @param string $tag
  *   MARC tag to retrieve
  * @param bool $pcre
  *   if true, then match as a regular expression
  * @return array
  */
function field_get($record = NULL, $tag = NULL, $pcre = FALSE) {
  $out = array();
  if (!empty($record)) {
    $fieldset = $record->getFields($tag, $pcre);
    foreach ($fieldset as $field) {
      $out[] = $field->formatField();
    }
  }
  return $out;
}

/**
  * Retrieve the first matching fields from a File_MARC_Record object,
  * specified by tag, and return a formatted string.
  *
  * @param File_MARC_Record $record
  *   record
  * @param string $tag
  *   MARC tag to retrieve
  * @param bool $pcre
  *   if true, then match as a regular expression
  * @return string
  */
function field_get_first($record = NULL, $tag = NULL, $pcre = FALSE) {
  $r = field_get($record, $tag, $pcre);
  if (!empty($r)) {
    return $r[0];
  } else {
    return "";
  }
}

if ( class_exists( 'WP_Importer' ) ) {
  class MARCXML_Import {
    
    var $file;
    var $id;
    
    function header() {
      echo '<div class="wrap">';
      echo '<h2>'.__('Import MARCXML').'</h2>';
    }
    
    function footer() {
      echo '</div>';
    }
    
    function greet() {
      echo '<div class="narrow">';
      echo '<p>'.__('This imports MARCXML records into individual WordPress posts.').'</p>';
      echo '<p>'.__('Upload your MARCXML file, and we will transform the records into posts.').'</p>';
      wp_import_upload_form("admin.php?import=marcxml&amp;step=1");
      echo '</div>';
    }
    
    function wp_authors_form() {
      echo '<h3>'.__('Select Author').'</h2>';
      echo '<p>'.__('Please select the author to which the imported records will be attributed.').'</p>';
      echo '<form action="?import=marcxml&amp;step=2" method="post">';
      wp_dropdown_users(array('name' => 'author'));
      wp_nonce_field('import-marcxml');
      echo '<input type="submit" name="submit" value="Select" />';
      echo '</form>';
    }
    
    function import_options() {
      $file = wp_import_handle_upload();
  		if ( isset($file['error']) ) {
  			echo '<p>'.__('Sorry, there has been an error.').'</p>';
  			echo '<p><strong>' . $file['error'] . '</strong></p>';
  			return;
  		}
  		$this->file = $file['file'];
  		$this->id = (int) $file['id'];
      // echo '<h3>'.__('Select Author').'</h2>';
      // echo '<p>'.__('Please select the author to which the imported records will be attributed.').'</p>';
      echo '<form action="?import=marcxml&amp;step=2&amp;id='. $this->id .'" method="post">';
      // wp_dropdown_users(array('name' => 'author'));
      wp_nonce_field('import-marcxml');
      echo '<input type="submit" name="submit" value="Select" />';
      echo '</form>';
    }
    
    function make_content($r) {
      $out = '<p><strong>Call Number: '. field_get_first($r, '099') ."</strong></p>\n";
      $out .= '<p><strong>Extent: '.field_get_first($r, '300')."</strong></p>\n";
      $scopeabs = field_get($r, '520');
      if (!empty($scopeabs)) {
        $out .= '<p>'. $scopeabs[0]. "</p>\n";
      }
      $bioghist = field_get($r, '545');
      if (!empty($bioghist) && (count($scopeabs > 1))) {
        $out .= '<p>'. $bioghist[0]. "</p>\n";
      }
      $names = $r->getFields('.(0|1)0', true);
      if (!empty($names)) {
        $out .= "<p><strong>Names:</strong></p>\n";
        foreach ($names as $name) {
          $out .= "<li>". $name->formatField() ."</li>\n";
        }
        $out .= "</ul>\n";
      }
      $places = $r->getFields('651');
      if (!empty($places)) {
        $out .= "<p><strong>Places:</strong></p>\n<ul>\n";
        foreach ($places as $place) {
          $out .= "<li>". $place->formatField() ."</li>\n";
        }
        $out .= "</ul>\n";
      }
      $subjects = $r->getFields('6(5|3)0', true);
      if (!empty($subjects)) {
        $out .= "<p><strong>Subjects:</strong></p>\n<ul>\n";
        foreach ($subjects as $subject) {
          $out .= "<li>". $subject->formatField() ."</li>\n";
        }
        $out .= "</ul>\n";
      }
      $types = $r->getFields('655');
      if (!empty($types)) {
        $out .= "<p><strong>Types of material:</strong></p>\n<ul>\n";
        foreach ($types as $type) {
          $out .= "<li>". $type->formatField() ."</li>\n";
        }
        $out .= "</ul>\n";
      }
      $url = $r->getField('555');
      if (!empty($url)) {
        $out .= '<p><a href="'. $url->getSubfield('u') .'">View Finding Aid</a></p>';
      }
      return $out; 
    }
    
    function parse_marcxml($file) {
      global $wpdb;
      
      $this->records = new File_MARCXML($file);
      while ($r = $this->records->next()) {
        $post = array();
        $post['post_title'] = field_get_first($r, '245'); // gets everything, not $a$f
        $post['post_date'] = date('Y-m-d H:M:S');
        $post['post_date_gmt'] = gmdate('Y-m-d H:M:S');
        $post['comment_status'] = 'closed';
        $post['ping_status'] = 'open';
        $post['post_status'] = 'publish';
        $post['post_parent'] = '0';
        $post['menu_order'] = '0';
        $post['post_type'] = 'post';
        $post['post_author'] = '1';
        $post['post_content'] = $wpdb->escape($this->make_content($r));
        $post_id = wp_insert_post($post);
        wp_set_post_categories($post_id, 1);
      }
    }
    
    function import() {
      print_r($this);
      print_r($_GET);
    }
    
    function dispatch() {
  		if (empty ($_GET['step']))
  			$step = 0;
  		else
  			$step = (int) $_GET['step'];

  		$this->header();
  		switch ($step) {
  			case 0 :
  				$this->greet();
  				break;
  			case 1 :
  				check_admin_referer('import-upload');
  				$this->import_options();
  				break;
  			case 2:
  				check_admin_referer('import-marcxml');
  				$this->id = (int) $_GET['id'];
  				$file = get_attached_file( $this->id );
  				set_time_limit(0);
  				$this->parse_marcxml( $file );
  				
  				break;
  		}
  		$this->footer();
  	}
  }
}

// Instantiate and register the importer
$marc_import = new MARCXML_Import();
register_importer('marcxml', __('MARCXML', 'marc-importer'), __('Import MARCXML records as posts', 'bhs-marc-importer'), array ($marc_import, 'dispatch'));

function marc_importer_init() {
    load_plugin_textdomain( 'bhs-marc-importer', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'init', 'marc_importer_init' );

?>