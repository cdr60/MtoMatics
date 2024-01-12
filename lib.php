<?php

function AddHourToStringDate($sd="",$format="dd/mm/ssaa",$nbhour=0)
{
	if ($sd=="") return "";
	$tb=explode(" ",$format);
	$formatdt=(isset($tb[0])?$tb[0]:"");
	$formathr=(isset($tb[1])?$tb[1]:"");
	if (($formatdt!="ssaa-mm-dd") and ($formatdt!="dd/mm/ssaa"))
		return "Format : ".$format." incorrect";
	$tb=explode(" ",$sd);
	$sd=(isset($tb[0])?$tb[0]:"");
	$st=(isset($tb[1])?$tb[1]:"");
	if (strlen($sd)!=10)
		return "La date ".$sd." ne correspond pas au format : ".$formatdt;
	if ($formatdt=="ssaa-mm-dd")
		$tab=explode("-",$sd);
	else
		$tab=explode("/",$sd);
	if (count($tab)!=3)
		return "La date ".$sd." ne correspond pas au format : ".$formatdt;
	if ($formatdt=="ssaa-mm-dd")
	{
		$ssaa=$tab[0];
		$mm=$tab[1];
		$jj=$tab[2];
	}
	else
	{
		$ssaa=$tab[2];
		$mm=$tab[1];
		$jj=$tab[0];
	}
	if (($formathr!="hh:ii:ss") and ($formathr!="hh:ii")) return "Format : ".$format." incorrect";
	$h=0;
	$i=0;
	$s=0;
	$tab=explode(":",$st);
	$h=(isset($tab[0])?$tab[0]:$h);
	$i=(isset($tab[1])?$tab[1]:$i);
	$s=(isset($tab[2])?$tab[2]:$s);

	$d=mktime(intval($h)+$nbhour,intval($i),intval($s),$mm,$jj,$ssaa);

	if ($format=="ssaa-mm-dd ".$formathr)
		return date("Y-m-d H:i:s",$d);
	else
 		return date("d/m/Y H:i:s",$d);
}

function SqlInteger($String,$nullable=True)
{
    $String = trim($String);
	if (($String=="") and ($nullable==True)) return "null";
	if (($String=="") and ($nullable==False)) return "0";
    return $String;
}

function SqlString($String,$Upper)
{
	$String =str_replace(chr(160),chr(32),$String);
    $String = trim($String);
	if ($String=="") return "null";
    $String = strip_tags ($String);
    $String = html_entity_decode($String,ENT_QUOTES);
	$String = str_replace ("\"", "'", $String);
	$String = str_replace ("\"", "'", $String);
	$String = str_replace ("\'", "'", $String);
	$String = str_replace ("&#8216;", "'", $String);
    $String = str_replace ("&#8217;", "'", $String);
	$String = str_replace ("\\", "", $String);
    $String = str_replace ("''", "'", $String);
    $String = str_replace ("'", "''", $String);
    $String = rtrim($String);
	If ($Upper==TRUE)
		$String=strtoupper($String);
    return "'".$String."'";
}

function encode_mdp($a="")
{
   $result="";
   $i=strlen($a)-1;
   while ($i >= 0)
      {
         $n=ord(substr($a,$i,1));
         $s=strval($n);
         if ($n < 10)  
            $result.="1".$s;
         elseif ($n < 100)
            $result.="2".substr($s,1,1).substr($s,0,1);
         else
            $result.="3".substr($s,2,1).substr($s,1,1).substr($s,0,1);
		 $i-=1;
      }
   return $result;
}


function decode_mdp($a="")
{
  $i=0;
  $result='';
  While ($i<strlen($a))
     {
        $l=intval(substr($a,$i,1));
        if (($l < 1) or ($l > 3))
           return $result;
        $i++;
        if ($l == 1) 
           $s=substr($a,$i,$l);
        elseif ($l == 2) 
           $s=substr($a,$i+1,1).substr($a,$i,1);
        elseif ($l == 3) 
           $s=substr($a,$i+2,1).substr($a,$i+1,1).substr($a,$i,1);
        $n=intval($s);
        if (($n < 0) or ($n > 255)) 
           return $result;
        $result=chr($n).$result;
        $i=$i+$l;
     }
  return $result;
}

