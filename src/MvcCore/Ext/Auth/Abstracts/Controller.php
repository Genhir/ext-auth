<?php

/**
 * MvcCore
 *
 * This source file is subject to the BSD 3 License
 * For the full copyright and license information, please view
 * the LICENSE.md file that are distributed with this source code.
 *
 * @copyright	Copyright (c) 2016 Tom Flídr (https://github.com/mvccore/mvccore)
 * @license		https://mvccore.github.io/docs/mvccore/4.0.0/LICENCE.md
 */

namespace MvcCore\Ext\Auth\Abstracts;

abstract class Controller extends \MvcCore\Controller {
	/**
	 * Authentication form submit action to sign in.
	 * Routed by route configured by:
	 * MvcCore\Ext\Auth::GetInstance()->SetSignInRoute();
	 * @return void
	 */
	public abstract function SignInAction ();
	/**
	 * Authentication form submit action to sign out.
	 * Routed by route configured by:
	 * MvcCore\Ext\Auth::GetInstance()->SetSignOutRoute();
	 * @return void
	 */
	public abstract function SignOutAction ();
}
