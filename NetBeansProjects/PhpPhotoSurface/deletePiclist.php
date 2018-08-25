<?php
    ////Possibilty of controlling the traffic!
    $user = "oma";
    if(file_exists('../../private/pics/' . $user . '/pic_list.txt')){
        unlink('../../private/pics/' . $user . '/pic_list.txt');
        echo "deleted list!";
    }