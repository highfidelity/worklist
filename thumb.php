<?php
require_once ("config.php");
if (!defined("ALL_ASSETS"))      define("ALL_ASSETS", "all_assets");

$thumb = new thumber();

class thumber {
     /**
      * This var holds the blob from database
      * @var string
      */
     var $content;
     /**
      * This var holds the mime type of the image
      * @var string
      */
     var $content_type;
     /**
      * This var holds the size of the image
      * @var int
      */
     var $size;
     /**
      * This var holds the image name
      * @var string
      */
     var $filename;
     /**
      * This is the date the image was created
      * @var string
      */
     var $created;
     /**
      * This is the date the image was modified
      * @var string
      */
     var $updated;
     /**
      * This is the requested width
      * @var int
      */
     var $width;
     /**
      * This is the requested height
      * @var int
      */
     var $height;
     /**
      * This is flag for zoom crop
      * @var int 1|0
      */
     var $zoom_crop;

     /**
      * This is the database object used to query the database
      * @var $db OBJECT
      */
     var $db;

     /**
      * This var holds an array of finerControl objects that represents
      * settings which we should NOT cache
      * @var array
      */
     var $blacklist;
     /**
      * This var holds an array of finerControl objects that represents
      * settings which we should cache
      * @var array
      */
     var $whitelist;

     /***
      * Constructor
      * Handles file requests, does initial checking to the server
      */
     public function __construct() {
          // make sure the system has support for what we need
          $this->initialChecks();

          $this->width = ( int ) $_REQUEST ["w"];
          $this->height = ( int ) $_REQUEST ["h"];
          $this->zoom_crop = isset($_REQUEST ["zc"]) ? 1 : 0;

          /**
          // example of files that the script will NOT cache
          $this->blacklist = array (
               // don't cache files with width = 0 and height =0
               new finerControl(0,0),
               // don't cache any files that are request from IP 10.10.10.10
               new finerControl(NULL,NULL,"10.10.10.10")
          );

          // example of files that the script will cache
          $this->whitelist = array (
               // cache files with width = 150 and height = 150 from any IP
               new finerControl(150,150),
               // cache all file requests that come from ip 10.10.10.11
               // disregarding the image dimensions
               new finerControl(NULL,NULL,"10.10.10.11")
          );
          */
          $this->db = new Database();
          $this->getImage();
     }

     /**
      * Does checks to find out of system has support for us
      * and also makes sure that the request has all the info we need
      */
     protected function initialChecks() {
          // check to see if GD function exist
          if (! function_exists('imagecreatetruecolor')) {
               $this->displayError('GD Library Error: imagecreatetruecolor does not exist - ' . 'please contact your webhost and ask them to install the GD library');
          }
          if (! isset($_REQUEST ["image"]) && ! isset($_REQUEST ["src"])) {
               $this->displayError('Image file is not set');
          }
          if (! isset($_REQUEST ["w"])) {
               $this->displayError('Width is not set');
          }
          if (! isset($_REQUEST ["h"])) {
               $this->displayError('Height is not set');
          }
          if ($_REQUEST["w"] > MAX_THUMB_SIZE || $_REQUEST["h"] > MAX_THUMB_SIZE) {
               $this->displayError('Dimensions exceed '.MAX_THUMB_SIZE.'px');
          }
     }

