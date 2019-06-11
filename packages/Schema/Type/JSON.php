<?php

/**
*
*/
namespace Schema\Type;

/**
*
*/
class JSON extends \Schema\Schema {
    
    /**
    *
    */
    public function __toString() {
        return json_encode($this->__attributes);
    }
}
?>