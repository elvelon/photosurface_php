<?php
   $server = "{hp181.hostpapa.com:993/imap/ssl/novalidate-cert}"; // For a IMAP connection    (PORT 143)  WORKING!
   //$ServerName = "{mail.reha-daheim.de:143/novalidate-cert}"; // For a IMAP connection    (PORT 143)  WORKING!
   $user = "oma";
   $username = $user . "@reha-daheim.de";
   $password = "1qay!QAY";

    //If no new mails --> no hassle
    if (!checkInFolder($user))
    {   
        //updatePicList($user);
        exit ("no new mail");
    }else{
        echo ("<html><body>processing mail<\br>");
    }
    
    ////Possibilty of controlling the traffic!
    if(file_exists('../../private/pics/' . $user . '/del_pic_list.txt')){
        unlink('../../private/pics/' . $user . '/del_pic_list.txt');
        if(file_exists('../../private/pics/' . $user . '/pic_list.txt')){
            unlink('../../private/pics/' . $user . '/pic_list.txt');
        }
    }
    
    $imap = imap_open($server, $username, $password) or die("imap connection error");
    $message_count = imap_num_msg($imap);
    
    echo ("message_count: " . $message_count . "<\br>");

    for ($m = 1; $m <= $message_count; ++$m){
        
        $header = imap_header($imap, $m);
        //print_r($header);

        $email[$m]['from'] = $header->from[0]->mailbox.'@'.$header->from[0]->host;
        $email[$m]['fromaddress'] = $header->from[0]->personal;
        $email[$m]['to'] = $header->to[0]->mailbox;
        $email[$m]['subject'] = $header->subject;
        $email[$m]['message_id'] = $header->message_id;
        $email[$m]['date'] = $header->date;
        $email[$m]['Unseen'] = $header->Recent;

        $from = $email[$m]['fromaddress'];
        $from_email = $email[$m]['from'];
        $to = $email[$m]['to'];
        $subject = $email[$m]['subject'];
        $unseen = $email[$m]['Unseen'];
                
        if($unseen != 'N')
        {
            continue;
        }
        
        date_default_timezone_set('Europe/Berlin');
        $date = date('Y_m_d-H_i_s');
        
        $attach = 'None';
        
        if($subject == '')
        {
            $subject = 'kein Betreff';
        }

        $structure = imap_fetchstructure($imap, $m);
        
        echo ("fetched mail and read structure<\br>");
        
        $attachments = array();
        if(isset($structure->parts) && count($structure->parts)) 
        {        
            for($i = 0; $i < count($structure->parts); $i++) 
            {

                $attachments[$i] = array(
                    'is_attachment' => false,
                    'filename' => '',
                    'name' => '',
                    'attachment' => ''
                );

                if($structure->parts[$i]->ifdparameters) {
                    foreach($structure->parts[$i]->dparameters as $object) 
                    {
                        if(strtolower($object->attribute) == 'filename') 
                        {
                            $attachments[$i]['is_attachment'] = true;
                            $attachments[$i]['filename'] = $object->value;
                        }
                    }
                }

                if($structure->parts[$i]->ifparameters) 
                {
                    foreach($structure->parts[$i]->parameters as $object) 
                    {
                        if(strtolower($object->attribute) == 'name') 
                        {
                            $attachments[$i]['is_attachment'] = true;
                            $attachments[$i]['name'] = $object->value;
                        }
                    }
                }

                if($attachments[$i]['is_attachment']) 
                {
                    echo "found attachment<\br>";
                    $fn = '/private/pics/' . $user . '/pic_' . $date . '_' .$i .'.jpg';
                    $new_i = $i;
                    while(file_exists($fn))
                    {
                        $fn = '/private/pics/' . $user . '/pic_' . $date . '_' .++$new_i .'.jpg';
                    }
                    $attach = 'yes';
                    $attachments[$i]['attachment'] = imap_fetchbody($imap, $m, $i+1);
                    if($structure->parts[$i]->encoding == 3)  // 3 = BASE64
                    {
                        $attachments[$i]['attachment'] = base64_decode($attachments[$i]['attachment']);
                        //save attachment
                        file_put_contents($fn, $attachments[$i]['attachment']);
                        //resize image
                        //smart_resize_image($file, null, 1024, 768, true);                       
                        //add filename to list.
                        $file_list = '../../private/pics/' . $user . '/pic_list.txt';
                        // Öffnet die Datei, und hänge Inhlat an
                        if(file_put_contents($file_list, $fn . "\n", FILE_APPEND) == FALSE)
                        {
                            echo "failed to write in pic_list.txt<\br>";
                        }
                    }
                    elseif($structure->parts[$i]->encoding == 4)   // 4 = QUOTED-PRINTABLE
                    {
                        $attachments[$i]['attachment'] = quoted_printable_decode($attachments[$i]['attachment']);
                    }
                }else
                {
                    $attach = 'no';
                    $fn = '-'; // no attachments
                }
            }
   
            if (strlen($subject) > 60) {
                $subject = substr($subject,0,59) ."...";
            }        
            echo "<\br>From: " . $from_email . "<\br>Subject: " . $subject . "<\br>Date: " .$date;
        }

        //imap_setflag_full($imap, $i, "\\Seen");
        //imap_mail_move($imap, $i, 'Trash');
    }
    imap_close($imap);
    //cron job: /usr/bin/php /home/rehad951/public_html/08_OmaPro/attach_extract.php

    exit("<\br>end of script<\body><\html>");

