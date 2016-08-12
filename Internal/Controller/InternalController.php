<?php
    namespace Sebastian\Internal\Controller;

    use Sebastian\Application;
    use Sebastian\Core\Component\Component;
    use Sebastian\Core\Controller\Controller;
    use Sebastian\Core\Event\Event;
    use Sebastian\Core\Event\EventBus;
    use Sebastian\Core\Http\Exception\HttpException;
    use Sebastian\Core\Http\Request;
    use Sebastian\Core\Http\Response\FileResponse;
    use Sebastian\Core\Http\Response\Response;
    use Sebastian\Utility\Utility\Utils;

    class InternalController extends Controller {
        public function __construct(Application $context, Component $component) {
            parent::__construct($context, $component);
            EventBus::register(Event::SHUTDOWN, [$this, 'deployToWebDirectory']);
        }

        public function getJSAction($filename, $v = 1) {
            $response = new FileResponse();
            $response->setHeader('Content-Type', 'text/javascript');

            $file = $this->getFileFromFolder('js', $filename);
            
            if ($file && file_exists($file)) {
                $response->setContent($file);
                $response->setResponseCode(Response::HTTP_OK);
                return $response;
            }

            throw HttpException::notFoundException();
        }

        public function getCSSAction($filename, $v = 1) {
            $response = new FileResponse();
            $response->setHeader('Content-Type', 'text/css');

            $file = $this->getFileFromFolder('css', $filename);

            if ($file) {
                $response->setContent($file);
                $response->setResponseCode(Response::HTTP_OK);
                return $response;
            }

            throw new \Exception("Error Processing Request", 1);
        }

        public function getFontAction($filename, $v = 1) {
            $extension = Utils::getExtension($filename);
            $response = new FileResponse();
            
            $typeMap = [
                'woff' => "application/font-woff",
                'woff2' => "application/font-woff2",
                'ttf' => "application/x-font-ttf"
            ];

            $contentType = isset($typeMap[$extension]) ? $typeMap[$extension] : ('application/x-font-' . strtolower($extension));
            $response->setHeader('Content-Type', $contentType);

            $file = $this->getFileFromFolder('font', $filename);

            if ($file) {
                $response->setContent($file);
                $response->setResponseCode(Response::HTTP_OK);
                return $response;
            }

            throw new \Exception("Error Processing Request", 1);
        }

        public function deployToWebDirectory(Request $request, Response $response) {
            $config = $this->getContext()->getConfig();

            if ($config->has('application.web_directory')) {
                $webDirectory = $config->get('application.web_directory');

                foreach (['sebastian.js', 'router.js'] as $file) {
                    $path = substr($this->generateUrl('_internal:javascript', ['filename' => $file]), 1);
                    $filename = implode(DIRECTORY_SEPARATOR, [\APP_ROOT, '..', $webDirectory, $path]);

                    if (!file_exists($filename)) {
                        $directories = explode('/', $filename);
                        array_pop($directories); // get rid of filename

                        $directory = '';
                        foreach ($directories as $mDirectory) {
                            if (!is_dir($directory .= "/${mDirectory}")) {
                                mkdir($directory);
                            }
                        }

                        $contents = file_get_contents($this->getFileFromFolder('js', $file));
                        if ($bytes = file_put_contents($filename, $contents) === false) {
                            print('failed to write file');
                        }
                    }
                }
            }
            
            return false;
        }

        private function getFileFromFolder($folder, $filename) {
            return $this->getContext()
                        ->getComponent("Sebastian\Internal")
                        ->getResourceUri("{$folder}/{$filename}", true);
        }
    }