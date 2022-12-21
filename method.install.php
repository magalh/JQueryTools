<?php

if( version_compare(phpversion(),'7.4.33') < 0 ) {
    return "Minimum PHP version of 7.4.33 required";
}

$this->RegisterLibraries();

?>