     /**
      * This function looks if the image exists in the database
      * with the added requirements. If it exists the image is outputed.
      * If it doesn't exist with the required dimensions but exists in the database with its
      * default size, the script creates a new image based on the passed dimensions and if
      * the image is part of the files we want to cache, a new record is inserted in the database.
      * If the original image doesn't exist an error is returned.
      * @param string $image
      * @param array $dimensions
      */
     protected function getImage() {
          $image = isset($_REQUEST["image"]) ? $_REQUEST["image"] : $_REQUEST["src"];
          if(empty($image)){
               $image = "no_picture.png";
          }
          // remove any directories that could be set from the request
          // and only get the filename in lowercase chars
          if(strrchr($image,"/")){
               $image = strtolower(substr(strrchr($image,"/"),1,strlen(strrchr($image,"/"))));
          }
          $imageName = $this->buildImageName($image);
          if ($this->imageExists($imageName)) {
               $this->outputImage();
          } else {
               // here we check to see if there is an image with that name in the db
               // if there is, we will adjust accordingly to the dimensions otherwise
               // we will return an error
	//GJ:FIX:start image transfer before testing for/save in db (writes are slow) - FIXED
               if ($this->imageExists($image)) {
                    $img = $this->resizeImage($this->content);
                    $this->outputImage($img);
                    if ($this->saveCache()) {
                         $this->createCache($img);
                    }
                    //$this->outputImage($img);
               } else {
		//GJ:New Task:provide an error image flag (file or db) [eg: ERR_HTTP_404]
                    $this->displayError('Image doesn\'t exists');
               }
          }
     }

     /**
      * Compares the image to the passed list
      * @param array $list
      * @return bool
      */
     protected function partOf($list) {
          if (empty($list)) { return false; }
          foreach ( $list as $fControl ) {
               $ipMatch = false;
               $wMatch = false;
               $hMatch = false;
		//GJ:TODO:Map to autoconfig remote address (detects proxy forwards)
               if (isset($fControl->ip) && $fControl->ip = $_SERVER ["REMOTE_ADDR"]) {
                    $ipMatch = true;
               }
               if (isset($fControl->width) && $fControl->width == $this->width) {
                    $wMatch = true;
               }
               if (isset($fControl->height) && $fControl->height == $this->height) {
                    $hMatch = true;
               }

               if ($ipMatch && $wMatch && $hMatch) {
                    return true;
               } else if ($ipMatch && $wMatch) {
                    if (! isset($fControl->height)) {
                         return true;
                    }
               } else if ($ipMatch && $hMatch) {
                    if (! isset($fControl->width)) {
                         return true;
                    }
               } else if ($hMatch && $wMatch) {
                    if (! isset($fControl->ip)) {
                         return true;
                    }
               } else if ($ipMatch) {
                    if (! isset($fControl->width) && ! isset($fControl->height)) {
                         return true;
                    }
               } else if ($wMatch) {
                    if (! isset($fControl->ip) && ! isset($fControl->height)) {
                         return true;
                    }
               } else if ($hMatch) {
                    if (! isset($fControl->width) && ! isset($fControl->ip)) {
                         return true;
                    }
               }
          }
          return false;
     }

     /**
      * Checks to see if we should cache file
      */
     protected function saveCache() {
          if ($this->partOf($this->blacklist)) {
               return false;
          } else if ($this->partOf($this->whitelist)) {
               return true;
          } else {
               return false;
          }
     }

     /**
      * Saves the cache to the database
      * @param string $image
      */
     protected function createCache($image) {
	//GJ:BUG?:WHere is this image value being untainted? should value be bound/encdoed?
          $sql = "INSERT INTO " . ALL_ASSETS . " " . "(`app`, `content_type`, `content`, `filename`, `created`,) " . "VALUES('".WORKLIST."', 'image/png','" . $image . "', '" . $this->buildImageName($this->filename) . "', NOW())";

          $this->db->query($sql);
     }

     /**
      * Builds the image name
      * @param string $im
      * @return string
      */
     protected function buildImageName($im) {
          return $im . "w:" . $this->width . "h:" . $this->height;
     }

     /**
      * Checks to see if image exists in the database
      * @param string $imageName
      * @return bool
      */
     protected function imageExists($imageName) {
          $sql = "SELECT * FROM " . ALL_ASSETS . " WHERE filename = '" . mysql_real_escape_string($imageName,$this->db->getLink()) . "'";
          $res = $this->db->query($sql);

          if (mysql_num_rows($res) > 0) {
               $this->assignImageProperties(mysql_fetch_assoc($res));
               return true;
          } else {
               return false;
          }
     }

