<?php
	namespace Sebastian\Internal\Controller;

	use Sebastian\Core\Controller\Controller;
	use Sebastian\Core\Http\Exception\HttpException;
	use Sebastian\Core\Http\Response\FileResponse;
	use Sebastian\Core\Http\Response\Response;
	use Sebastian\Utility\Utility\Utils;

	class ResourceController extends Controller {
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

		public function getFaviconAction() {}

		public function getAssetAction($filename, $v = 1) {
			
		}

		private function getFileFromFolder($folder, $filename) {
			return $this->getContext()
				->getComponent("Internal")
				->getResourceUri("{$folder}/{$filename}", true);
		}
	}