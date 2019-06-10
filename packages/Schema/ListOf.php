<?php

/**
*
*/
namespace Schema;

/**
*
*/

class ListOf implements \ArrayAccess {
    
    /**
    *
    */
    protected $class_name = null;
    protected $data = [];

    /**
    *
    */
    public function __construct(string $class_name, array $data) {
        $this->class_name = $class_name;

        foreach ($data as $i => $Schema)
            $this->offsetSet($i, $Schema);
    }

    /**
    *
    */
    public function __get($offset) {
        return $this->offsetGet($offset);
    }

    /**
    *
    */
    public function __set($offset, $Schema) {
        $this->offsetSet($offset, $Schema);
    }

    /**
    *
    */
    public function __isset($offset) {
        $this->offsetExists($offset);
    }

    /**
    *
    */
    public function __unset($offset) {
        $this->offsetUnset($offset);
    }
    
    /**
    *
    */
    public function offsetGet($offset) {
        return $this->data[$offset] ?? null;
    }

    /**
    *
    */
    public function offsetSet($offset, $Schema) {
        if ($Schema instanceOf $this->class_name || (is_array($Schema) && $Schema = new $this->class_name($Schema))) {
            $offset = $Schema->ID() ?: $offset;

            if (is_null($offset))
                return $this->data[] = $Schema;
            
            $this->data[$offset] = $Schema;
        }
    }

    /**
    *
    */
    public function offsetExists($offset) {
        return isset($this->data[$offset]);
    }

    /**
    *
    */
    public function offsetUnset($offset) {
        if (is_object($offset) && $offset instanceOf $this->class_name)
            $offset = array_search($offset, $this->data);

        unset($this->data[$offset]);
    }

    /**
    *
    */
    public function items(): array {
        return $this->data;
    }
}
?>