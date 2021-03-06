<?php
//==========================================================================
//
//Université de Strasbourg - Direction Informatique
//Auteur : Guilhem BORGHESI
//Création : Février 2008
//
//borghesi@unistra.fr
//
//Ce logiciel est régi par la licence CeCILL-B soumise au droit français et
//respectant les principes de diffusion des logiciels libres. Vous pouvez
//utiliser, modifier et/ou redistribuer ce programme sous les conditions
//de la licence CeCILL-B telle que diffusée par le CEA, le CNRS et l'INRIA 
//sur le site "http://www.cecill.info".
//
//Le fait que vous puissiez accéder à cet en-tête signifie que vous avez 
//pris connaissance de la licence CeCILL-B, et que vous en avez accepté les
//termes. Vous pouvez trouver une copie de la licence dans le fichier LICENCE.
//
//==========================================================================
//
//Université de Strasbourg - Direction Informatique
//Author : Guilhem BORGHESI
//Creation : Feb 2008
//
//borghesi@unistra.fr
//
//This software is governed by the CeCILL-B license under French law and
//abiding by the rules of distribution of free software. You can  use, 
//modify and/ or redistribute the software under the terms of the CeCILL-B
//license as circulated by CEA, CNRS and INRIA at the following URL
//"http://www.cecill.info". 
//
//The fact that you are presently reading this means that you have had
//knowledge of the CeCILL-B license and that you accept its terms. You can
//find a copy of this license in the file LICENSE.
//
//==========================================================================

session_start();

//setlocale(LC_TIME, "fr_FR");
include_once('fonctions.php');
if (file_exists('bandeaux_local.php')) {
  include_once('bandeaux_local.php');
} else {
  include_once('bandeaux.php');
}

// Initialisation des variables
$numsondageadmin = false;
$sondage = false;

// recuperation du numero de sondage admin (24 car.) dans l'URL
if (issetAndNoEmpty('sondage', $_GET) && is_string($_GET['sondage']) && strlen($_GET['sondage']) === 24) {
  $numsondageadmin=$_GET["sondage"];
  //on découpe le résultat pour avoir le numéro de sondage (16 car.)
  $numsondage=substr($numsondageadmin, 0, 16);
}

if (preg_match(";[\w\d]{24};i", $numsondageadmin)) {
  $sql = 'SELECT * FROM sondage WHERE id_sondage_admin = '.$connect->Param('numsondageadmin');
  $sql = $connect->Prepare($sql);
  $sondage = $connect->Execute($sql, array($numsondageadmin));
  
  if ($sondage !== false) {
    $sql = 'SELECT * FROM sujet_studs WHERE id_sondage = '.$connect->Param('numsondage');
    $sql = $connect->Prepare($sql);
    $sujets = $connect->Execute($sql, array($numsondage));
    
    $sql = 'SELECT * FROM user_studs WHERE id_sondage = '.$connect->Param('numsondage').' order by id_users';
    $sql = $connect->Prepare($sql);
    $user_studs = $connect->Execute($sql, array($numsondage));
  }
}

//verification de l'existence du sondage, s'il n'existe pas on met une page d'erreur
if (!$sondage || $sondage->RecordCount() != 1){
  print_header(false);
  echo '<body>'."\n";

  logo();
  bandeau_tete();
  bandeau_titre(_("Error!"));
  echo '<div class=corpscentre>'."\n";
  print "<H2>" . _("This poll doesn't exist !") . "</H2><br><br>"."\n";
  print "" . _("Back to the homepage of ") . " <a href=\"index.php\"> ".NOMAPPLICATION."</A>. "."\n";
  echo '<br><br><br><br>'."\n";
  echo '</div>'."\n";
# sur_bandeau_pied();
  bandeau_pied();
  
  echo'</body>'."\n";
  echo '</html>'."\n";
  die();
}

$dsujet=$sujets->FetchObject(false);
$dsondage=$sondage->FetchObject(false);


//si la valeur du nouveau titre est valide et que le bouton est activé
$adresseadmin = $dsondage->mail_admin;
$headers_str = <<<EOF
From: %s <%s>
Content-Type: text/plain; charset=UTF-8
Content-Transfer-Encoding: 8bit
EOF;
$headers = sprintf($headers_str, NOMAPPLICATION, ADRESSEMAILADMIN);


if (isset($_POST["boutonnouveautitre"]) || isset($_POST["boutonnouveautitre_x"])) {
  if(issetAndNoEmpty('nouveautitre') === false) {
    $err |= TITLE_EMPTY;
  } else {
    //modification de la base SQL avec le nouveau titre
    $nouveautitre = htmlentities(html_entity_decode($_POST['nouveautitre'], ENT_QUOTES, 'UTF-8'), ENT_QUOTES, 'UTF-8');
    $sql = 'UPDATE sondage SET titre = '.$connect->Param('nouveautitre').' WHERE id_sondage = '.$connect->Param('numsondage');
    $sql = $connect->Prepare($sql);
    
    //envoi du mail pour prevenir l'admin de sondage
    if ($connect->Execute($sql, array($nouveautitre, $numsondage))) {
      mail ($adresseadmin,
            _("[ADMINISTRATOR] New title for your poll") . ' ' . NOMAPPLICATION,
            _("You have changed the title of your poll. \nYou can modify this poll with this link") .
            " :\n\n".getUrlSondage($numsondageadmin, true)."\n\n" .
            _("Thanks for your confidence.") . "\n" . NOMAPPLICATION,
            $headers);
    }
  }
}
  
// si le bouton est activé, quelque soit la valeur du champ textarea
if (isset($_POST["boutonnouveauxcommentaires"]) || isset($_POST["boutonnouveauxcommentaires_x"])) {
  if(issetAndNoEmpty('nouveautitre') === false) {
    $err |= COMMENT_EMPTY;
  } else {
    $commentaires = htmlentities(html_entity_decode($_POST['nouveauxcommentaires'], ENT_QUOTES, 'UTF-8'), ENT_QUOTES, 'UTF-8');
    
    //modification de la base SQL avec les nouveaux commentaires
    $sql = 'UPDATE sondage SET commentaires = '.$connect->Param('commentaires').' WHERE id_sondage = '.$connect->Param('numsondage');
    $sql = $connect->Prepare($sql);
    
    if ($connect->Execute($sql, array($commentaires, $numsondage))) {
      //envoi du mail pour prevenir l'admin de sondage
      mail ($adresseadmin,
            _("[ADMINISTRATOR] New comments for your poll") . ' ' . NOMAPPLICATION,
            _("You have changed the comments of your poll. \nYou can modify this poll with this link") .
            " :\n\n".getUrlSondage($numsondageadmin, true)." \n\n" .
            _("Thanks for your confidence.") . "\n" . NOMAPPLICATION,
            $headers);
    }
  }
}

