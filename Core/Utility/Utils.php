<?php
	namespace Sebastian\Core\Utility;
	
	use Sebastian\Core\Entity\Entity;
	
	/**
	 * Utils
	 *
	 * various utility methods. 
	 * 
	 * @author Tyler <tyler@sbstn.ca>
	 * @since Oct. 2015
	 */
	class Utils {
		public static function startsWith($haystack, $needle) {
		    return $needle === "" || strrpos($haystack, $needle, -strlen($haystack)) !== false;
		}

		public static function endsWith($haystack, $needle) {
		    return $needle === "" || (($temp = strlen($haystack) - strlen($needle)) >= 0 && strpos($haystack, $needle, $temp) !== false);
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

		public static function cast($value, $type = 'guess') {
			if (strstr($type, ':')) {
				$type = explode(':', $type);
				$subtype = $type[1];
				$type = $type[0];
			}

			if ($type === 'number' || $type === 'numeric' || ($type === 'guess' && is_float($value))) {
				$value = floatval($value);
			} else if ((strpos($type, 'int') !== false) || 
					   ($type === 'serial') ||
					   ($type === 'guess' && self::isInteger($value))) {
				$value = intval($value);
			} else if ($type === 'array' || $type === '_text') { // postgres returns _text
				if (gettype($value) === 'string') { // string to array
					$value = explode(',', trim($value, '{}'));
				} else if (gettype($value) === 'array') { // array to string
					$value = "{".implode(',', $value)."}";
				}
			} else if ($type === 'boolean' || $type === 'bool' || ($type === 'guess' && is_bool($value))) {
				if (is_bool($value)) {
					$value = $value ? "t" : "f";
				} else {
					$value = (strtolower($value) === 't' 
						   || strtolower($value) === 'true'
						   || strtolower($value) === 'on' // checkboxes -.-
						   || strtolower($value) === '1');
				}
			} else if ($type === 'string' || $type === 'text' || $type === 'varchar') {
				return (string) $value;
			} else {
			}

			if ($value instanceof Entity) {
				$value = $value->getId();
			}

			return $value;
		}

		public static function isInteger($input) {
			if (is_object($input)) return false;
    		return (ctype_digit(strval($input)));
    	}

    	public static function isBool($input) {
    		//$input = strval($input)
    		//return 
    	}

    	// 0 beginning
    	// 1 camel
    	public function capitalize($string, $type = 0) {
    		if ($type === 0) {
    			$string[0] = strtoupper($string[0]);
    		}

    		return $string;
    	}

		public static function sanitize($string) {
			//$string = preg_replace('/[\/\\]/', '', $string);
			//$string = preg_replace('/[ ]/', '', $string);
			return $string;
		}

		public static function niceBytes($bytes) {
			$sizes = ['','K','M','G'];

			for ($j = 0; $bytes >= 1024; $j++) $bytes /= 1024;

			$bytes = round($bytes, 2);

			if ($bytes != 1) $pBytes = 'Bytes';
			else $pBytes = 'Byte';

			return "{$bytes} {$sizes[($j)]}{$pBytes}";
		}

		public static function niceExceptions($trade) {
			//$trace = $e->getTrace();

			return "Exception @ {$trace[0]['class']}->{$trace[0]['function']}();";

			return $result;
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
				} elseif ($value instanceof Entity) {
					$return .= "<br /><span style='margin-left: {$margin}px'>$key => {{$value->getId()}}";
				} else {
					$return .= "<br /><span style='margin-left: {$margin}px'>$key => {$value}";
				}

				if (++$index != count($array)) $return .= ',';
				else $return .= "<br />";
			}

			$margin = ($level - 1) * 30;
			return $return."<span style='margin-left: {$margin}px'>]</span>";
		}

		public static function buildItemsFromRequest($request, $type) {
			
		}

		public static function getExtension($filename) {
			return pathinfo($filename, PATHINFO_EXTENSION);
		}

		public static function packVar($var) {
			if (is_array($var)) {
				$object = new \StdClass;
		        
				foreach ($var as $key => $value) $object->{$key} = Utils::packVar($value);

				return $object;
			} elseif (is_object($var)) { // already packed
				return $var;
			} else { // value
				return $var;
			}
		}

		public function requestIsMobile() {
			$useragent = $_SERVER['HTTP_USER_AGENT'];

			return (preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|netfront|opera m(ob|in)i|palm(os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows (ce|phone)|xda|xiino/i',$useragent) ||
			   preg_match('/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp(i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac(|\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt(|\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg(g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i',substr($useragent,0,4)));
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

		// REMOVE ME TODO FIX ME

		public static function generateSlug($string) {
			$string = preg_replace('~[^\\pL\d]+~u', '-', $string); // replace non letter or digits by -
			$string = trim($string, '-'); // trim
			$string = iconv('utf-8', 'us-ascii//TRANSLIT', $string); // transliterate
			$string = strtolower($string); // lowercase
			$string = preg_replace('~[^-\w]+~', '', $string); // remove unwanted characters
			$string = str_replace(['the','a'], '', $string);

			return $string;
		}

		public static function renderText($text, $unescapeSQL = false, $renderLinks = false) {
			if ($unescapeSQL) {
				$text = str_replace(
					['\\\\', '\\0', '\\n', '\\r', "''", '\\"', '\\Z'],
					['\\', "\0", "\n", "\r", "'", '"', "\x1a"], 
					$text
				);
			}

			if ($renderLinks) {
				$text = preg_replace("/\[@([^\]]+)\]/", "<a href='/u/$1'>@$1</a>", $text);
			} else {
				$text = preg_replace("/\[@([^\]]+)\]/", "<span href='/u/$1'>@$1</span>", $text);
			}
			
			$text = preg_replace("/\[b\](.+?)\[\\\b\]/", "<b>$1</b>", $text);
			$text = preg_replace("/\[i\](.+?)\[\\\i\]/", "<i>$1</i>", $text);
			$text = preg_replace("/\[u\](.+?)\[\\\u\]/", "<u>$1</u>", $text);
			$text = preg_replace("/\[s\](.+?)\[\\\s\]/", "<s>$1</s>", $text);
			$text = preg_replace("/\[c=(.+)\](.+?)\[\\\c\]/", "<span style=\"color:$1;\">$2</span>", $text);
			$text = preg_replace("/\[img src=(.*?)\](.*?)\[\\img\]/", "<img src='$1' title='$2'>", $text);
			if ($renderLinks) {
				$text = preg_replace("/\[a href=(.*?)\](.*?)\[\\\a\]/", "<a class='u' href='$1'>$2</a>", $text);
			} else {
				$text = preg_replace("/\[a href=(.*?)\](.*?)\[\\\a\]/", "<span class='u' href='$1'>$2</span>", $text);
			}
			$text = preg_replace("/\[quote(?:=(.*?))?\](.*?)\[\\\quote\]/", "<blockquote>$2<span class=credit>$1</span></blockquote>", $text);

			$text = "<p>" . preg_replace('/\n\n/', "</p><p>", $text) . "</p>";
			//$text = str_replace(['\n'], ['<br/>'], $text);
			$text = preg_replace('/\n/', "<br/>", $text);
			$text = preg_replace('/<p><\/p>/', "", $text);
			return $text;
		}
	}