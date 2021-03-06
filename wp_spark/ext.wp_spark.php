<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

// include config file
require PATH_THIRD.'wp_spark/config'.EXT;

/**
 * Wee Pixel Spark Extension
 *
 * @package		Spark
 * @subpackage	Addons
 * @category	Extension
 * @author		John Clark - Wee Pixel
 * @link	http://weepixel.com	
 */

class Wp_spark_ext
{
	/**
	* Extension settings
	*
	* @var	array
	*/
	var $settings = array();

	/**
	* Extension name
	*
	* @var	string
	*/
	var $name = WP_SPARK_NAME;

	/**
	* Extension version
	*
	* @var	string
	*/
	var $version = WP_SPARK_VERSION;

	/**
	* Extension description
	*
	* @var	string
	*/
	var $description = 'Transforms text output into HTML using markup tags';

	/**
	* Do settings exist?
	*
	* @var	bool
	*/
	var $settings_exist = FALSE;

	/**
	* Documentation link
	*
	* @var	string
	*/
	var $docs_url = WP_SPARK_DOCS;

	/**
	* Format category name?
	*
	* @var	bool
	*/
	var $format = TRUE;
	
	var $default_settings = array();

	// --------------------------------------------------------------------

	/**
	* PHP4 Constructor
	*
	* @see	__construct()
	*/
	function Wp_spark_ext($settings = FALSE)
	{
		$this->__construct($settings);
	}

	// --------------------------------------------------------------------

	/**
	* PHP 5 Constructor
	*
	* @param	$settings	mixed	Array with settings or FALSE
	* @return	void
	*/
	function __construct($settings = FALSE)
	{
		// Get global instance
		$this->EE =& get_instance();

		$this->settings = $settings;
	}

	// --------------------------------------------------------------------

	/**
	* Settings
	*
	* @return	array
	*/
	function settings()
	{
		return array();
	}

	// --------------------------------------------------------------------
	
	/**
	* Search for and replace Spark tags with HTML
	* Executed at the typography_parse_type_start extension hook
	*
	* @param 	string
	* @param	object
	* @param	array
	* @return	string
	*/
	
	function spark_parse($str, $obj, $prefs) {
		$str = $this->parse_headers($str);
		$str = $this->parse_emphases($str);
		$str = $this->parse_strongs($str);
		$str = $this->parse_lists($str);
		$str = $this->parse_emails($str);
		$str = $this->encode_emails($str);
		$str = $this->parse_links($str);
		return $str;
	}
	
	// --------------------------------------------------------------------
	
	/**
	* Parse Spark formatted headings
	*
	* @param 	string
	* @return	string
	*/
	
	function parse_headers($str) {
		$regex = "/^(#{1,6})(.*)$/um";
		$str = preg_replace_callback($regex, array(&$this, "_parse_headers"), $str);
		return $str;
	}
	
	// --------------------------------------------------------------------
	
	/**
	* Heading replacement callback
	*
	* @param 	array
	* @return	string
	*/
	
	function _parse_headers($matches) {
		$size = "h".strlen($matches[1]);
		return "<$size>".$matches[2]."</$size>";
	}
	
	// --------------------------------------------------------------------
	
	/**
	* Parse Spark formatted emphases
	*
	* @param 	string
	* @return	string
	*/
	
	function parse_emphases($str) {
		$regex = "/((((\\n?(?<=\\*\\*)([*]{1})))|((\\n?(?<!(\\*)+)([*]{1}))))(?=[^\\s*])(([^*\\n]|(\\*{2})(?!\\*))+)(?<!\\s)([*]{1}))/um";
		if(preg_replace_callback($regex, array(&$this, "_parse_emphases"), $str) == TRUE){
			$str = preg_replace_callback($regex, array(&$this, "_parse_emphases"), $str);
		}
		return $str;
	}
	
	// --------------------------------------------------------------------
	
	/**
	* Emphasis replacement callback
	*
	* @param 	array
	* @return	string
	*/
	
	function _parse_emphases($matches) {
		return "<em>".$matches[10]."</em>";
	}

	// --------------------------------------------------------------------
	
	/**
	* Parse Spark formatted strongs
	*
	* @param 	string
	* @return	string
	*/
	
	function parse_strongs($str) {
		$regex = "/((?<!([^*]\\*))([*]{2})(?=[^\\s*])(([^*\\n]+|(\\*{1})(?!\\*))+)(?<!\\s)([*]{2}))/um";
		if(preg_replace_callback($regex, array(&$this, "_parse_strongs"), $str) == TRUE){
			$str = preg_replace_callback($regex, array(&$this, "_parse_strongs"), $str);
		}
		return $str;
	}
	
	// --------------------------------------------------------------------
	
	/**
	* Strong replacement callback
	*
	* @param 	array
	* @return	string
	*/
	
	function _parse_strongs($matches) {
		return "<strong>".$matches[4]."</strong>";
	}
	