//si la valeur de la nouvelle adresse est valide et que le bouton est activé
if (isset($_POST["boutonnouvelleadresse"]) || isset($_POST["boutonnouvelleadresse_x"])) {
  if(issetAndNoEmpty('nouvelleadresse') === false || validateEmail($_POST["nouvelleadresse"]) === false) {
    $err |= INVALID_EMAIL;
  } else {
    $nouvelleadresse = htmlentities(html_entity_decode($_POST['nouvelleadresse'], ENT_QUOTES, 'UTF-8'), ENT_QUOTES, 'UTF-8');
    
    //modification de la base SQL avec la nouvelle adresse
    $sql = 'UPDATE sondage SET mail_admin = '.$connect->Param('nouvelleadresse').' WHERE id_sondage = '.$connect->Param('numsondage');
    $sql = $connect->Prepare($sql);
    
    if ($connect->Execute($sql, array($nouvelleadresse, $numsondage))) {
      //envoi du mail pour prevenir l'admin de sondage
      mail ($_POST['nouvelleadresse'],
            _("[ADMINISTRATOR] New email address for your poll") . ' ' . NOMAPPLICATION,
            _("You have changed your email address in your poll. \nYou can modify this poll with this link") .
            " :\n\n".getUrlSondage($numsondageadmin, true)."\n\n" .
            _("Thanks for your confidence.") . "\n" . NOMAPPLICATION,
            $headers);
    }
  }
}

// reload
$dsujet=$sujets->FetchObject(false);
$dsondage=$sondage->FetchObject(false);

if (isset($_POST["ajoutsujet"]) || isset($_POST["ajoutsujet_x"])) {
  print_header(true);
  echo '<body>'."\n";
  logo();
  bandeau_tete();
  bandeau_titre(_("Make your polls"));
  sous_bandeau();
  
  //on recupere les données et les sujets du sondage
  echo '<form name="formulaire" action="'.getUrlSondage($numsondageadmin, true).'" method="POST" onkeypress="javascript:process_keypress(event)">'."\n";
    
  echo '<div class="corpscentre">'."\n";
  echo "<H2>" . _("Column's adding") . "</H2><br><br>"."\n";
  
  if ($dsondage->format=="A"||$dsondage->format=="A+"){
    echo _("Add a new column") .' :<br> <input type="text" name="nouvellecolonne" size="40"> <input type="image" name="ajoutercolonne" value="Ajouter une colonne" src="images/accept.png" alt="Valider"><br><br>'."\n";
  } else {
    //ajout d'une date avec creneau horaire 
    echo _("You can add a new scheduling date to your poll.<br> If you just want to add a new hour to an existant date, put the same date and choose a new hour.") .'<br><br> '."\n";
    echo _("Add a date") .' :<br><br>'."\n";
    echo '<select name="nouveaujour"> '."\n";
    echo '<OPTION VALUE="vide"></OPTION>'."\n";
    for ($i=1;$i<32;$i++){
      echo '<OPTION VALUE="'.$i.'">'.$i.'</OPTION>'."\n";
    }
    echo '</SELECT>'."\n";

    echo '<select name="nouveaumois"> '."\n";
    echo '<OPTION VALUE="vide"></OPTION>'."\n";
    for($i = 1; $i < 13; $i++) {
      echo '<OPTION VALUE="'.$i.'">'.strftime('%B', mktime(0, 0, 0, $i, 1)).'</OPTION>'."\n";
    }
    echo '</SELECT>'."\n";

    
    echo '<select name="nouvelleannee"> '."\n";
    echo '<OPTION VALUE="vide"></OPTION>'."\n";
    for ($i = date("Y"); $i < (date("Y") + 5); $i++) {
      echo '<OPTION VALUE="'.$i.'">'.$i.'</OPTION>'."\n";
    }
    echo '</SELECT>'."\n";
    echo '<br><br>'. _("Add a start hour (optional)") .' : <br><br>'."\n";
    echo '<select name="nouvelleheuredebut"> '."\n";
    echo '<OPTION VALUE="vide"></OPTION>'."\n";
    for ($i = 0; $i < 24; $i++) {
      echo '<OPTION VALUE="'.$i.'">'.$i.' H</OPTION>'."\n";
    }
    echo '</SELECT>'."\n";
    echo '<select name="nouvelleminutedebut"> '."\n";
    echo '<OPTION VALUE="vide"></OPTION>'."\n";
    echo '<OPTION VALUE="00">00</OPTION>'."\n";
    echo '<OPTION VALUE="15">15</OPTION>'."\n";
    echo '<OPTION VALUE="30">30</OPTION>'."\n";
    echo '<OPTION VALUE="45">45</OPTION>'."\n";
    echo '</SELECT>'."\n";
    echo '<br><br>'. _("Add a end hour (optional)") .' : <br><br>'."\n";
    echo '<select name="nouvelleheurefin"> '."\n";
    echo '<OPTION VALUE="vide"></OPTION>'."\n";
    for ($i = 0; $i < 24; $i++) {
      echo '<OPTION VALUE="'.$i.'">'.$i.' H</OPTION>'."\n";
    }
    echo '</SELECT>'."\n";
    echo '<select name="nouvelleminutefin"> '."\n";
    echo '<OPTION VALUE="vide"></OPTION>'."\n";
    echo '<OPTION VALUE="00">00</OPTION>'."\n";
    echo '<OPTION VALUE="15">15</OPTION>'."\n";
    echo '<OPTION VALUE="30">30</OPTION>'."\n";
    echo '<OPTION VALUE="45">45</OPTION>'."\n";
    echo '</SELECT>'."\n";

    echo '<br><br><input type="image" name="retoursondage" value="Retourner au sondage" src="images/cancel.png"> '."\n";
    echo' <input type="image" name="ajoutercolonne" value="Ajouter une colonne" src="images/accept.png" alt="Valider">'."\n";
  }

  echo '</form>'."\n";
  echo '<br><br><br><br>'."\n";
  echo '</div>'."\n";

  bandeau_pied();
  
  echo'</body>'."\n";
  echo '</html>'."\n";
  die();  
}

