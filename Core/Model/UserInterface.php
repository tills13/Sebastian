<?php
    namespace Sebastian\Core\Model;

    interface UserInterface extends EntityInterface {
        public function setUsername($username);
        public function getUsername();
        public function isAdmin();
    }