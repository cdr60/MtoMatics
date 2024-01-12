<?php
require_once("./lib.php");
require_once("./db.php");



class PageMeteo extends PageWeb 
{
	public $popupmessage;
	public $Title;
	public $PageTitle;
	public $City;
	public $PressureUnit;
	public $PrecipUnit;
	public $current_weather;
	public $idsite;
	public $WinUnit;
	
    function __construct($Titre="")
    {
		parent::__construct($Titre);
		$this->idsite=GetVariableFrom($_REQUEST,"idsite","1");
		$this->popupmessage="";
		$this->Title=$Titre;
		$this->WinUnit="km/h";
		$this->PressureUnit="hPa";
		$this->PrecipUnit="mm";
		$db=new Donnees();
		$param=$db->GetParam($this->idsite);
		$this->City=$param->SITE;
		$this->PageTitle=$param->SITE;
		$this->current_weather=$db->GetCurrentWeather($this->idsite);
		$db->Close();
    } 
		
	
	function HTML()
	{
		$local_sunrise="";
		if ($this->current_weather->SUNRISE!="") $local_sunrise=$this->current_weather->SUNRISE;
		$local_sunset="";
		if ($this->current_weather->SUNSET!="") $local_sunset=$this->current_weather->SUNSET;
		$html="";
		$html.="<div >";
		$html.="<div class='card'>";    
        $html.="<table style='width:100%;'>";
		$html.="<tr class='city_date' ><td style='text-align:center; width:50%;' colspan='2'>".$this->City."</td>";
		$html.="<td style='text-align:center; width:50%;' colspan='2'>".date("d/m, H:i")."</td>";
		$html.="</tr>";
        $html.="<tr class='temp_pressure'>";
		$html.="<td style='text-align:center;' class='wi wi-thermometer'></td>";
		$html.="<td style='text-align:left;' >".$this->current_weather->TEMP."&deg; C</td>";
		$html.="<td style='text-align:center;' class='wi wi-barometer'></td>";
		$html.="<td style='text-align:left;'>".$this->current_weather->PRESSURE." ".$this->PressureUnit."</td>";
		$html.="</tr>";
        $html.="<tr class='sunrise_sunset'>";
		$html.="<td style='text-align:center;' class='wi wi-sunrise'></td>";
		$html.="<td style='text-align:left;'>".$local_sunrise."</td>";
		$html.="<td style='text-align:center;' class='wi wi-sunset'></td>";
		$html.="<td style='text-align:left;'>".$local_sunset."</td>";
		$html.="</tr>";		


        $html.="<tr>";
		$html.="<td colspan='2'>";
			$html.="<table >";
			$html.="<tr class='wind'>";
			$html.="<td style='text-align:center; padding-right:5px; padding-left:5px;' class='wi wi-forecast-io-wind'></td>";
			$html.="<td nowrap>".$this->current_weather->WIN_SPEED." >> ".$this->current_weather->WIN_GUST." ".$this->WinUnit."</td>";
			$html.="</tr>";
			$html.="<tr class='wind'>";
			$html.="<td style='text-align:center; padding-right:5px; padding-left:5px;' class='wi wi-wind towards-".$this->current_weather->WIN_DIR."-deg'></td>";
			$html.="<td >".$this->current_weather->WIN_DIR." &deg;</td>";
			$html.="</tr>";
			
			$html.="<tr class='rain'>";
			$html.="<td style='text-align:center; padding-right:5px; padding-left:5px;'class='wi wi-raindrops'></td>";
			$html.="<td >".$this->current_weather->PRECIP." ".$this->PrecipUnit."</td>";
			$html.="</tr>";
			$html.="</table>";
		$html.="</td>";
		
		$html.="<td colspan='2' style='text-align:center; overflow: visible;' >";
		$img="./mm_api_symbols/".$this->current_weather->SYMBOL_1H.".png";
		if (file_exists($img)) $html.="<img valign='middle' width='128px' src='".$img."'>"; else $html.="<img src='./mm_api_symbols/0.png'>";
		$html.="</td></tr>";
		$html.="</table>";
		$html.="</div>";
		$html.="<div ><button class='histobutton' onclick=\"window.location.href='index.php?action=statj&idsite=".$this->idsite."'\";>Historique</button></div>";
		$html.="</div>";
		return $html;
	}
	
    function MkBody()
    {
		$html=$this->HTML();
		return $html;
    } 
}