//action si bouton confirmation de suppression est activé
if (isset($_POST["confirmesuppression"]) || isset($_POST["confirmesuppression_x"])) {
  $nbuser=$user_studs->RecordCount();
  $date=date('H:i:s d/m/Y:');

  //destruction des données dans la base SQL
  $sql = 'DELETE FROM sondage LEFT INNER JOIN sujet_studs ON sujet_studs.id_sondage = sondage.id_sondage '.
         'LEFT INNER JOIN user_studs ON user_studs.id_sondage = sondage.id_sondage '.
         'LEFT INNER JOIN comments ON comments.id_sondage = sondage.id_sondage '.
         'WHERE id_sondage = '.$connect->Param('numsondage');
  $sql = $connect->Prepare($sql);
  if ($connect->Execute($sql, array($numsondage))) {
    // on ecrit dans le fichier de logs la suppression du sondage
    error_log($date . " SUPPRESSION: $dsondage->id_sondage\t$dsondage->format\t$dsondage->nom_admin\t$dsondage->mail_admin\t$nbuser\t$dsujets->sujet\n", 3, 'admin/logs_studs.txt');
  
    //envoi du mail a l'administrateur du sondage
    mail ($adresseadmin, 
          _("[ADMINISTRATOR] Removing of your poll") . ' ' . NOMAPPLICATION,
          _("You have removed your poll. \nYou can make new polls with this link") .
          " :\n\n".get_server_name()."index.php \n\n" .
          _("Thanks for your confidence.") . "\n" . NOMAPPLICATION,
          $headers);

    //affichage de l'ecran de confirmation de suppression de sondage
    print_header();
    echo '<body>'."\n";
    logo();
    bandeau_tete();
    bandeau_titre(_("Make your polls"));
  
    echo '<div class="corpscentre">'."\n";
    print "<H2>" . _("Your poll has been removed!") . "</H2><br><br>";
    print  _("Back to the homepage of ") . " <a href=\"index.php\"> ".NOMAPPLICATION."</A>."."\n";
    echo '<br><br><br>'."\n";
    echo '</div>'."\n";
    sur_bandeau_pied();
    bandeau_pied();
    echo '</form>'."\n";
    echo '</body>'."\n";
    echo '</html>'."\n";
    die();
  }
}

// quand on ajoute un commentaire utilisateur
if(isset($_POST['ajoutcomment']) || isset($_POST['ajoutcomment_x'])) {
  if(issetAndNoEmpty('commentuser') === false) {
    $err |= COMMENT_USER_EMPTY;
  } else {
    $comment_user = htmlentities(html_entity_decode($_POST["commentuser"], ENT_QUOTES, 'UTF-8'), ENT_QUOTES, 'UTF-8');
  }
  
  if(issetAndNoEmpty('comment') === false) {
    $err |= COMMENT_EMPTY;
  }

  if (issetAndNoEmpty('comment') && !is_error(COMMENT_EMPTY) && !is_error(NO_POLL) && !is_error(COMMENT_USER_EMPTY)) {
    $comment = htmlentities(html_entity_decode($_POST["comment"], ENT_QUOTES, 'UTF-8'), ENT_QUOTES, 'UTF-8');
    
    $sql = 'INSERT INTO comments (id_sondage, comment, usercomment) VALUES ('.
            $connect->Param('id_sondage').','.
            $connect->Param('comment').','.
            $connect->Param('comment_user').')';
    $sql = $connect->Prepare($sql);
    
    $comments = $connect->Execute($sql, array($numsondage, $comment, $comment_user));
    if ($comments === false) {
      $err |= COMMENT_INSERT_FAILED;
    }
  }
}


//s'il existe on affiche la page normale
// DEBUT DE L'AFFICHAGE DE LA PAGE HTML
print_header(true);
echo '<body>'."\n";
logo();
bandeau_tete();
bandeau_titre(_("Make your polls"));
sous_bandeau();
  
echo '<div class="presentationdate"> '."\n";

//affichage du titre du sondage
$titre=str_replace("\\","",$dsondage->titre);       
echo '<H2>'.$titre.'</H2>'."\n";

//affichage du nom de l'auteur du sondage
echo _("Initiator of the poll") .' : '.$dsondage->nom_admin.'<br>'."\n";

//affichage des commentaires du sondage
if ($dsondage->commentaires){
  echo '<br>'. _("Comments") .' :<br>'."\n";
  $commentaires=$dsondage->commentaires;
  $commentaires=str_replace("\\","",$commentaires);       
  echo nl2br($commentaires);
  echo '<br>'."\n";
}
echo '<br>'."\n";
echo '</div>'."\n";


$nbcolonnes = substr_count($dsujet->sujet, ',') + 1;
$nblignes = $user_studs->RecordCount();

//si il n'y a pas suppression alors on peut afficher normalement le tableau

//action si le bouton participer est cliqué
if (isset($_POST["boutonp"]) || isset($_POST["boutonp_x"])) {
  //si on a un nom dans la case texte
  if (issetAndNoEmpty('nom')){
    $nouveauchoix = '';
    $erreur_prenom = false;
    
    for ($i=0;$i<$nbcolonnes;$i++){
      //si la checkbox est cochée alors valeur est egale à 1
      if (isset($_POST["choix$i"])){
        $nouveauchoix.="1";
      } else { //sinon 0
        $nouveauchoix.="0";
      }
    }
    
    $nom = htmlentities(html_entity_decode($_POST["nom"], ENT_QUOTES, 'UTF-8'), ENT_QUOTES, 'UTF-8');
    while($user = $user_studs->FetchNextObject(false)) {
      if ($nom == $user->nom){
        $erreur_prenom="yes";
      }
    }
    
    // Ecriture des choix de l'utilisateur dans la base
    if (!$erreur_prenom) {
      $sql = 'INSERT INTO user_studs (nom, id_sondage, reponses) VALUES ('.
              $connect->Param('nom').','.
              $connect->Param('numsondage').','.
              $connect->Param('nouveauchoix').')';
              
      $sql = $connect->Prepare($sql);
      $connect->Execute($sql, array($nom, $numsondage, $nouveauchoix));
    }
  }
}


//action quand on ajoute une colonne au format AUTRE
if (isset($_POST["ajoutercolonne_x"]) && issetAndNoEmpty('nouvellecolonne') && ($dsondage->format == "A" || $dsondage->format == "A+")) {
  $nouveauxsujets=$dsujet->sujet;
  
  //on rajoute la valeur a la fin de tous les sujets deja entrés
  $nouveauxsujets.=",";
  $nouveauxsujets.=str_replace(","," ",$_POST["nouvellecolonne"]);
  $nouveauxsujets = htmlentities(html_entity_decode($nouveauxsujets, ENT_QUOTES, 'UTF-8'), ENT_QUOTES, 'UTF-8');
  
  //mise a jour avec les nouveaux sujets dans la base
  $sql = 'UPDATE sujet_studs SET sujet = '.$connect->Param('nouveauxsujets').' WHERE id_sondage = '.$connect->Param('numsondage');
  $sql = $connect->Prepare($sql);
  if ($connect->Execute($sql, array($nouveauxsujets, $numsondage))) {
    //envoi d'un mail pour prévenir l'administrateur du changement
    $headers="From: ".NOMAPPLICATION." <".ADRESSEMAILADMIN.">\r\nContent-Type: text/plain; charset=\"UTF-8\"\nContent-Transfer-Encoding: 8bit";
    mail ("$adresseadmin", "" . _("[ADMINISTRATOR] New column for your poll").NOMAPPLICATION, "" .
          _("You have added a new column in your poll. \nYou can inform the voters of this change with this link") .
          " : \n\n".getUrlSondage($numsondage)." \n\n " . _("Thanks for your confidence.") . "\n".NOMAPPLICATION,
          $headers);
  }
}


