<?php
	namespace Sebastian\Core\Controller;

	use Sebastian\Core\Utility\Utils;

	use Sebastian\Core\Http\Response\Response;
	use Sebastian\Core\Http\Response\FileResponse;

	class ResourceController extends Controller {
		public function __construct($context) {
			parent::__construct($context);

			$namespace = $context->getAppNamespace();
			$this->resourceFolder = (\APP_ROOT . "/{$namespace}/Common/Resources/public");
		}

		public function getJSAction($filename, $v = 1) {
			$response = new FileResponse();
			$response->setHeader('Content-Type', 'text/javascript');

			$file = $this->getFileFromFolder('js', $filename);
			
			if ($file) {
				//require $file;
				$response->setContent($file);
				$response->setResponseCode(Response::HTTP_OK);
				return $response;
			}

			throw new \Exception("Error Processing Request", 1);
		}

		public function getCSSAction($filename, $v = 1) {
			$response = new FileResponse();
			$response->setHeader('Content-Type', 'text/css');
			//header('Content-Type: text/css');

			$file = $this->getFileFromFolder('css', $filename);

			if ($file) {
				//require $file;
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

		public function getFaviconAction() {}

		public function getAssetAction($filename, $v = 1) {
			//$extension = Utils::getExtension($filename);
			//header("Content-Type: application/x-font-" . strtolower($extension));

			//include "{$this->resourceFolder}/asset/{$filename}";
		}

		private function getFileFromFolder($folder, $filename) {
			$components = $this->getContext()->getComponents(true);
			$namespace = $this->getContext()->getAppNamespace();

			foreach ($components as $component) {
				$potentialFilePath = \APP_ROOT."/{$namespace}{$component['path']}/Resources/public/{$folder}/{$filename}";
				
				if (file_exists($potentialFilePath)) return $potentialFilePath;
			}

			return null;
		}
	}