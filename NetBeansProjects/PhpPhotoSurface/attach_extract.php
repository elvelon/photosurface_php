<?php
   $server = "{hp181.hostpapa.com:993/imap/ssl/novalidate-cert}"; // For a IMAP connection    (PORT 143)  WORKING!
   //$ServerName = "{mail.reha-daheim.de:143/novalidate-cert}"; // For a IMAP connection    (PORT 143)  WORKING!
   $user = "oma";
   $username = $user . "@reha-daheim.de";
   $password = "1qay!QAY";
   $validAttachments = 0;
   $receiverInfo = "Empty";
    
    date_default_timezone_set('Europe/Berlin');
    $date = strftime('%G_%m_%d-%H_%M_%S');
   
   $clientIP = $_SERVER['REMOTE_ADDR'];
   
  
    //If no new mails --> no hassl
    if (!checkInFolder($user))
    {   
        //updatePicList($user);
        exit ("");
    }else{
        //echo ("processing mail\n\r");
    }
    
    

    
    $imap = imap_open($server, $username, $password) or die("imap connection error");
    $message_count = imap_num_msg($imap);
    
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
              
        $attach = 'None';
        
        if($subject == '')
        {
            $subject = 'kein Betreff';
        }

        $structure = imap_fetchstructure($imap, $m);
        
        //echo ("fetched mail and read structure\n\r");
        
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
                    //echo "found attachment. \n\r";
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
                        file_put_contents("../../" . $fn, $attachments[$i]['attachment']);
                        //resize image
                        //smart_resize_image($file, null, 1024, 768, true);                       
                        //add filename to list.
                        $file_list = '../../private/pics/' . $user . '/pic_list.txt';
                        // Öffnet die Datei, und hänge Inhlat an
                        if(file_put_contents($file_list, $fn . "\n", FILE_APPEND) == FALSE)
                        {
                            //echo "failed to write in pic_list.txt\n\r";
                        }
                        $validAttachments++;
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
            $receiverInfo = "From: " . $from_email . "\nDate: " .$date . "\nNumberOfPicsSent: " . $validAttachments;
        }

        //imap_setflag_full($imap, $i, "\\Seen");
        //imap_mail_move($imap, $i, 'Trash');
    }
    imap_close($imap);
    //cron job: /usr/bin/php /home/rehad951/public_html/08_OmaPro/attach_extract.php
    sendInfoMail($receiverInfo);
    logStatistics($clientIP, $date, $from_email, $validAttachments);
    exit("");
    
    
function logStatistics($ipLog, $dateLog, $mailLog, $nrOfAttachmentsLog)
{
    $servername = "reha-daheim.de";
    $username = "rehad951";
    $password = "1qay!QAY";
    $dbname = "rehad951_photosurface";

    // Create connection
    $conn = new mysqli($servername, $username, $password, $dbname);
    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $sql = "INSERT INTO scriptPings (Client_IP, DateTimeAsString, MailFrom, NrOfPicsSent)
    VALUES ('". $ipLog ."', '".$dateLog."', '".$mailLog."', '".$nrOfAttachmentsLog."')";

    if ($conn->query($sql) === TRUE) {
        //echo "New record created successfully\n\r";
    } else {
        //echo "Error: " . $sql . "<br>" . $conn->error;
    }

    $conn->close();
}
    
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
        //echo "failed to open Dir\n\r";
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
        //echo "\nfailed to open Dir";
    }
}

function sendInfoMail($msg)
{
    $to      = 'kai.hinderer@gmail.com';
    $subject = 'New Download of Image';
    $message = $msg;
    $headers = "From: oma@reha-daheim.de";

    mail($to, $subject, $message, $headers);
}
