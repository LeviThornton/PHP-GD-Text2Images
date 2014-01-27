<?php
/** 
 * @author Levi Thornton 11/16/2012
 * 
 * Class library to create text images on the fly
 * with PHP's GD image service.
 * 
 */

// Remove this line after development is complete
error_reporting(E_ALL); ini_set('display_errors', '1');

class FitTextToImage {

	protected $_im;
	protected $_image_width;
	protected $_image_height;
	protected $_background_image;
	protected $_font;
	protected $_font_size;
	protected $_x = 0;
	protected $_y = 0;
	protected $_color;
	protected $_margins = array(0,0,0,0);
	
	protected $_author;
	
	/**
	 * Method to create an image with background 
	 * color or background image resized and text
	 * @param Numeric $width
	 * @param Numeric $height
	 */
	public function createImage($width='450',$height='450') {
		$this->_image_width = $width;
		$this->_image_height = $height;
		
		$this->_im = imagecreatetruecolor($this->_image_width, $this->_image_height);
	}
	
	/**
	 * Setup the background design with a solid color
	 * or image source.
	 * 
	 * @example ->setColor(FFFFFF); ->setBackground();
	 * @param String $image_src
	 */
	public function setBackground($image_src=false) {
		
		// create a colored canvas
		$color = imagecolorallocate($this->_im, $this->_color[0],$this->_color[1],$this->_color[2]);
		imagefilledrectangle($this->_im, 0, 0, $this->_image_width, $this->_image_height, $color);
		
		// IF image source was provided...
		if($image_src) {
			// get the image
			$tmp_img = @imagecreatefromjpeg($image_src);
			list($width_orig, $height_orig, $image_type) = getimagesize($image_src);

			// copy and resize image into the canvas
			imagecopyresized($this->_im, $tmp_img, 0, 0, 0, 0, $this->_image_width, $this->_image_height, $width_orig, $height_orig);
		}
	}
	/**
	 * Deprecated, use placeText()
	 * 
	 * Set the author value
	 * @param string $string
	 */
	// public function setAuthor($string) {
	//	$this->_author = $string;
	// }
	/**
	 * Set the color for
	 * Text, Background, etc.
	 * 
	 * See http://html-color-codes.com/
	 * 
	 * @param hex $hex
	 */
	public function setColor($hex) {
		$hex = str_replace("#", "", $hex);
		
		if(strlen($hex) == 3) {
			$r = hexdec(substr($hex,0,1).substr($hex,0,1));
			$g = hexdec(substr($hex,1,1).substr($hex,1,1));
			$b = hexdec(substr($hex,2,1).substr($hex,2,1));
		} else {
			$r = hexdec(substr($hex,0,2));
			$g = hexdec(substr($hex,2,2));
			$b = hexdec(substr($hex,4,2));
		}
		$this->_color = array($r, $g, $b);
	} 

	/**
	 * Set the font, include file path
	 * @param String $font
	 */
	public function setFont($font) {
		$this->_font = $font;
	}
	
	/**
	 * Set the font size
	 * @param unknown_type $size
	 */
	public function setFontSize($size) {
		$this->_font_size = $size;
	}
	/**
	 * Set a position
	 * @param int $x
	 * @param int $y
	 */
	public function setPosition($x,$y) {
		$this->_x = $x;
		$this->_y = $y;
	}
	/**
	 * Set margins
	 * @param Num (left) $l
	 * @param Num (top) $t
	 * @param Num (bottom) $b
	 * @param Num (right) $r
	 */
	public function setMargin($l,$t,$b,$r) {
		$this->_margins = array($l,$t,$b,$r);
	}
	/**
	 * Wrap some text
	 * 
	 * @abstract this is a supporting method
	 * 
	 * @param unknown_type $fontSize
	 * @param unknown_type $angle
	 * @param unknown_type $fontFace
	 * @param unknown_type $string
	 * @param unknown_type $width
	 * @return string
	 */
	private function wrap($fontSize, $angle, $fontFace, $string, $width) {
	
		$ret = "";
	
		$arr = explode(' ', $string);
	
		foreach ( $arr as $word ){
	
			$teststring = $ret.' '.($word);
			$testbox = imagettfbbox($fontSize, $angle, $fontFace, $teststring);
			if ( $testbox[2] > ($width-($this->_margins[0]+$this->_margins[0])) ){
				$ret.=($ret==""?"":"\n").$word;
			} else {
				$ret.=($ret==""?"":' ').$word;
			}
		}
	
		return $ret;
	}
	
