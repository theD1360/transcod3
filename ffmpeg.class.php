<?php

/*
	Transcoder class
	
	Simple wrapper for ffmpeg with some presets and helper methods.
	
*/

class Transcoder{

	private $settings = array(
		"ffmpeg" => '/usr/bin/ffmpeg',
		"ffprobe" => '/usr/bin/ffprobe',
		"args" => array(
		    "i"=>"",
            "vcodec" => '',
            "vb" => '',
            "acodec" => '',
			"ab" => '',
            "ar" => '',
            "s" => '',
			"aspect" => "16:9",
			"r" => "29.97",
			"y" => ""
		)	
	),
	$presets = array(
		"flv" => array(
            "vcodec" => 'flv',
            "vb" => '1500k',
            "acodec" => 'libmp3lame',
			"ab" => '128k',
            "ar" => '44100',
            "s" => '720x405'
		),
		"1080"=> array(
            "vcodec" => 'libx264',
            "vb" => '24000k',
            "acodec" => 'libfaac',
			"ab" => '157k',
            "ar" => '48000',
            "s" => '1920x1080',
            "pix_fmt"=>"yuv420p",
            "cmp"=>"+chroma",
            "crf"=>"22"
		),
		"720"=> array(
            "vcodec" => 'libx264',
            "vb" => '22000k',
            "acodec" => 'libfaac',
			"ab" => '157k',
            "ar" => '48000',
            "s" => '1280x720',
            "pix_fmt"=>"yuv420p",
            "cmp"=>"+chroma",
            "crf"=>"22"	
		),
		"480"=> array(
            "vcodec" => 'libx264',
            "vb" => '19000k',
            "acodec" => 'libfaac',
			"ab" => '157k',
            "ar" => '48000',
            "s" => '854x480',
            "pix_fmt"=>"yuv420p",
            "cmp"=>"+chroma",
            "crf"=>"22"		
		),
		"mov"=> array(
            "vcodec" => 'libx264',
            "vb" => '16000k',
            "acodec" => 'libfaac',
			"ab" => '157k',
            "ar" => '48000',
            "s" => '720x480',
            "pix_fmt"=>"yuv420p",
            "cmp"=>"+chroma",
            "crf"=>"22"	
		),
		"stream"=> array(
            "vcodec" => 'flv',
            "vb" => '600k',
            "acodec" => 'libmp3lame',
			"ab" => '128k',
            "ar" => '44100',
            "s" => '364x200'
		)
	);

	// The construct should only make sure that the settings are set and that the ffmpeg ffmpeg exists.
	// setting file transcoding queues should be in another method.
	function __construct(){
		$opts = func_get_args();
		
		if(func_num_args()>1){
			throw new Exception("Constructor expects only one parameter of type string or array");
		}
		
		if(is_array($opts)){
			$this->settings = array_merge($this->settings, $opts);
		}else{
			throw new Exception("Constructor expects only one parameter of type string or array");
		}

		// Check to see if ffmpeg and ffprobe are installed.
		if(!self::checkBinary($this->settings['ffmpeg']))
			throw new Exception("Couldn't find ffmpeg at {$this->settings['ffmpeg']}");
			
		if(!self::checkBinary($this->settings['ffprobe']))
			throw new Exception("Couldn't find ffbrobe at {$this->settings['ffprobe']}");

		
	}

    // Set the path for ffmpeg
    public function setFFMpegPath($path){
 		if(!self::checkBinary($path))
			throw new Exception("Couldn't find ffmpeg at $path");       
        else
            $this->settings['ffmpeg'] = $path;
    }

    // Set the path for ffprobe
    public function setFFProbePath($path){
 		if(!self::checkBinary($path))
			throw new Exception("Couldn't find ffprobe at $path");       
        else
            $this->settings['ffprobe'] = $path;
    }


	// look for the ffmpeg binary and confirm that it is executable.
	public static function checkBinary($path){
            
			if(file_exists($path) && is_executable($path)) {
                return true;
            }		
			
			return false;
	}
	
	// Check the existence of the input file and verify that it has the proper permissions.
	public static function checkInputFile($file){
		if(file_exists($file) && is_readable($file))
			return true;
		
		return false;
	}
	
	// Check the output directory to ensure that it exists and has the proper permissions.
	public static function checkOutputDir($path){
		if(is_dir($path) && is_writable($path))
			return true;
		
		return false;		
	}

