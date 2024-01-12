<?php
require_once ("lib.php");
if(!isset($_SESSION)) {  session_start(); }
$erreur="";
$detail="";
if (isset($_REQUEST['erreur'])) $erreur=$_REQUEST['erreur'];
if (isset($_REQUEST['detail'])) $detail=$_REQUEST['detail'];

$etat = "";
if (isset($_SESSION['etat'])) session_unset();
session_destroy();
?>
<html>
<head>
<title>La page d'erreur</title>
<body>
<BIG>
Une erreur s'est produite !<br>
<dl>
</BIG>
<?php
switch ($erreur)
{
    case "db" : 
        echo "<dt>La base de données est indisponible</dt>";
        break;
    case "user" : 
        echo "<dt>Utilisateur inconnu</dt>";
        break;
}
echo "<dd>".decode_mdp($detail)."</dd>";
?>
</dl>
<br>
Veuillez ré-essayer dans quelques minutes <br><br>
<a href='index.php'>Se connecter</a><br><br>
<br><br>
</body>
</html>