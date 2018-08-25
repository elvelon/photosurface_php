<?php
$reportingLevel = -1; //0 für alle PHP Fehler und Warungen ausblenden, -1 für alle anzeigen
error_reporting($reportingLevel); 

//Sicherheitsabfrage ob der Authentifizierungscode mit übergeben wurde.
//Wenn der Code nicht übergeben wurde wird die gesamte Prozedure abgebrochen.
checkAuthCode();

//Datenbankverbindung aufbauen
$connection = getDBConnection();

//$method =  $_POST['method'];
//if ($method == 'allEntrys'){
//   getAllEntrys($connection);
//}
getAllEntrys($connection);


function getDBConnection(){
  //Einstellungen der Datenbank
  $dbusername = 'rehad951'; //Benutzername
  $dbpassword = '1qay!QAY'; //Passwort
  $dburl='reha-daheim.de'; //URL
  $dbname='rehad951_photosurface'; //Datenbankname

  $fehler1 = "Fehler 1: Fehler beim aufbauen der Datenbankverbindung!";
	$link = mysqli_connect($dburl, $dbusername, $dbpassword,$dbname);
	if (!$link) {
		die('Verbindung schlug fehl: ' . mysqli_error());
	}
  
  /* check connection */
  if (mysqli_connect_errno()) {
       die($fehler1);
  }
  return $link;
}

function getAllEntrys($connection){
  $sqlStmt = 'SELECT * FROM scriptPings ORDER BY count DESC;';
  $result =  mysqli_query($connection,$sqlStmt);
  $data = array();
  if ($result = $connection->query($sqlStmt)) {
      while ($row = $result->fetch_assoc()) {
        $count = $row["count"];
        $ip = $row["Client_IP"];
        $date = $row["DateTimeAsString"]; 
        $from = $row["MailFrom"];
        $nr = $row["NrOfPicsSent"];        
        array_push($data,array("count"=> $count,"ClientIP"=>$ip,"date"=>$date,"mailFrom"=>$from,"nr"=>$nr));  
      }
  $result->free();
}
  closeConnection($connection);
  
  foreach ($data as $d){
//    echo "Index: " . $d["count"];
//    echo " | ";
//    echo " ClientIP: " . $d["ClientIP"];
//    echo " | ";
    echo "Date: " . $d["date"];
    echo " | ";
    echo " From: " . $d["mailFrom"];
    echo " | ";
    echo " NrOfPics: " . $d["nr"];
    echo " | \n";
  }  
}

function checkAuthCode(){
$fehler0 = "Fehler 0: Keine erfolgreiche Authentifizierung!";
if (isset($_POST['authkey'])){
  $authkey = $_POST['authkey'];
  if ($authkey != 'OmasKino'){
    echo "Wrong authentification";
    die($authkey);
  }
} else {
  die(var_dump($_POST));
}
}

function closeConnection($connection){
  mysqli_close($connection);
}