//action quand on ajoute une colonne au format DATE
if (isset($_POST["ajoutercolonne_x"]) && ($dsondage->format == "D" || $dsondage->format == "D+")) {
  $nouveauxsujets=$dsujet->sujet;
  
  if (isset($_POST["nouveaujour"]) && $_POST["nouveaujour"] != "vide" &&
      isset($_POST["nouveaumois"]) && $_POST["nouveaumois"] != "vide" &&
      isset($_POST["nouvelleannee"]) && $_POST["nouvelleannee"] != "vide") {
    
    $nouvelledate=mktime(0, 0, 0, $_POST["nouveaumois"], $_POST["nouveaujour"], $_POST["nouvelleannee"]);
    
    if (isset($_POST["nouvelleheuredebut"]) && $_POST["nouvelleheuredebut"]!="vide"){
      $nouvelledate.="@";
      $nouvelledate.=$_POST["nouvelleheuredebut"];
      $nouvelledate.="h";
      
      if ($_POST["nouvelleminutedebut"]!="vide") {
        $nouvelledate.=$_POST["nouvelleminutedebut"];
      }
    }
    
    if (isset($_POST["nouvelleheurefin"]) && $_POST["nouvelleheurefin"]!="vide"){
      $nouvelledate.="-";
      $nouvelledate.=$_POST["nouvelleheurefin"];
      $nouvelledate.="h";
      
      if ($_POST["nouvelleminutefin"]!="vide") {
        $nouvelledate.=$_POST["nouvelleminutefin"];
      }
    }
    
    if($_POST["nouvelleheuredebut"] == "vide" || (isset($_POST["nouvelleheuredebut"]) && isset($_POST["nouvelleheurefin"]) && (($_POST["nouvelleheuredebut"] < $_POST["nouvelleheurefin"]) || (($_POST["nouvelleheuredebut"] == $_POST["nouvelleheurefin"]) && ($_POST["nouvelleminutedebut"] < $_POST["nouvelleminutefin"]))))) {
      $erreur_ajout_date = false;
    } else {
      $erreur_ajout_date = "yes";
    }
    
    //on rajoute la valeur dans les valeurs
    $datesbase = explode(",",$dsujet->sujet);
    $taillebase = sizeof($datesbase);
    
    //recherche de l'endroit de l'insertion de la nouvelle date dans les dates deja entrées dans le tableau
    if ($nouvelledate < $datesbase[0]) {
      $cleinsertion = 0;
    } elseif ($nouvelledate > $datesbase[$taillebase-1]) {
      $cleinsertion = count($datesbase);
    } else {
      for ($i = 0; $i < count($datesbase); $i++) {
        $j = $i + 1;
        if ($nouvelledate > $datesbase[$i] && $nouvelledate < $datesbase[$j]) {
          $cleinsertion = $j;
        }
      }
    }
    
    array_splice($datesbase, $cleinsertion, 0, $nouvelledate);
    $cle = array_search($nouvelledate, $datesbase);
    $dateinsertion = '';
    for ($i = 0; $i < count($datesbase); $i++) {
      $dateinsertion.=",";
      $dateinsertion.=$datesbase[$i];
    }
    
    $dateinsertion = substr("$dateinsertion", 1);
    
    //mise a jour avec les nouveaux sujets dans la base
    if (isset($erreur_ajout_date) && !$erreur_ajout_date){
      $sql = 'UPDATE sujet_studs SET sujet = '.$connect->Param('dateinsertion').' WHERE id_sondage = '.$connect->Param('numsondage');
      $sql = $connect->Prepare($sql);
      $connect->Execute($sql, array($dateinsertion, $numsondage));
      
      if ($nouvelledate > strtotime($dsondage->date_fin)) {
        $date_fin=$nouvelledate+200000;
        $sql = 'UPDATE sondage SET date_fin = '.$connect->Param('date_fin').' WHERE id_sondage = '.$connect->Param('numsondage');
        $sql = $connect->Prepare($sql);
        $connect->Execute($sql, array($date_fin, $numsondage));
      }
    }
    
    //mise a jour des reponses actuelles correspondant au sujet ajouté
    $sql = 'UPDATE user_studs SET reponses = '.$connect->Param('reponses').' WHERE nom = '.$connect->Param('nom').' AND id_users='.$connect->Param('id_users');
    $sql = $connect->Prepare($sql);
    while ($data = $user_studs->FetchNextObject(false)) {
      $ensemblereponses=$data->reponses;
      $newcar = '';
      
      //parcours de toutes les réponses actuelles
      for ($j = 0; $j < $nbcolonnes; $j++) {
        $car=substr($ensemblereponses,$j,1);
        
        //si les reponses ne concerne pas la colonne ajoutée, on concatene
        if ($j==$cle) {
          $newcar.="0";
        }
        
        $newcar.=$car;
      }
      
      //mise a jour des reponses utilisateurs dans la base
      if (isset($erreur_ajout_date) && !$erreur_ajout_date){
        $connect->Execute($sql, array($newcar, $data->nom, $data->id_users));
      }
    }
    
    //envoi d'un mail pour prévenir l'administrateur du changement
    $adresseadmin = $dsondage->mail_admin;
    
    mail ($adresseadmin,
          _("[ADMINISTRATOR] New column for your poll"),
          _("You have added a new column in your poll. \nYou can inform the voters of this change with this link").
          " : \n\n".getUrlSondage($numsondage)." \n\n " . _("Thanks for your confidence.") . "\n".NOMAPPLICATION,
          $headers);
  } else {
    $erreur_ajout_date="yes";
  }
}


//suppression de ligne dans la base
for ($i = 0; $i < $nblignes; $i++) {
  if (isset($_POST["effaceligne$i"]) || isset($_POST['effaceligne'.$i.'_x'])) {
    $compteur=0;
    $sql = 'DELETE FROM user_studs WHERE nom = '.$connect->Param('nom').' AND id_users = '.$connect->Param('id_users');
    $sql = $connect->Prepare($sql);
    
    while ($data=$user_studs->FetchNextObject(false)) {
      if ($compteur==$i){
        $connect->Execute($sql, array($data->nom, $data->id_users));
      }
      
      $compteur++;
    }
  }
}


//suppression d'un commentaire utilisateur
$sql = 'SELECT * FROM comments WHERE id_sondage='.$connect->Param('numsondage').' ORDER BY id_comment';
$sql = $connect->Prepare($sql);
$comment_user = $connect->Execute($sql, array($numsondage));
$i = 0;
while ($dcomment = $comment_user->FetchNextObject(false)) {
  if (isset($_POST['suppressioncomment'.$i.'_x'])) {
    $sql = 'DELETE FROM comments WHERE id_comment = '.$connect->Param('id_comment');
    $sql = $connect->Prepare($sql);
    $connect->Execute($sql, array($dcomment->id_comment));
  }
  
  $i++;
}


