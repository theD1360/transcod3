transcod3
=========

PHP ffmpeg wrapper class

instanciate transcoder you can specify an array with settings and the defaults if you want.
`
    $transcoder = new Transcoder();
`

set the -i variable for ffmpeg
`
    $transcoder->inputFile("sample_iPod.m4v");
`

Use ffprobe to return an array of information

`
    $info = $transcoder->getInfo();
`

Grab a frame from the video
`
    $transcoder->getFrame("./testing.jpg", 60, "160x120");
`

Use ffmpeg to transcode to a certain set of presets or feed it an array of options for custom arguments
`
    $transcoder->encode('sample_ipod.flv', "flv");
`

alternative to using presets is passing an array of ffmpeg switches
`
    $transcoder->encode('sample_ipod.flv', array("vcodec" => 'flv',"vb" => '1500k',"acodec" => 'libmp3lame',"ab" => '128k',"ar" => '44100',"s" => '720x405'));
`