     /**
      * Populates class data members
      * @param array $properties
      */
     protected function assignImageProperties($properties) {
          foreach ( $properties as $property => $value ) {
               if (!isset($this->$property) || is_null($this->$property)) {
                    $this->$property = $value;
               }
          }
     }

     /**
      * Outputs the image to the browser with proper header
      * @param bool|image resource $im
      */
     protected function outputImage($im = false) {
          if ($im === false) {
               $im = imagecreatefromstring($this->content);
               if ($im !== false) {
                    header('Content-Type: image/png');
                    imagepng($im);
                    imagedestroy($im);
               } else {
                    $this->displayError('An error while creating the image occurred.');
               }
          } else {
               header('Content-Type: image/png');
               imagepng($im);
               imagedestroy($im);
          }
     }

     /**
      * generic error message
      */
     protected function displayError($errorString = '') {

          header('HTTP/1.1 400 Bad Request');
          echo '<pre>' . $errorString . '</pre>';
          die();

     }

     /*
	 * The code below is based on:
 	 * TimThumb script created by Tim McDaniels and Darren Hoyt with tweaks by Ben Gillbanks
 	 **/
     protected function resizeImage($image) {
          $im = imagecreatefromstring($image);
          // Get original width and height
          $width = imagesx($im);
          $height = imagesy($im);

          // generate new w/h if not provided
          if ($this->width && ! $this->height) {

               $this->height = $height * ($this->width / $width);

          } elseif ($this->height && ! $this->width) {

               $this->width = $width * ($this->height / $height);

          } elseif (! $this->width && ! $this->height) {

               $this->width = $width;
               $this->height = $height;

          }
          // create a new true color image
          $canvas = imagecreatetruecolor($this->width,$this->height);
          imagealphablending($canvas,false);
          // Create a new transparent color for image
          $color = imagecolorallocatealpha($canvas,0,0,0,127);
          // Completely fill the background of the new image with allocated color.
          imagefill($canvas,0,0,$color);
          // Restore transparency blending
          imagesavealpha($canvas,true);

          if ($this->zoom_crop == 1) {

               $src_x = $src_y = 0;
               $src_w = $width;
               $src_h = $height;

               $cmp_x = $width / $this->width;
               $cmp_y = $height / $this->height;

               // calculate x or y coordinate and width or height of source
               if ($cmp_x > $cmp_y) {

                    $src_w = round(($width / $cmp_x * $cmp_y));
                    $src_x = round(($width - ($width / $cmp_x * $cmp_y)) / 2);

               } elseif ($cmp_y > $cmp_x) {

                    $src_h = round(($height / $cmp_y * $cmp_x));
                    $src_y = round(($height - ($height / $cmp_y * $cmp_x)) / 2);

               }

               imagecopyresampled($canvas,$im,0,0,$src_x,$src_y,$this->width,$this->height,$src_w,$src_h);

          } else {

               if ($width > $height) {
                    $factor = ( float ) $this->width / ( float ) $width;
                    $newer_height = $factor * $height;
                    $newer_width = $this->width;
               } else {
                    $factor = ( float ) $this->height / ( float ) $height;
                    $newer_width = $factor * $width;
                    $newer_height = $this->height;
               }

               $new_x = ($this->width - $newer_width) / 2;
               $new_y = ($this->height - $newer_height) / 2;

               // copy and resize part of an image with resampling
               imagecopyresampled($canvas,$im,$new_x,$new_y,0,0,$newer_width,$newer_height,$width,$height);

          }
          return $canvas;
     }
}

class finerControl {
     public $width = NULL;
     public $height = NULL;
     public $ip = NULL;
     public function __construct($w = NULL, $h = NULL, $ip = NULL) {
          if (isset($w)) {
               $this->width = $w;
          }
          if (isset($h)) {
               $this->height = $h;
          }
          if (isset($ip)) {
               $this->ip = $ip;
          }
     }
}