	/**
	 * Place some text on the image
	 * 
	 * @param String $text
	 */
	public function placeText($layout,$text) {
		// get the color
		$color = imagecolorallocate($this->_im, $this->_color[0],$this->_color[1],$this->_color[2]);
		
		switch($layout) {
			case 'text-filled';
				$text = $this->wrap($this->_font_size, 0, $this->_font, $text, $this->_image_width);
				$text_lines = explode("\n",$text);
				$text_area_width = ($this->_image_width)-($this->_margins[0]+$this->_margins[3]);

				$x = abs($this->_x + $this->_margins[0]);

				$y = abs($this->_y + $this->_margins[1]);

				$new_y = $y;
								
				// loop the lines and adjust as needed
				foreach($text_lines as $line) {
					$dimensions = imagettfbbox($this->_font_size, 0, $this->_font, $line);
					
					$textWidth = abs($dimensions[4] - $dimensions[0]);
					$textHeight = abs($dimensions[1] - $dimensions[7]);
					$font_size = $this->_font_size;
					
					while($textWidth<($text_area_width-1)) {
						$font_size = ($font_size+0.01);
						$new_line = imagettfbbox($font_size, 0, $this->_font, $line);
						$textWidth = abs($new_line[4] - $new_line[0]);
						$textHeight = abs($new_line[1] - $new_line[7]);
					}

					$new_y = ($new_y + $textHeight);

										
					// Write the text

					imagettftext($this->_im, $font_size, 0, $x, $new_y, $color, $this->_font, $line);
				}

			break;
			/*

			 * like the other right aligned styles

			* we wrap the text, split, then calculate

			* the center point for each line and place it

			* as needed.

			*/

			case "centered":

				$text = $this->wrap($this->_font_size, 0, $this->_font, $text, $this->_image_width);
				
				$dimensions = imagettfbbox($this->_font_size, 0, $this->_font, $text);

				$textWidth = abs($dimensions[4] - $dimensions[0]);

				$textHeight = abs($dimensions[1] - $dimensions[7]);

				$document_center_x = $this->_image_width/2;
				$document_center_y = $this->_image_height/2;
				
				$x = abs($document_center_y - ($textWidth/2) );
				$y = abs($document_center_x - ($textHeight/2) );
				
				
				imagettftext($this->_im, $this->_font_size, 0, $x, $y, $color, $this->_font, $text);

				

			break;
			/*

			 * center text with quotes added
			 * and author name shown.

			*/

			case 'quote-centered-author';

				// Why not use ceneted and add quotes to the conent in the place of duplicating the logic?

			break;
			/*

			 * GD line break defaulteds

			 * to left aling when a new line char \n

			 * is supplied. Marings and all are included

			 * with the calculation

			 */

			case "left-aligned":

				$text = $this->wrap($this->_font_size, 0, $this->_font, $text, $this->_image_width);
				
				$x = abs($this->_x+$this->_margins[0]);
				$y = abs($this->_y+$this->_margins[1]);
				

				// Write the text

				imagettftext($this->_im, $this->_font_size, 0, $x, $y, $color, $this->_font, $text);

			break;

			/*

			* First wrap the text to fit

			* the image with margins, then its splits

			* the line on the new line char \n and

			* positions the line of text on the right

			* side of the image based on the width

			* of the line.

			*/

			case "right-aligned":

				$text = $this->wrap($this->_font_size, 0, $this->_font, $text, $this->_image_width);

				$right_aln_list = explode("\n",$text);

				$y_position = 0;

				
				foreach($right_aln_list as $line) {

					$dimensions = imagettfbbox($this->_font_size, 0, $this->_font, $line);

					$textWidth = abs($dimensions[4] - $dimensions[0]);

					

					$this_y_position = abs( ($this->_y+$this->_margins[1]) + ($dimensions[1]+$dimensions[7]) ) + ($y_position);

					$this_x_position = abs( ($this->_image_width-$this->_margins[3]) - ($textWidth) );
					
					imagettftext($this->_im, $this->_font_size, 0, $this_x_position, $this_y_position, $color, $this->_font, $line);

					$y_position = $this_y_position;

				}

			break;
			/*
			 * 
			 */
			case 'left-text-filled':
				// This would produce the same results at text-filled IMHO.
			break;
			/*

			 * Depending on how margins are setup compared to the

			* text some lines will look centered or to complete the

			* entire page width of image margins. It is alternating

			* its, the pargaraph is just fitting the entire width at
			* at line.

			*/

			case "alternate-aligned":

				$text = $this->wrap($this->_font_size, 0, $this->_font, $text, $this->_image_width);

				$right_aln_list = explode("\n",$text);

				$y_position = 0;

				foreach($right_aln_list as $line) {

					$dimensions = imagettfbbox($this->_font_size, 0, $this->_font, $line);

					$textWidth = abs($dimensions[4] - $dimensions[0]);

					$this_y_position = abs($this->_y+$this->_margins[1]+($dimensions[1]+$dimensions[7]))+$y_position;

						

					if($y_position = $this_y_position%2 ) {

						imagettftext($this->_im, $this->_font_size, 0, (($this->_image_width-$this->_margins[3])-($textWidth)), $this_y_position, $color, $this->_font, $line);

					} else {

						imagettftext($this->_im, $this->_font_size, 0, ($this->_x+$this->_margins[0]), $this_y_position, $color, $this->_font, $line);

					}

					$y_position = $this_y_position;

				}

			break;
			/*
			 * alternate the text size and calculate the size
			 * so the next line fits in without overlap.
			 */
			case "alternate-size-centered":
				$text = $this->wrap($this->_font_size, 0, $this->_font, $text, $this->_image_width);

				$text_lines = explode("\n",$text);

				

				$x = abs($this->_x + $this->_margins[0]);

				$y = abs($this->_y + $this->_margins[1]);

				$new_y = $y;
				
				$document_center_x = $this->_image_width/2;

				$document_center_y = $this->_image_height/2;

				
				$this_font_size = $this->_font_size;
				

				// loop the lines and adjust as needed

				foreach($text_lines as $line) {

					$max_font_size = $this->_font_size;

					$small_font_size = ($max_font_size*0.90);				
					if($this_font_size==$this->_font_size) {
						$this_font_size = $small_font_size;
					} else {
						$this_font_size = $max_font_size;	
					}
					$last_font_size = $this_font_size;
					
					$new_line = imagettfbbox($this_font_size, 0, $this->_font, $line);

					$textHeight = abs($new_line[1] - $new_line[7]);
					$textWidth = abs($new_line[4] - $new_line[0]);

						

					$new_y = ($new_y + $textHeight);

					$x = abs($document_center_y - ($textWidth/2) );				
					

					// Write the text

					imagettftext($this->_im, $this_font_size, 0, $x, $new_y, $color, $this->_font, $line);

				}
			break;
			/*
			 * In the event no matching style case
			 * default to left aligned.
			 */
			default: // default to left-aligned
				$text = $this->wrap($this->_font_size, 0, $this->_font, $text, $this->_image_width);
				$x = abs($this->_x + $this->_margins[0]);
				$y = abs($this->_y + $this->_margins[1]);
				
				// Write the text
				imagettftext($this->_im, $this->_font_size, 0, $x, $y, $color, $this->_font, $text);
		}
	}
	