class PageStatJ extends PageWeb 
{
	public $popupmessage;
	public $Title;
	public $PageTitle;
	public $idsite;
	public $WinUnit;
	public $PressureUnit;
	public $City;
	public $datechoisie;
	public $statwindtemppress;
	
    function __construct($Titre="")
    {
		parent::__construct($Titre);
		$this->idsite=GetVariableFrom($_REQUEST,"idsite","1");
		$this->popupmessage="";
		$this->Title=$Titre;
		$this->WinUnit="km/h";
		$this->PressureUnit="hPa";
		$db=new Donnees();
		$param=$db->GetParam($this->idsite);
		$this->City=$param->SITE;
		$this->PageTitle=$param->SITE;
		$btn=GetVariableFrom($_POST,"btn","");
		$n=($btn==">"?1:0)+($btn=="<"?-1:0)+0;
		$this->datechoisie=GetVariableFrom($_POST,"datechoisie",date("d/m/Y"));
		$tb=date_parse_from_format("d/m/Y", $this->datechoisie);
		$intts=mktime(0,0,0,intval($tb["month"]),intval($tb["day"]),intval($tb["year"]))+24*60*60*$n;

		$this->datechoisie=date("d/m/Y",$intts);
		$this->statwindtemppress=$db->GetStatWind($this->idsite,date("Y-m-d 00:00:00",$intts));

		$db->Close();
		$this->MkChart4Script($this->statwindtemppress->DATA,"chartContainerWind","chartContainerTemp");
	}
	
	function MkForm()
	{
		$html="<div class='choixperiode'><form method='post' action='index.php?action=statj' >";
		$html.="<input type='hidden' name='idsite' value='".$this->idsite."'>";
		$html.="<input type='hidden' name='datechoisie' value='".$this->datechoisie."'>";
		$html.="<table style='border:none;'><tr><td style='padding-right:10px; padding-left:10px;'><input type=submit name='btn' value='<' style='width:50px;'></td>";
		$html.="<td>Direction, vitesse des rafales par heure du ".$this->datechoisie."</td>";
		$html.="<td style='padding-right:10px; padding-left:10px;'><input type=submit name='btn' value='>' style='width:50px; padding-right:10px; padding-left:10px;'></td></tr>";
		$html.="<tr><td></td>";
		$html.="<td style='text-align:center;'><br>";
		$html.="<a style='width:50px;' title='Mesures sur une journée' href='index.php?action=statj'>Journée</a>&nbsp;&nbsp;";
		$html.="<a style='width:50px;' title='Stats par mois' href='index.php?action=statm'>Mois</a>&nbsp;&nbsp;";
		$html.="<a style='width:50px;' title='Stats par trimestre' href='index.php?action=statt'>Trimestre</a>&nbsp;&nbsp;";
		$html.="<a style='width:50px;' title='Stats par année' href='index.php?action=stata'>Année</a>";
		$html.="<td></td></tr></table><br></div>";
		
		return $html;
	}
	