	// Convert an array of parameters to CLI switches for ffmpeg
	public static function arrayToSwitches($array){
		
		if(is_array($array)){
			$out = "";
			foreach($array as $switch=>$value){
				$out.=" -$switch $value"; 
			}
			return $out;
			
		}else if(is_string($array)){
			return $array;
		}
		
		return false;
	}
	
	// Set the input file parameter specifically. This can also be set in the with the settings on instantiation.
	// This is abstracted so that we can get video info with ffprobe as well as encode it using the same instantiation.
	public function inputFile($file){
		if(!self::checkInputFile($file))
			throw new Exception("The specified input file $file does not exist or is not readable.");
		
		$this->settings['args']['i'] = $file;
	}
	
	// This is method will return an array of video information.
	public function getInfo($options = array()){
	
	    $defaults = array("loglevel"=>"quiet", "show_streams"=>"");
	
	    // Always expect an array
	    if(!is_array($options))
            throw new Exception("getInfo expects options parameter to be an array");
            
        // Merge new options with the defaults.    
	    if(!empty($options))
	        $options = array_merge($defaults, $options);
	    else 
	        $options = $defaults;
	    
        // Run ffprobe with the switches 
        $res = $this->exec("ffprobe", $options, $this->settings['args']['i']);
        
        // Because the version of ffmpeg that we have doesn't have -print_format we have to parse our own
        // Eventually this function should be turned into a closure to give us flexability later.
        preg_match_all('/(?:\[STREAM\])(.*?)(?:\[\/STREAM\])/ims', $res, $matches);        
        $matches = $matches[1];
        
        $newArr = array();
        $index = 0;
        foreach($matches as $matchString){
            $temp = @explode("\n", $matchString);
            foreach($temp as $line){
                $ltemp = @explode("=", $line);
                $index = ($ltemp[0]=="index")?$ltemp[1]:$index;
                if(!empty($ltemp[0]))
                $newArr[$index][$ltemp[0]] = $ltemp[1];
            }
        }
        // return our parsed results in a similar structure as when using -print_format json        
        return array("streams"=>array_filter($newArr));
        
	}

    // Method to get a frame at a specified dimension or the size of the video if no size specified.
    public function getFrame($output, $time=1, $size=""){
        $options = array(
            "i"=>$this->settings['args']['i'],
            "deinterlace"=>"",
            "an"=>"",
            "ss"=>"1",
            "t"=> "00:00:01",
            "r"=>"1",
            "y"=>"",
            "s"=>"320x240",
            "vcodec"=>"mjpeg",
            "f"=>"mjpeg"           
        );
        
        if(empty($output))
            throw new Exception("output filename cannot be left blank.");
        
        // Get info for this video
        $info = $this->getInfo();
        
        // make sure we have some stream information
        if(empty($info))
            throw new Exception("Could not fetch stream information for input file. Are you sure this filetype is supported?");
        // make sure we have an actual time to fetch a frame from
        if(empty($time) || !is_numeric("$time"))
            throw new Exception("Fet frame expects time parameter to be an number");

        // If we are getting a frame out of scope just set grab the last frame        
        $time = ($time>$info['streams'][1]['duration'])?$info['streams'][1]['duration']-1:$time;
       
        $options['ss'] = $time;
        $options['s'] = (!empty($size))?$size:"{$info['streams'][1]['width']}x{$info['streams'][1]['height']}";
        
        return $this->exec("ffmpeg", $options, $output);

    }

    
    // Abstracting the way we run CLI arguments with this function, NOTE: output is just a string area at the end of the command use output for ffprobe input
    private function exec($command = "ffmpeg", $parameters="", $fileIO=""){

  	    if(!empty($parameters)){

			// Our parameters is a string
			if(is_string($parameters)){

				// Check if were looking for a preset
				if(!empty($this->presets[$parameters])){

					$parameters = array_merge($this->settings['args'], $this->presets[$parameters]);

                   				
				}
				
			}

		}

		$switches = self::arrayToSwitches($parameters);			
		
		$command = $this->settings[$command]." $switches $fileIO 2>&1";
		return `$command`;      
    }


	// Encode the input file using presets or with specific parameters. Be careful not to override the input file as this will bypass checks
	public function encode($output = "", $parameters=""){

		// Check that the output directory exists or else throw exception
		if(!self::checkOutputDir(dirname($output)))
			throw new Exception("The specified output path directory".dirname($output)." does not exist or is not writable.");
		
		// run ffmpeg with the given parameters.
	    return $this->exec("ffmpeg", $parameters, $output);
		
	}


}

?>