//on teste pour voir si une ligne doit etre modifiée
$testmodifier = false;
$testligneamodifier = false;

for ($i = 0; $i < $nblignes; $i++) {
  if (isset($_POST["modifierligne$i"]) || isset($_POST['modifierligne'.$i.'_x'])) {
    $ligneamodifier=$i;
    $testligneamodifier="true";
  }
  
  //test pour voir si une ligne est a modifier
  if (isset($_POST["validermodifier$i"]) || isset($_POST['validermodifier'.$i.'_x'])) {
    $modifier=$i;
    $testmodifier="true";
  }
}


//si le test est valide alors on affiche des checkbox pour entrer de nouvelles valeurs
if ($testmodifier) {
  $nouveauchoix = '';
  for ($i = 0; $i < $nbcolonnes; $i++) {
    //recuperation des nouveaux choix de l'utilisateur
    if (isset($_POST["choix$i"])) {
      $nouveauchoix.="1";
    } else {
      $nouveauchoix.="0";
    }
  }
  
  $compteur=0;
  
  while ($data=$user_studs->FetchNextObject(false)) {
    //mise a jour des données de l'utilisateur dans la base SQL
    if ($compteur==$modifier) {
      $sql = 'UPDATE user_studs SET reponses = '.$connect->Param('reponses').' WHERE nom = '.$connect->Param('nom').' AND id_users = '.$connect->Param('id_users');
      $sql = $connect->Prepare($sql);
      $connect->Execute($sql, array($nouveauchoix, $data->nom, $data->id_users));
    }
    
    $compteur++;
  }
}


//suppression de colonnes dans la base
for ($i = 0; $i < $nbcolonnes; $i++) {
  if ((isset($_POST["effacecolonne$i"]) || isset($_POST['effacecolonne'.$i.'_x'])) && $nbcolonnes > 1){
    $toutsujet = explode(",",$dsujet->sujet);
    $j = 0;
    $nouveauxsujets = '';
    
    //parcours de tous les sujets actuels
    while (isset($toutsujet[$j])) {
      //si le sujet n'est pas celui qui a été effacé alors on concatene
      if ($i != $j) {
        $nouveauxsujets .= ',';
        $nouveauxsujets .= $toutsujet[$j];
      }
      
      $j++;
    }
    
    //on enleve la virgule au début
    $nouveauxsujets = substr("$nouveauxsujets", 1);
    
    //nettoyage des reponses actuelles correspondant au sujet effacé
    $compteur = 0;
    $sql = 'UPDATE user_studs SET reponses = '.$connect->Param('reponses').' WHERE nom = '.$connect->Param('nom').' AND id_users = '.$connect->Param('id_users');
    $sql = $connect->Prepare($sql);
    
    while ($data = $user_studs->FetchNextObject(false)) {
      $newcar = '';
      $ensemblereponses = $data->reponses;
      
      //parcours de toutes les réponses actuelles
      for ($j = 0; $j < $nbcolonnes; $j++) {
        $car=substr($ensemblereponses, $j, 1);
        //si les reponses ne concerne pas la colonne effacée, on concatene
        if ($i != $j) {
          $newcar .= $car;
        }
      }
      
      $compteur++;
      
      //mise a jour des reponses utilisateurs dans la base
      $connect->Execute($sql, array($newcar, $data->nom, $data->id_users));
    }
    
    //mise a jour des sujets dans la base
    $sql = 'UPDATE sujet_studs SET sujet = '.$connect->Param('nouveauxsujets').' WHERE id_sondage = '.$connect->Param('numsondage');
    $sql = $connect->Prepare($sql);
    $connect->Execute($sql, array($nouveauxsujets, $numsondage));
  }
}


//recuperation des donnes de la base
$sql = 'SELECT * FROM sondage WHERE id_sondage_admin = '.$connect->Param('numsondageadmin');
$sql = $connect->Prepare($sql);
$sondage = $connect->Execute($sql, array($numsondageadmin));

if ($sondage !== false) {
  $sql = 'SELECT * FROM sujet_studs WHERE id_sondage = '.$connect->Param('numsondage');
  $sql = $connect->Prepare($sql);
  $sujets = $connect->Execute($sql, array($numsondage));
  
  $sql = 'SELECT * FROM user_studs WHERE id_sondage = '.$connect->Param('numsondage').' order by id_users';
  $sql = $connect->Prepare($sql);
  $user_studs = $connect->Execute($sql, array($numsondage));
} else {
  print_header(false);
  echo '<body>'."\n";
  logo();
  bandeau_tete();
  bandeau_titre(_("Error!"));
  echo '<div class=corpscentre>'."\n";
  print "<H2>" . _("This poll doesn't exist !") . "</H2><br><br>"."\n";
  print "" . _("Back to the homepage of ") . " <a href=\"index.php\"> ".NOMAPPLICATION."</A>. "."\n";
  echo '<br><br><br><br>'."\n";
  echo '</div>'."\n";
  bandeau_pied();
  echo'</body>'."\n";
  echo '</html>'."\n";
  die();
}

//on recupere les données et les sujets du sondage
$dsujet=$sujets->FetchObject(false);
$dsondage=$sondage->FetchObject(false);

$toutsujet=explode(",",$dsujet->sujet);
$toutsujet=str_replace("@","<br>",$toutsujet);
$toutsujet=str_replace("°","'",$toutsujet);
$nbcolonnes=substr_count($dsujet->sujet,',')+1;

echo '<form name="formulaire" action="'.getUrlSondage($numsondageadmin, true).'" method="POST" onkeypress="javascript:process_keypress(event)">'."\n";
echo '<div class="cadre"> '."\n";
echo _('As poll administrator, you can change all the lines of this poll with <img src="images/info.png" alt="infos">.<br> You can, as well, remove a column or a line with <img src="images/cancel.png" alt="Cancel">. <br>You can also add a new column with <img src="images/add-16.png" alt="Add column">.<br> Finally, you can change the informations of this poll like the title, the comments or your email address.') ."\n";
echo '<br><br>'."\n";

//debut de l'affichage de résultats
echo '<table class="resultats">'."\n";

//reformatage des données des sujets du sondage
$toutsujet=explode(",",$dsujet->sujet);
echo '<tr>'."\n";
echo '<td></td>'."\n";
echo '<td></td>'."\n";

//boucle pour l'affichage des boutons de suppression de colonne
for ($i = 0; isset($toutsujet[$i]); $i++) {
  echo '<td class=somme><input type="image" name="effacecolonne'.$i.'" value="Effacer la colonne" src="images/cancel.png"></td>'."\n";
}

