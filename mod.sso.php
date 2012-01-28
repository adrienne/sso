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
	 *
	 * Handles the authorization with the provider.
	 *
	 * <code>
	 *   {exp:sso:register_start provider="facebook" callback_uri="register/facebook/callback"}
	 * </code>
	 */
	public function register_start()
	{
		$provider = $this->EE->TMPL->fetch_param('provider');
		$callback = $this->EE->TMPL->fetch_param('callback_uri', $this->EE->uri->uri_string());
		
		$this->validate_provider($provider);
		
		static::$providers[$provider]->register_start($this->EE->functions->create_url($callback));
	}
	
	/**
	 * Finish the registration process
	 *
	 * This will not create the actual EE account, but should redirect to a form to do so.
	 *
	 * <code>
	 *   {exp:sso:register_finish provider="facebook" redirect="register"}
	 * </code>
	 */
	public function register_finish()
	{
		$provider = $this->EE->TMPL->fetch_param('provider');
		$redirect = $this->EE->TMPL->fetch_param('redirect');
		
		$this->validate_provider($provider);
		
		$result = static::$providers[$provider]->register_finish();
		
		// registration failed for some reason
		if($result === FALSE)
		{
			$this->EE->output->show_message(array(
				'title' => 'Social Sign On Error',
				'heading' => 'Social Sign On Error',
				'content' => '<p>Sorry, but something went wrong during registration.</p>',
			));
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
				$this->EE->output->show_message(array(
					'title' => 'Social Sign On Error',
					'heading' => 'Social Sign On Error',
					'content' => '<p>You have already registered with this provider.</p>',
				));
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
		
		// if the user is logged in, link the accounts
		if($this->EE->session->userdata('member_id') != 0 && ! empty($_SESSION['sso_id']))
		{
			$this->EE->db->where('sso_id', $_SESSION['sso_id'])->limit(1)->update('sso_accounts', array(
				'member_id' => $this->EE->session->userdata('member_id'),
			));
			
			unset($_SESSION['sso_id']);
			
			$this->EE->functions->redirect('/account');
		}
		
		// redirect them to the registration form
		$this->EE->functions->redirect('/'.$redirect);
	}
	
	/**
	 * Login via a provider
	 *
	 * <code>
	 *   {exp:sso:login provider="facebook" redirect="account"}
	 * </code>
	 */
	public function login()
	{
		$provider = $this->EE->TMPL->fetch_param('provider');
		$redirect = $this->EE->TMPL->fetch_param('redirect');
		
		$this->validate_provider($provider);
		
		// if user is already logged in, redirect
		if($this->EE->session->userdata('member_id'))
		{
			$this->EE->functions->redirect('/'.$redirect);
		}
		
		// get the provider's user id
		$user_id = static::$providers[$provider]->login($redirect);
		
		if($user_id !== FALSE)
		{
			// find user in database
			$user = $this->EE->db->select('member_id')->get_where('sso_accounts', array(
				'provider' => $provider,
				'user_id' => $user_id,
			), 1);
			
			// we found the user, so let's log them in
			if($user->num_rows() > 0 && $user->row('member_id') != NULL)
			{
				$this->EE->session->create_new_session($user->row('member_id'));
				
				unset($_SESSION['sso_id']);
				
				$this->EE->functions->redirect('/'.$redirect);
			}
		}
		
		// show error if we can't log them in
		$this->EE->output->show_message(array(
			'title' => 'Social Sign On Error',
			'heading' => 'Social Sign On Error',
			'content' => '<p>Sorry, we couldn\'t log you in.</p>',
		));
	}
	
	/**
	 * Allows you to display information about the authorized user
	 *
	 * <code>
	 *   {exp:sso:user_info}
	 *     {if has_sso_id}...{/if}
	 *     {sso_id}
	 *     {sso_first-name}
	 *     {sso_last-name}
	 *   {/exp:sso:user_info}
	 * </code>
	 */
	public function user_info()
	{
		$tagdata = $this->EE->TMPL->tagdata;
		
		// parse conditionals
		$conditionals['has_sso_id'] = empty($_SESSION['sso_id']) ? FALSE : TRUE;
		$tagdata = $this->EE->functions->prep_conditionals($tagdata, $conditionals);
		
		// we found a sso id!
		if( ! empty($_SESSION['sso_id']))
		{
			// get the user from the sso_accounts table
			$user = $this->EE->db->get_where('sso_accounts', array(
				'sso_id' => $_SESSION['sso_id'],
			), 1);
			
			if($user->num_rows() > 0)
			{
				// parse the template tags
				$vars = array(
					'sso_id' => $_SESSION['sso_id'],
				);
				$data = json_decode($user->row('data'));
				
				foreach($data as $prop => $val)
				{
					$vars['sso_'.$prop] = htmlspecialchars($val);
				}
				
				$tagdata = $this->EE->TMPL->parse_variables_row($tagdata, $vars);
			}
		}
		
		// wipe out any remaining sso tags
		$tagdata = preg_replace('#\{sso_.+?\}#i', '', $tagdata);
		
		return $tagdata;
	}
	
	/**
	 * Run on instantiation
	 */
	private function initialize()
	{
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
	
	/**
	 * Ensures that the requested provider is available
	 *
	 * @param	string
	 */
	private function validate_provider($provider)
	{
		if( ! in_array(strtolower($provider), array_keys(static::$providers)))
		{
			$this->EE->output->show_message(array(
				'title' => 'Social Sign On Error',
				'heading' => 'Social Sign On Error',
				'content' => '<p>This provider is not available.</p>',
			));
		}
	}
	
}