	function MkChart4Script($data,$windcontainer,$tempcontainer)
	{
		if (count($data)==0) return;
		$xlabels=array();
		$windspeedvalues=array();
		$gustspeedvalues=array();
		$directionvalues=array();
		$tempvalues=array();
		$pressvalues=array();
		$i=0;
		foreach($this->statwindtemppress->DATA as $row) 
		{
			$xlabels[$i]=$row->HR." h";
			$windspeedvalues[$i]=$row->SPEED;
			$gustspeedvalues[$i]=$row->GUST_SPEED;
			$directionvalues[$i]=$row->FROM;
			$tempvalues[$i]=$row->TEMP;
			$pressvalues[$i]=$row->PRESSURE;
			$i++;
		}
		
		$s="window.onload = function () {\r\n";
		
		$s.="Chart.defaults.font.size = 16;\r\n";
		$s.="const title_text_1='Vent : force et direction';\r\n";
		$s.="const xlabels = ".json_encode($xlabels).";\r\n";

		$s.="const col_1='rgb(180, 120, 192)';\r\n";
		$s.="const legend_1='Vent';\r\n";
		$s.="const data_1 = ".json_encode($windspeedvalues, JSON_NUMERIC_CHECK).";\r\n"; 
		$s.="const data_1_y_text='Km/h';\r\n"; 

		$s.="const col_2='rgb(120, 70, 192)';\r\n";
		$s.="const legend_2='Rafales';\r\n";
		$s.="const data_2 = ".json_encode($gustspeedvalues, JSON_NUMERIC_CHECK).";\r\n"; 
		$s.="const data_2_y_text='Km/h';\r\n"; 

		$s.="const col_3='rgb(75, 192, 192)';\r\n";
		$s.="const legend_3='direction';\r\n";
		$s.="const data_3 = ".json_encode($directionvalues, JSON_NUMERIC_CHECK).";\r\n"; 
		$s.="const data_3_y_text='Direction';\r\n"; 

		$s.="const datawind = {\r\n";
		$s.="  labels: xlabels,\r\n";
		$s.="  datasets: [\r\n";
		$s.="  		{  label: legend_1,  data: data_1,  fill: false,  borderColor: col_1,  tension: 0.3, yAxisID: 'speed'  },\r\n";
		$s.="  		{  label: legend_2,  data: data_2,  fill: false,  borderColor: col_2,  tension: 0.3, yAxisID: 'speed'  },\r\n";
		$s.="  		{  label: legend_3,  data: data_3,  fill: false,  borderColor: col_3,  tension: 0.3, yAxisID: 'direction', borderDash: [5, 5] },\r\n";
		$s.="  		]\r\n";
		$s.="  		};\r\n";

		$s.="const configwind = {\r\n";
		$s.="  type: 'line',\r\n";
		$s.="  data: datawind,\r\n";
		$s.="  options: \r\n";
		$s.="  {\r\n";
		$s.="    responsive: true,maintainAspectRatio: false, \r\n";
		$s.="    plugins: \r\n";
		$s.="	{\r\n";
		$s.="      legend: { position: 'bottom'  },\r\n";
		$s.="      title: {  display: true,  text: title_text_1   }\r\n";
		$s.="    },\r\n";
		$s.="    scales: \r\n";
		$s.="	{\r\n";
		$s.="      speed: \r\n";
		$s.="	  {\r\n";
		$s.="        type: 'linear', \r\n";
		$s.="        position: 'left',\r\n";
		$s.="        suggestedMin : 0,\r\n";
		$s.="        title: {  color: 'rgb(0, 0, 0)',  display: true,  text: data_1_y_text  },\r\n";
		$s.="        ticks: {  color: 'rgb(0, 0, 0)'  }\r\n";
		$s.="      },\r\n";
		$s.="      direction: \r\n";
		$s.="	  {\r\n";
		$s.="        type: 'linear',\r\n";
		$s.="        position: 'right',\r\n";
		$s.="        title: {  color: 'rgb(0, 0, 0)',  display: true,  text: data_3_y_text   },\r\n";
		$s.="        ticks: {  color: 'rgb(0, 0, 0)'   },\r\n";
		$s.="        grid: {   drawOnChartArea: false   },\r\n";
		$s.="      }\r\n";
		$s.="    }\r\n";
		$s.="  },\r\n";
		$s.="};\r\n";
		
		/*******************************************************/
		
		$s.="const title_text_temppress='Température et pression';\r\n";

		$s.="const col_temp='rgb(180, 120, 192)';\r\n";
		$s.="const legend_temp='Température';\r\n";
		$s.="const data_temp = ".json_encode($tempvalues, JSON_NUMERIC_CHECK).";\r\n"; 
		$s.="const data_temp_y_text='Température °C';\r\n"; 

		$s.="const col_press='rgb(75, 192, 192)';\r\n";
		$s.="const legend_press='Pression';\r\n";
		$s.="const data_press = ".json_encode($pressvalues, JSON_NUMERIC_CHECK).";\r\n"; 
		$s.="const data_press_y_text='Pression Hpa';\r\n"; 

		$s.="const datatemppress = {\r\n";
		$s.="  labels: xlabels,\r\n";
		$s.="  datasets: [\r\n";
		$s.="  		{  label: legend_temp,  data: data_temp,  fill: false,  borderColor: col_temp,  tension: 0.2, yAxisID: 'temp'  },\r\n";
		$s.="  		{  label: legend_press,  data: data_press,  fill: false,  borderColor: col_press,  tension: 0.2, yAxisID: 'press', borderDash: [5, 5] },\r\n";
		$s.="  		]\r\n";
		$s.="  		};\r\n";

		$s.="const configtemp = {\r\n";
		$s.="  type: 'line',\r\n";
		$s.="  data: datatemppress,\r\n";
		$s.="  options: \r\n";
		$s.="  {\r\n";
		$s.="    responsive: true,maintainAspectRatio: false, \r\n";
		$s.="    plugins: \r\n";
		$s.="	{\r\n";
		$s.="      legend: { position: 'bottom'  },\r\n";
		$s.="      title: {  display: true,  text: title_text_temppress   }\r\n";
		$s.="    },\r\n";
		$s.="    scales: \r\n";
		$s.="	{\r\n";
		$s.="      temp: \r\n";
		$s.="	  {\r\n";
		$s.="        type: 'linear', \r\n";
		$s.="        position: 'left',\r\n";
		$s.="        suggestedMin : -1,\r\n";
		$s.="        suggestedMax : 1,\r\n";
		$s.="        title: {  color: 'rgb(0, 0, 0)',  display: true,  text: data_temp_y_text  },\r\n";
		$s.="        ticks: {  color: 'rgb(0, 0, 0)'  }\r\n";
		$s.="      },\r\n";
		$s.="      press: \r\n";
		$s.="	  {\r\n";
		$s.="        type: 'linear',\r\n";
		$s.="        position: 'right',\r\n";
		$s.="        suggestedMin : 1000,\r\n";
		$s.="        suggestedMax : 1040,\r\n";
		$s.="        title: {  color: 'rgb(0, 0, 0)',  display: true,  text: data_press_y_text   },\r\n";
		$s.="        ticks: {  color: 'rgb(0, 0, 0)'   },\r\n";
		$s.="        grid: {   drawOnChartArea: false   },\r\n";
		$s.="      }\r\n";
		$s.="    }\r\n";
		$s.="  },\r\n";
		$s.="};\r\n";
		
		/*******************************************************/
		
		$s.="const ctxwind = document.getElementById('".$windcontainer."');\r\n";
		$s.="chart=new Chart(ctxwind, configwind);\r\n";
		$s.="const ctxtemp = document.getElementById('".$tempcontainer."');\r\n";
		$s.="chart=new Chart(ctxtemp, configtemp);\r\n";
		$s.="}\r\n";

		$this->AddJsScript($s);
	}
	
