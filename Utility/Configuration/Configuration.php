<?php
    namespace Sebastian\Utility\Configuration;

    use \APP_ROOT;
    use Sebastian\Utility\Collection\Collection;
    use Sebastian\Utility\Utility\Utils;

    class Configuration extends Collection {
        const TYPE_YAML = 0;
        const TYPE_JSON = 1;

        public static function fromFilename($filename, $defaults = []) {
            return Configuration::fromPath("../config/{$filename}", true, $defaults);
        }

        public static function fromPath($path, $relativeToAppRoot = false, $defaults = []) {
            if ($relativeToAppRoot) {
                $path = APP_ROOT . DIRECTORY_SEPARATOR . $path;
            }

            //print ($path); print("<br/>");

            if (!file_exists($path)) return null;

            $fileType = strtolower(Utils::getExtension($path));
            //if ($extensionOverride === null) $fileType = Utils::getExtension($filename);
            //else $fileType = $extensionOverride;

            if ($fileType == 'yaml' || $fileType == 'yml') $config = yaml_parse_file($path);
            else if ($fileType == 'json') $config = json_decode(file_get_contents($path));
            else $config = [];

            return new Configuration($config);
        }
    }