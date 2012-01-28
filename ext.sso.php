<?php

/**
 * Social Sign On Extension
 *
 * @package		ExpressionEngine
 * @subpackage	Addons
 * @category	Extension
 * @author		eecoder
 * @link		http://eecoder.com/
 */
class Sso_ext {
	
	/**
	 * @var	array
	 */
	public $settings = array();
	
	/**
	 * @var	string
	 */
	public $description = 'TBD...';
	
	/**
	 * @var	string
	 */
	public $docs_url = '';
	
	/**
	 * @var	string
	 */
	public $name = 'Social Sign On';
	
	/**
	 * @var	string
	 */
	public $settings_exist = 'y';
	
	/**
	 * @var	string
	 */
	public $version = '1.0';
	
	/**
	 * @var	object
	 */
	private $EE;
	
	/**
	 * Constructor
	 *
	 * @param	array
	 */
	public function __construct($settings = array())
	{
		$this->EE =& get_instance();
		$this->settings = $settings;
	}
	
	/**
	 * Settings Form
	 */
	public function settings()
	{
		return array(
			'available_providers' => array('i', '', ''),
		);
	}
	
	/**
	 * Activate Extension
	 */
	public function activate_extension()
	{
		$this->settings = array(
			'available_providers' => 'facebook|twitter',
		);
		
		$hooks = array(
			'member_member_login_single' => 'member_member_login_single',
			'member_member_logout' => 'member_member_logout',
			'user_register_end' => 'user_register_end',
			'sessions_start' => 'sessions_start',
		);
		
		foreach($hooks as $hook => $method)
		{
			$data = array(
				'class' => __CLASS__,
				'method' => $method,
				'hook' => $hook,
				'settings' => serialize($this->settings),
				'version' => $this->version,
				'enabled' => 'y'
			);
			
			$this->EE->db->insert('extensions', $data);
		}
	}
	
	/**
	 * Member login
	 *
	 * @param	object
	 */
	public function member_member_login_single($row)
	{
		// TODO
	}
	
	/**
	 * Member logout
	 */
	public function member_member_logout()
	{
		// TODO
	}
	
	/**
	 * User registration
	 *
	 * @param	object
	 * @param	int
	 */
	public function user_register_end($user, $member_id)
	{
		if( ! empty($_SESSION['sso_id']))
		{
			// update the sso accounts table
			$this->EE->db->where('sso_id', $_SESSION['sso_id'])->limit(1)->update('sso_accounts', array(
				'member_id' => $member_id,
			));
			
			unset($_SESSION['sso_id']);
		}
	}
	
	/**
	 * Run on session start
	 */
	public function sessions_start($session)
	{
		// start a native php session if we don't have one
		if( ! session_id())
		{
			session_start();
		}
	}
	
	/**
	 * Disable Extension
	 */
	function disable_extension()
	{
		$this->EE->db->where('class', __CLASS__);
		$this->EE->db->delete('extensions');
	}
	
	/**
	 * Update Extension
	 *
	 * @return 	bool|void
	 */
	function update_extension($current = '')
	{
		if ($current == '' OR $current == $this->version)
		{
			return FALSE;
		}
	}
	
	/**
	 * Allows easy access to the settings from other classes
	 *
	 * @return	array
	 */
	public static function get_settings()
	{
		return $this->settings;
	}
	
}