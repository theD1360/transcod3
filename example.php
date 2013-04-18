#!/usr/bin/php

<?php 

include("ffmpeg.class.php");

// Testing ffmpeg stuff
try{
    // instanciate transcoder you can specify an array with settings and the defaults if you want.
    $transcoder = new Transcoder();
    // set the -i variable for ffmpeg
    $transcoder->inputFile("test_wmv.wmv");
    // Use ffprobe to return an array of information
    $info = $transcoder->getInfo();

#    var_dump($info);

    // Use ffmpeg to transcode to a certain set of presets or feed it an array of options for custom arguments
    echo $transcoder->getFrame("./testing.jpg", 60, "160x120");
    echo $transcoder->encode('./test_flv.flv', "flv");


}catch(Exception $e){
    echo $e->getMessage();
}

?>
