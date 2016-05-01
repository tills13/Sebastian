<?php
    namespace Sebastian\Utility\Form\Field;
    
    /**
     * InputFormPart
     * 
     * @author Tyler <tyler@sbstn.ca>
     * @since  Oct. 2015
     */
    class PasswordField extends Field {
        protected $type = Field::TYPE_PASSWORD;
        protected $tag = 'input';

        public function render() {
            $attrs = $this->getAttributesString();
            return "<{$this->getTag()} type=\"password\" name=\"{$this->getFullName()}\" {$attrs} value=\"{$this->getValue()}\">";
        }
    }