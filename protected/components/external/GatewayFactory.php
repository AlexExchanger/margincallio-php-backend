<?php

class GatewayFactory extends CComponent {
    
    public static function create($type, $userId = null) {
        if (!isset($type)) {
            return false;
        }
        $className = ucfirst($type).'Gateway';
        if (class_exists($className)) {
            return new $className($userId);
        }
        return false;
    }
    
}