	// --------------------------------------------------------------------
	
	/**
	* Parse Spark formatted lists
	*
	* @param 	string
	* @return	string
	*/
	
	function parse_lists($str) {
		$regex = "/^(([-=])(?![ ])(?s:.+?)(\\Z|\\n(?=[-=][ ])|(?=\\n[^-=].*)\\n|\\n{2,}(?=\\S)(?![ ]*\\n)))/um";
		// If breaks here, look at \n in (?![]*\\n)
		$str = preg_replace_callback($regex, array(&$this, "_parse_list"), $str);
		return $str;
	}
	
	// --------------------------------------------------------------------
	
	/**
	* List replacement callback
	*
	* @param 	array
	* @return	string
	*/
	
	function _parse_list($matches) {
		$list = preg_replace_callback("/(\\n?)^([ ]?)([=-]{1})(.*)(\\n)?/umx", array(&$this, '_parse_list_items'), $matches);
		$tag = $matches[2] == "=" ? "ol" : "ul";
		return "<$tag>\n".$list[1]."</$tag>";
	}
	
	// --------------------------------------------------------------------
	
	/**
	* List item replacement callback
	*
	* @param 	array
	* @return	string
	*/
	
	function _parse_list_items($matches) {
		return "<li>".$matches[4]."</li>\n";
	}
	
	// --------------------------------------------------------------------
	
	/**
	* Parse Spark formatted email links
	*
	* @param 	string
	* @return	string
	*/
	
	function parse_emails($str) {
		$regex = "/(?<!%)%(([\w-\.]+)@((?:[\w]+\.)+)([a-zA-Z]{2,4}))/";
		$str = preg_replace_callback($regex, array(&$this, "_parse_emails"), $str);
		return $str;
	}
	
	// --------------------------------------------------------------------
	
	/**
	* Email link replacement callback
	*
	* @param 	array
	* @return	string
	*/
	
	function _parse_emails($matches) {
		return "<a href='mailto:".$matches[1]."'>".$matches[1]."</a>";
	}
	
	// --------------------------------------------------------------------
	
	/**
	* Encode email links
	*
	* @param 	string
	* @return	string
	*/
	
	function encode_emails($str) { 
		$regex = "/\\<a\\s+(href\\=[\"']mailto\\:)(([\\w-\\.]+)\\@((?:[\\w]+\\.)+)([a-zA-Z]{2,4}))[\"'](\\s*title=[\"']([^\\\"']))?[^\\<]*\\<\\/a\\>/um";
		$str = preg_replace_callback($regex, array(&$this, "_encode_emails"), $str);
		return $str;
	}
	
	// --------------------------------------------------------------------
	
	/**
	* Encoded email link replacement callback
	*
	* @param 	array
	* @return	string
	*/
	
	function _encode_emails($matches) {
		return "{encode=".$matches[2]."}";
	}
	
	// --------------------------------------------------------------------
	
	/**
	* Parse Spark formatted links
	*
	* @param 	string
	* @return	string
	*/
	
	function parse_links($str) {
		
		// Internal links w/ anchor text		
		$regex = "/(?<=[^\\w]|\\A)\\(([^%\\(\\)]*)(%{1})((\\/)|(\\/?((([A-Za-z]{3,9}):\\/\\/)|(\\{filedir_[0-9]+\\}))?(([-{};:&=\\+\\$,\\w]+@{1})?([-A-Za-z0-9\\.{}]+)+:?(\\d+)?((\\/[-#!{}\\+~%\\/\\.\\w\\(\\)]+)?\\??([-{}\\+=&;%@\\.\\w\\(\\)\\,\\?]+)?#?([\\w]+\\/?)?)?)))\\)(?=[^\\w]|\\Z)/um";
		if(preg_replace_callback($regex, array(&$this, "_parse_int_links"), $str) == TRUE){
			$str = preg_replace_callback($regex, array(&$this, "_parse_int_links"), $str);
		}
		
		// External links w/ anchor text
		$regex = "/(?<=[^\\w]|\\A)\\(([^%\\(\\)]*)(%{2})((\\/)|(\\/?((([A-Za-z]{3,9}):\\/\\/)|(\\{filedir_[0-9]+\\}))?(([-{};:&=\\+\\$,\\w]+@{1})?([-A-Za-z0-9\\.{}]+)+:?(\\d+)?((\\/[-#!{}\\+~%\\/\\.\\w\\(\\)]+)?\\??([-{}\\+=&;%@\\.\\w\\(\\)\\,\\?]+)?#?([\\w]+\\/?)?)?)))\\)(?=[^\\w]|\\Z)/um";
		if(preg_replace_callback($regex, array(&$this, "_parse_ext_links"), $str) == TRUE){
			$str = preg_replace_callback($regex, array(&$this, "_parse_ext_links"), $str);
		}

		// Internal links w/o anchor text
		$regex = "/(?<=[^\\w])(?<!%)(%{1})\\/?(([A-Za-z]{3,9}):\\/\\/)?(([-;:&=\\+\\$,\\w]+@{1})?([-A-Za-z0-9\\.]+)+:?(\\d+)?((\\/[#!-\\+~%\\/\\.\\w\\(\\)]+)?\\??([-\\+=&;%@\\.\\w\\(\\)]+)?#?([\\w]+)?)?[^.\\s,!?*;:\\('\\\\\\\"><\\[\\]-])(?=[ ]|\\Z|\\n|\\<|\\.|\\!|\\?)/um";
		if(preg_replace_callback($regex, array(&$this, "_parse_int_plain_links"), $str) == TRUE){
			$str = preg_replace_callback($regex, array(&$this, "_parse_int_plain_links"), $str);
		}

		// External links w/o anchor text		
		$regex = "/(?<=[^\\w])(?<!%)(%{2})\\/?(([A-Za-z]{3,9}):\\/\\/)?(([-;:&=\\+\\$,\\w]+@{1})?([-A-Za-z0-9\\.]+)+:?(\\d+)?((\\/[#!-\\+~%\\/\\.\\w\\(\\)]+)?\\??([-\\+=&;%@\\.\\w\\(\\)]+)?#?([\\w]+)?)?[^.\\s,!?*;:\\('\\\\\\\"><\\[\\]-])(?=[ ]|\\Z|\\n|\\<|\\.|\\!|\\?)/um";
		if(preg_replace_callback($regex, array(&$this, "_parse_ext_plain_links"), $str) == TRUE){
			$str = preg_replace_callback($regex, array(&$this, "_parse_ext_plain_links"), $str);
		}
		
		return $str;
	}
	