function GetVariableFrom ($from,$name,$default = "") 
{
	if (!is_array($from)) $from=array();
	elseif (!isset($from[$name])) return $default;
	else return $from[$name];
}

//Enregistrement de l'erreur dans un fichier de log
//et renvoie sur la pagae d'erreur avec message en clair (et codé html) si le mode debug est activé
function PageErreur($Erreur,$Message="",$dbmsg="")
{
	$dt=date('Y-m-d H:i:s'); 
	//Pas d'erreur si c'est c'est juste adhérent inconnu
	$dberrmsg=encode_mdp($dbmsg);
    header ("Location: erreur.php?erreur=".$Erreur."&message=".$Message."&detail=".$dberrmsg);
    exit(0);
}

class PageWeb
{
	public $Title;
	public $Head;
	public $JSFiles;
	public $CSSFiles;
	public $user_info;
	public $PageTitle;
	
	function __construct ($Titre="")
	{   
		date_default_timezone_set('UTC');
		$this->Title = $Titre;
		$this->PageTitle=$Titre;
		$this->Head="";
		$this->JSFiles=array();
		
		$this->JSFiles[]="./assets/js/jquery-1.12.4.min.js";
		$this->JSFiles[]="./assets/js/bootstrap.min.js";
		
		$this->JSFiles[]="./assets/js/chart.umd.min.js";
		
		$this->CSSFiles=array();
		$this->CSSFiles[]="./assets/accueil.css";
		$this->CSSFiles[]="./assets/weather.css";
		$this->CSSFiles[]="./assets/bootstrap.min.css";
		$this->CSSFiles[]="./assets/style.css";
		$this->CSSFiles[]="./assets/font-awesome/css/font-awesome.min.css";
		
		
		//C'est pas beau mais pas trouvé mieux, ça me soule
		//Retour paiement en ligne : perdant l'id de la session, je l'ai stocké dans le request ssid
		//Comme ça, je peux retrouver mes variables de sessions
		$sid=GetVariableFrom($_REQUEST,"ssid");
		if (($sid!="") && (!isset($_SESSION))) { session_id(strip_tags($sid)); }
	}
	function AddJs($src="")
	{
		return "<script type='text/javascript' language='JavaScript' src='".$src."?ts=".date("YmdHis")."'></script>\r\n";
	}
	
	function AddJsScript($script="")
	{
		$this->Head.="<script>".$script."</script>\r\n";
	}
	
	function AddCSS($src="")
	{
		return "<link REL='STYLESHEET' HREF='".$src."?ts=".date("YmdHis")."' TYPE='text/css'>\r\n";
	}
	function MkHead()
	{
		 foreach($this->JSFiles as $row) $this->Head.=$this->AddJs($row);
		 foreach($this->CSSFiles as $row) $this->Head.=$this->AddCSS($row);
		 $this->Head.="<meta http-equiv='X-UA-Compatible' content='IE=7'>\r\n";
		 $this->Head.="<meta http-equiv='Content-Type' content='text/html;charset=UTF-8'>\r\n";
		 $this->Head.="<meta charset='UTF-8'>\r\n";
		 $this->Head.="<meta name='viewport' content='width=device-width, initial-scale=1'>\r\n";
		 $this->Head.="<!-- Gestion aero-club C. Derenne 2007-".date("Y")." -->\r\n";
		 $this->Head.="<title>".$this->Title."</title>\r\n";
	}
	function MkBody()
	{
		return "Il faut surcharger MkBody !!";
	}
	function WritePage()
	{	 
		 $html="<!DOCTYPE HTML>\r\n";
		 $html.="<html>\r\n";
		 $html.="<head>\r\n";
		 $this->MkHead();
		 $html.=$this->Head;
		 $html.="</head>\r\n";
		 $html.="<body>\r\n";
		 $html.=$this->MkBody()."\r\n";
		 $html.="</body>\r\n";
		 $html.="</html>\r\n";
		 echo($html);
	}
	
}
?>
