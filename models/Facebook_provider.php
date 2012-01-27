<?php

require_once __DIR__.'/../libraries/facebook/facebook.php';

class Facebook_provider extends Provider {
	
	/**
	 * @var	string
	 */
	const APP_ID = '354745324553617';
	
	/**
	 * @var	string
	 */
	const APP_SECRET = 'beb2ee995165fb0da4cc47964ab01881';
	
	/**
	 * Constructor
	 */
	public function __construct()
	{
		parent::__construct();
		
		// setup sdk
		$this->facebook = new Facebook(array(
			'appId' => self::APP_ID,
			'secret' => self::APP_SECRET,
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
			'scope' => array('email'),
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
			
			// build profile information
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
	
}