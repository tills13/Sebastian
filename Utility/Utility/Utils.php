<?php
    namespace Sebastian\Utility\Utility;
    
    /**
     * Utils
     *
     * various utility methods. 
     * 
     * @author Tyler <tyler@sbstn.ca>
     * @since Oct. 2015
     */
    class Utils {
        public static function startsWith($haystack, $needles, $regex = false) {
            if ($needles == null) return false;
            if (!is_array($needles)) $needles = [$needles];

            foreach ($needles as $needle) {
                if ($regex && ($needle === "" || preg_match("^{$needle}.*", $haystack))) {
                    return true;
                } else {
                    if ($needle === "" || strrpos($haystack, $needle, -strlen($haystack)) !== false) {
                        return true;
                    }
                } 
            }

            return false;
        }

        public static function endsWith($haystack, $needles = null) {
            if ($needles == null) return false;
            if (!is_array($needles)) $needles = [$needles];

            foreach ($needles as $needle) {
                if ($needle === "" || (
                    ($temp = strlen($haystack) - strlen($needle)) >= 0 
                    && strpos($haystack, $needle, $temp) !== false)
                ) return true;
            }

            return false;
        }

        public static function ago($time) {
            $periods = ["second", "minute", "hour", "day", "week", "month", "year", "decade"];
            $lengths = ["60", "60", "24", "7", "4.35", "12", "10"];

            $now = time();
            $difference = $now - $time;
            $tense = "ago";

            for ($j = 0; $difference >= $lengths[$j] && $j < count($lengths) - 1; $j++) $difference /= $lengths[$j];
            $difference = round($difference);

            if ($difference != 1) $periods[$j] .= "s";
            if ($difference <= 10 && $j == 0) return "just now";
            else return "{$difference} {$periods[$j]} ago";
        }

        public static function escapeSQL($value) {
            switch (gettype($value)) {
                case "boolean": return $value ? "true" : "false";
                case "NULL": return "NULL";
                case "integer":
                    $value = (int)$value;
                    return $value ? (string)$value : "0";
                default:
                    $value = str_replace(array('\\', "\0", "\n", "\r", "'", '"', "\x1a"), 
                                         array('\\\\', '\\0', '\\n', '\\r', "''", '\\"', '\\Z'), 
                                         $value);
                    return "'" . $value . "'";
            }
        }

        public static function unescapeSQLString($text) {
            return str_replace(
                ['\\\\', '\\0', '\\n', '\\r', "''", '\\"', '\\Z'],
                ['\\', "\0", "\n", "\r", "'", '"', "\x1a"], 
                $text
            );
        }

        public static function isInteger($input) {
            if (is_object($input)) return false;
            return (ctype_digit(strval($input)));
        }

        public static function niceBytes($bytes) {
            $sizes = ['','K','M','G'];

            for ($j = 0; $bytes >= 1024; $j++) $bytes /= 1024;

            $bytes = round($bytes, 2);

            if ($bytes != 1) $pBytes = 'Bytes';
            else $pBytes = 'Byte';

            return "{$bytes} {$sizes[($j)]}{$pBytes}";
        }

        public static function niceArrayToString($array, $level = 1, $return = "<span>[</span>") {
            $index = 0;
            $margin = $level * 30;

            foreach ($array as $key => $value) {
                if (is_array($value)) {
                    $return .= "<br /><span style='margin-left: {$margin}px'>$key => " . self::niceArrayToString($value, ($level + 1)) . "</span>";
                } elseif ($value instanceof StdClass) {
                    $value = get_class($value);
                    $return .= "<br /><span style='margin-left: {$margin}px'>$key => {{$value}}";
                } else {
                    $return .= "<br /><span style='margin-left: {$margin}px'>$key => {$value}";
                }

                if (++$index != count($array)) $return .= ',';
                else $return .= "<br />";
            }

            $margin = ($level - 1) * 30;
            return $return."<span style='margin-left: {$margin}px'>]</span>";
        }

        public static function getExtension($filename) {
            return pathinfo($filename, PATHINFO_EXTENSION);
        }

        // HTML helpers

        public static function makeLinkControl($href = '#', $id = '', $classes = '', $data = [], $label, $icon = null) {
            if (is_array($classes)) $classes = implode(" ", $classes);
            if (!is_null($icon)) {
                $icon = "<i class='fa fa-{$icon}'></i>";
            } else $icon = '';

            $dataString = "";
            foreach ($data as $key => $value) {
                $dataString .= "data-{$key}='{$value}' ";
            }

            return "<a id='{$id}' {$dataString}class='button {$classes}' href='{$href}'>{$label}{$icon}</a>";   
        }

        public static function makeButtonControl($name = '', $classes, $label = null, $icon = null) {
            if (is_array($classes)) $classes = implode(" ", $classes);
            if (!is_null($icon)) {
                $icon = "<i class='fa fa-{$icon}'></i>";
            } else $icon = '';

            return "<button name='{$id}' class='button {$classes}' href='{$href}'>{$label}{$icon}</button>";    
        }

        public static function makeInputControl($id = '', $type, $classes, $label) {
            if (is_array($classes)) $classes = implode(" ", $classes);

            return "<input id='{$id}' type='$type' class='button {$classes}' value='$label'>";  
        }

        public static function makeDivControl($id = '', $classes = '', $label, $icon = null) {
            if (is_array($classes)) $classes = implode(" ", $classes);
            if (!is_null($icon)) {
                $icon = "<i class='fa fa-{$icon}'></i>";
            } else $icon = '';

            return "<div id='{$id}' class='button {$classes}'>{$label}{$icon}</div>";   
        }

        public static function makeChooser($choices, $name, $default) {
            foreach ($choices as $label => $choice) {
                if ($label == $default) $choices[$label] = "<span class='option active'>{$choice}</span>";
                else $choices[$label] = "<span class='option'>{$choice}</span>";
            }

            $innerHtml = implode("<span>|</span>", $choices);
            $innerHtml .= "<input type='hidden' name='{$name}' value='$default'>";
            
            return "<div class='button chooser'>{$innerHtml}</div>";
        } 

        public static function makeProgressBar($class, $segments) {
            if (is_array($class)) $class = implode(' ', $class); ?>
            <div class="progress-bar start <?=$class?>">
            <?php foreach ($segments as $segment) { ?>
                <div rel="popup" style="width: <?=$segment['progress']?>%;  background: <?=$segment['color']?>;" data-popup-message="<?=@$segment['popup']?>">
                    <span class="label"><?=$segment['label']?></span>
                </div>
            <?php } ?>
            </div>
        <?php }
    }