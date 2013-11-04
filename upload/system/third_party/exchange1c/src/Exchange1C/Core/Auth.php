<?php namespace Exchange1C\Core;

class Auth {

	/**
	 * Attempt authentication.
	 *
	 * @param string $username
	 * @param string $password
	 * @return bool
	 */
	public static function attempt($username = null, $password = null)
	{
		if ( ! empty($username) && ! empty($password))
		{
			OpenCart::session()->data['token'] = md5(mt_rand());
			return OpenCart::user()->login($username, $password);
		}

		return false;
	}


	/**
	 * Check authentication.
	 *
	 * @return bool
	 */
	public static function check()
	{
		if (isset(OpenCart::session()->data['token']))
		{
			$token = OpenCart::session()->data['token'];
			
			if ((Request::cookie('key') == $token) || (Request::get('token') == $token))
			{
				if ( ! OpenCart::user()->hasPermission('access', 'module/exchange1c'))
				{
					return false;
				}
			}
			else
			{
				return false;
			}
		}

		return true;
	}

}