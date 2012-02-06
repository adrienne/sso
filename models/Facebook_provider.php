<?php

require_once __DIR__.'/../libraries/facebook/facebook.php';

class Facebook_provider extends Provider {
	
	/**
	 * @var	string
	 */
	private $APP_ID;
	
	/**
	 * @var	string
	 */
	private $APP_SECRET;
	
	/**
	 * @var array
	 */
	private $permissions = array('email');
	
	/**
	 * Constructor
	 */
	public function __construct()
	{
		parent::__construct();
		
		// SET API KEYS FOR PROJECT
		$this->APP_ID = 'APP_ID_HERE';
		$this->APP_SECRET = 'APP_SECRET_HERE';
		
		// setup sdk
		$this->facebook = new Facebook(array(
			'appId' => $this->APP_ID,
			'secret' => $this->APP_SECRET,
		));
	}
	
	/**
	 * Start the registration process
	 *
	 * @param	string
	 */
	public function register_start($redirect)
	{
		// go to facebook for authorization
		$this->EE->functions->redirect($this->facebook->getLoginUrl(array(
			'redirect_uri' => $redirect,
			'scope' => $this->permissions,
		)));
	}
	
	/**
	 * Finish the registration process
	 *
	 * @return	array|bool
	 */
	public function register_finish()
	{
		// we got an error
		if($this->EE->input->get('error'))
		{
			return FALSE;
		}
		
		try
		{
			// get user info from facebook
			$me = $this->facebook->api('/me');
			
			// EDIT FIELD MAPPINGS FOR PROJECT
			$profile = json_encode(array(
				'first-name' => $me['first_name'],
				'middle-initial' => '',
				'last-name' => $me['last_name'],
				'employer' => '',
				'email' => $me['email'],
				'phone' => '',
				'address' => '',
				'address2' => '',
				'city' => '',
				'state-province' => '',
				'postal-code' => '',
				'country' => '',
				'gender' => ucfirst($me['gender']),
				'bday_m' => '',
				'bday_d' => '',
				'bday_y' => '',
				'occupation' => '',
				'username' => $me['username'],
			));
			
			$data = array(
				'user_id' => $me['id'],
				'data' => $profile,
			);
			
			return $data;
		}
		catch(FacebookApiException $e)
		{
			return FALSE;
		}
	}
	
	/**
	 * Login with the this provider
	 *
	 * @param	string
	 * @return	string
	 */
	public function login()
	{
		$user = $this->facebook->getUser();
		
		if( ! $user)
		{
			$redirect = $this->EE->functions->create_url($this->EE->uri->uri_string());
			
			$this->EE->functions->redirect($this->facebook->getLoginUrl(array(
				'redirect_uri' => $redirect,
				'scope' => $this->permissions,
			)));
		}
		
		// return the user id
		return $user;
	}
	
}