	function MkBody()
	{
		$html="<h1>".$this->PageTitle."</h1>";
		$html.=$this->MkForm();
		if (count($this->statwindtemppress->DATA)>0)
		{
			$html.="\r\n<div class='flex-container'>";
			$html.="\r\n<div class='flex-graph'><canvas id='chartContainerWind' ></canvas></div>";
			$html.="\r\n<div class='flex-graph'><canvas id='chartContainerTemp' ></canvas></div>";
			$html.="\r\n</div>";
		}
		else $html.="Pas de données";

		return $html;
	}
}

class PageStatTA extends PageWeb 
{
	public $popupmessage;
	public $Title;
	public $PageTitle;
	public $City;
	public $idsite;
	public $WinUnit;
	public $PressureUnit;
	public $typ;
	public $ydeb;
	public $yfin;
	public $statwind;
	
    function __construct($Titre="",$typ="T")
    {
		parent::__construct($Titre);
		$this->idsite=GetVariableFrom($_REQUEST,"idsite","1");
		$this->popupmessage="";
		$this->Title=$Titre;
		$this->WinUnit="km/h";
		$this->PressureUnit="hPa";
		$this->typ=$typ;
		$db=new Donnees();
		$param=$db->GetParam($this->idsite);
		$this->City=$param->SITE;
		$this->PageTitle=$param->SITE;
		$btn1=GetVariableFrom($_POST,"btn1","");
		$n1=($btn1==">"?1:0)+($btn1=="<"?-1:0)+0;
		$btn2=GetVariableFrom($_POST,"btn2","");
		$n2=($btn2==">"?1:0)+($btn2=="<"?-1:0)+0;
		$this->ydeb=GetVariableFrom($_REQUEST,"ydeb",date("Y"))+$n1;
		$this->yfin=GetVariableFrom($_REQUEST,"yfin",date("Y"))+$n2;
		if ($this->yfin<$this->ydeb) $this->yfin=$this->ydeb;
		$this->statwind=$db->GetStatWind2($this->idsite,$this->ydeb,$this->yfin,$this->typ);

		$db->Close();

		$this->MkChart4Script($this->statwind->DATA);
	}
	
