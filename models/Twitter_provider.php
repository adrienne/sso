<?php

require_once __DIR__.'/../libraries/twitter/twitteroauth.php';

class Twitter_provider extends Provider {
	
	/**
	 * @var	string
	 */
	const CONSUMER_KEY = 't06FVvkxmELax1SzRZrLSg';
	
	/**
	 * @var	string
	 */
	const CONSUMER_SECRET = 'bAneplyrXeiYfC0O7AckGAe7foyKCiIbPxdPiLriN4';
	
	/**
	 * Constructor
	 */
	public function __construct()
	{
		parent::__construct();
		
		// setup sdk
		$this->twitter = new TwitterOAuth(self::CONSUMER_KEY, self::CONSUMER_SECRET);
	}
	
	/**
	 * Start the registration process
	 *
	 * @param	string
	 */
	public function register_start($redirect)
	{
		$token = $this->twitter->getRequestToken($redirect);
		
		$_SESSION['oauth_token'] = $token['oauth_token'];
		$_SESSION['oauth_token_secret'] = $token['oauth_token_secret'];
		
		$this->EE->functions->redirect($this->twitter->getAuthorizeURL($_SESSION['oauth_token']));
	}
	
	/**
	 * Finish the registration process
	 *
	 * @return	array|bool
	 */
	public function register_finish()
	{
		if( ! $this->EE->input->get('oauth_verifier'))
		{
			return FALSE;
		}
		
		$this->twitter = new TwitterOAuth(self::CONSUMER_KEY, self::CONSUMER_SECRET, $_SESSION['oauth_token'], $_SESSION['oauth_token_secret']);
		
		$access_token = $this->twitter->getAccessToken($this->EE->input->get('oauth_verifier'));
		
		$user = $this->twitter->get('account/verify_credentials');
		
		// build profile information
		$profile = json_encode(array(
			'first-name' => '',
			'middle-initial' => '',
			'last-name' => '',
			'employer' => '',
			'email' => '',
			'phone' => '',
			'address' => '',
			'address2' => '',
			'city' => '',
			'state-province' => '',
			'postal-code' => '',
			'country' => '',
			'gender' => '',
			'bday_m' => '',
			'bday_d' => '',
			'bday_y' => '',
			'occupation' => '',
			'username' => $user->screen_name,
		));
		
		$data = array(
			'user_id' => $user->id_str,
			'data' => $profile,
		);
		
		return $data;
	}
	
	/**
	 * Login with the this provider
	 *
	 * @param	string
	 * @return	string
	 */
	public function login($redirect)
	{
		
	}
	
}