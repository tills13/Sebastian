<?php
	namespace Sebastian\Core\Form\FormPart; 
	
	/**
	 * SelectFormPart
	 * 
	 * @author Tyler <tyler@sbstn.ca>
	 * @since  Oct. 2015
	 */
	class SelectFormPart extends FormPart {
		protected $selected;
		protected $options;

		public function __construct() {
			$this->options = [];
			$this->selected = null;
		}

		public function setValue($value) {
			if (!in_array($value, array_keys($this->options))) return;
			$this->selected = $value;

			return $this;
		}

		public function setOptions($options = []) {
			$this->options = $options;

			return $this;
		}

		public function getValue() {
			return $this->selected;
		}

		public function getOptions() {
			return $this->options['choices'];
		}

		public function render() {
			parent::render();

			$attrs = $this->getAttributes();

			echo "<{$this->getTag()} $attrs>";
			echo "<option>" . (isset($this->attrs['empty_value']) ? $this->attrs['empty_value'] : "select an option") . "</option>";
			foreach ($this->getOptions() as $value => $option) { ?>
				<option value="<?=$value?>" <?php echo ($this->selected == $value ? "selected" : ""); ?>><?=$option?></option>
			<?php }

			echo "</{$this->getTag()}>";
		}
	}