	function MkForm()
	{
		$html="<div class='choixperiode'><form method='post' action='index.php?action=stat".strtolower($this->typ)."' >";
		$html.="<input type='hidden' name='idsite' value='".$this->idsite."'>";
		$html.="<input type='hidden' name='ydeb' value='".$this->ydeb."'>";
		$html.="<input type='hidden' name='yfin' value='".$this->yfin."'>";
		$html.="<table style='border:none;'><tr>";
		$html.="<td style='padding-right:10px; padding-left:10px;'>";
		$html.="<input type=submit name='btn1' value='<' style='width:20px;'><input type=submit name='btn1' value='>' style='width:20px;'>";
		$html.="</td>";
		$html.="<td style='text-align:center;'>Statistiques ".($this->ydeb==$this->yfin?" sur l'année ".$this->ydeb:" entre ".$this->ydeb." et ".$this->yfin)."</td>";
		$html.="<td style='padding-right:10px; padding-left:10px;'>";
		$html.="<input type=submit name='btn2' value='<' style='width:20px;'><input type=submit name='btn2' value='>' style='width:20px;'>";
		$html.="</td></tr>";
		$html.="<tr><td></td>";
		$html.="<td style='text-align:center;'><br>";
		$hrefy="&ydeb=".$this->ydeb."&yfin=".$this->yfin;
		$html.="<a style='width:50px;' title='Mesures sur une journée' href='index.php?action=statj".$hrefy."'>Journée</a>&nbsp;&nbsp;";
		$html.="<a style='width:50px;' title='Stats par mois' href='index.php?action=statm".$hrefy."'>Mois</a>&nbsp;&nbsp;";
		$html.="<a style='width:50px;' title='Stats par trimestre' href='index.php?action=statt".$hrefy."'>Trimestre</a>&nbsp;&nbsp;";
		$html.="<a style='width:50px;' title='Stats par année' href='index.php?action=stata".$hrefy."'>Année</a>";
		$html.="<td></td></tr></table><br></div>";
		
		return $html;
	}
	
