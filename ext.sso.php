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
	
	public $settings = array();
	public $description = 'TBD...';
	public $docs_url = '';
	public $name = 'Social Sign On';
	public $settings_exist = 'y';
	public $version = '1.0';
	
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
		
	}
	
	/**
	 * Member logout
	 */
	public function member_member_logout()
	{
		
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
	
	public static function get_settings()
	{
		return $this->settings;
	}
	
}