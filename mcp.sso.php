<?php

/**
 * Social Sign On Module Control Panel File
 *
 * @package		ExpressionEngine
 * @subpackage	Addons
 * @category	Module
 * @author		eecoder
 * @link		http://eecoder.com/
 */
class Sso_mcp {
	
	/**
	 * @var	string
	 */
	public $return_data;
	
	/**
	 * @var	string
	 */
	private $_base_url;
	
	/**
	 * @var	object
	 */
	private $EE;
	
	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->EE =& get_instance();
		
		$this->_base_url = BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=sso';
		
		$this->EE->cp->set_right_nav(array(
			'module_home' => $this->_base_url,
		));
	}
	
	/**
	 * Module home screen
	 */
	public function index()
	{
		$this->EE->cp->set_variable('cp_page_title', lang('sso_module_name'));
	}
	
}