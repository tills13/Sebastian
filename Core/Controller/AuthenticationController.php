<?php
	namespace Sebastian\Core\Controller;

	use Sebastian\Core\Http\Request;
	use Sebastian\Core\Session\Session;
	use Sebastian\Core\Exception\SebastianException;

	/**
	 * AuthenticationController
	 * 
	 * @author Tyler <tyler@sbstn.ca>
	 * @since  Oct. 2015
	 */
	class AuthenticationController extends Controller {
		public function __construct($context) {
			parent::__construct($context);
		}

		public function loginAction(Request $request, Session $session) {
			//if ($session->get('session_token'))

			if ($user = $this->authenticate($request, $session)) {
				$session->setUser($user);
				$session->reload();

				if ($redirect = $request->get('_rdr')) $this->redirect($redirect);
				else $this->redirect($this->generateUrl('root'));
			} else throw new SebastianException("Could not sign in...");
		}

		public function logoutAction(Request $request, Session $session) {
			$session->destroy();
			$this->redirect($this->generateUrl('root'));
		}
	}