	// --------------------------------------------------------------------
	
	/**
	* Link replacement callback
	* For same window/tab links with anchor text e.g. (hello%example.com)
	*
	* @param 	array
	* @return	string
	*/
	
	function _parse_int_links($matches) {
		if(empty($matches[6]) && preg_match("/((.*)(\.[a-zA-Z]+))/", $matches[3])){
			return "<a href='http://".$matches[3]."' title='".$matches[1]."'>".$matches[1]."</a>";
		}
		return "<a href='".$matches[3]."' title='".$matches[1]."'>".$matches[1]."</a>";
	}
	
	// --------------------------------------------------------------------
	
	/**
	* Link replacement callback
	* For new window/tab links with anchor text e.g. (hello%%example.com)
	*
	* @param 	array
	* @return	string
	*/
	
	function _parse_ext_links($matches) {
		if(empty($matches[6]) && preg_match("/((.*)(\.[a-zA-Z]+))/", $matches[3])){
			return "<a href='http://".$matches[3]."' title='".$matches[1]."' target='_blank'>".$matches[1]."</a>";
		}
		return "<a href='".$matches[3]."' title='".$matches[1]."' target='_blank'>".$matches[1]."</a>";
	}
	
	// --------------------------------------------------------------------
	
	/**
	* Link replacement callback
	* For same window/tab links with no anchor text e.g. %example.com
	*
	* @param 	array
	* @return	string
	*/
	
	function _parse_int_plain_links($matches) {
		return "<a href='http://".$matches[4]."'>".$matches[4]."</a>";
	}
	
	// --------------------------------------------------------------------
	
	/**
	* Link replacement callback
	* For new window/tab links with no anchor text e.g. %%example.com
	*
	* @param 	array
	* @return	string
	*/
	
	function _parse_ext_plain_links($matches) {
		return "<a href='http://".$matches[4]."' target='_blank'>".$matches[4]."</a>";
	}

	// --------------------------------------------------------------------

	/**
	* Activate extension
	*
	* @return	null
	*/
	function activate_extension()
	{
		// data to insert
		$data = array(
			'class'		=> __CLASS__,
			'method'	=> 'spark_parse',
			'hook'		=> 'typography_parse_type_start',
			'priority'	=> 10,
			'version'	=> $this->version,
			'enabled'	=> 'y',
			'settings'	=> serialize($this->default_settings)
		);

		// insert in database
		$this->EE->db->insert('exp_extensions', $data);
	}

	// --------------------------------------------------------------------

	/**
	* Update extension
	*
	* @param	string	$current
	* @return	null
	*/
	function update_extension($current = '')
	{
		if ($current == '' OR $current == $this->version)
		{
			return FALSE;
		}

		// init data array
		$data = array();

		// Add version to data array
		$data['version'] = $this->version;

		// Update records using data array
		$this->EE->db->where('class', __CLASS__);
		$this->EE->db->update('exp_extensions', $data);
	}

	// --------------------------------------------------------------------

	/**
	* Disable extension
	*
	* @return	null
	*/
	function disable_extension()
	{
		// Delete records
		$this->EE->db->where('class', __CLASS__);
		$this->EE->db->delete('exp_extensions');
	}

	// --------------------------------------------------------------------

}
// END CLASS

/* End of file ext.wp_spark.php */