	function MkChart4Script($data)
	{
		$windcontainer="windcontainer";
		$tempcontainer="tempcontainer";
		$dircontainer="dircontainer";
		$precipcontainer="precipcontainer";
		$presscontainer="presscontainer";
		
		if (count($data)==0) return;
		$tbmois=array("Jan","Fév","Mars","Avr","Mai","Juin","Juil","Août","Sep","Oct","Nov","Déc");
		$xlabels=array();
		$tempminvalues=array();
		$tempmaxvalues=array();
		$tempavgvalues=array();
		$speedminvalues=array();
		$speedmaxvalues=array();
		$speedavgvalues=array();
		$gustmaxvalues=array();
		$gustavgvalues=array();
		$dirvalues=array();
		$precipvalues=array();
		$pressurevalues=array();
		$i=0;
		foreach($data as $row) 
		{
			$tempminvalues[$i]=$row->TEMP_MIN;
			$tempmaxvalues[$i]=$row->TEMP_MAX;
			$tempavgvalues[$i]=$row->TEMP_AVG;
			$speedminvalues[$i]=$row->SPEED_MIN;
			$speedmaxvalues[$i]=$row->SPEED_MAX;
			$speedavgvalues[$i]=$row->SPEED_AVG;
			$gustmaxvalues[$i]=$row->GUST_MAX;
			$gustavgvalues[$i]=$row->GUST_AVG;
			$dirvalues[$i]=$row->WIND_DIR;
			$precipvalues[$i]=$row->PRECIP_TOTAL;
			$pressurevalues[$i]=$row->PRESSURE;
			if ($this->typ=="A") $t=$row->ANNEE;
			elseif ($this->typ=="T") $t=$row->ANNEE." ".$row->PERIODE." ".($row->PERIODE==1?"er T":"").($row->PERIODE>1?"eme T":"");
			elseif ($this->typ=="M") $t=$row->ANNEE." ".$tbmois[$row->PERIODE-1];
			$xlabels[$i]=$t;
			$i++;
		}
		$s="window.onload = function () {\r\n";
		
		$s.="Chart.defaults.font.size = 16;\r\n";
		$s.="const xlabels = ".json_encode($xlabels).";\r\n";
		$s.="const col_min_1='rgb(20, 102, 152)';\r\n";
		$s.="const col_max_1='rgb(40, 162, 172)';\r\n";
		$s.="const col_avg_1='rgb(75, 192, 192)';\r\n";
		$s.="const col_max_2='rgb(210, 60, 92)';\r\n"; 
		$s.="const col_avg_2='rgb(180, 120, 192)';\r\n"; 

		
		/****************************************************/
		$s.="const title_wind='Vitesse du vent et rafales';\r\n";
		$s.="const legend_windmin='Vent mini';\r\n";
		$s.="const data_windmin = ".json_encode($speedminvalues, JSON_NUMERIC_CHECK).";\r\n"; 
		$s.="const data_wind_y_text='Km/h';\r\n"; 

		$s.="const legend_windmax='Vent max';\r\n";
		$s.="const data_windmax = ".json_encode($speedmaxvalues, JSON_NUMERIC_CHECK).";\r\n"; 

		$s.="const legend_windavg='Vent moyen';\r\n";
		$s.="const data_windavg = ".json_encode($speedavgvalues, JSON_NUMERIC_CHECK).";\r\n"; 
		
		$s.="const legend_gustavg='Rafales moyennes';\r\n";
		$s.="const data_gustavg = ".json_encode($gustavgvalues, JSON_NUMERIC_CHECK).";\r\n"; 
		
		$s.="const legend_gustmax='Rafales max';\r\n";
		$s.="const data_gustmax = ".json_encode($gustmaxvalues, JSON_NUMERIC_CHECK).";\r\n"; 

		$s.="const datawind = {\r\n";
		$s.="  labels: xlabels,\r\n";
		$s.="  datasets: [\r\n";
		$s.="  		{  label: legend_windmin,  data: data_windmin,  fill: false,  borderColor: 'rgb(90,90,90)', backgroundColor:col_min_1 },\r\n";
		$s.="  		{  label: legend_windmax,  data: data_windmax,  fill: false,  borderColor: 'rgb(90,90,90)', backgroundColor:col_max_1 },\r\n";
		$s.="  		{  label: legend_windavg,  data: data_windavg,  fill: false,  borderColor: 'rgb(90,90,90)', backgroundColor:col_avg_1 },\r\n";
		$s.="  		{  label: legend_gustavg,  data: data_gustavg,  fill: false,  borderColor: 'rgb(90,90,90)', backgroundColor:col_avg_2 },\r\n";
		$s.="  		{  label: legend_gustmax,  data: data_gustmax,  fill: false,  borderColor: 'rgb(90,90,90)', backgroundColor:col_max_2 },\r\n";
		$s.="  		]\r\n";
		$s.="  		};\r\n";

		$s.="const configwind = {\r\n";
		$s.="type: 'bar', data: datawind,\r\n";
		$s.="options: {\r\n";
		$s.="indexAxis: 'y',\r\n";
		$s.="elements: {    bar: {    borderWidth: 2,   }    },\r\n";
		$s.="responsive: true,maintainAspectRatio: false, \r\n";
		$s.="plugins: {     legend: {   position: 'bottom',    },\r\n";
		$s.="title: {   display: true,    text: title_wind   },\r\n";
		$s.="}\r\n";
		$s.="},\r\n";
		$s.="};\r\n";

		/*******************************************************/
		
		$s.="const title_dir='Vent dominant';\r\n";
		$s.="const legend_dir='Direction';\r\n";
		$s.="const data_dir = ".json_encode($dirvalues, JSON_NUMERIC_CHECK).";\r\n"; 
		$s.="const data_dir_y_text='°';\r\n"; 

		$s.="const datadomindir = {\r\n";
		$s.="  labels: xlabels,\r\n";
		$s.="  datasets: [\r\n";
		$s.="  		{  label: legend_dir,  data: data_dir,  fill: false,  borderColor: 'rgb(90,90,90)', backgroundColor:col_avg_2 },\r\n";
		$s.="  		]\r\n";
		$s.="  		};\r\n";

		$s.="const configdomindir= {\r\n";
		$s.="type: 'bar', data: datadomindir,\r\n";
		$s.="options: {\r\n";
		$s.="indexAxis: 'y',\r\n";
		$s.="elements: {    bar: {    borderWidth: 2,   }    },\r\n";
		$s.="responsive: true,maintainAspectRatio: false, \r\n";
		$s.="plugins: {     legend: {   position: 'bottom',    },\r\n";
		$s.="title: {   display: true,    text: title_dir   },\r\n";
		$s.="}\r\n";
		$s.="},\r\n";
		$s.="};\r\n";
		/*******************************************************/

		$s.="const title_temp='Températures';\r\n";
		$s.="const legend_tempmin='Temp mini';\r\n";
		$s.="const data_tempmin = ".json_encode($tempminvalues, JSON_NUMERIC_CHECK).";\r\n"; 
		$s.="const data_temp_y_text='°C';\r\n"; 

		$s.="const legend_tempmax='Temp max';\r\n";
		$s.="const data_tempmax = ".json_encode($tempmaxvalues, JSON_NUMERIC_CHECK).";\r\n"; 

		$s.="const legend_tempavg='Temp moyenne';\r\n";
		$s.="const data_tempavg = ".json_encode($tempavgvalues, JSON_NUMERIC_CHECK).";\r\n"; 

		$s.="const datatemp = {\r\n";
		$s.="  labels: xlabels,\r\n";
		$s.="  datasets: [\r\n";
		$s.="  		{  label: legend_tempmin,  data: data_tempmin,  fill: false,  borderColor: 'rgb(90,90,90)', backgroundColor:col_min_1 },\r\n";
		$s.="  		{  label: legend_tempmax,  data: data_tempmax,  fill: false,  borderColor: 'rgb(90,90,90)', backgroundColor:col_max_2 },\r\n";
		$s.="  		{  label: legend_tempavg,  data: data_tempavg,  fill: false,  borderColor: 'rgb(90,90,90)', backgroundColor:col_avg_2 },\r\n";
		$s.="  		]\r\n";
		$s.="  		};\r\n";

		$s.="const configtemp = {\r\n";
		$s.="type: 'bar', data: datatemp,\r\n";
		$s.="options: {\r\n";
		$s.="indexAxis: 'y',\r\n";
		$s.="elements: {    bar: {    borderWidth: 2,   }    },\r\n";
		$s.="responsive: true,maintainAspectRatio: false, \r\n";
		$s.="plugins: {     legend: {   position: 'bottom',    },\r\n";
		$s.="title: {   display: true,    text: title_temp   },\r\n";
		$s.="}\r\n";
		$s.="},\r\n";
		$s.="};\r\n";

		/*******************************************************/
		
		$s.="const title_precip='Précipitations cumulées';\r\n";
		$s.="const legend_precip='Précipitations';\r\n";
		$s.="const data_precip = ".json_encode($precipvalues, JSON_NUMERIC_CHECK).";\r\n"; 
		$s.="const data_precip_y_text='mm';\r\n"; 

		$s.="const dataprecip = {\r\n";
		$s.="  labels: xlabels,\r\n";
		$s.="  datasets: [\r\n";
		$s.="  		{  label: legend_precip,  data: data_precip,  fill: false,  borderColor: 'rgb(90,90,90)', backgroundColor:col_min_1 },\r\n";
		$s.="  		]\r\n";
		$s.="  		};\r\n";

		$s.="const configprecip= {\r\n";
		$s.="type: 'bar', data: dataprecip,\r\n";
		$s.="options: {\r\n";
		$s.="indexAxis: 'y',\r\n";
		$s.="elements: {    bar: {    borderWidth: 2,   }    },\r\n";
		$s.="responsive: true,maintainAspectRatio: false, \r\n";
		$s.="plugins: {     legend: {   position: 'bottom',    },\r\n";
		$s.="title: {   display: true,    text: title_precip   },\r\n";
		$s.="}\r\n";
		$s.="},\r\n";
		$s.="};\r\n";
		
		/*******************************************************/
		
		$s.="const title_press='Pression atmostphérique';\r\n";
		$s.="const legend_press='Pression';\r\n";
		$s.="const data_press = ".json_encode($pressurevalues, JSON_NUMERIC_CHECK).";\r\n"; 
		$s.="const data_press_y_text='hPa';\r\n"; 

		$s.="const datapress = {\r\n";
		$s.="  labels: xlabels,\r\n";
		$s.="  datasets: [\r\n";
		$s.="  		{  label: legend_press,  data: data_press,  fill: false,  borderColor: 'rgb(90,90,90)', backgroundColor:col_max_1 },\r\n";
		$s.="  		]\r\n";
		$s.="  		};\r\n";

		$s.="const configpress= {\r\n";
		$s.="type: 'bar', data: datapress,\r\n";
		$s.="options: {\r\n";
		$s.="indexAxis: 'y',\r\n";
		$s.="elements: {    bar: {    borderWidth: 2,   }    },\r\n";
		$s.="responsive: true,maintainAspectRatio: false, \r\n";
		$s.="plugins: {     legend: {   position: 'bottom',    },\r\n";
		$s.="title: {   display: true,    text: title_press   },\r\n";
		$s.="}\r\n";
		$s.="},\r\n";
		$s.="};\r\n";
		/*******************************************************/
		$s.="const ctxwind = document.getElementById('".$windcontainer."');\r\n";
		$s.="chart=new Chart(ctxwind, configwind);\r\n";
		/*******************************************************/
		$s.="const ctxdir = document.getElementById('".$dircontainer."');\r\n";
		$s.="chart=new Chart(ctxdir, configdomindir);\r\n";

		/*******************************************************/
		$s.="const ctxtemp = document.getElementById('".$tempcontainer."');\r\n";
		$s.="chart=new Chart(ctxtemp, configtemp);\r\n";

		/*******************************************************/
		$s.="const ctxprecip = document.getElementById('".$precipcontainer."');\r\n";
		$s.="chart=new Chart(ctxprecip, configprecip);\r\n";

		/*******************************************************/
		$s.="const ctxpress = document.getElementById('".$presscontainer."');\r\n";
		$s.="chart=new Chart(ctxpress, configpress);\r\n";


		$s.="}\r\n";

		$this->AddJsScript($s);
	}
	