echo '</tr>'."\n";

//si le sondage est un sondage de date
if ($dsondage->format=="D"||$dsondage->format=="D+") {
  
  //affichage des sujets du sondage
  echo '<tr>'."\n";
  echo '<td></td>'."\n";
  echo '<td></td>'."\n";
  
  //affichage des années
  $colspan=1;
  for ($i = 0; $i < count($toutsujet); $i++) {
    $current = $toutsujet[$i];
    
    if (strpos($toutsujet[$i], '@') !== false) {
      $current = substr($toutsujet[$i], 0, strpos($toutsujet[$i], '@'));
    }
    
    if (isset($toutsujet[$i+1]) && strpos($toutsujet[$i+1], '@') !== false) {
      $next = substr($toutsujet[$i+1], 0, strpos($toutsujet[$i+1], '@'));
    } elseif (isset($toutsujet[$i+1])) {
      $next = $toutsujet[$i+1];
    }
    
    if (isset($toutsujet[$i+1]) && strftime("%Y",$current) == strftime("%Y",$next)){
      $colspan++;
    } else {
      echo '<td colspan='.$colspan.' class="annee">'.strftime("%Y", $current).'</td>'."\n";
      $colspan=1;
    }
  }
  
  echo '<td class="annee"><input type="image" name="ajoutsujet" src="images/add-16.png"  alt="' . _('Add') . '"></td>'."\n";
  echo '</tr>'."\n";
  echo '<tr>'."\n";
  echo '<td></td>'."\n";
  echo '<td></td>'."\n";
  
  //affichage des mois
  $colspan = 1;
  for ($i = 0; $i < count($toutsujet); $i++) {
    $current = $toutsujet[$i];
    
    if (strpos($toutsujet[$i], '@') !== false) {
      $current = substr($toutsujet[$i], 0, strpos($toutsujet[$i], '@'));
    }
    
    if (isset($toutsujet[$i+1]) && strpos($toutsujet[$i+1], '@') !== false) {
      $next = substr($toutsujet[$i+1], 0, strpos($toutsujet[$i+1], '@'));
    } elseif (isset($toutsujet[$i+1])) {
      $next = $toutsujet[$i+1];
    }
    
    if (isset($toutsujet[$i+1]) && strftime("%B", $current) == strftime("%B", $next) && strftime("%Y", $current) == strftime("%Y", $next)){
      $colspan++;
    } else {
      if ($_SESSION["langue"]=="EN") {
        echo '<td colspan='.$colspan.' class="mois">'.date("F",$current).'</td>'."\n";
      } else {
        echo '<td colspan='.$colspan.' class="mois">'.strftime("%B",$current).'</td>'."\n";
      }
      
      $colspan=1;
    }
  }
  
  echo '<td class="mois"><input type="image" name="ajoutsujet" src="images/add-16.png"  alt="' . _('Add') . '"></td>'."\n";
  echo '</tr>'."\n";
  echo '<tr>'."\n";
  echo '<td></td>'."\n";
  echo '<td></td>'."\n";
  
  //affichage des jours
  $colspan = 1;
  for ($i = 0; $i < count($toutsujet); $i++) {
    $current = $toutsujet[$i];
    
    if (strpos($toutsujet[$i], '@') !== false) {
      $current = substr($toutsujet[$i], 0, strpos($toutsujet[$i], '@'));
    }
    
    if (isset($toutsujet[$i+1]) && strpos($toutsujet[$i+1], '@') !== false) {
      $next = substr($toutsujet[$i+1], 0, strpos($toutsujet[$i+1], '@'));
    } elseif (isset($toutsujet[$i+1])) {
      $next = $toutsujet[$i+1];
    }
    
    if (isset($toutsujet[$i+1]) && strftime("%a %e",$current)==strftime("%a %e",$next)&&strftime("%B",$current)==strftime("%B",$next)){
      $colspan++;
    } else {
      if ($_SESSION["langue"]=="EN") {
        echo '<td colspan='.$colspan.' class="jour">'.date("D jS",$current).'</td>'."\n";
      } else {
        echo '<td colspan='.$colspan.' class="jour">'.strftime("%a %e",$current).'</td>'."\n";
      }
      
      $colspan=1;
    }
  }
  
  echo '<td class="jour"><input type="image" name="ajoutsujet" src="images/add-16.png"  alt="' . _('Add') . '"></td>'."\n";
  echo '</tr>'."\n";
  
  //affichage des horaires
  if (strpos($dsujet->sujet,'@') !== false) {
    echo '<tr>'."\n";
    echo '<td></td>'."\n";
    echo '<td></td>'."\n";
    
    for ($i = 0; isset($toutsujet[$i]); $i++) {
      $heures=explode("@", $toutsujet[$i]);
      if (isset($heures[1])) {
        echo '<td class="heure">'.$heures[1].'</td>'."\n";
      } else {
        echo '<td class="heure"></td>'."\n";
      }
    }
    
    echo '<td class="heure"><input type="image" name="ajoutsujet" src="images/add-16.png"  alt="' . _('Add') . '"></td>'."\n";
    echo '</tr>'."\n";
  }
} else {
  $toutsujet=str_replace("°","'",$toutsujet);
  
  //affichage des sujets du sondage
  echo '<tr>'."\n";
  echo '<td></td>'."\n";
  echo '<td></td>'."\n";
  
  for ($i = 0; isset($toutsujet[$i]); $i++) {
    echo '<td class="sujet">'.$toutsujet[$i].'</td>'."\n";
  }
  
  echo '<td class="sujet"><input type="image" name="ajoutsujet" src="images/add-16.png"  alt="' . _('Add') . '"></td>'."\n";
  echo '</tr>'."\n";
}


//affichage des resultats
$somme[] = 0;
$compteur = 0;

