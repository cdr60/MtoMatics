<?php
require_once ("lib.php");

function sql_cos($angle) 
{
    return cos($angle);
}

function sql_sin($angle) 
{
    return sin($angle);
}



class Donnees {
var $db;

function __construct()
{
	$errmsg="";
	date_default_timezone_set("Europe/Paris");
	$dbfile = "./db/mtomatics.db";
	$ok=FALSE;
	foreach(PDO::getAvailableDrivers() as $driver) 
    {
	 $ok=($ok or (strtoupper($driver)=="SQLITE"));
    }  
	if (!$ok)
	{
	   $errmsg="Extension PDO-SQLITE manquante";
	   PageErreur("db", "mtomatics",$errmsg);			   
	 }

	  
	if ($errmsg=="")
	{
		try
		{
			$this->db = new PDO("sqlite:".$dbfile);
			$this->db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
			$this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$this->db->setAttribute(PDO::ATTR_EMULATE_PREPARES , FALSE);
			$this->db->sqliteCreateFunction('cos', 'sql_cos', 1);
			$this->db->sqliteCreateFunction('sin', 'sql_sin', 1);
		}
		catch(PDOException $e) 
		{
			$errmsg=$e->getMessage();
			PageErreur("db", "mtomatics",$errmsg);
		}
	}
	if ($errmsg=="") 
	{
	   $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
	   $this->db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
	   #$this->db->setAttribute(PDO::ATTR_AUTOCOMMIT, 1);
	}
	return $errmsg;
} 

function Close()
{
	if ($this->db) 
	{
		$inttrans=$this->db->inTransaction();
		if ($inttrans==1) $this->db->commit();
		$this->db=NULL;
	}
}

function execute_query($stmt,$timestampformat="%d/%m/%Y")
{
	$err="";
	try 
	{ 
		$st = @$this->db->prepare($stmt);		
	}
	catch(PDOException $e)  
	{
		$err = 'ERREUR PDO dans ' . $e->getFile() . ' L.' . $e->getLine() . ' : ' . $e->getMessage();
	}	
	if (!$st) 
	{
		$tber=$this->db->errorInfo();
		$err=(isset($tber[2])?$tber[2]:"");
	}	
	if (($err=="") and ($st))
	{
		try 
		{	
			$st->execute();   
		}
		catch(PDOException $e)  
		{
			$err = 'ERREUR PDO dans ' . $e->getFile() . ' L.' . $e->getLine() . ' : ' . $e->getMessage(); 
		}		
	}
	
	if (($err=="") and (!$st)) $err = 'ERREUR SQL ';
	if ($err!="") 
	{
		if (strripos($err,"deadlock update conflicts with concurrent")) 
			$err="Transaction déjà lancée ! Merci de ne pas double cliquer !";
		return $err;
	}
	return $st;
}


function GetParam($idsite=1)
{
	
	$result=new stdclass();
	$result->CR=0;
	$result->MSG="";
	$result->SITE="";
	$result->LAT="";
	$result->LONT="";
	$stmt ="SELECT site, lat ,long from param ";
	$stmt.="WHERE id = ".$idsite;
	$sth = $this->execute_query($stmt,"%d/%m/%Y");
	if (is_string($sth))
	{
		$result->CR="-1";
		$result->MSG=$stmt."  ".$sth;
		return $result;
	}
	if ($row = $sth->fetchObject())
	{
		$result->SITE=$row->site;
		$result->LAT=$row->lat;
		$result->LONT=$row->long;
	}
	else
	{
		$result->CR="-2";
		$result->MSG = "Pas de données";
	}
	$sth->closeCursor(); 
	$sth=NULL;
    return $result;
}

function GetCurrentWeather($idsite=1)
{
	$ts1=date("Y-m-d H:i:s");
	$ts0=AddHourToStringDate($ts1,$format="ssaa-mm-dd hh:ii:ss",-1);
	
	$result=new stdclass();
	$result->CR=0;
	$result->MSG="";
	$result->TS="";
	$result->TEMP="";
	$result->PRECIP="";
	$result->WIN_DIR="";
	$result->WIN_SPEED="";
	$result->WIN_GUST="";
	$result->PRESSURE="";
	$result->SYMBOL_1H="";
	$result->SYMBOL_24H="";
	$result->SUNRISE="";
	$result->SUNSET="";
	$stmt ="SELECT datetime(ts,'localtime') as ts, t_2m ,precip_1h,wind_dir_10m, ";
	$stmt.="       3.6*wind_speed_10m as wind_speed_10m,";
	$stmt.="       3.6*wind_gusts_10m_1h as wind_gusts_10m_1h,";
	$stmt.="       msl_pressure,";
	$stmt.="       weather_symbol_1h,weather_symbol_24h,";
	$stmt.="       strftime('%H:%M',datetime(sunset,'localtime')) as sunset, ";
	$stmt.="       strftime('%H:%M',datetime(sunrise,'localtime')) as sunrise from mto ";
	$stmt.="WHERE idsite = ".$idsite;
	$stmt.=" AND datetime(ts,'localtime') >=".SqlString($ts0,FALSE);
	$stmt.=" AND datetime(ts,'localtime') <=".SqlString($ts1,FALSE);
	$stmt.=" LIMIT 1";

	$sth = $this->execute_query($stmt,"%d/%m/%Y");
	if (is_string($sth))
	{
		$result->CR="-1";
		$result->MSG=$stmt."  ".$sth;
		return $result;
	}

	if ($row = $sth->fetchObject())
	{
		$result->TS=$row->ts;
		$result->TEMP=$row->t_2m;
		$result->PRECIP=$row->precip_1h;
		$result->WIN_DIR=$row->wind_dir_10m;
		$result->WIN_SPEED=round($row->wind_speed_10m);
		$result->WIN_GUST=round($row->wind_gusts_10m_1h);
		$result->PRESSURE=$row->msl_pressure;
		$result->SYMBOL_1H=$row->weather_symbol_1h;
		$result->SYMBOL_24H=$row->weather_symbol_24h;
		$result->SUNRISE=$row->sunrise;
		$result->SUNSET=$row->sunset;

	}
	else
	{
		$result->CR="-2";
		$result->MSG = "Pas de données";
	}
	$sth->closeCursor(); 
	$sth=NULL;
    return $result;
}


//vent, température et pression à une date données, d'heures en heure
function GetStatWind($idsite=1,$tsutc="")
{
	if ($tsutc=="") $tsutc=date("Y-m-d 00:00:00");
	$ts0=$tsutc;
	$ts1=AddHourToStringDate($tsutc,$format="ssaa-mm-dd hh:ii:ss",24);
	
	$result=new stdclass();
	$result->CR=0;
	$result->MSG="";
	$result->DATA=array();

	$stmt ="SELECT strftime('%H',datetime(ts,'localtime')) as HR, t_2m , msl_pressure, wind_dir_10m, ";
	$stmt.="       3.6*wind_speed_10m as wind_speed_10m,";
	$stmt.="       3.6*wind_gusts_10m_1h as wind_gusts_10m_1h from mto ";
	$stmt.="WHERE idsite = ".$idsite." AND datetime(ts,'localtime') >=".SqlString($ts0,FALSE)." and datetime(ts,'localtime') <".SqlString($ts1,FALSE);
	$stmt.="ORDER BY  ts ASC";
	
	$sth = $this->execute_query($stmt,"%d/%m/%Y");
	if (is_string($sth))
	{
		$result->CR="-1";
		$result->MSG=$stmt."  ".$sth;
		return $result;
	}
	while ($row = $sth->fetchObject())
	{
		$row->FROM=floor($row->wind_dir_10m);
		$row->SPEED=floor($row->wind_speed_10m);
		$row->GUST_SPEED=floor($row->wind_gusts_10m_1h);
		$row->TEMP=floor($row->t_2m);
		$row->PRESSURE=floor($row->msl_pressure);
		$result->DATA[]=$row;
	}
	$sth->closeCursor(); 
	$sth=NULL;
    return $result;
}


function GetStatWind2($idsite=1,$anneedeb="",$anneefin="",$type="T")
{
	if ($anneedeb=="") $anneedeb=date("Y");
	if ($anneefin=="") $anneefin=date("Y");
	if (($type!="T") and ($type!="A") and ($type!="M")) $type="T";
	
	$result=new stdclass();
	$result->CR=0;
	$result->MSG="";
	$result->DATA=array();

	$stmt ="SELECT ANNEE, ";
	if ($type=="T") 
	{
		$stmt.="  case when MOIS < 4 then 1 when MOIS >=4 and MOIS < 7 then 2 when MOIS >=7 and MOIS < 10 then 3 else 4 end as PERIODE, "; 
	}
	elseif ($type=="M") $stmt.="  MOIS as PERIODE, "; 
	else $stmt.="0 as PERIODE, "; 
	$stmt.="       min(TEMP_MIN) as TEMP_MIN, max(TEMP_MAX) as TEMP_MAX, avg(TEMP_AVG) as TEMP_AVG, ";
	$stmt.="       3.6*min(SPEED_MIN) as SPEED_MIN, 3.6*MAX(SPEED_MAX) as SPEED_MAX, 3.6*AVG(SPEED_AVG) as SPEED_AVG, ";
	$stmt.="       3.6*MAX(GUST_MAX) as GUST_MAX, 3.6*AVG(GUST_AVG) as GUST_AVG, ";
	$stmt.="       AVG(PRESSURE) AS PRESSURE, SUM(PRECIP_TOTAL) AS PRECIP_TOTAL, SUM(WD1) AS WD1, SUM(WD1C) AS WD1C, SUM(WD1S) AS WD1S, SUM(WD2) AS WD2 ";
	$stmt.="       from vmtomonth ";
	$stmt.="WHERE idsite = ".$idsite;
	$stmt.=" AND ANNEE >=".SqlInteger($anneedeb,FALSE);
	$stmt.=" AND ANNEE <=".SqlInteger($anneefin,FALSE);
	$stmt.=" group by ANNEE ";
	if ($type=="M") 
		$stmt.=" , MOIS "; 
	elseif ($type=="T") 
	{
		$stmt.=" , case when MOIS < 4 then 1 when MOIS >=4 and MOIS < 7 then 2 when MOIS >=7 and MOIS < 10 then 3 else 4 end "; 
	}
	$stmt.="ORDER BY  1,2 ASC";

	$sth = $this->execute_query($stmt,"%d/%m/%Y");
	if (is_string($sth))
	{
		$result->CR="-1";
		$result->MSG=$stmt."  ".$sth;
		return $result;
	}
	while ($row = $sth->fetchObject())
	{
		$row->TEMP_MIN=round($row->TEMP_MIN);
		$row->TEMP_MAX=round($row->TEMP_MAX);
		$row->TEMP_AVG=round($row->TEMP_AVG);
		$row->SPEED_MIN=round($row->SPEED_MIN);
		$row->SPEED_MAX=round($row->SPEED_MAX);
		$row->SPEED_AVG=round($row->SPEED_AVG);
		$row->GUST_MAX=round($row->GUST_MAX);
		$row->GUST_AVG=round($row->GUST_AVG);
		$row->PRESSURE=round($row->PRESSURE);
		$row->WIND_DIR="";
		if ($row->WD2!=0)
		{
			$x=$row->WD1C/$row->WD2;
			$y=$row->WD1S/$row->WD2;
			$norme=sqrt($x*$x+$y*$y);
			$alphac=acos($x/$norme)*180.0/pi();
			$alphas=asin($y/$norme)*180.0/pi();
			if ($y<0) $row->WIND_DIR=round(360-$alphac); else $row->WIND_DIR=round($alphac);
		}
		$row->PRECIP_TOTAL=round($row->PRECIP_TOTAL);
		$result->DATA[]=$row;
	}
	$sth->closeCursor(); 
	$sth=NULL;
    return $result;
}
}
?>
