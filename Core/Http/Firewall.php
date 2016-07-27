<?php
    namespace Sebastian\Core\Http;

    use \Exception;
    use Sebastian\Core\Context\ContextInterface;
    use Sebastian\Utility\Collection\Collection;
    
    class Firewall {
        protected static $context;
        protected static $listeners;

        public static function init(ContextInterface $context) {
            self::$context = $context;
            self::$listeners = [];
        }

        public static function addListener(Callable $listener) {
            self::$listeners[] = $listener;
        }

        public static function handle(Request &$request) {
            $session = $request->getSession();
            $config = self::$context->getConfig();

            if (($token = $request->get('token') ?? (empty($request->body) || (!$request->body instanceof Collection) ? null : $request->body->get('token'))) && !$session->check()) {
                $em = self::$context->getEntityManager();
                $userRepo = $em->getRepository($config->get('firewall.user_class'));
                $user = $userRepo->find([ 'token' => $token ]);

                if ($user !== null && is_array($user) && count($user) === 1) {
                    $session->setUser($user[0]);
                } else {
                    throw new Exception("Invalid token {$token}");
                }
            }

            foreach (self::$listeners as $index => $listener) {
                if ($response = $listener($request) instanceof Response) {
                    return $response;
                }
            }
        }
    }