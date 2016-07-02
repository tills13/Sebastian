<?php
    namespace Sebastian\Core\Http;

    use \Exception;
    use Sebastian\Core\Context\ContextInterface;
    use Sebastian\Utility\Collection\Collection;
    
    class Firewall {
        protected static $context;
        protected static $listeners;

        public static function init(ContextInterface $context) {
            Firewall::$context = $context;
            Firewall::$listeners = [];
        }

        public static function addListener(Callable $listener) {
            Firewall::$listeners[] = $listener;
        }

        public static function handle(Request &$request) {
            $session = $request->getSession();
            $config = self::$context->getConfig();

            if (($token = $request->get('token') ?? (empty($request->body) || (!$request->body instanceof Collection) ? null : $request->body->get('token'))) && !$session->check()) {
                $em = self::$context->getEntityManager();
                $userRepo = $em->getRepository($config->get('firewall.user_interface_class'));
                $user = $userRepo->find([ 'token' => $token ]);

                if ($user !== null && is_array($user) && count($user) == 1) {
                    $user = $user[0];
                    $session->setUser($user);
                } else {
                    throw new Exception("invalid token {$token}");
                }
            }

            foreach (Firewall::$listeners as $listener) {
                if ($response = $listener($request) instanceof Response) {
                    return $response;
                }
            }
        }
    }