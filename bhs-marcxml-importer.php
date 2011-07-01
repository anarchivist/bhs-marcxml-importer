<?php
/*
Plugin Name: BHS MARCXML Importer
Plugin URI: 
Description: Imports data from MARCXML records and generates WordPress posts.
Author: Mark A. Matienzo
Author URI: http://matienzo.org/
Version: 0.2
Stable tag: 1.0
License: GPL v2 - http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
*/

require_once('File/MARCXML.php');
require_once('class.marcxml-parser.php');

/**
 * BHS MARCXML Importer
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

if ( class_exists( 'WP_Importer' ) ) {
  class MARCXML_Import {
    
    var $file;
    var $id;
    var $count = 0;
    
    function header() {
      echo '<div class="wrap">';
      echo '<h2>'.__('Import MARCXML').'</h2>';
    }
    
    function footer() {
      echo '</div>';
    }
    
    function greet() {
      echo '<div class="narrow">';
      echo '<p>'.__('This imports XML files containing MARCXML records into individual WordPress posts.').'</p>';
      echo '<p>'.__('Upload your file containing MARCXML records, and we will transform the records into posts.').'</p>';
      wp_import_upload_form("admin.php?import=". strtolower(get_class($this)) ."&amp;step=1");
      echo '</div>';
    }

    // TODO: Make this actually set stuff.
    function import_options() {
      // FYI: Using wp_import_handle_upload() will automatically append
      // '.txt' to the filename.
      $file = wp_import_handle_upload();
  		if ( isset($file['error']) ) {
  			echo '<p>'.__('Sorry, there has been an error.').'</p>';
  			echo '<p><strong>' . $file['error'] . '</strong></p>';
  			return;
  		}
  		$this->file = $file['file'];
  		$this->id = (int) $file['id'];
      echo '<h3>'.__('Select Author').'</h2>';
      echo '<p>'.__('Please select the author to which the imported records will be attributed.').'</p>';
      echo '<form action="?import='. strtolower(get_class($this)) .'&amp;step=2&amp;id='. $this->id .'" method="post">';
      wp_dropdown_users(array('name' => 'author'));
      wp_nonce_field('import-marcxml');
      echo '<input type="submit" name="submit" value="Select" />';
      echo '</form>';
    }
    
    function parse_data($file) {
      $this->records = new File_MARCXML($file);
      while ($r = $this->records->next()) {
        $parser = new MARCXML_Parser($r);
        $post = $parser->get_postdata();
        $post_id = wp_insert_post($post);
        wp_set_post_categories($post_id, 1);
        ++ $this->count;
      }
    }
    
    function done() {
      $this->file = get_attached_file($this->id);
      if ($this->file) {
        wp_import_cleanup($this->id);
      }
      echo '<div class="narrow">';
      echo '<h3 id="complete">'.__('Processing complete.').'</h3>';
      echo '<p>'. $this->count .' '. __('records imported.') .'</p>';
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
  				$this->parse_data( $file );
  				$this->done();
  				break;
  		}
  		$this->footer();
  	}
  }
  
  // This is subclassed because of the relatively minor changes needed.
  class MARCXML_Zip_Import extends MARCXML_Import {

    function header() {
      echo '<div class="wrap">';
      echo '<h2>'.__('Import MARCXML from Zip file').'</h2>';
    }
    
    function greet() {
      echo '<div class="narrow">';
      echo '<p>'.__('This imports MARCXML records in a Zip file into individual WordPress posts.').'</p>';
      echo '<p>'.__('Upload your Zip file containing MARCXML records, and we will transform the records into posts.').'</p>';
      wp_import_upload_form("admin.php?import=". strtolower(get_class($this)) ."&amp;step=1");
      echo '</div>';
    }
    
    function parse_data($file) {
      // Instantiate the WordPress file system.
      global $wp_filesystem;
      WP_Filesystem();
      
      // Specify the temporary directory. The WordPress unzip_file function
      // will attempt to create this directory automatically if it doesn't
      // exist; however, if the permissions won't allow it, it will fail.
      $tempdir = $wp_filesystem->wp_content_dir() . 'marcxml-import/';
      
      // Attempt unzipping the uploaded file.
      if( unzip_file( $file, $tempdir )) {
			  
			  // Get a listing of all the XML files in the tempdir.
			  // TODO: handle zip files containing subdirectories.
			  $xml_files = glob("$tempdir*.xml");
			  
			  // Repeat for each XML file.
			  foreach ( $xml_files as $x ) {
          $this->records = new File_MARCXML($x);
          while ($r = $this->records->next()) {
            $parser = new MARCXML_Parser($r);
            $post = $parser->get_postdata();
            $post_id = wp_insert_post($post);
            wp_set_post_categories($post_id, 1);
            ++ $this->count;
          }
          
          // Delete the current XML file.
          $wp_filesystem->delete($x);
        }
      }
    }
  }
}

// Instantiate and register the importer
$marcxml_import = new MARCXML_Import();
register_importer('marcxml_import', __('MARCXML', 'marcxml-importer'), __('Import MARCXML records as posts', 'bhs-marcxml-importer'), array ($marcxml_import, 'dispatch'));

$marcxml_zip_import = new MARCXML_Zip_Import();
register_importer('marcxml_zip_import', __('MARCXML from Zip file', 'marcxml-zip-importer'), __('Import MARCXML records in a Zip file as posts', 'bhs-marcxml-importer'), array ($marcxml_zip_import, 'dispatch'));

function marc_importer_init() {
    load_plugin_textdomain( 'bhs-marcxml-importer', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'init', 'marc_importer_init' );

?>