<?php

require_once __DIR__.'/models/Provider.php';

/**
 * Social Sign On Module Front End File
 *
 * @package		ExpressionEngine
 * @subpackage	Addons
 * @category	Module
 * @author		eecoder
 * @link		http://eecoder.com/
 */
class Sso {
	
	/**
	 * @var	string
	 */
	public $return_data;
	
	/**
	 * @var	object
	 */
	private $EE;
	
	/**
	 * @var	array
	 */
	private static $settings = array();
	
	/**
	 * @var	array
	 */
	private static $providers = array();
	
	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->EE =& get_instance();
		$this->initialize();
	}
	
	/**
	 * Start the registration process
	 */
	public function register_start()
	{
		$provider = $this->EE->TMPL->fetch_param('provider');
		$callback = $this->EE->TMPL->fetch_param('callback_uri', $this->EE->uri->uri_string());
		
		static::$providers[$provider]->register_start($this->EE->functions->create_url($callback));
	}
	
	/**
	 * Finish the registration process
	 */
	public function register_finish()
	{
		$provider = $this->EE->TMPL->fetch_param('provider');
		$redirect = $this->EE->TMPL->fetch_param('redirect');
		
		$result = static::$providers[$provider]->register_finish();
		
		// registration failed for some reason
		if($result === FALSE)
		{
			$this->EE->output->show_user_error('general', 'Sorry, but something went wrong during registration.');
		}
		
		// check to see if this user has authorized before
		$user = $this->EE->db->select('sso_id, member_id')->get_where('sso_accounts', array(
			'provider' => $provider,
			'user_id' => $result['user_id'],
		), 1);
		
		// the user was already authorized
		if($user->num_rows() > 0)
		{
			// if they already have a member id, don't let them register
			if($user->row('member_id') != NULL)
			{
				$this->EE->output->show_user_error('general', 'You have already registered with this provider.');
			}
			
			// update their information so that the registration form is up to date
			$this->EE->db->where(array(
				'provider' => $provider,
				'user_id' => $result['user_id'],
			))->update('sso_accounts', array(
				'data' => $result['data'],
			));
			
			$_SESSION['sso_id'] = $user->row('sso_id');
		}
		else
		{
			// add the user to the database
			$this->EE->db->insert('sso_accounts', array(
				'provider' => $provider,
				'user_id' => $result['user_id'],
				'data' => $result['data'],
			));
			
			$_SESSION['sso_id'] = $this->EE->db->insert_id();
		}
		
		// redirect them to the registration form
		$this->EE->functions->redirect('/'.$redirect);
	}
	
	/**
	 * Run on instantiation
	 */
	private function initialize()
	{
		// start the session
		session_start();
		
		// get settings if we haven't already done so
		if( ! static::$settings)
		{
			$settings = $this->EE->db->select('settings')->get_where('extensions', array(
				'class' => 'Sso_ext',
			), 1);
			
			if($settings->num_rows() > 0)
			{
				static::$settings = unserialize($settings->row('settings'));
			}
		}
		
		// get the list of providers
		if( ! static::$providers)
		{
			$providers = explode('|', static::$settings['available_providers']);
			
			foreach($providers as $provider)
			{
				$class = ucfirst($provider) . '_provider';
				$file = __DIR__."/models/{$class}.php";
				
				if(file_exists($file))
				{
					require_once $file;
					
					static::$providers[$provider] = new $class;
				}
			}
		}
	}
	
}