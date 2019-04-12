<?php

function statut_present_absent ($statut)
{
        $r = 'Erreur';
        switch($statut)
        {
            case 'F':
                $r = 'absent';
                break;
            case 'Q':
            case 'N':
            case 'A':
                $r = 'présent';
                break;
            case 'E':
                $r = 'exclu';
                break;
        }
    return $r;
}

function ordonneListeParticipants ( $tireurs)
{
    $order = array();
    foreach ($tireurs as $ref=>$val)
    {
	$order[$ref] = $val['ACCU']['Nom'] . ' ' . $val['ACCU']['Prenom'] . ' ' . $val['ACCU']['Club'] ; 
    }
    asort($order);     
    return array_keys($order);
}

function afficheListeParticipants( $xml )
{
    $tireurs = suitTireurs ( $xml );
    $ordre   = ordonneListeParticipants ( $tireurs);
    
    $head = "";
  //  $head .= ($ncol==1)?"<div class='tblhd'>\n<div></div>\n":"<div class='tblhd_multicol'>\n";
    $head .= "<table id='head' class='listeTireur'>\n";
    
    $r = "<table class='lst_nom'>";
    
    foreach ($ordre as $ref)
    {
	$r .= "<tr><td class='inscription_nom'>";

	$nom = $tireurs[$ref]['ACCU']['Nom'] . ' ' . $tireurs[$ref]['ACCU']['Prenom'];
	$nat = $tireurs[$ref]['ACCU']['Nation'];
	$ran = $tireurs[$ref]['ACCU']['Ranking'];
	$clu = $tireurs[$ref]['ACCU']['Club'];
        $pre = statut_present_absent ($tireurs[$ref]['ACCU']['St']);
	$r .= ' '.flag_icon($nat,'small').' ';
	$r .= $ran . ' ';
	$r .= (strlen($nom)>30)?fractureNom($nom):$nom;
	$r .= "</td>";
	$r .=  "<td class='inscription_status'> $pre </td>";
	$r .=  "<td class='inscription_rank'>   $ran </td>";
	$r .=  "<td class='inscription_club'>   $clu </td>";
	$r .= "</tr>\n";
    }
    $r .= "/<table>";
    return $r;
}
?>