while ($data = $user_studs->FetchNextObject(false)) {
  $ensemblereponses = $data->reponses;
  
  echo '<tr>'."\n";
  echo '<td><input type="image" name="effaceligne'.$compteur.'" value="Effacer" src="images/cancel.png"  alt="Icone efface"></td>'."\n";
  
  //affichage du nom
  $nombase=str_replace("°","'",$data->nom);
  echo '<td class="nom">'.$nombase.'</td>'."\n";
  
  //si la ligne n'est pas a changer, on affiche les données
  if (!$testligneamodifier) {
    for ($k = 0; $k < $nbcolonnes; $k++) {
      $car = substr($ensemblereponses, $k, 1);
      if ($car == "1") {
        echo '<td class="ok">OK</td>'."\n";
        if (isset($somme[$k]) === false) {
          $somme[$k] = 0;
        }
        $somme[$k]++;
      } else {
        echo '<td class="non"></td>'."\n";
      }
    }
  } else { //sinon on remplace les choix de l'utilisateur par une ligne de checkbox pour recuperer de nouvelles valeurs
    
    //si c'est bien la ligne a modifier on met les checkbox
    if ($compteur == "$ligneamodifier") {
      for ($j = 0; $j < $nbcolonnes; $j++) {
        $car = substr($ensemblereponses, $j, 1);
        if ($car == "1") {
          echo '<td class="vide"><input type="checkbox" name="choix'.$j.'" value="" checked></td>'."\n";
        } else {
          echo '<td class="vide"><input type="checkbox" name="choix'.$j.'" value=""></td>'."\n";
        }
      }
    } else { //sinon on affiche les lignes normales
      for ($k = 0; $k < $nbcolonnes; $k++) {
        $car = substr($ensemblereponses, $k, 1);
        
        if ($car == "1") {
          echo '<td class="ok">OK</td>'."\n";
          if (isset($somme[$k]) === false) {
            $somme[$k] = 0;
          }
          $somme[$k]++;
        } else {
          echo '<td class="non"></td>'."\n";
        }
      }
    }
  }
  
  //a la fin de chaque ligne se trouve les boutons modifier
  if (!$testligneamodifier=="true") {
    echo '<td class=somme><input type="image" name="modifierligne'.$compteur.'" value="Modifier" src="images/info.png" alt="Icone infos"></td>'."\n";
  }
  
  //demande de confirmation pour modification de ligne
  for ($i = 0; $i < $nblignes; $i++) {
    if (isset($_POST["modifierligne$i"]) || isset($_POST['modifierligne'.$i.'_x'])) {
      if ($compteur == $i) {
        echo '<td><input type="image" name="validermodifier'.$compteur.'" value="Valider la modification" src="images/accept.png"  alt="Icone valider"></td>'."\n";
      }
    }
  }
  
  $compteur++;
  echo '</tr>'."\n";
}


//affichage de la case vide de texte pour un nouvel utilisateur
echo '<tr>'."\n";
echo '<td></td>'."\n";
echo '<td class=nom>'."\n";
echo '<input type="text" name="nom"><br>'."\n";
echo '</td>'."\n";

//une ligne de checkbox pour le choix du nouvel utilisateur
for ($i = 0; $i < $nbcolonnes; $i++) {
  echo '<td class="vide"><input type="checkbox" name="choix'.$i.'" value=""></td>'."\n";
}

// Affichage du bouton de formulaire pour inscrire un nouvel utilisateur dans la base
echo '<td><input type="image" name="boutonp" value="Participer" src="images/add-24.png" alt="' . _('Add') . '"></td>'."\n";
echo '</tr>'."\n";

//determination du meilleur choix
for ($i = 0; $i < $nbcolonnes + 1; $i++) {
  if (isset($somme[$i]) === true) {
    if ($i == "0") {
      $meilleurecolonne = $somme[$i];
    }
    
    if (isset($somme[$i]) && $somme[$i] > $meilleurecolonne){
      $meilleurecolonne = $somme[$i];
    }
  }
}


//affichage de la ligne contenant les sommes de chaque colonne
echo '<tr>'."\n";
echo '<td></td>'."\n";
echo '<td align="right">'. _("Addition") .'</td>'."\n";

for ($i = 0; $i < $nbcolonnes; $i++) {
  if (isset($somme[$i]) === true) {
    $affichesomme = $somme[$i];
  } else {
    $affichesomme = '';
  }
  
  if ($affichesomme == "") {
    $affichesomme = "0";
  }
  
  if (isset($somme[$i]) === true && isset($meilleurecolonne) === true && $somme[$i] == $meilleurecolonne){
    echo '<td class="somme">'.$affichesomme.'</td>'."\n";
  } else {
    echo '<td class="somme">'.$affichesomme.'</td>'."\n";
  }
}

echo '<tr>'."\n";
echo '<td></td>'."\n";
echo '<td class="somme"></td>'."\n";

for ($i = 0; $i < $nbcolonnes; $i++) {
  if (isset($somme[$i]) === true && isset($meilleurecolonne) === true && $somme[$i] == $meilleurecolonne){
    echo '<td class="somme"><img src="images/medaille.png" alt="Meilleur resultat"></td>'."\n";
  } else {
    echo '<td class="somme"></td>'."\n";
  }
}

echo '</tr>'."\n";


// S'il a oublié de remplir un nom
if ((isset($_POST["boutonp"]) || isset($_POST["boutonp_x"])) && $_POST["nom"] == "") {
  echo '<tr>'."\n";
  print "<td colspan=10><font color=#FF0000>" . _("Enter a name !") . "</font>\n";
  echo '</tr>'."\n";
}

if (isset($erreur_prenom) && $erreur_prenom) {
  echo '<tr>'."\n";
  print "<td colspan=10><font color=#FF0000>" . _("The name you've chosen already exist in this poll!") . "</font></td>\n";
  echo '</tr>'."\n";
}

if (isset($erreur_injection) && $erreur_injection) {
  echo '<tr>'."\n";
  print "<td colspan=10><font color=#FF0000>" . _("Characters \"  '  < et > are not permitted") . "</font></td>\n";
  echo '</tr>'."\n";
}

if (isset($erreur_ajout_date) && $erreur_ajout_date) {
  echo '<tr>'."\n";
  print "<td colspan=10><font color=#FF0000>" . _("The date is not correct !") . "</font></td>\n";
  echo '</tr>'."\n";
}

//fin du tableau
echo '</table>'."\n";
echo '</div>'."\n";

//focus en javascript sur le champ texte pour le nom d'utilisateur
echo '<script type="text/javascript">'."\n";
echo 'document.formulaire.nom.focus();'."\n";
echo '</script>'."\n";

//recuperation des valeurs des sujets et adaptation pour affichage
$toutsujet = explode(",", $dsujet->sujet);

//recuperation des sujets des meilleures colonnes
$compteursujet = 0;
$meilleursujet = '';
for ($i = 0; $i < $nbcolonnes; $i++) {
  if (isset($somme[$i]) === true && isset($meilleurecolonne) === true && $somme[$i] == $meilleurecolonne){
    $meilleursujet.=", ";
    
    if ($dsondage->format == "D" || $dsondage->format == "D+") {
      $meilleursujetexport = $toutsujet[$i];
      
      if (strpos($toutsujet[$i], '@') !== false) {
        $toutsujetdate = explode("@", $toutsujet[$i]);
        if ($_SESSION["langue"] == "EN") {
          $meilleursujet .= date("l, F jS Y",$toutsujetdate[0])." " . _("for") ." ".$toutsujetdate[1];
        } else {
          $meilleursujet .= strftime(_("%A, den %e. %B %Y"),$toutsujetdate[0]). ' ' . _("for")  . ' ' . $toutsujetdate[1];
        }
      } else {
        if ($_SESSION["langue"] == "EN") {
          $meilleursujet .= date("l, F jS Y",$toutsujet[$i]);
        } else {
          $meilleursujet .= strftime(_("%A, den %e. %B %Y"),$toutsujet[$i]);
        }
      }
    } else {
      $meilleursujet.=$toutsujet[$i];
    }
    
    $compteursujet++;
  }
}