	/**
	 * Display the image as png.
	 * Using imagepng() results in 
	 * clearer text compared 
	 * with imagejpeg().
	 */
	public function displayImage() {
		header('Content-Type: image/png');
		imagepng($this->_im);
		imagedestroy($this->_im);
	} 
	
}

#######################################
### EXAMPLES
#######################################

$gd1 = new FitTextToImage();

	$gd1->createImage(450,450);
	
	$gd1->setColor('FF6600');
	// $gd1->setBackground(); // Create an image without a background image
	$gd1->setBackground("./Another_by_mullybinks_things.jpg"); // Create an image with a background image
	
	// Set font file, size, postion (xy), color, and text
	$gd1->setFont('./ArialBLK.ttf');
	$gd1->setFontSize(14);
	$gd1->setMargin(10,30,10,10);
	$gd1->setPosition(0,0); // set position beyound margin offset.
	$gd1->setColor('300000'); // color of text, color of anything that follows this method call.
	//$gd1->placeText("alternate-size-centered","A test string to test things that are testing...");
	$gd1->placeText("centered","\"The retailer offers some of the best prices in the retail industry. Their products are made affordably by bargaining with manufactures to bring you the lowest price possible. All stores offer a 30 day money-back offer on all products sold in the store. I highly recommend shopping there.\"");

	
## NOW SEND THE IMAGE TO SCREEN	
	$gd1->displayImage();
?>
