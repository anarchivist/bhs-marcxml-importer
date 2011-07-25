<?php
/**
 * class.marcxml-parser.php
 *
 */

require_once( 'File/MARCXML.php' );

define( 'SUBDIV', 'vxyz' );
define( 'SUBJECTSF', 'avxyz' );
define( 'SUBFIELD_VALUES', 'abcdefghijklmnopqrstuvwxyz0123456789' );


/**
 * Compares length of two strings.
 * 
 * @var $a string
 *	 first string to compare
 * @var $b string
 *	 second string to compare
 * @return int
 *	 difference in length between first and second string
 */
function length_cmp ( $a, $b ){
	return strlen( $a ) - strlen( $b );
}

/**
* 
*/
class MARCXML_Parser {
	
	// {{{ properties
	/**
	 * Record object
	 * 
	 * @var $record File_MARCXML
	 */
	var $record;	
	// }}}
	
	// {{{ Constructor: function __construct()
	/**
	 * Create a MARCXML record parser
	 * 
	 * This function reads in a MARCXML record.
	 *
	 * @param File_MARCXML $record
	 */
	function __construct( $record ) {
		$this->record = $record;
	}
	// }}}
	
	// {{{ field_get()
	/**
		* Retrieve an array of formatted fields from a File_MARC_Record object,
		* specified by tag
		*
		* @param string $tag
		*		MARC tag to retrieve
		* @param bool $pcre
		*		if true, then match as a regular expression
		* @return array
		*/
	function field_get( $tag = NULL, $pcre = FALSE ) {
		$out = array();
		$record = $this->record;
		if (! empty( $record ) ) {
			$fieldset = $record->getFields( $tag, $pcre );
			foreach	 ($fieldset as $field ) {
				$out[] = $field->formatField();
			}
		}
		return $out;
	}
	// }}}
	
	// {{{ join_field()
	/**
	 * Similar to File_MARC_Field::formatField, but allows you to specify which
	 * subfields and the join character
	 *
	 * @param File_MARC_Field $field
	 *	 MARC field to join
	 * @param string $joiner
	 *	 String used to join the subfield values
	 * @param array $subfields
	 *	 Subfields to return
	 * @return string
	 */
	function join_field( $field = NULL, $joiner = ' ', $subfields = 'abcdefghijklmnopqrstuvwxyz0123456789' ) {
		$sarr = str_split( $subfields );
		if ($field->isControlfield()) {
			return $field->getData();
		} else {
			$out = '';
			foreach ( $field->getSubfields() as $sf ) {
				$code = $sf->getCode();
				if ( in_array( $code, $sarr ) ) {
					if ( substr( $field->getTag(), 0, 1 ) == '6' and ( in_array( $code, array( 'v','x','y','z' ) ) ) ) {
						$out .= ( empty( $out ) ) ? $sf->getData() : ' -- ' . $sf->getData();
					} else {
						$out .= ( empty( $out ) ) ? $sf->getData() : $joiner . $sf->getData();
					}
				}
			}
			return preg_replace( '/\s[|].\s/', ' -- ', $out );
		}
	}
	// }}}
	
	// {{{ title() 
	/**
	 * Return a formatted title string based on MARC 245 field data.
	 *
	 * @return string
	 */
	function title() {
		return $this->join_field( $this->record->getField('245'), ', ', 'af' );
	}
	// }}}
	
	// {{{ call_number()
	/** 
	 * Return the call number within the record.
	 * 
	 * @return string
	 */
	function call_number() {
		return $this->record->getField( '099' )->formatField();
	}
	// }}}
	
	// {{{ extent()
	/**
	 * Return formatted extent string based on MARC 300 field data.
	 * 
	 * @return string
	 */
	function extent() {
		return $this->join_field( $this->record->getField( '300' ), ', ', 'af' );
	}
	// }}}
	
	// {{{ scope_or_abstract()
	/**
	 * Return an array of formatted scope and contents notes or abstracts
	 * based on MARC 520 field data
	 * 
	 * TODO: Force function to check value of field's first indicator pending
	 * resolution of Archivists' Toolkit issue ART-2503 or an AT export plugin
	 * that sets indicator accordingly.
	 *
	 * @return array
	 */
	function scope_or_abstract() {
		return $this->field_get( '520' );
	}
	// }}}
	
	// {{{ bioghist()
	/**
	 * Return an array of formatted biographical statments based on MARC 545
	 * field data
	 *
	 * @return array
	 */
	function bioghist() {
		return $this->field_get( '545' );
	}
	// }}}
	
	// {{{ names()
	/**
	 * Return an array of formatted names based on MARC 100, 110, 600, 610, 700
	 * and 710 field data
	 *
	 * @return array
	 */
	function names() {
		$names = $this->record->getFields( '(1|6|7)(0|1)0', true );
		$out = array();
		foreach ( $names as $name ) {
			if ( substr( $name->getTag(), 1, 2 ) == '00' ) {
				$out[] = $this->join_field( $name, ', ', 'ad' . SUBDIV );
			} else {
				$out[] = $this->join_field( $name, '. ', 'abgn' . SUBDIV );
			}
		}
		return array_unique( $out );
	}	 
	// }}}
	