//adaptation pour affichage des valeurs
$meilleursujet = substr("$meilleursujet", 1);
$meilleursujet = str_replace("°", "'", $meilleursujet);

//ajout du S si plusieurs votes
$vote_str = _('vote');
if (isset($meilleurecolonne) && $meilleurecolonne > 1) {
  $vote_str = _('votes');
}

echo '<p class=affichageresultats>'."\n";

//affichage de la phrase annoncant le meilleur sujet
if (isset($meilleurecolonne) && $compteursujet == "1") {
  print "<img src=\"images/medaille.png\" alt=\"Meilleur resultat\">" . _("The best choice at this time is") . " : <b>$meilleursujet </b>" . _("with") . " <b>$meilleurecolonne </b>" . $vote_str . ".<br>\n";
} elseif (isset($meilleurecolonne)) {
  print "<img src=\"images/medaille.png\" alt=\"Meilleur resultat\"> " . _("The bests choices at this time are") . " : <b>$meilleursujet </b>" . _("with") . " <b>$meilleurecolonne </b>" . $vote_str . ".<br>\n";
}

echo '<br><br>'."\n";
echo '</p>'."\n";
echo '</form>'."\n";
echo '<form name="formulaire4" action="#bas" method="POST" onkeypress="javascript:process_keypress(event)">'."\n";

//Gestion du sondage
echo '<div class=titregestionadmin>'. _("Poll's management") .' :</div>'."\n";
echo '<p class=affichageresultats>'."\n";
echo '<br>'."\n";

//Changer le titre du sondage
$adresseadmin=$dsondage->mail_admin;
echo _("Change the title") .' :<br>' .
    '<input type="text" name="nouveautitre" size="40" value="'.$dsondage->titre.'">'.
    '<input type="image" name="boutonnouveautitre" value="Changer le titre" src="images/accept.png" alt="Valider"><br><br>'."\n";

//si la valeur du nouveau titre est invalide : message d'erreur
if ((isset($_POST["boutonnouveautitre"]) || isset($_POST["boutonnouveautitre_x"])) && !issetAndNoEmpty('nouveautitre')) {
  echo '<font color="#FF0000">'. _("Enter a new title!") .'</font><br><br>'."\n";
}

//Changer les commentaires du sondage
echo _("Change the comments") .' :<br> <textarea name="nouveauxcommentaires" rows="7" cols="40">'.$dsondage->commentaires.'</textarea><br><input type="image" name="boutonnouveauxcommentaires" value="Changer les commentaires" src="images/accept.png" alt="Valider"><br><br>'."\n";

//Changer l'adresse de l'administrateur
echo _("Change your email address") .' :<br> <input type="text" name="nouvelleadresse" size="40" value="'.$dsondage->mail_admin.'"> <input type="image" name="boutonnouvelleadresse" value="Changer votre adresse" src="images/accept.png" alt="Valider"><br>'."\n";

//si l'adresse est invalide ou le champ vide : message d'erreur
if ((isset($_POST["boutonnouvelleadresse"]) || isset($_POST["boutonnouvelleadresse_x"])) && !issetAndNoEmpty('nouvelleadresse')) {
  echo '<font color="#FF0000">'. _("Enter a new email address!") .'</font><br><br>'."\n";
}

//affichage des commentaires des utilisateurs existants
$sql = 'SELECT * FROM comments WHERE id_sondage='.$connect->Param('numsondage').' ORDER BY id_comment';
$sql = $connect->Prepare($sql);
$comment_user = $connect->Execute($sql, array($numsondage));
if ($comment_user->RecordCount() != 0) {
  print "<br><b>" . _("Comments") . " :</b><br>\n";
  
  $i = 0;
  while ( $dcomment=$comment_user->FetchNextObject(false)) {
    print "<input type=\"image\" name=\"suppressioncomment$i\" src=\"images/cancel.png\" alt=\"supprimer commentaires\"> $dcomment->usercomment : $dcomment->comment <br>";
    $i++;
  }
  
  echo '<br>';
}

if (isset($erreur_commentaire_vide) && $erreur_commentaire_vide=="yes") {
  print "<font color=#FF0000>" . _("Enter a name and a comment!") . "</font>";
}

//affichage de la case permettant de rajouter un commentaire par les utilisateurs
print "<br>" . _("Add a comment in the poll") . " :<br>\n";
echo _("Name") .' : <input type=text name="commentuser"><br>'."\n";
echo '<textarea name="comment" rows="2" cols="40"></textarea>'."\n";
echo '<input type="image" name="ajoutcomment" value="Ajouter un commentaire" src="images/accept.png" alt="Valider"><br>'."\n";

//suppression du sondage
echo '<br>'."\n";
echo _("Remove your poll") .' : <input type="image" name="suppressionsondage" value="'. _("Remove the poll") .'" src="images/cancel.png" alt="' . _('Cancel') . '"><br><br>'."\n";

if (isset($_POST["suppressionsondage"])) {
  echo _("Confirm removal of your poll") .' : <input type="submit" name="confirmesuppression" value="'. _("Remove this poll!") .'">'."\n";
  echo '<input type="submit" name="annullesuppression" value="'. _("Keep this poll!") .'"><br><br>'."\n";
}

echo '</form>'."\n";

if ($dsondage->format == "D" || $dsondage->format == "D+") {
  echo '<form name="formulaire2" action="'.get_server_name().'exportpdf.php" method="POST" onkeypress="javascript:process_keypress(event)">'."\n";
  echo _("Generate the convocation letter (.PDF), choose the place to meet and validate") .'<br>';
  echo '<input type="text" name="lieureunion" size="100" value="" />';
  echo '<input type="hidden" name="sondage" value="$numsondageadmin" />';
  echo '<input type="hidden" name="meilleursujet" value="$meilleursujetexport" />';
  echo '<input type="image" name="exportpdf" value="Export en PDF" src="images/accept.png" alt="Export PDF"><br><br>';
  echo '</form>'."\n";
  // '<font color="#FF0000">'. _("Enter a meeting place!") .'</font><br><br>'."\n";
}

// TODO
if (isset($_POST["exportpdf_x"]) && !issetAndNoEmpty('lieureunion')) {
  echo '<font color="#FF0000">'. _("Enter a meeting place!") .'</font><br><br>'."\n";
}

echo '<a name="bas"></a>'."\n";
echo '<br><br>'."\n";

//fin de la partie GESTION et beandeau de pied
echo '</p>'."\n";
bandeau_pied_mobile();
echo '</form>'."\n";
echo '</body>'."\n";
echo '</html>'."\n";
