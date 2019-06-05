<?php

namespace FlexiDatabase;

class DataSchema {

    final public function __construct(array $data) {
        foreach ($data as $field => $value)
            $this->{$field} = $value;
    }  

}
?>