	function MkBody()
	{
		$html="<h1>".$this->PageTitle."</h1>";
		$html.=$this->MkForm();
		if (count($this->statwind->DATA)>0)
		{
		$html.="\r\n<div class='flex-container'>";
		$html.="\r\n<div class='flex-graph'><canvas id='windcontainer' ></canvas></div>";
		$html.="\r\n<div class='flex-graph'><canvas id='dircontainer' ></canvas></div>";
		$html.="\r\n<div class='flex-graph'><canvas id='tempcontainer' ></canvas></div>";
		$html.="\r\n<div class='flex-graph'><canvas id='precipcontainer' ></canvas></div>";
		$html.="\r\n<div class='flex-graph'><canvas id='presscontainer' ></canvas></div>";
		$html.="\r\n</div>";
		}

		else  $html.="Pas de données";
		return $html;
	}
}

//Fournir juste les données au format JSON //N'est pas une extension de pageweb !
class PageJSON 
{
	public $PageTitle;
	public $City;
	public $PressureUnit;
	public $PrecipUnit;
	public $current_weather;
	public $idsite;
	public $WinUnit;
	public $Title;
	
    function __construct($Title="")
    {
		$this->idsite=GetVariableFrom($_REQUEST,"idsite","1");
		$this->WinUnit="km/h";
		$this->PressureUnit="hPa";
		$this->PrecipUnit="mm";
		$db=new Donnees();
		$this->current_weather=$db->GetCurrentWeather($this->idsite);
		$param=$db->GetParam($this->idsite);
		$this->current_weather->City=$param->SITE;
		$this->current_weather->PageTitle=$param->SITE;
		$this->current_weather->Title=$Title;
		$this->current_weather->WINUNIT=$this->WinUnit;
		$this->current_weather->PRESSUREUNIT=$this->PressureUnit;
		$this->current_weather->PRECIPUNIT=$this->PrecipUnit;
		$db->Close();
		echo(json_encode($this->current_weather));
		if ($this->current_weather->CR!="0") http_response_code(500);
		else http_response_code(200);
	}
}

$action=GetVariableFrom($_REQUEST,"action","");
switch ($action)
{
  case "mto":
	default:
	$page = new PageMeteo("MTO - cd-ii.fr");
    break;
  case "statj": 
	  $page = new PageStatJ("MTO - cd-ii.fr");
      break;
  case "statm": 
	  $page = new PageStatTA("MTO - cd-ii.fr","M");
      break;
  case "statt": 
	  $page = new PageStatTA("MTO - cd-ii.fr","T");
      break;
  case "stata": 
	  $page = new PageStatTA("MTO - cd-ii.fr","A");
      break;
  case "json": 
	  $page = new PageJSON("MTO - cd-ii.fr");
      break;

}
if ($action!="json") $page->WritePage();

?>