	// {{{ places()
	/**
	 * Return an array of formatted places based on MARC 651 field data
	 *
	 * @return array
	 */
	function places() {
		$places = $this->record->getFields( '651' );
		$out = array();
		foreach ( $places as $place ) {
			$out[] = $this->join_field( $place, ', ', SUBJECTSF );
		}
		return $out;
	}	 
	// }}}
	
	// {{{ subjects()
	/**
	 * Return array of formatted subjects based on MARC 650 and 630 field data
	 *
	 * @return array
	 */
	function subjects() {
		$subjects = $this->record->getFields( '6(5|3)0', true );
		$out = array();
		foreach ( $subjects as $subject ) {
			$out[] = $this->join_field( $subject, ', ', SUBJECTSF );
		}
		return $out;
	}	 
	// }}}
	
	// {{{ types()
	/**
	 * Return array of formatted types of material based on MARC 655 field data
	 *
	 * @return array
	 */
	function types() {
		$types = $this->record->getFields( '655' );
		$out = array();
		foreach ( $types as $type ) {
			$out[] = $this->join_field( $type, ', ', SUBJECTSF );
		}
		return $out;
	}	 
	// }}}
	
	// {{{ copies()
	/**
	 * Return array of note fields specifying the existence of copies based
	 * on MARC 535 field data.
	 */
	function copies() {
		return $this->field_get( '535' );
	}	 
	// }}}
	
	// {{{ finding_aid()
	/**
	 * Return string containing URL to hosted finding aid
	 *
	 * @return array
	 */
	function finding_aid() {
		$link = $this->record->getField( '555' );
		if (!empty($link)) {
			return $link->getSubfield( 'u' )->getData();
		} else {
			return '';
		}
	}	 
	// }}}
	
	// {{{ content()
	/**
	 * Build the contents of a post body using MARC record data.
	 *
	 * @return string
	 */
	function content() {
		$out = '';
		$call_number = $this->call_number();
		
		$out .= (! empty( $call_number ) ) ? "<p><strong>Call Number: " . $call_number . "</strong></p>\n" : "";
		
		$extent = $this->extent();
		$out .= (! empty( $extent ) ) ? "<p><strong>Extent: " . $extent . "</strong></p>\n" : "";
		
		// What follows is a horrid workaround; it selects the shortest 520
		// field by doing a comparison of the length of all of the formatted
		// fields. Eventually we should be able to handle this properly if AT devs
		// implement ART-2503 to differentiate abstracts and scopecontents in
		// MARCXML exports.
		//
		// The previous workaround selected the second 520 field if there were
		// more than one, otherwise, it only selected the first.
		$scope_or_abstract = $this->scope_or_abstract();
		usort( $scope_or_abstract, "length_cmp" );
		if ( ! empty( $scope_or_abstract ) ) {
			$out .= '<p>' . $scope_or_abstract[0] . "</p>\n"; 
		}
		
		$copies = $this->copies();
		if ( ! empty( $copies ) ) {
			foreach ( $copies as $copy ) {
				$out .= '<p>' . $copy . "</p>\n";
			}
		}
		
		$bioghist = $this->bioghist();
		if ( ! empty( $bioghist ) && ( count( $scope_or_abstract ) < 2 ) ) {
			foreach ( $bioghist as $bh ) {
				$out .= '<p>' . $bh . "</p>\n";
			}
		}
		
		$names = $this->names();
		if ( ! empty( $names ) ) {
			$out .= "<p><strong>Names:</strong></p>\n<ul>\n";
			foreach ( $names as $name ) {
				$out .= "<li>" . $name . "</li>\n";
			}
			$out .= "</ul>\n";
		}
		
		$places = $this->places();
		if ( ! empty( $places ) ) {
			$out .= "<p><strong>Places:</strong></p>\n<ul>\n";
			foreach ( $places as $place ) {
				$out .= "<li>" . $place . "</li>\n";
			}
			$out .= "</ul>\n";
		}
		
		$subjects = $this->subjects();
		if ( ! empty( $subjects ) ) {
			$out .= "<p><strong>Subjects:</strong></p>\n<ul>\n";
			foreach ( $subjects as $subject ) {
				$out .= "<li>" . $subject . "</li>\n";
			}
			$out .= "</ul>\n";
		}
		
		$types = $this->types();
		if ( ! empty( $types )) {
			$out .= "<p><strong>Types of material:</strong></p>\n<ul>\n";
			foreach ( $types as $type ) {
				$out .= "<li>" . $type . "</li>\n";
			}
			$out .= "</ul>\n";
		}
		
		$url = $this->finding_aid();
		if ( ! empty( $url ) ) {
			$out .= '<p><a href="'. $url . '">View Finding Aid</a></p>';
		}
		
		return $out;
	}
	// }}}
	
	// {{{ get_postdata()
	/**
	 * Build an array of data needed to create a WordPress post using data
	 * derived from a MARC record.
	 *
	 * @return array
	 */
	function get_postdata($options = array(
		'post_status' => 'publish',
		'post_type' => 'post',
		'post_author' => '1',
		'comment_status' => 'closed',
		'ping_status' => 'open',			
		'post_parent' => '0',
		'menu_order' => '0',
		'post_category' => '1')
		){
		global $wpdb;
		$options['post_category'] = array( $options['post_category'] );
		$post_title = $this->title();
		$post_content = $this->content();
		return array_merge( $options, compact( array( 'post_title', 'post_content' ) ) );
	}
	// }}}
}
?>