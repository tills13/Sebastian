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