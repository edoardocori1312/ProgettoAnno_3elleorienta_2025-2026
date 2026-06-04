<?php
    function SostituisciLink($str){
        return preg_replace('"\b(https?://\S+)"', '<a href="$1">$1</a>', $str);
    }
?>