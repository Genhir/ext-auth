<?php

namespace MvcCore\Ext\Auth\Basic\Traits\Auth;

trait Handling
{
	/**
	 * Return singleton instance. If instance exists, return existing instance,
	 * if not, create new basic authentication module instance, store it and return it.
	 * @param array $configuration Optional configuration passed into method
	 *                             `\MvcCore\Ext\Auth\Basic::__construct($configuration)`.
	 * @return \MvcCore\Ext\Auth\Basic|\MvcCore\Ext\Auth\Basic\Interfaces\IAuth
	 */
	public static function GetInstance ($configuration = array()) {
		if (static::$instance === NULL)
			static::$instance = new static($configuration);
		return static::$instance;
	}

	/**
	 * Create new Auth service instance.
	 * Initialize class definition properties into full class names
	 * if their values have no backslash inside.
	 * Set up MvcCore application instance reference and set up pre route handler
	 * to add authentication routes when necessary and user instance when necessary.
	 */
	public function __construct ($config = array()) {
		// set up possible configuration
		if ($config) $this->SetConfiguration($config);
		// initialize classes configuration
		$baseClassName = '\\' . __CLASS__ . '\\';
		if ($this->controllerClass && substr($this->controllerClass, 0, 1) != '\\')
			$this->controllerClass = $baseClassName . $this->controllerClass;
		if ($this->signInFormClass && substr($this->signInFormClass, 0, 1) != '\\')
			$this->signInFormClass = $baseClassName . $this->signInFormClass;
		if ($this->signOutFormClass && substr($this->signOutFormClass, 0, 1) != '\\')
			$this->signOutFormClass = $baseClassName . $this->signOutFormClass;
		if ($this->userClass && substr($this->userClass, 0, 1) != '\\')
			$this->userClass = $baseClassName . $this->userClass;
		// set up application reference
		$this->application = & \MvcCore\Application::GetInstance();
		// set up tools class
		static::$toolClass = $this->application->GetToolClass();
		$this->application
			// add sing in or sing out forms routes, complete form success and error addresses
			->AddPreRouteHandler(function () {
				$this->preRouteHandler();
			}, 100)
			// try to set up user instance into dispatched controller instance if user is not null
			->AddPreDispatchHandler(function () {
				$this->preDispatchHandler();
			}, 100);
	}

	/**
	 * Process necessary operations before request is routed by core router:
	 * - Everytime try to load user by stored session username from any previous request(s).
	 * - If request could target any authentication route or request is post:
	 *   - Set up signin form success url, signout form success url and error
	 *     url for both (sign in and sign out) forms, all urls as current request url by default.
	 *     If any url is configured already, nothing is changed.
	 *   - Set up sign in or sign out route into router, only route which
	 *     is currently necessary by authenticated/not authenticated user.
	 * @return void
	 */
	protected function preRouteHandler () {
		$this->GetUser();
		if (
			$this->addRoutesForAnyRequestMethod ||
			$this->application->GetRequest()->GetMethod() == \MvcCore\Interfaces\IRequest::METHOD_POST
		) {
			$this->preRouteHandlerSetUpUrlAdresses();
			$this->preRouteHandlerSetUpRouter();
		}
	}

	/**
	 * Try to set up authenticated user into controller instance dispatched by core.
	 * @return void
	 */
	protected function preDispatchHandler () {
		if ($this->user!== NULL)
			$this->application->GetController()->SetUser($this->user);
	}

	/**
	 * Set up sign in form success url, sign out form success url and error
	 * url for both sign in/out forms, as current request url by default.
	 * If any url is configured already, nothing is changed.
	 * @return void
	 */
	protected function preRouteHandlerSetUpUrlAdresses () {
		$currentFullUrl = $this->application->GetRequest()->GetFullUrl();
		if ($this->signedInUrl === NULL)	$this->signedInUrl	= $currentFullUrl;
		if ($this->signedOutUrl === NULL)	$this->signedOutUrl	= $currentFullUrl;
		if ($this->signErrorUrl === NULL)	$this->signErrorUrl	= $currentFullUrl;
	}

	/**
	 * Set up sign in or sign out route into router, only route which
	 * is currently by authenticated/not authenticated user necessary
	 * to process in `$router->Route();` processing.
	 * @return void
	 */
	protected function preRouteHandlerSetUpRouter () {
		$routerClass = $this->application->GetRouterClass();
		$router = & $routerClass::GetInstance();
		if ($this->IsAuthenticated()) {
			$router->AddRoute(
				$this->getInitializedRoute('SignOut'), TRUE
			);
		} else {
			$router->AddRoute(
				$this->getInitializedRoute('SignIn'), TRUE
			);
		}
	}

	/**
	 * Prepare configured route record into route instance if record is string or array.
	 * @param string $routeName
	 * @param string $actionName
	 * @return \MvcCore\Route|\MvcCore\Interfaces\IRoute
	 */
	protected function getInitializedRoute ($actionName) {
		$routeName = lcfirst($actionName) . 'Route';
		$rawRoute = & $this->$routeName;
		if ($rawRoute instanceof \MvcCore\Interfaces\IRoute) {
			return $rawRoute;
		} else {
			$routeClass = $this->application->GetRouteClass();
			$routeInitData = array('controller' => $this->controllerClass, 'action' => $actionName);
			$route = $routeClass::GetInstance(
				gettype($rawRoute) == 'array'
					? array_merge($routeInitData, $rawRoute)
					: array_merge(array('pattern' => $rawRoute), $routeInitData)
			);
			$this->$routeName = & $route;
			return $route;
		}
	}
}