function getFileExtension($fileName){
   $parts=explode(".",$fileName);
   return $parts[count($parts)-1];
}

function checkInFolder($user){
    if ($handle = opendir('../../mail/reha-daheim.de/' . $user . '/new')) 
    {
        while (false !== ($entry = readdir($handle))) {

            if ($entry != "." && $entry != "..") {

                return true;
            }
        }
        closedir($handle);
    }
    else
    {
        echo "\nfailed to open Dir";
    }

}

function updatePicList($user){
    
    if ($handle = opendir('../../private/pics/' . $user)) {
        while (false !== ($entry = readdir($handle))) {

            if ($entry != "." && $entry != "..") {
                file_put_contents('/private/pics' . $user, $entry . "\n", FILE_APPEND);
            }
        }
        closedir($handle);
    }
    else{
        echo "\nfailed to open Dir";
    }

}

//Funktio from the internet ☺
function smart_resize_image($file,
                              $string             = null,
                              $width              = 0, 
                              $height             = 0, 
                              $proportional       = false, 
                              $output             = 'file', 
                              $delete_original    = true, 
                              $use_linux_commands = false,
                              $quality            = 100,
                              $grayscale          = false
  		 ) {
      
    if ( $height <= 0 && $width <= 0 ) return false;
    if ( $file === null && $string === null ) return false;

    # Setting defaults and meta
    $info                         = $file !== null ? getimagesize($file) : getimagesizefromstring($string);
    $image                        = '';
    $final_width                  = 0;
    $final_height                 = 0;
    list($width_old, $height_old) = $info;
	$cropHeight = $cropWidth = 0;

    # Calculating proportionality
    if ($proportional) {
      if      ($width  == 0)  $factor = $height/$height_old;
      elseif  ($height == 0)  $factor = $width/$width_old;
      else                    $factor = min( $width / $width_old, $height / $height_old );

      $final_width  = round( $width_old * $factor );
      $final_height = round( $height_old * $factor );
    }
    else {
      $final_width = ( $width <= 0 ) ? $width_old : $width;
      $final_height = ( $height <= 0 ) ? $height_old : $height;
	  $widthX = $width_old / $width;
	  $heightX = $height_old / $height;
	  
	  $x = min($widthX, $heightX);
	  $cropWidth = ($width_old - $width * $x) / 2;
	  $cropHeight = ($height_old - $height * $x) / 2;
    }

    # Loading image to memory according to type
    switch ( $info[2] ) {
      case IMAGETYPE_JPEG:  $file !== null ? $image = imagecreatefromjpeg($file) : $image = imagecreatefromstring($string);  break;
      case IMAGETYPE_GIF:   $file !== null ? $image = imagecreatefromgif($file)  : $image = imagecreatefromstring($string);  break;
      case IMAGETYPE_PNG:   $file !== null ? $image = imagecreatefrompng($file)  : $image = imagecreatefromstring($string);  break;
      default: return false;
    }
    
    # Making the image grayscale, if needed
    if ($grayscale) {
      imagefilter($image, IMG_FILTER_GRAYSCALE);
    }    
    
    # This is the resizing/resampling/transparency-preserving magic
    $image_resized = imagecreatetruecolor( $final_width, $final_height );
    if ( ($info[2] == IMAGETYPE_GIF) || ($info[2] == IMAGETYPE_PNG) ) {
      $transparency = imagecolortransparent($image);
      $palletsize = imagecolorstotal($image);

      if ($transparency >= 0 && $transparency < $palletsize) {
        $transparent_color  = imagecolorsforindex($image, $transparency);
        $transparency       = imagecolorallocate($image_resized, $transparent_color['red'], $transparent_color['green'], $transparent_color['blue']);
        imagefill($image_resized, 0, 0, $transparency);
        imagecolortransparent($image_resized, $transparency);
      }
      elseif ($info[2] == IMAGETYPE_PNG) {
        imagealphablending($image_resized, false);
        $color = imagecolorallocatealpha($image_resized, 0, 0, 0, 127);
        imagefill($image_resized, 0, 0, $color);
        imagesavealpha($image_resized, true);
      }
    }
    imagecopyresampled($image_resized, $image, 0, 0, $cropWidth, $cropHeight, $final_width, $final_height, $width_old - 2 * $cropWidth, $height_old - 2 * $cropHeight);
	
	
    # Taking care of original, if needed
    if ( $delete_original ) {
      if ( $use_linux_commands ) exec('rm '.$file);
      else @unlink($file);
    }

    # Preparing a method of providing result
    switch ( strtolower($output) ) {
      case 'browser':
        $mime = image_type_to_mime_type($info[2]);
        header("Content-type: $mime");
        $output = NULL;
      break;
      case 'file':
        $output = $file;
      break;
      case 'return':
        return $image_resized;
      break;
      default:
      break;
    }
    
    # Writing image according to type to the output destination and image quality
    switch ( $info[2] ) {
      case IMAGETYPE_GIF:   imagegif($image_resized, $output);    break;
      case IMAGETYPE_JPEG:  imagejpeg($image_resized, $output, $quality);   break;
      case IMAGETYPE_PNG:
        $quality = 9 - (int)((0.9*$quality)/10.0);
        imagepng($image_resized, $output, $quality);
        break;
      default: return false;
    }

    return true;
  }
?>
