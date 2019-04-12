
<?php


define("NB_ROWS_TIREUR",     "2");

function isPouleFinished ( $pouleXml )
{
    $matches = $pouleXml->getElementsByTagName('Match');
    $no  = 0;
    $yes = 0;
    foreach ($matches as $m)
    {
	//	echo "inside match " .$m->localName . "->" . $m->getAttribute('ID') ;
	$tireurs = $m->getElementsByTagName('Tireur'); // EQUIPE 
	$s = array();
	$n = 0;
	foreach ($tireurs as $t)
	{
	    $s[$n++] = $t->getAttribute('Statut');
	}
	//	echo "statut:" . $s[0] . ":" . $s[1] . "<br>";
	if ( $s[0]=="V" || $s[0]=="D" || $s[0]=="A" || $s[0]=="E" ||
	     $s[1]=="V" || $s[1]=="D" || $s[1]=="A" || $s[1]=="E" )
	$yes++;
	else
	    $no++;
    }
    if ($yes==0)
	return -1; // not started
    if ($no>0)
	return 0;  // started, not finished
    return 1;      
}

function prepareTablePoule ($poule)
{
    $table = array();
    $matches = $poule->getElementsByTagName('Match');
    $tl      = $poule->getElementsByTagName('Tireur'); // We have a mix of results, needs filterring
    $lu      = array();  // Tireurs de la poule
    
    foreach ($tl as $t)
    if ($t->hasAttribute("NoDansLaPoule"))
    {
	$REF = $t->getAttribute('REF');
	$no = $t->getAttribute('NoDansLaPoule');
	$lu [$t->getAttribute("REF")] = $no;
	$table[$no] = array( 'REF' => $REF, 'Ma' => 0, 'Vi' => 0, 'TD' => 0, 'TR' => 0,
			     'Pl' => 0, 'Sco' => array(), 'Sta' => array() ); 
    }

    foreach ($matches as $m)
    {
	$t = $m->getElementsByTagName('Tireur'); // EQUIPE 
	$rf0     = $t[0]->getAttribute('REF');
	$rf1     = $t[1]->getAttribute('REF');
	$st0     = $t[0]->getAttribute('Statut');
	$st1     = $t[1]->getAttribute('Statut');
	$sc0     = $t[0]->getAttribute('Score');
	$sc1     = $t[1]->getAttribute('Score');
	$no0     = $lu[$rf0];
	$no1     = $lu[$rf1];

	$table[$no0]['Sta'][$no1]=$st0;
	$table[$no0]['Sco'][$no1]=$sc0;
	$table[$no1]['Sta'][$no0]=$st1;
	$table[$no1]['Sco'][$no0]=$sc1;

	if (is_numeric($sc0))
	{
	    $table[$no0]['TD'] += $sc0;
	    $table[$no1]['TR'] += $sc0;
	}
	if (is_numeric($sc1))
	{
	    $table[$no1]['TD'] += $sc1;
	    $table[$no0]['TR'] += $sc1;
	}
	
	if ($st0 == 'V')
	{
	    $table[$no0]['Vi']++;
	    $table[$no0]['Ma']++;
	}
	if ($st0 == 'D')
	{
	    $table[$no0]['Ma']++;
	}

	if ($st1 == 'V')
	{
	    $table[$no1]['Vi']++;
	    $table[$no1]['Ma']++;
	}
	if ($st1 == 'D')
	{
	    $table[$no1]['Ma']++;
	}
    }

    $f = array();
    for ($no = 1; $no <= count($table); $no++)
	$f[$no] = -1.0*computeVMfloat (0, $table[$no]['Vi'], $table[$no]['Ma'], $table[$no]['TD'], $table[$no]['TR']);

    asort($f);
    $pl = 1;
    $np = 0;
    $of = 999999999999;
    foreach ($f as $no => $v)
    {
	$np++;
	if ($v>$of)
	    $pl = $np;
	$table[$no]['Pl'] = $pl;
	$of = $v;
    }
    return $table;
}


function renderTourDePoules ($phaseXml, $tireurs,$arbitres, $detail)
{
    $rt = '';
    $scomax = $phaseXml->getAttribute('ScoreMax');
    $poules = $phaseXml->getElementsByTagName('Poule');
    //  $rt = "<article class='lespoules'>";
    foreach ($poules as $p)
    {
	$table = prepareTablePoule ($p);
	
	$pid = $p->getAttribute('ID');
	$pis = $p->getAttribute('Piste');
	$dat = $p->getAttribute('Date');
	$txt2 = explode(" ", $dat);
	$txt = (count($txt2)>1)? $txt2[1]: $dat;

	$r = "<div  class='poule_div'>";
	
	$r .= "<table class='poule_tab' style='border-collapse:collapse;'>";

	
	$info = "<span class='poule_arb'>Poule </span>$pid<span class='poule_arb'>";
	if (strlen($pis)>0)
	    $info .= " - piste </span>$pis ";
	if (strlen($txt)>0)
	    $info .= "<span class='poule_arb'> - </span>$txt";
	
	$al  = $p->getElementsByTagName('Arbitre');

	if (isset($al[0]) && (1 || $detail))
	{
	    $ref = $al[0]->getAttribute('REF');
	    $nom = $arbitres[$ref]['Nom'];
	    $info .= "<br><span class='poule_arb'>Arbitre: " . $arbitres[$ref]['Nom'] .' '. $arbitres[$ref]['Prenom'] . "</span>";
	}



	// NOM No
	$r .= "<tr><td colspan='2' class='poule_doc'>$info</td>";
	for ($no = 1 ; $no <= count($table); $no++)
	    $r .= "<td class='poule_not'>$no</td>";
	if ($detail)
	{
	    $r .= "<td class='poule_tit'>V/M</td>";
	    $r .= "<td class='poule_tit'>&permil;</td>";
	    $r .= "<td class='poule_tit'>TD</td>";
	    $r .= "<td class='poule_tit'>TR</td>";
	    $r .= "<td class='poule_tit'>Ind</td>";
	    $r .= "<td class='poule_tit'>Pl</td>";
	}
	$r .= "</tr>\n";
	for ($no = 1 ; $no <= count($table); $no++)
	{
	    $r .= "<tr><td class='poule_nom'>";
	    $ref = $table[$no]['REF'];
	    $nom = $tireurs[$ref]['ACCU']['Nom'] . ' ' . $tireurs[$ref]['ACCU']['Prenom'];
	    $nat = $tireurs[$ref]['ACCU']['Nation'];
	    $r .= ' '.flag_icon($nat,'').' ';

	    $r .= (strlen($nom)>30)?fractureNom($nom):$nom;
	    $r .=  "</td><td class='poule_nor'>$no</td>";
	    for ($co = 1 ; $co <= count($table); $co++)
	    {
		if ($co == $no)
		    $r .= "<td class='poule_dia'> </td>";
		else 
		{
		    $sta = (isset($table[$no]['Sta'][$co]))?$table[$no]['Sta'][$co]:' ';
		    $sco = (isset($table[$no]['Sta'][$co]))?$table[$no]['Sco'][$co]:' ';
		    switch($sta)
		    {
			case 'V': $r .= ($sco == $scomax)? "<td class='poule_sco'>V</td>" : "<td class='poule_sco'>V$sco</td>"; break;
			case 'E': $r .= "<td class='poule_sco poule_exclusion'>Ex</td>"; break;
 			case 'A': $r .= "<td class='poule_sco poule_abandon'>Ab</td>"; 	 break;
			case 'D': $r .= "<td class='poule_sco'>$sco</td>";  		break;
			default:  $r .= "<td class='poule_sco'></td>";
		    }
		}
	    }

	    if ($table[$no]['Ma']>0)
	    {
		
		$ma = $table[$no]['Ma'];
		$vi = $table[$no]['Vi'];
		$td = $table[$no]['TD'];
		$tr = $table[$no]['TR'];
		$pl = $table[$no]['Pl'];
		$ind = sprintf("%+d",$td-$tr);
		
		$txt =  sprintf("%3.0f",1000*$vi / $ma);
		if ($detail)
		{
		    $r .= "<td class='poule_res'>$vi/$ma</td>";
		    $r .= "<td class='poule_res'>$txt</td>";
		    $r .= "<td class='poule_res'>$td</td>";
		    $r .= "<td class='poule_res'>$tr</td>";
		    $r .= "<td class='poule_res'>$ind</td>";
		    $r .= "<td class='poule_res'>$pl</td>";
		}
		$r .= "</tr>";
	    }
	    else
	    {
		if ($detail)
		{
		    $r .= "<td class='poule_res'></td>"; // Vi
		    $r .= "<td class='poule_res'></td>"; // V/M
		    $r .= "<td class='poule_res'></td>"; // TD
		    $r .= "<td class='poule_res'></td>"; // TR
		    $r .= "<td class='poule_res'></td>"; // IND
		    $r .= "<td class='poule_res'></td>"; // Pl
		}
		$r .= "</tr>";

	    }
	    $r .= "</tr>";
	}
	$r .= "</table></div>\n";
	$rt .= $r;
    }
    //   $rt .= "</article>";
    return $rt;
}

function etatTourDePoulesFinished ($phaseXml)
{
    $poules = $phaseXml->getElementsByTagName('Poule');
    //    echo "<h1>Is tour " . $phaseXml->getAttribute('ID') , " finished?<br></h1>";
    $not_started  = 0;
    $started      = 0;
    $finished     = 0;
    foreach ($poules as $p)
    {
	$f = isPouleFinished ($p);
	//	echo "Poule ID:" . $p->getAttribute('ID') .  "=$f <br>";
	switch ($f)
	{
	    case -1: $not_started++; break;
	    case 0 : $started++;     break;
	    case +1: $finished++;    break;
	}
    }

    //    echo "Started:$started NotSta:$not_started Finished:$finished<br>";
    $r=0;
    if ($not_started==0 && $started==0 && $finished>0)
	$r=1; // all finished
    if ($started==0 && $finished==0)
	$r=-1; // no started

    //   echo "Result=$r<br>";
    return $r;  // on going
}

function getPhaseEnCoursID ($topXml)
{
    $phasesXml = $topXml->getElementsByTagName('Phases');
    return $phasesXml[0]->getAttribute('PhaseEnCours');
}

function getAllPhases ($topXml)
{
    $phasesXml = $topXml->getElementsByTagName('Phases');
    $phases = array();
    foreach ($phasesXml[0]->childNodes as $node)
    if ($node->hasAttributes()) 
	$phases[] = $node;
    return $phases;
}

function countTourDePoules ($topXml )
{
    $phases = getAllPhases($topXml);
    $cnt    = 0;
    foreach ($phases as $phase)
    if ($phase->localName =='TourDePoules')
	$cnt++;
    return $cnt;
}

function getArbitres($xml)
{
    $r = array();
    $arbitres = $xml->getElementsByTagName('Arbitre');
    foreach ($arbitres as $a)
    {
	if ($a->hasAttribute('ID'))
	{
	    $ref = $a->getAttribute('ID');
	    $r[$ref] = array(
		'Nom'    => $a->getAttribute('Nom'),
		'Prenom' => $a->getAttribute('Prenom'),
		'Club'   => $a->getAttribute('Club'),
		'Nation' => $a->getAttribute('Nation'));
	}
    }
    return $r;
}

function affichePoules($xml, $tour,$detail)
{
    $r = '';
    $head = '';
    $head .= "<div class='tblhd_top' onclick='mickey()'><span class='tbl_banner'>&#9776; POULES</span><br>";
    $head .= "<div class='tblhdpou'>\n";
    $fixed_height = isset($_GET['scroll'])?'fhpou':'';

    $head .= "<div id='scrollme' class='listePoules $fixed_height'>\n";

    $phases  = getAllPhases($xml);
    $tireurs = suitTireurs ($xml);
    $arbitres = getArbitres($xml);
    $cnt = 0;
    foreach ($phases as $phase)
    {
        if ($phase->localName =='TourDePoules')
        {
            $cnt++;
            if ($tour == $cnt)
                $r .= renderTourDePoules($phase,$tireurs,$arbitres,$detail);
        }
    }
    $foot = '</div></div></div>';
    return $head . $r . $foot;
}

function decideOrdre ($topXml)
{
    $encours = getPhaseEnCoursID ($topXml);
    //    $tot     = countTourDePoules ($topXml);
    $phases  = getAllPhases($topXml);

    // These two variables decide how we sort the displayed table
    $r = array('PHA' => 0,      // Phase 0
	       'TAG' => 'ABC'); // Alphabetic ordering

    if ($encours=='')
	echo "Oups, vide alors<br>";
    
    foreach ($phases as $p)
    {
	$pid = $p->getAttribute('PhaseID');
	$pha = $p->localName;
	switch($pha)
	{
	    case 'TourDePoules':
	    $etat = etatTourDePoulesFinished($p);
	    if      ($encours>$pid)  		  // Fini et classé par BellePoule
		$r = array('PHA' => $pid, 'TAG' => 'Rf');
	    else if (($encours==$pid||$encours=='') && $etat==1) // Fini, mais pas classé par BellePoule
		$r = array('PHA' => $pid, 'TAG' => 'VM');
	    else if ($encours==$pid && $etat==0) // En cours
		$r = array('PHA' => $pid, 'TAG' => 'VM');
	    else if ($encours==$pid && $etat==-1) //Pas commencé
		$r = array('PHA' => $pid, 'TAG' => 'Po');
	    else if ($encours==$pid-1) //Pas commencé, en cours de composition
	    {}// On garde l'ancienne façon de classer
	    else
	    {} // On garde l'ancienne façon de classer
	    break;

	    case 'PointDeScission':
	    // On garde l'ancienne façon de classer
	    break;

	    case 'PhaseDeTableaux':
	    if ($encours>$pid)
		$r = array('PHA' => $pid, 'TAG' => 'RangFinal');
	    break;
	    
	    case 'ClassementGeneral':
	    if ($encours==$pid)
		$r = array('PHA' => $pid, 'TAG' => 'RangFinal');
	    break;
	}
    }
    return $r;
}

function addContenu(&$a, $phase, $titre, $element,  $attribut)
{
    if (count($a) < 1)
    {
	$a['TITRES']   =array();
	$a['ELEMENTS'] =array();
	$a['PHASES']   =array();
	$a['ATTRIBUTS']=array();
    }
    array_push ($a['TITRES'],   $titre);
    array_push ($a['ELEMENTS'], $element);
    array_push ($a['PHASES'],   $phase);
    array_push ($a['ATTRIBUTS'],$attribut);
}

function contenuListeTireurs()
{
    $r = array();
    $acc='ACCU';
    addContenu($r,$acc,"Statut","Present","class='VR'");
    addContenu($r,$acc,"Athlete","Flag","");
    addContenu($r,$acc,"Nom","NomPrenom","class='VR'");
    addContenu($r,$acc,"Ranking","Ranking", "class='B RIG VR'");
    addContenu($r,$acc,"Club","Club","class='VR'");
    return $r;
}

function contenuClassementFinal()
{
    $r = array();
    $acc='ACCU';
    //    addContenu($r,$acc,"Athlete","line","");
    addContenu($r,$acc,"Place","PlaTab", "class='B RIG VR'");
    addContenu($r,$acc,"Athlete","Flag","");
    addContenu($r,$acc,"Nom","NomPrenom","class='VR'");
    addContenu($r,$acc,"Club","Club","class='VR'");
    if (isset($_GET['dbg']))
    {
	
	addContenu($r,$acc,"Out","out", "class='B RIG VR'");
	addContenu($r,$acc,"tab","intab", "class='B RIG VR'");
	addContenu($r,$acc,"tab","PlaTabTri", "class='B RIG VR'");
	addContenu($r,$acc,"tdo","tourdone", "class='B RIG VR'");
    }
    addContenu($r,$acc,"Piste","PiTa", "class='B RIG VR'");
    addContenu($r,$acc,"Heure","DaTa", "class='B RIG VR'");
    return $r;
}


function decideContenu ($topXml)
{
    $acc='ACCU';
    $encours = getPhaseEnCoursID ($topXml);
    $tot     = countTourDePoules ($topXml);
    $phases  = getAllPhases($topXml);

    // These variables decide how we sort the displayed table
    $r = array();

 
    $cnt=0;
    $need_acc = 0;
    $need_tab = 0;
    foreach ($phases as $p)
    {
	$pid = $p->getAttribute('PhaseID');
	$pha = $p->localName;
	switch($pha)
	{
	    case 'PhaseDeTableaux':
	    //$need_tab = $pid;
	    break;
	    
	    case 'TourDePoules':
	    $cnt++;
	    $etat = etatTourDePoulesFinished($p);
	    if ($cnt>1 && $tot>1)
	    {
		// Pas le premier de plusieurs tours
		if      ($encours>$pid)  		  // Fini et classé par BellePoule
		{
                       addContenu($r,$acc,"Athlete","Flag","");
    addContenu($r,$acc,"Nom","NomPrenom","class='VR'");
		    addContenu($r, $pid, "Tour $cnt fini", "V/M", "class='MID'");
		    addContenu($r, $pid, "Tour $cnt fini", "TD-TR", "class='MID'");
		    addContenu($r, $pid, "Tour $cnt fini", "Pl", "class='RIG VR'");
		    $need_acc = 1;
		}
		else if ($encours==$pid && $etat==1) // Fini, mais pas classé par BellePoule
		{
                       addContenu($r,$acc,"Athlete","Flag","");
    addContenu($r,$acc,"Nom","NomPrenom","class='VR'");
		    addContenu($r, $pid, "Tour $cnt", "V/M",    "class='MID'");
		    addContenu($r, $pid, "Tour $cnt", "TD-TR",  "class='VR'");
		    $need_acc=1;
		}
		else if ($encours==$pid && $etat==0) // En cours
		{
                       addContenu($r,$acc,"Athlete","Flag","");
    addContenu($r,$acc,"Nom","NomPrenom","class='VR'");
		    addContenu($r, $pid, "Tour $cnt prov.", "Po", "");
		    addContenu($r, $pid, "Tour $cnt prov.", "Pi", "");
		    addContenu($r, $pid, "Tour $cnt prov.", "V/M",    "class='MID'");
		    addContenu($r, $pid, "Tour $cnt prov.", "TD-TR",  "class='VR'");
		    $need_acc=1;
		}
		else if ($encours==$pid && $etat==-1) //Pas commencé
		{
                       addContenu($r,$acc,"Athlete","Flag","");
    addContenu($r,$acc,"Nom","NomPrenom","class='VR'");
		    addContenu($r, $pid, "Tour $cnt", "Po", "class='B'");
		    addContenu($r, $pid, "Tour $cnt", "Pi", "class='B'");
		    addContenu($r, $pid, "Tour $cnt", "Da", "class='B VR'");
		}
		else if ($encours==$pid-1) //Pas commencé, en cours de composition
		{
                       addContenu($r,$acc,"Athlete","Flag","");
    addContenu($r,$acc,"Nom","NomPrenom","class='VR'");
		    addContenu($r, $pid, "Tour $cnt", "Po", "class='B VR'");
		}
		else // Pas commencé, même pas en composition
		{
                       addContenu($r,$acc,"Athlete","Flag","");
    addContenu($r,$acc,"Nom","NomPrenom","class='VR'");
		    addContenu($r, $pid, "Tour $cnt", "-", "class='VR'");
		}
	    }
	    else  if ($cnt<$tot)
	    {
		// Plusieur tours, pas le dernier
		if  ($encours>$pid+1)  		  // Fini et classé par BellePoule, et le tour d'après a commencé
		{
                       addContenu($r,$acc,"Athlete","Flag","");
    addContenu($r,$acc,"Nom","NomPrenom","class='VR'");
		    addContenu($r, $pid, "Tour $cnt fini", "V/M", "class='MID'");
		    addContenu($r, $pid, "Tour $cnt fini", "TD-TR", "class='MID'");
		    addContenu($r, $pid, "Tour $cnt fini", "Pl", "class='RIG VR'");
		}
		else if ($encours==$pid+1)  		  // Fini et classé par BellePoule, mais le tour d'après pas commencé
		{
                       addContenu($r,$acc,"Athlete","Flag","");
    addContenu($r,$acc,"Nom","NomPrenom","class='VR'");
		    addContenu($r, $pid, "Tour $cnt", "V/M", "class='MID'");
		    addContenu($r, $pid, "Tour $cnt", "TD-TR", "class='MID'");
		    addContenu($r, $pid, "Tour $cnt", "Pl", "class='RIG VR'");
		    $need_acc=1;
		}
		else if ($encours==$pid && $etat==1) // Fini, mais pas classé par BellePoule
		{
                       addContenu($r,$acc,"Athlete","Flag","");
    addContenu($r,$acc,"Nom","NomPrenom","class='VR'");
		    addContenu($r, $pid, "Tour $cnt", "V/M", "class='MID'");
		    addContenu($r, $pid, "Tour $cnt", "TD-TR", "class='VR'");
		    $need_acc=1;
		}
		else if ($encours==$pid && $etat==0) // En cours
		{
                       addContenu($r,$acc,"Athlete","Flag","");
    addContenu($r,$acc,"Nom","NomPrenom","class='VR'");
		    addContenu($r, $pid, "Tour $cnt", "Po", "class='MID'");
		    addContenu($r, $pid, "Tour $cnt", "V/M", "class='MID'");
		    addContenu($r, $pid, "Tour $cnt", "TD-TR", "class='VR'");
		}
		else if ($encours==$pid && $etat==-1) //Pas commencé
		{
                       addContenu($r,$acc,"Athlete","Flag","");
    addContenu($r,$acc,"Nom","NomPrenom","class='VR'");
		    addContenu($r, $pid, "Tour $cnt", "RI", "class='RIG'");
		    addContenu($r, $pid, "Tour $cnt", "Po", "class='B'");
		    addContenu($r, $pid, "Tour $cnt", "Pi", "class='B'");
		    addContenu($r, $pid, "Tour $cnt", "Da", "class='B VR'");
		}
		else if ($encours==$pid-1) //Pas commencé, en cours de composition
		{
                       addContenu($r,$acc,"Athlete","Flag","");
    addContenu($r,$acc,"Nom","NomPrenom","class='VR'");
		    addContenu($r, $pid, "Tour $cnt", "RI", "class='RIG'");
		    addContenu($r, $pid, "Tour $cnt", "Po", "class='B'");
		    addContenu($r, $pid, "Tour $cnt", "Pi", "class='B'");
		    addContenu($r, $pid, "Tour $cnt", "Da", "class='B VR'");
		}
		else // Pas commencé, même pas en composition
		{
                       addContenu($r,$acc,"Athlete","Flag","");
    addContenu($r,$acc,"Nom","NomPrenom","class='VR'");
		    addContenu($r, $pid, "Tour $cnt", "-", "class='VR'");
		}
	    }
	    else if ($cnt==1 && $tot==1)
	    {
		// Un seul tour
		if      ($encours>$pid)  		  // Fini et classé par BellePoule
		{
		    addContenu($r, $acc, "Total",   "Pl", "class='RIG B VR'");
                       addContenu($r,$acc,"Athlete","Flag","");
    addContenu($r,$acc,"Nom","NomPrenom","class='VR'");
		    addContenu($r, $pid, "Poule", "Po", "class='RIG VR'");
		    addContenu($r, $acc, "Total",   "V/M", "class='MID'");
		    addContenu($r, $acc, "Total",   "&permil;", "class='RIG'");
		    addContenu($r, $acc, "Total",   "TD-TR", "class='MID'");
		    addContenu($r, $acc, "Total",   "Ind", "class='RIG VR'");
		}
		else if (($encours==$pid||$encours=='') && $etat==1) // Fini, mais pas classé par BellePoule
		{
		    addContenu($r, $acc, "Total",   "Pl", "class='RIG B VR'");
                       addContenu($r,$acc,"Athlete","Flag","");
    addContenu($r,$acc,"Nom","NomPrenom","class='VR'");
		    addContenu($r, $pid, "Poule", "Po", "class='RIG VR'");
		    addContenu($r, $acc, "Total",   "V/M", "class='MID'");
		    addContenu($r, $acc, "Total",   "&permil;", "class='RIG'");
		    addContenu($r, $acc, "Total",   "TD-TR", "class='MID'");
		    addContenu($r, $acc, "Total",   "Ind", "class='RIG VR'");
		}
		else if (($encours==$pid||$encours=='') && $etat==0) // En cours
		{
		    addContenu($r, $acc, "Total prov.",   "Pl", "class='RIG B VR'");
                       addContenu($r,$acc,"Athlete","Flag","");
    addContenu($r,$acc,"Nom","NomPrenom","class='VR'");
		    addContenu($r, $acc, "Poule prov.", "Po", "class='MID'");
		    addContenu($r, $acc, "Poule prov.", "Pi", "class='MID '");
		    addContenu($r, $acc, "Poule prov.", "Da", "class='MID VR'");
		    addContenu($r, $acc, "Total prov.",   "V/M", "class='MID'");
		    addContenu($r, $acc, "Total prov.",   "&permil;", "class='RIG'");
		    addContenu($r, $acc, "Total prov.",   "TD-TR", "class='MID'");
		    addContenu($r, $acc, "Total prov.",   "Ind", "class='RIG VR'");
		}
		else if (($encours==$pid||$encours=='') && $etat==-1) //Pas commencé
		{
                       addContenu($r,$acc,"Athlete","Flag","");
    addContenu($r,$acc,"Nom","NomPrenom","class='VR'");
		    addContenu($r, $pid, "Poule", "RI", "class='RIG'");
		    addContenu($r, $pid, "Poule", "Po", "class='RIG'");
		    addContenu($r, $pid, "Poule", "Pi", "class='RIG B'");
		    addContenu($r, $pid, "Poule", "Da", "class='RIG B VR'");
		}
		else if ($encours==$pid-1) //Pas commencé, en cours de composition
		{
                       addContenu($r,$acc,"Athlete","Flag","");
    addContenu($r,$acc,"Nom","NomPrenom","class='VR'");
		    addContenu($r, $pid, "Poule",   "RI", "class='RIG'");
		    addContenu($r, $pid, "Poule", "Po", "class='RIG B VR'");
		    addContenu($r, $pid, "Poule", "Pi", "class='RIG B VR'");
		    addContenu($r, $pid, "Poule", "Da", "class='RIG B VR'");
		}
		else // Pas commencé, même pas en composition
		{
                       addContenu($r,$acc,"Athlete","Flag","");
    addContenu($r,$acc,"Nom","NomPrenom","class='VR'");
		    addContenu($r, $pid, "Poule",   "-", "class='VR'");
		}
	    }
	    
	    break;

	    case 'PointDeScission':
	    break;

	    case 'PhaseDeTableaux':
	    break;
	    
	    case 'ClassementGeneral':
	    break;
	}
    }
    if ($need_acc && !$need_tab)
    {
	addContenu($r, $acc, "Total",   "Pl", "class='RIG B VR'");
           addContenu($r,$acc,"Athlete","Flag","");
    addContenu($r,$acc,"Nom","NomPrenom","class='VR'");
	addContenu($r, $acc, "Total",   "V/M", "class='MID'");
	addContenu($r, $acc, "Total",   "&permil;", "class='RIG'");
	addContenu($r, $acc, "Total",   "TD-TR", "class='MID'");
	addContenu($r, $acc, "Total",   "Ind", "class='RIG'");
    }
    else if ($need_acc && $need_tab)
    {
	addContenu($r, $acc, "Tabl.",   "Pl", "class='RIG B VR'");
           addContenu($r,$acc,"Athlete","Flag","");
    addContenu($r,$acc,"Nom","NomPrenom","class='VR'");
	addContenu($r, $acc, "Total",   "V/M", "class='MID'");
	addContenu($r, $acc, "Total",   "&permil;", "class='RIG'");
	addContenu($r, $acc, "Total",   "TD-TR", "class='MID'");
	addContenu($r, $acc, "Total",   "Ind", "class='RIG VR'");
    }
    else if ($need_tab)
    {
	addContenu($r, $need_tab, "Tab.", "Pl", "class='RIG B VR'");
    }

    addContenu($r, $acc, "Statut",   "St", "");
    return $r;
}


function renderPair ( $col, $tag )
{
    $out = "";
    $out .= "<$tag";
    $out .= (isset($col['ATT']))? " ".$col['ATT'] : "";
    $out .= ">" . $col['TXT'] . "</$tag>";
    return $out;
}

function renderClassementCombineHead ($r,$multicol)
{
    $labels = array (
	'RI'      => 'Entrée',
	'Nation'  => 'Nation',
	'Ranking' => 'Rang',
	'NomPrenom'  => 'Nom',
	'Prenom'  => 'Prénom',
	'Nom'     => 'Nom',
	'Flag'    => '',
	'PlaTab'  => 'Place',
	'Po'      => 'Poule',
	'Pi'      => 'Piste',
	'Da'      => 'Heure',
	'PiTa'    => 'Piste',
	'DaTa'    => 'Heure',
	'TR'      => 'TR',
	'TD'      => 'TD',
	'Vi'      => 'V',
	'Ma'      => 'M',
	'St'      => 'Status',
	'Pl'      => 'Place',
	'Ind'     => 'Ind.',
	'TD-TR'   => 'TD-TR'    );
    
    $out = "";

    if ($multicol)
        $out .= "<thead class='multicol'>\n";
    else
        $out .= "<thead class='monocol'>\n";
    
    $out .= "<tr>\n";
    for ($k=0; $k<count($r['ELEMENTS']); $k++)
    {	
        $out .= "<th ". $r['ATTRIBUTS'][$k] . ">";
        $out .= ($multicol)?"":"<div class='tblhead'>";
        
        $e = $r['ELEMENTS'][$k];
        
        if (isset($labels[$e]))
            $e = $labels[$e];
        
        $out .= $e;
        $out .= ($multicol)?"</th>":"</div></th>";
    }
    $out .= "</tr>\n</thead>\n";
    
    $out .= "<tbody id='tblbdy'>\n";
    
    if (!$multicol)
        for ($d = 0; $d<0*2; $d++) // Insert dummy rows hidden by fixed header
    {
        $out .= "<tr><td>X</td>";
	/*        for ($k=0; $k<count($r['ELEMENTS']); $k++)
           {	
           $out .= "<td ". $r['ATTRIBUTS'][$k] . ">";
           $e = $r['ELEMENTS'][$k];
           if (isset($labels[$e]))
           $e = $labels[$e];
           $out .= $e;
           $out .= "</td>";
           }
	 */        $out .= "</tr>\n";
    }
    return $out;
}



function computeVMfloat ($ph, $v, $m, $td, $tr )
{
    if ($m>0)
    {
	$vm  = $v  / $m;
	$ind = 500 + $td - $tr;
	$str = sprintf ("%06.4f%03d%03d",$ph+$vm,$ind,$td);
	//	echo "$str<br>";
    }
    else $str="-1.0000000000";
    return $str;
}


function recomputeClassement (&$a)
{
    $order = array();
    foreach ($a as $ref=>$val)
    $order[$ref] = isset($val['ACCU']['Fl'])?$val['ACCU']['Fl']:1;
    
    asort($order);
    $ligne = 1;
    $place = 1;
    $avant = 0;
    foreach ($order as $id => $val)
    {
	//	echo "LINE:$ligne VAL:$val <br>";
	if ($val != $avant)
	    $place = $ligne;
	$a[$id]['ACCU']['Pl2'] = ($val==INF)?INF:$place;
	$ligne++;
	$avant = $val;
    }
}
function mixteMaleFemale($xml)
{
    $a = suitTireursTableau($xml);  // $a  = suitTireurs($xml);
    
    $femmes      = 0;
    $hommes      = 0;
    foreach ($a as $key=>$val)
    {
        if ($val['ACCU']['St'] == 'F')
            $femmes++;
        if ($val['ACCU']['Sexe'] == 'M')
            $hommes++;
    }
    if ($hommes>0 && $femmes>0)
        return 'FM';
    if ($hommes>0 && $femmes==0)
        return 'M';
    if ($hommes==0 && $femmes>0)
        return 'F';
    return 'E';
}

function drapeauxPodium ($xml)
{
    $a = suitTireursTableau($xml);  // $a  = suitTireurs($xml);
    $order = array();
    foreach ($a as $ref=>$val)
    $order[$ref] = isset($val['ACCU']['PlaTabTri'])?1*$val['ACCU']['PlaTabTri']:"INF";

    asort($order);
    
    $r='';
    $cnt = 1;
    foreach ($order as $key=>$val)
    {
        $r .= flag_icon($a[$key]['ACCU']['Nation'],"huge podium$cnt");
        $cnt++;
        if ($cnt>4)
            break;
    }
    return $r;
}
function afficheClassementPoules ($xml, $ncol, $abc, $etape,$titre)
{
    $tri     = decideOrdre($xml);
    if ($abc)
    {
        $tri['TAG'] = 'ABC'; // Force alpabetic sort
    }
    
    if ($etape == -1)
    {
        $tri['TAG'] = 'Ta'; // Sort by tableau classement    
    }
    $nb_bidouilles = 1;
    if ($etape==0)
        $contenu = contenuListeTireurs();
    else if ($etape > 0)
        $contenu = decideContenu($xml);
    else
    {
        $contenu = contenuClassementFinal();
        $nb_bidouilles = 2;
    }
    $a = suitTireursTableau($xml);  // $a  = suitTireurs($xml);
    
    $cnt_present = 0;
    $cnt_total   = 0;
    $hommes      = 0;
    foreach ($a as $key=>$val)
    {
        $cnt_total++;
        if ($val['ACCU']['St'] != 'F')
            $cnt_present++;
	if ($val['ACCU']['Sexe'] == 'M')
            $hommes++;
    }
    if ($etape==0)
    {
        if ($hommes>0)
            $titre = "$cnt_present PRÉSENTS SUR $cnt_total INSCRITS";
        else
            $titre = "$cnt_present PRÉSENTES SUR $cnt_total INSCRITES";
    }
    
    $order = array();
    foreach ($a as $ref=>$val)
    {
        switch ($tri['TAG'])
        {
            case 'ABC':
            $order[$ref] = $val['ACCU']['Nom'] . " ". $val['ACCU']['Prenom'];
            break;

            case 'Po':
            $order[$ref] = isset($val[$tri['PHA']]['Po'])?$val[$tri['PHA']]['Po']:"INF";
            break;

            case 'Ta':
            $order[$ref] = isset($val['ACCU']['PlaTabTri'])?1*$val['ACCU']['PlaTabTri']:"INF";
            break;

            default:
            $order[$ref] = isset($val['ACCU']['Fl'])?$val['ACCU']['Fl']:1;
        }
    }

    $tireurs = getTireurList($xml);
    asort($order);

    /////////////////////////////
    // RECOMPUTE CLASSEMENT HERE
    ////////////////////////////
    recomputeClassement($a);

    $q = qualifiesPourTableau ($xml);
    
    $out = "";
    $ids = array_keys($order);
    
    $nb   = count($ids);
    $div = ($nb / $ncol);
    $cei = ceil($div);
    $npc  = intval($cei);
    //   echo "NB:$nb NCOL:$ncol DIV:$div CEI:$cei NPC:$npc<br>";
    $sta = 0;
    $sto = $npc; 
    $line=1;
    for ($col = 0; $col < $ncol; $col++)
    {
        $head = "";
        $head .= "<div class='tblhd_top' onclick='mickey()'><span class='tbl_banner'>&#9776; $titre</span><br>";
        $head .= "<div class='tblhd'><div></div>\n";
        $fixed_height = isset($_GET['scroll'])?'fh':'';

        $head .= "<table id='scrollme' class='listeTireur $fixed_height'>\n";
        $head .= renderClassementCombineHead($contenu, $ncol>1);
        $head .= "\n";
        $body = "";     
        $foot    = "</table></div></div>";

        $pair    = "impair";
        $opair   = "pair";
        $oqual   = "O";
        
        //    var_dump($tri);
        //    echo "SORT PHASE " . $toks['SORT_PHASE'] . " FIELD " . $toks['SORT_FIELD'] . "<br>";
        //    $order = $acc['RK']; // Start with latested estimated ranking
	for ($bidouille=0;$bidouille<$nb_bidouilles; $bidouille++) // affiche ceux qui sont encore en jeu, puis les éliminés
            for ($idx = $sta; $idx < $sto; $idx++)
        {
            $id = $ids[$idx];
            $elimine = 0;
            $tireur = $tireurs[$id];

            if ($etape!=0)
            {
                if ($a[$id]['ACCU']['Ex'])
                    continue; // Don't display exported 

                if ($a[$id]['ACCU']['St']=='F')
                    continue; // Don't  display failed to show up
            }            
            
            if ($etape == 0)
            {
                $qual = ($a[$id]['ACCU']['St'] == 'F')?'O':'Q';
            }
            
            if ($etape == 1)
            {
                if ($tireur->getAttribute('Statut') == 'E')
                    $qual = 'E';
                else
                    if ( $q[$id])
			$qual = 'Q';
                else 
                    $qual = 'O';
            }
            
            if ($etape == -1)
            {
                if ( $a[$id]['ACCU']['out'] || !$a[$id]['ACCU']['intab'])
                    $qual = 'O';
                else
                    $qual = 'Q';
            }

            if ($qual != $oqual)
                $pair = "impair";
            else
                $pair = $pair == "pair" ? "impair" : "pair";

            $oqual = $qual;
            
            if ($etape==0)
                $style = $pair . $qual.'L';
            
            if ($etape == 1)
                $style = $pair . $qual;

            if ($etape==-1)
                $style = $pair . $qual.'C';
            
            if ($nb_bidouilles>1)
            {
                if ($bidouille == 0)
                {
                    if ($a[$id]['ACCU']['out'])
                        continue;
                }
                else 
                {
                    if (!$a[$id]['ACCU']['out'])
                        continue;  
                }
            }
            $body .= "<tr class='$style'>";

            //	echo "Rank:" .$place. " REF:" .$id. "<br>";
            for ($k=0; $k<count($contenu['ELEMENTS']); $k++)
            {
                $dat = $contenu['ELEMENTS'][$k]; // Data to print
                $pha = $contenu['PHASES'][$k];   //  phase, if relevant
                $att = $contenu['ATTRIBUTS'][$k]; 

                $txt = "";
                switch ($dat)
                {
                    case 'Pl' :
                    //		$place = isset($a[$id][$pha]['Pl'])?$a[$id][$pha]['Pl']:INF;
                    // Always use recomputed place, not Bellepoule one, since it changes after tableau
                    $place = isset($a[$id][$pha]['Pl2'])?$a[$id][$pha]['Pl2']:INF;
                    //		echo "YYY ID:$id PHA:$pha $place XXX<br>";
                    if ($place == "PL2") // || $place == INF)
                        $place = $a[$id]['ACCU']['Pl2'];
                    $txt = ($place<INF)?$place:"-";
                    if ($tireur->getAttribute('Statut') == POULE_STATUT_EXPULSION)
                        $txt = '-';

                    break;
                    case 'NomPrenom' :
                    $txt = isset($a[$id][$pha]['Nom'])?$a[$id][$pha]['Nom']:"-";
                    $txt .= ' ';
                    $txt .= isset($a[$id][$pha]['Prenom'])?$a[$id][$pha]['Prenom']:"-";
                    break;

                    case 'Present' :
                    $txt  = statut_present_absent ($a[$id]['ACCU']['St'],$a[$id]['ACCU']['Sexe']);
                    break;
                    
                    case 'out' :
                    $txt = '?';
                    if (isset($a[$id][$pha]['out']))
                        $txt = $a[$id][$pha]['out'];
		    break;

                    case 'Flag':
                    $nat = $a[$id]['ACCU']['Nation'];
                    $txt = flag_icon($nat,'small');
                    break;

                    case 'PlaTab':
                    $platab    = isset($a[$id][$pha][$dat])?$a[$id][$pha][$dat]:INF;
                    $platabtri = isset($a[$id][$pha]['PlaTabTri'])?$a[$id][$pha]['PlaTabTri']:INF;
                    $sorti     = !(!$a[$id][$pha]['out'] && $a[$id]['ACCU']['St'] == 'Q');
                    $intab     = isset($a[$id][$pha]['intab'])?$a[$id][$pha]['intab']:0;
                    $txt       = isset($a[$id][$pha][$dat])?$a[$id][$pha][$dat]:'-';
                    $T = floor($platabtri/10000.0); // Tableau atteint
                    if ($sorti && $intab)
                    {
                        $rang_max = floor($T/2);      // Rang maximum
			
                        if ($platab < $rang_max)
                            $txt = "(".($rang_max+1)."&hellip;$T)";
                        else if (isset($a[$id][$pha]['tourdone']) && !$a[$id][$pha]['tourdone'])
                            $txt = "(".($rang_max+1)."&hellip;$T)"; 
                    }
                    else if (!$sorti && $intab)
                        $txt = "T".$T.""; //($platab) prov";
                    if ($elimine) $txt='-';
                    if ($txt==INF)
                        $txt = 'Expuls.'; 
                    break;
                    
                    case 'line':
                    $txt = $line;
                    $line++;
                    break;
                    
                    case 'PlaTabTri':
                    case 'intab':
                    case 'tourdone':
                    case 'Pl2' :
                    case 'RI' :
                    case 'Club' :
                    case 'Nation' :
                    case 'Ranking' :
                    case 'Prenom' :
                    case 'Nom' :
                    case 'Fl' :
                    case 'TR' :
                    case 'TD' :
                    case 'Vi' :
                    case 'Ma' :
                    case 'Ph' :
                    case 'Ind' :
                    case 'TD-TR' :
                    $txt = isset($a[$id][$pha][$dat])?$a[$id][$pha][$dat]:"-";
                    if ($elimine) $txt='-';
                    break;

                    case 'Po' : 
                    case 'Pi' :
                    case 'PiTa' :
                    $txt = '&nbsp;&nbsp;&nbsp;&nbsp;';
                    if (isset($a[$id][$pha][$dat]))
                        $txt = $a[$id][$pha][$dat];
                    break;
                    
                    case 'DaTa': // Date 
                    case 'Da': // Date 
                    $txt3 = isset($a[$id][$pha][$dat])?$a[$id][$pha][$dat]:"&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
                    $txt2 = explode(" ", $txt3);
                    $txt  = isset($txt2[1])?$txt2[1]:$txt3;
                    if ((!isset($a[$id][$pha]['PiTa']) || $a[$id][$pha]['PiTa']=='') && $txt!='' && (isset($a[$id][$pha][$dat])))                        
                        $txt = "dès $txt";
                    if ($elimine) $txt='-';
                    break;

                    case '&permil;' :
                    if (isset($a[$id][$pha]['Ma']) && isset($a[$id][$pha]['Vi']) && !$elimine)
                    {
                        $ma = $a[$id][$pha]['Ma'];
                        $vi = $a[$id][$pha]['Vi'];
                        if ($ma>0)
                            $txt .=  sprintf("%3.0f",1000*$vi / $ma);
                        else
                        {
                            $txt .= "-";
                            $elimine = 1;
                        }
                    }
                    else
                    {
                        $txt = "-";
                        $elimine = 1;
                    }
                    break;

                    case 'V/M' :
                    if (isset($a[$id][$pha]['Ma']) && isset($a[$id][$pha]['Vi']) && !$elimine)
                    {
                        $ma = $a[$id][$pha]['Ma'];
                        $vi = $a[$id][$pha]['Vi'];
                        if ($ma>0)
                            $txt .=  $vi . "/".$ma;
                        else
                        {
                            $txt .= "-";
                            $elimine = 1;
                        }
                    }
                    else
                    {
                        $txt = "-";
                        $elimine = 1;
                    }
                    break;


                    case '-' :
                    $txt = '-';
                    break;


                    case 'St':
                    if (isset($a[$id][$pha]['Pl']) && $a[$id][$pha]['Pl'] == "PL2")
                        $sta = 'V';
                    else
                        $sta = $a[$id][$pha]['St'];
                    switch( $sta )
                    {
                        case 'V':
                        $txt = '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'; // Verification
                        break;
                        case STATUT_PRESENT :
                        $txt = 'Qual.';
                        break;
                        case STATUT_ELIMINE: 
                        $txt = 'Élim.';
                        break;
                        case POULE_STATUT_ABANDON: 
                        if ($q[$id])
                            $txt = 'Qual.';
                        else
                            $txt = 'Abandon';
                        break;
                        case POULE_STATUT_EXPULSION: 
                        $txt = 'Expuls.';
                        break;
                    }
                    if ($a[$id][$pha]['Ex'])
                        $txt .= "(*)";

                    break;
                    default:
                    $txt = $dat ."?";
                } // switch($dat)
                $body .= "<td $att>$txt</td>";    
            }
            $body .= "</tr>\n";
        } // for sta sto
        $sta = $sto;
        $sto = $sto + $npc;
        if ($sto>$nb)
            $sto = $nb;
        
        $body .= "</tbody>";
        
        $out .= $head . $body . $foot;
    } // for cols
    return $out;
}

function qualifiesPourTableau ($xml)
{
    $r = array();
    $inscrits	= $xml->getElementsByTagName( 'Tireurs' );
    foreach ($inscrits as $list) 
    {
        $tireur = $list->getElementsByTagName( 'Tireur' );
        foreach ($tireur as $t) 
        {
            $ref = $t->getAttribute('ID');
            $r[$ref] = 0; // initial
        }
    }
    
    $tableau= $xml->getElementsByTagName( 'PhaseDeTableaux' );
    foreach ($tableau as $list) 
    {
        $tireur = $list->getElementsByTagName( 'Tireur' );
        foreach ($tireur as $t) 
        {
            $ref = $t->getAttribute('REF');
            $r[$ref] = 1; // dans le tableau
        }
    }
    return $r;
}

function getResetPhases($topXml)
{
    $pha = array();
    foreach ($topXml->getElementsByTagName('TourDePoules') as $e)
    $pha[$e->getAttribute('PhaseID')] = 'TourDePoules';

    foreach ($topXml->getElementsByTagName('PointDeScission') as $e)
    $pha[$e->getAttribute('PhaseID')] = 'PointDeScission';

    foreach ($topXml->getElementsByTagName('PhaseDeTableaux') as $e)
    $pha[$e->getAttribute('PhaseID')] = 'PhaseDeTableaux';

    ksort($pha);

    $rst= 1; // Start by clearing accumulated score
    $r  = array();
    foreach ($pha as $pid => $typ)
    {
	switch($typ)
	{
	    case 'TourDePoules':
	    $r[$pid] = $rst;
	    $rst = 0;
	    break;
	    
	    case 'PointDeScission':
	    $rst = 1; // Next TourDePoules starts with a zero accumulated score
	    break;
	}
    }
    return $r;
}

function suitTireurs ($topXml)
{
    $a = array();
    $verbose = 0*1;
    $encours = getPhaseEnCoursID ($topXml);
    $rst = getResetPhases($topXml);
    //    var_dump($rst);
    foreach ($topXml->getElementsByTagName('Tireur') as $t)
    {
	$p   = $t->parentNode;
	$pp  = "-";
	//	$pid = "-";
	$ref = $t->getAttribute('REF');
	if (!is_numeric($ref))
	    $ref= $t->getAttribute('ID');
	
	switch ($p->localName)
	{
	    case 'Tireurs':
	    $a[$ref] = array();
	    $a[$ref]['ACCU']=array();
	    $st     = $t->getAttribute('Statut');
	    $rk     = $t->getAttribute('Ranking');
	    $cl     = $t->getAttribute('Classement');
	    $a[$ref]['ACCU']['Vi'] = 0;  // Victoires accumulees
	    $a[$ref]['ACCU']['Ma'] = 0;  // Matches accumules
	    $a[$ref]['ACCU']['TD'] = 0;  // TD accumules
	    $a[$ref]['ACCU']['TR'] = 0;  // TR accumules
	    $a[$ref]['ACCU']['Ph'] = 0;  // Phase atteinte
	    $a[$ref]['ACCU']['St2']= 0;  // Status recalcule
            $a[$ref]['ACCU']['Ta'] = 0;  // Made it in the table

	    $a[$ref]['ACCU']['Sexe']    = $t->getAttribute('Sexe');
	    $a[$ref]['ACCU']['Nom']     = $t->getAttribute('Nom');
	    $a[$ref]['ACCU']['Prenom']  = $t->getAttribute('Prenom');
	    $a[$ref]['ACCU']['Nation']  = $t->getAttribute('Nation');
	    $a[$ref]['ACCU']['Club']    = $t->getAttribute('Club');
	    $a[$ref]['ACCU']['Ranking'] = ($rk==0)?'-':$rk;

	    if (is_numeric($cl))
	    {
		$a[$ref]['ACCU']['Cl'] = $cl;  // Le tireur est définitivement classé
		$a[$ref]['ACCU']['Pl'] = $cl;  // Le tireur est définitivement classé
	    }
	    else
		$a[$ref]['ACCU']['Pl'] = $t->getAttribute('Nom') . " " .$t->getAttribute('PreNom'); // Pas encore classé, on commence en alphabet

	    $a[$ref]['ACCU']['Ex'] = $t->getAttribute('Exporte');
	    $a[$ref]['ACCU']['St'] = $st;
	    if ($st=='E')
	    {
		$a[$ref]['ACCU']['Pl']=INF;
		$a[$ref]['ACCU']['Cl']=INF;
	    }

	    if($verbose)
		echo "Entree  ID:$ref at " . $a[$ref]['ACCU']['Pl']. "<br>"  ;
	    break;

	    case 'PhaseDeTableaux':
	    $phaid  = $p->getAttribute('PhaseID');
	    if ($encours>$phaid)
	    {
		$a[$ref][$phaid]['Pl'] = $t->getAttribute('RangFinal');
		$a[$ref]['TAB']['Pl']  = $t->getAttribute('RangFinal');
	    }
	    if($verbose)
		echo "TAB phase:$phaid ref:$ref<br>";
	    break;
	    
	    case 'TourDePoules':
	    $phaid  = $p->getAttribute('PhaseID');
	    
	    if ($phaid>$a[$ref]['ACCU']['Ph'])
		$a[$ref]['ACCU']['Ph'] = $phaid;

	    if ($rst[$phaid] && $encours >= $phaid)
	    {
		$a[$ref]['ACCU']['Vi'] = 0;  // Victoires accumulees
		$a[$ref]['ACCU']['Ma'] = 0;  // Matches accumules
		$a[$ref]['ACCU']['TD'] = 0;  // TD accumules
		$a[$ref]['ACCU']['TR'] = 0;  // TR accumules
	    }
	    
	    $st = $t->getAttribute('Statut');
	    $a[$ref][$phaid]=array();
	    $a[$ref][$phaid]['RI'] = $t->getAttribute('RangInitial');
	    $a[$ref][$phaid]['RF'] = $t->getAttribute('RangFinal');
	    
	    if ($encours>$phaid)
	    {
		$a[$ref][$phaid]['St'] = $st;
		$a[$ref][$phaid]['Pl'] = $t->getAttribute('RangFinal');
		$a[$ref]['ACCU']['Pl'] = $t->getAttribute('RangFinal');
	    }
	    else if ($encours==$phaid)
	    {
		$a[$ref]['ACCU']['Pl'] = "PL2"; //$t->getAttribute('RangInitial');
	    }

	    if($verbose)
		echo "TDP phase:$phaid ref:$ref<br>";
	    break;

	    case 'Poule':
	    $pouid = $p->getAttribute('ID');
	    $poupi = $p->getAttribute('Piste');
	    $pouda = $p->getAttribute('Date');
	    $tdp   = $p->parentNode;
	    $phaid = $tdp->getAttribute('PhaseID');
	    $a[$ref]['ACCU']['Po'] = $pouid;  // Dans quelle poule est ce tireur
	    $a[$ref][$phaid]['Po'] = $pouid;  // Dans quelle poule est ce tireur
            
	    $a[$ref]['ACCU']['Pi'] = $poupi;  // Sur quelle piste est ce tireur
	    $a[$ref][$phaid]['Pi'] = $poupi;  // Sur quelle piste est ce tireur
	    
            $a[$ref]['ACCU']['Da'] = $pouda;  // A quelle heure est ce tireur
	    $a[$ref][$phaid]['Da'] = $pouda;  // A quelle heure est ce tireur
            
            $vi = $t->getAttribute('NbVictoires');
	    $td = $t->getAttribute('TD');
	    $tr = $t->getAttribute('TR');
	    $a[$ref][$phaid]['Vi'] = $vi;
	    $a[$ref][$phaid]['Ma'] = 0;  // On doit compter les matches, car NbMatches s'embrouille avec les abandons et exclusions
	    $a[$ref][$phaid]['TD'] = $td;
	    $a[$ref][$phaid]['TR'] = $tr;
	    $a[$ref][$phaid]['TD-TR'] = $td ."&minus;".$tr;

	    if (is_numeric($vi))
		$a[$ref]['ACCU']['Vi'] += $t->getAttribute('NbVictoires');
	    
	    if (is_numeric($td) && is_numeric($tr))
	    {
		$a[$ref][$phaid]['Ind']   = $td - $tr;
		$a[$ref]['ACCU']['TD']   += $t->getAttribute('TD');
		$a[$ref]['ACCU']['TR']   += $t->getAttribute('TR');
		$a[$ref]['ACCU']['Ind']   = $a[$ref]['ACCU']['TD'] - $a[$ref]['ACCU']['TR'];
		$a[$ref]['ACCU']['TD-TR'] = $a[$ref]['ACCU']['TD'] ."&minus;". $a[$ref]['ACCU']['TR'];
	    }
	    
	    if($verbose)
		echo "TDP Phase:$phaid Poule no:$pouid ref:$ref <br>"; 
	    break;

	    case 'Match':
	    $matid = $p->getAttribute('ID');
	    $pp    = $p->parentNode;
	    $st    = $t->getAttribute('Statut');
	    if ($st=='E')
	    {
		$a[$ref]['ACCU']['Pl'] = INF;
		$a[$ref]['ACCU']['Cl'] = INF;
		$a[$ref]['ACCU']['St'] = 'E';
		$a[$ref][$phaid]['Pl'] = INF;
	    }

	    switch ($pp->localName)
	    {
		case 'Poule':
		$pou   = $pp;
		$pouid = $pou->getAttribute('ID');
		$tdp   = $pou->parentNode;
		$phaid = $tdp->getAttribute('PhaseID');
		$s     = $t->getAttribute('Statut');
		if ($s=='V' || $s=='D')
		{
		    $a[$ref][$phaid]['Ma']++;
		    $a[$ref]['ACCU']['Ma']++;
		    $a[$ref]['ACCU']['Fl'] = -computeVMfloat ($a[$ref]['ACCU']['Ph'],
							      $a[$ref]['ACCU']['Vi'],
							      $a[$ref]['ACCU']['Ma'],
							      $a[$ref]['ACCU']['TD'],
							      $a[$ref]['ACCU']['TR']);
		    $a[$ref]['ACCU']['St2']='Q';
		}
                if ($s=='A')
                {
		    //                    echo "REF:$ref POUID:$pouid  PHA:$phaid STATUT:$s <br>";
                    $a[$ref]['ACCU']['St2']='A';
                }
		if ($s=='E')
		{
		    $a[$ref]['ACCU']['Fl'] = INF;
                    $a[$ref]['ACCU']['St2']='E';
		}
		if($verbose)
		    echo "TDP Phase:$phaid Poule no:$pouid MATCH $matid ref:$ref <br>"; 
		break;

		case 'Tableau':
		$tab   = $pp;
		//		$tabid = $tab->getAttribute('ID');
		$sdt   = $tab->parentNode;
		$pdt   = $sdt->parentNode;
		$phaid = $pdt->getAttribute('PhaseID');
		if($verbose)
		    echo "Tableau Phase:$phaid Poule no:$pouid MATCH $matid ref:$ref <br>"; 
		break; 
	    }
	    break; // case 'Match':
	}
    }
    return $a;
}



function dump_ranking($ranking)
{
    foreach ($ranking as $ref => $tireur)
    {
	if (isset ($tireur[ RANK_FIN ]))
	{
	    echo "REF = " . $ref . " rang " . $tireur[ RANK_FIN ] ."<br>";
	}
    }
}




function repairTableau($xml )
{
    // Add missing victory to the remaining fencer if his opponent is excluded or gives up
    $phases = getAllPhases($xml);
    foreach ($phases as $phase)
    if ($phase->localName =='PhaseDeTableaux')
    {
	foreach ($phase->getElementsByTagName('Tableau') as $t)
	{
	    foreach ($t->getElementsByTagName('Match') as $m)
	    {
		$tireurs = $m->getElementsByTagName('Tireur');
		if ($tireurs->length == 2)
		{
		    $s0 = $tireurs[0]->getAttribute('Statut');
		    $s1 = $tireurs[1]->getAttribute('Statut');
		    
		    if (($s0=="A" || $s0=="E") && $s1!="A" && $s1!="E")
			$tireurs[1]->setAttribute('Statut','V');
		    if (($s1=="A" || $s1=="E") && $s0!="A" && $s0!="E")
			$tireurs[0]->setAttribute('Statut','V');
		}
		
	    }
	    
	}
    }
}

function autoscaleTableau ($xml )
{
    $r        = array();
    
    $taille   = array();
    $connus   = array();
    $finis    = array();
    $maxtaille = 0;
    $phases = getAllPhases($xml);
    foreach ($phases as $phase)
    if ($phase->localName =='PhaseDeTableaux')
    {
	foreach ($phase->getElementsByTagName('Tableau') as $t)
	{
	    $tabid          = $t->getAttribute('ID');
	    $taille[$tabid] = $t->getAttribute('Taille');
	    if ($taille[$tabid]>$maxtaille)
		$maxtaille = $taille[$tabid];
	    $connus[$tabid]  = 0;
	    $finis[$tabid]  = 0;
	    foreach ($t->getElementsByTagName('Match') as $m)
	    {
		$tireurs = $m->getElementsByTagName('Tireur');
		$s = array();
		$n = 0;
		foreach ($tireurs as $tt)
		{
		    $s[$n++] = $tt->getAttribute('Statut');
		    $connus[$tabid]++;
		}
		//	echo "statut:" . $s[0] . ":" . $s[1] . "<br>";
		if (count($s)==2)
		{
		    if ( $s[0]=="V" || $s[0]=="D" || $s[0]=="A" || $s[0]=="E" ||
			 $s[1]=="V" || $s[1]=="D" || $s[1]=="A" || $s[1]=="E" )
		    $finis[$tabid]+=2;
		}
		
	    }
	}

	$tabStart = $maxtaille;
	$tabEnd   = 1;

	$state="";
	foreach ($taille as $id => $val)
	{
	    //            echo "Tableau:$id Taille:$val Connu:".$connus[$id]. " Fini:".$finis[$id]."<br>";
	    if ($finis[$id] == $val)
		$state .= 'D'; // Done
	    else if ($connus[$id] > 0)
		$state .= 'R'; // Ready
	    else
		$state .= '-'; // Empty
	}
	$timeout = 0;
	while (strlen($state)>5) // We cannot fit the whole tableau
	{
	    //	    	    echo "trimming table $state " . $tabStart . ":" . $tabEnd . "<br> ";
	    if (++$timeout > 16)
		break;
	    if (substr ($state,0,3) == 'RDD') // Case of incomple first table
	    {
		//                echo "RDD<br>";
		$tabStart /= 2;
		$state = substr($state,1);
		//                echo "new $state<br>";
		continue;
	    }
            
            if (substr ($state,0,2) == 'DD')
	    {
		$tabStart /= 2;
		$state = substr($state,1);
		continue;
	    }
	    if (substr ($state,-3) == '---')
	    {
		$tabEnd *= 2;
		$state = substr($state,0,-1);
		continue;
	    }
	    if (substr ($state,-2) == '--')
	    {
		$tabEnd *= 2;
		$state = substr($state,0,-1);
		continue;
	    }
            
	    if (substr ($state,0,2) == 'RD') // Case of incomple first table
	    {
		#	$tabStart /= 2;
		$state = substr($state,1);
		continue;
	    }



	    if (substr ($state,-1) == '-')
	    {
		$tabEnd *= 2;
		$state = substr($state,0,-1);
		continue;
	    }
	    if (substr ($state,0,1) == 'D')
	    {
		$tabStart /= 2;
		$state = substr($state,1);
		continue;
	    }
	}
	//	echo "trimming table $state " . $tabStart . ":" . $tabEnd . "<br> ";
	//	echo "State : $state<br>";
	$r[ 'tabStart' ] = $tabStart;
	$r[ 'tabEnd' ]   = $tabEnd;
    }
    return $r;
}	


function fractureNom($str)
{
    $str = str_replace(' ',' ',$str);
    $str = str_replace('_',' ',$str); 
    $tok = preg_split('/\s/', $str);
    $n   = sizeof($tok);
    $r   = $tok[0];
    $f   = 0;

    for ($k=1;$k<$n;$k++)
    {
	if (($k>=(($n-1)/2))&&($f==0))
	{
	    $r .= '<br>';
	    $f = 1;
	}
	else
	    $r .= ' ';
	
	$r .= $tok[$k];
    }

    return $r;
}

function getTableauPattern($col)
{
    $f = 2*(pow(2,$col+1)-3);
    return array(
	'net' => ($col>1)?pow(2,$col):0,    // Narrow part, empty top
	'nrt' => ($col>1)?1:0,  // Narrow part  row   top
	'nvt' => ($col>1)?pow(2,$col)-4:0,  // Narrow part  vertical top
	'nem' => ($col>1)?6:8,              // Narrow part  empty middle
	'nrb' => ($col>1)?1:0,  // Narrow part  row   bottom
	'nvb' => ($col>1)?pow(2,$col)-4:0,  // Narrow part  row   bottom
	'neb' => ($col>1)?pow(2,$col):0,    // Narrow part  empty bottom
	'wet' => pow(2,$col+1)-3,           // Wide part
	'web' => pow(2,$col+1)-3,           // Wide part
    );
}


function renderMyTableau( $xml , $detail, $fold, $titre)
{
    $tireurs = array();
    foreach ($xml->getElementsByTagName( 'Tireurs' ) as $s)
    {
	foreach ($s->getElementsByTagName('Tireur') as $t)
	{
	    $id = $t->getAttribute('ID');
	    $tireurs[$id] = array('DispNom' => $t->getAttribute('Nom') .' '. $t->getAttribute('Prenom'),
				  'Nation'  => $t->getAttribute('Nation'),
				  'Club'    => $t->getAttribute('Club'),
	    );
	}
    }

    $arbitres = array();
    foreach ($xml->getElementsByTagName( 'Arbitres' ) as $s)
    {
	foreach ($s->getElementsByTagName('Arbitre') as $t)
	{
	    $id = $t->getAttribute('ID');
	    $arbitres[$id] = array('DispNom' => $t->getAttribute('Nom') .' '. $t->getAttribute('Prenom'),
				   'Nation'  => $t->getAttribute('Nation'),
				   'Club'    => $t->getAttribute('Club'),
	    );
	}
    }
    
    $full = $fold>0;
    $bb = prepairMyTableau($xml,$full);
    $b=$bb['b'];
    $a=$bb['a'];
    
    if ($fold > 0)
    {        
        $bb = origami($a,$b,$fold==1);
        $b=$bb['b'];
        $a=$bb['a'];
    }
    if ($fold > 1)
    {    
        $bb = origami2($a,$b);
        $b=$bb['b'];
        $a=$bb['a'];
    } 
    

    
    $tab  = "<div class='tblhd_top' onclick='mickey()'><span class='tbl_banner'>&#9776; $titre</span><br>";
    $tab .= "<div class='tblhd_tab'><div></div>\n";
    $fixed_height = isset($_GET['scroll'])?'fhtab':'';
    $tab .= "<table id='scrollme' class='myTableau $fixed_height'><thead>";
    $tab .= "<tr>";
    $keep = 2;
    for ($col=0;$col<count($b);$col++)
    {
	$tab .= "<th class='Tableau_TXX'><div class='tblhead_tab'>".$a[$col]."</div></th>";
    }
    $tab .= "</tr></thead><tbody>\n";
    
    if (count($b)>0)
	for ($row=0;$row<count($b[0]);$row++)
    {
	$tab .= "<tr>";
        $nbc = count($b);
	for ($col=0;$col<count($b);$col++)
	{

	    $cla = $b[$col][$row]['class'];
	    //	    $inl = $b[$col][$row]['inl'];
	    //	    $con = $b[$col][$row]['con'];
	    $rowspan = isset($b[$col][$row]['row'])?$b[$col][$row]['row']:1;
	    $inl = '';
	    $con = '';
	    $class = $cla;
	    if ($cla == 'Tableau_nul' || $cla == 'Tableau_nul_flip')
	    {
	    }
	    else
	    {
		if ($cla == 'Tableau_wbo_lef' || 
                    $cla == 'Tableau_wto_lef' ||
                    $cla == 'Tableau_wbo_lef_flip' || 
                    $cla == 'Tableau_wto_lef_flip' ||
                    $cla == 'Tableau_wbo_lef_final' || 
                    $cla == 'Tableau_wto_lef_final' ||
                    $cla == 'Tableau_wbo_lef_flip_final' || 
                    $cla == 'Tableau_wto_lef_flip_final' )
		{
		    $l = 0;
		    $t = "";
		    if (isset($b[$col][$row]['REF']))
		    {
			$t = $tireurs[$b[$col][$row]['REF']]['DispNom'];
			$l =strlen($t);
		    }

		    
		    if ($l >40)
		    {
			$class .= ' gros40';
			//			$t = fractureNom($t);
		    }
		    else if ($l>30)
		    {
			$class .= ' gros30';
			//			$t = fractureNom($t);
		    }
		    else if ($l>20)
		    {
			$class .= ' gros20';
			//			$t = fractureNom($t);
		    }
		    else
			$class .= ' gros';

		    if (isset($b[$col][$row]['Fla']))
			$class .= ' avec_rang';
		    else
			$class .= ' sans_rang';
		    
		    $tab .= "<td $inl class='$class' $rowspan >";
		    
		    $flag="";
		    if ( isset($b[$col][$row]['REF']))
		    {
			$nat = $tireurs[$b[$col][$row]['REF']]['Nation'];
			$flag = ' '.flag_icon($nat,'').' ';
		    }
		    $nom="";
		    if (isset($b[$col][$row]['REF']))
			$nom = $t; //ireurs[$b[$col][$row]['REF']]['DispNom'];
                    
		    if ((isset($b[$col][$row]['Fla']) && $b[$col][$row]['Fla']) && (isset($b[$col][$row]['REF'])))
                    {
			if ($cla == 'Tableau_wbo_lef' || $cla == 'Tableau_wto_lef' || $cla == 'Tableau_wto_lef_final')
			    $nom = "<span class='gros_nobold'>(".$b[$col][$row]['Ran'].") </span>$nom";
                        else
			    $nom = "$nom <span class='gros_nobold'> (".$b[$col][$row]['Ran'].")</span>";
                    }   
                    if (!isset($b[$col][$row]['Fla']) || !$b[$col][$row]['Fla'])
                        $flag = '';

                    if ($cla == 'Tableau_wbo_lef' || $cla == 'Tableau_wto_lef' || $cla == 'Tableau_wto_lef_final')
                        $tab .= $flag . $nom;
                    else 
                        $tab .= $nom . $flag;
                    
                    
		    if (isset($b[$col][$row]['Statut']))
			$tab .= " ".$b[$col][$row]['Statut'];
		    if (isset($b[$col][$row]['Score']))
			$tab .= " ".$b[$col][$row]['Score'];

		}
		else if ( $cla == 'Tableau_wto' || 
                          $cla == 'Tableau_wbo' ||
                          $cla == 'Tableau_wto_flip' || 
                          $cla == 'Tableau_wbo_flip' ||
                          $cla == 'Tableau_wto_final' || 
                          $cla == 'Tableau_wbo_final' ||
                          $cla == 'Tableau_wto_flip_final' || 
                          $cla == 'Tableau_wbo_flip_final' 
                )
		{
		    $tmp='';
		    $sta=0;
		    $dat=0;
		    $pis=0;
		    if (isset($b[$col][$row]['Statut']))
		    {
			$tmp .= $b[$col][$row]['Statut'];
			
			if ($b[$col][$row]['Statut'] != '')
			    $sta=1;
		    }
		    if (isset($b[$col][$row]['Score']))
		    {
			$tmp .= $b[$col][$row]['Score'];
		    }
		    
		    if (isset($b[$col][$row]['Pi']))
			if ($b[$col][$row]['Pi']!='')
			    $pis=1;
		    if (isset($b[$col][$row]['Da']))
			if ($b[$col][$row]['Da']!='')
			    $dat=1;
		    
		    if ($sta)
			$class .= ' score ';
		    else if ($pis)
			$class .= ' piste ';
		    else if ($dat)
			$class .= ' date ';
		    
		    $tab .= "<td $inl class='$class' $rowspan >$tmp ";
		    
		}
		
		else
		{
		    $tab .= "<td $inl class='$class' $rowspan>";
		}

		$print_ID = 0;
		if ($print_ID && isset($b[$col][$row]['ID']))
		    $tab .= " ID".$b[$col][$row]['ID'];
		
		if (isset($b[$col][$row]['Pi']))
		{
		    $piste = $b[$col][$row]['Pi'];
		    if (strlen($piste)>0)
			$tab .= "Piste ".$b[$col][$row]['Pi'] ."   ";
		}
		
		if (isset($b[$col][$row]['Da'])) // && $b[$col][$row]['Da'])
		{
		    $txt2 = explode(" ", $b[$col][$row]['Da']);
		    $txt = (count($txt2)>1)? $txt2[1]: $b[$col][$row]['Da'];
		    if (is_string($txt) && strlen($txt)>0)
		    {
                           if (isset($b[$col][$row]['DaApprox']))
                        $txt = 'dès ' . $txt;
			$tab .= ''. $txt;
		    }
		}

		if (isset($b[$col][$row]['ArRef'])) // && $b[$col][$row]['Da'])
		{
		    $aref = $b[$col][$row]['ArRef'];
		    if (isset($arbitres[$aref]))
		    {
			$arb = $arbitres[$aref];
			$txt = $arb['DispNom'];
			if ($detail && strlen($txt)>0)
			    $tab .= "Arbitre: $txt ";
		    }
		}
		
		$tab .= $con . "</td>";
	    }
	}
	$tab .= "</tr>\n";
    }
    $tab .= "</tbody></table></div></div>";
    return $tab;
}

function rangEntreeTableau ($phase)
{
    $rank = array();
    foreach($phase->getElementsByTagName('Tireur') as $t)
    {
	$REF = $t->getAttribute('REF');
	$Ran = $t->getAttribute('RangInitial');
	if (is_numeric($Ran))
	{
	    $rank[$REF] = $Ran;
	}
    }
    return $rank;
}

function suitTireursTableau ( $xml)
{
    $phases = getAllPhases($xml);
    $tireurs = suitTireurs ($xml);
    recomputeClassement($tireurs);

    $elimines = array();
    $premier = 0;
    
    $rk = array();
    // Injecte tous les tireurs
    foreach ($tireurs as $ref=>$tireur)
    {
        $rk[$ref] = $tireurs[$ref]['ACCU']['Pl2']+40960000;
    }

    foreach ($phases as $phase)
    if ($phase->localName =='PhaseDeTableaux')
    {
        $entree_tableau = rangEntreeTableau($phase);
        foreach ($tireurs as $id=>$val)
        {
            $tireurs[$id]['ACCU']['intab'] = isset($entree_tableau[$id])?1:0;
            $tireurs[$id]['ACCU']['out']   = isset($entree_tableau[$id])?0:1;
	    //        echo $tireurs[$id]['ACCU']['Nom'] . " is out: " . $tireurs[$id]['ACCU']['out'] ."<br>";
        }
        
	foreach($phase->getElementsByTagName('SuiteDeTableaux') as $sdt)
	{
	    foreach($sdt->getElementsByTagName('Tableau') as $tab)
	    {
		$tabid    = $tab->getAttribute('ID');
		$taille   = $tab->getAttribute('Taille');
		$titre    = $tab->getAttribute('Titre');
		$elimines[$tabid] = array();
		$elimines[$tabid]['taille'] = $taille;
		$elimines[$tabid]['matches'] = 0;
		$elimines[$tabid]['finis'] = 0;
		$elimines[$tabid]['out']    = array();

		foreach($tab->getElementsByTagName('Match') as $m)
    		{
                    $dat  = $m->getAttribute('Date');
                    $pis  = $m->getAttribute('Piste');
		    $adv = $m->getElementsByTagName('Tireur');
		    $len     = $adv->length;
                    if ($len == 1)
                    {
			$ref0 = $adv[0]->getAttribute('REF');
			$ran0 = $tireurs[$ref0]['ACCU']['Pl2'];
                        $tireurs[$ref0]['ACCU']['PiTa'] = $pis;
                        $tireurs[$ref0]['ACCU']['DaTa'] = $dat;
			switch ($adv[0]->getAttribute('Statut'))
			{
			    case 'V':
			    $premier = $ref0;
			    $elimines[$tabid]['finis']++;
                            $tireurs[$ref0]['ACCU']['out'] = ($taille>2)?0:1;
                            $rk[$ref0]= $tireurs[$ref0]['ACCU']['Pl2']+($taille/2)*10000;
			    break;
                        }
                    }
		    if ($len == 2)
		    {
			$elimines[$tabid]['matches']++;
			// Keep track of eliminated fencers
			$ref0 = $adv[0]->getAttribute('REF');
			$ref1 = $adv[1]->getAttribute('REF');
			$ran0 = $tireurs[$ref0]['ACCU']['Pl2'];
			$ran1 = $tireurs[$ref1]['ACCU']['Pl2'];
                        $dat  = $m->getAttribute('Date');
                        $pis  = $m->getAttribute('Piste');
                        

			switch ($adv[0]->getAttribute('Statut'))
			{
			    case 'V':
			    $premier = $ref0;
			    $elimines[$tabid]['finis']++;
                            $tireurs[$ref0]['ACCU']['out'] = ($taille>2)?0:1;
                            $rk[$ref0]= $tireurs[$ref0]['ACCU']['Pl2']+($taille/2)*10000;
                            $tireurs[$ref0]['ACCU']['PiTa'] = '';
                            $tireurs[$ref0]['ACCU']['DaTa'] = '';
			    break;

			    case '':
                            $tireurs[$ref0]['ACCU']['PiTa'] = $pis;
                            $tireurs[$ref0]['ACCU']['DaTa'] = $dat;
                            $rk[$ref0] = $tireurs[$ref0]['ACCU']['Pl2']+($taille)*10000;
                            break;
			    
                            case 'E':  // Exclusion means you are not ranked
                            $tireurs[$ref0]['ACCU']['out'] = 1;
                            $rk[$ref0] = INF;
                            $tireurs[$ref0]['ACCU']['PiTa'] = '';
                            $tireurs[$ref0]['ACCU']['DaTa'] = '';
			    break;

			    case 'D':
			    case 'A':
			    $elimines[$tabid]['out'][$ref0] = 1*$ran0;
			    //                                echo "OUT $ran0:" . $tireurs[$ref0]['ACCU']['Nom'] ."<br>";
                            $tireurs[$ref0]['ACCU']['out'] = 1;
                            $rk[$ref0]= $tireurs[$ref0]['ACCU']['Pl2']+($taille)*10000+5000;
                            $tireurs[$ref0]['ACCU']['PiTa'] = '';
                            $tireurs[$ref0]['ACCU']['DaTa'] = '';
			    break;
			}
			switch ($adv[1]->getAttribute('Statut'))
			{
			    case 'V':
			    $premier = $ref1;
			    $elimines[$tabid]['finis']++;
                            $tireurs[$ref1]['ACCU']['out'] = ($taille>2)?0:1;
                            $rk[$ref1] = $tireurs[$ref1]['ACCU']['Pl2']+($taille/2)*10000;
                            $tireurs[$ref1]['ACCU']['PiTa'] = '';
                            $tireurs[$ref1]['ACCU']['DaTa'] = '';

			    break;
			    
			    case '':
                            $tireurs[$ref1]['ACCU']['PiTa'] = $pis;
                            $tireurs[$ref1]['ACCU']['DaTa'] = $dat;
                            $rk[$ref1] = $tireurs[$ref1]['ACCU']['Pl2']+($taille)*10000;
                            break;
                            
			    case 'E':  // Exclusion means you are not ranked
                            $tireurs[$ref1]['ACCU']['out'] = 1;
                            $rk[$ref1] = INF;
                            $tireurs[$ref1]['ACCU']['PiTa'] = '';
                            $tireurs[$ref1]['ACCU']['DaTa'] = '';
                            break;
			    
			    case 'D':
			    case 'A':
			    $elimines[$tabid]['out'][$ref1] = 1*$ran1;
			    //                            echo "OUT $ran1:" . $tireurs[$ref1]['ACCU']['Nom'] ."<br>";
                            $tireurs[$ref1]['ACCU']['out'] = 1;
                            $rk[$ref1]= $tireurs[$ref1]['ACCU']['Pl2']+($taille)*10000+5000;
                            $tireurs[$ref1]['ACCU']['PiTa'] = '';
                            $tireurs[$ref1]['ACCU']['DaTa'] = '';
			    break;
			}
		    } // LEN == 2

		} // foreach matches
	    } // foreach tableaux
	}// suite de tabelau
    } // phases

    asort($rk);
    $pl=1;
    $ln=0;
    $ov=0;
    $Tdone = array();
    $Tprov = array();
    foreach ($rk as $ref => $v)
    {
        $tou = floor($v/10000.0);
        
        if(!isset($Tdone[$tou]))
            $Tdone[$tou]=0;
        if(!isset($Tprov[$tou]))
            $Tprov[$tou]=0;
        
        
        if($tireurs[$ref]['ACCU']['out'])
            $Tdone[$tou]++;
        else
            $Tprov[$tou]++;
        
        $tireurs[$ref]['ACCU']['PlaTabTri'] = $v;
        $ln++;
        if ($v>$ov)
        {
            if ($v == INF)
                $tireurs[$ref]['ACCU']['PlaTab'] = INF;
            else
		$tireurs[$ref]['ACCU']['PlaTab'] = ($ln==4)?3:$ln;
            $pl = $ln;
        }
        else
        {
            if ($v == INF)
                $tireurs[$ref]['ACCU']['PlaTab'] = INF;
            else
		$tireurs[$ref]['ACCU']['PlaTab'] = $pl;
        }   
        $ov = $v;
    }	

    foreach ($rk as $ref => $v)
    {
        $tou = floor($v/10000.0);
        $tireurs[$ref]['ACCU']['tourdone']=1;
        
        if($Tdone[$tou]>0 && $Tprov[$tou]>0)
            $tireurs[$ref]['ACCU']['tourdone']=0;
        
        $rang_max = floor($tou/2);
        if($tireurs[$ref]['ACCU']['PlaTab'] < $rang_max)
            $tireurs[$ref]['ACCU']['tourdone']=0;
        
    }
    
    return $tireurs;
}

//------------
function prepairMyTableau( $xml, $full )
{
    $verbose = 0*1;  // Enable to see who the function works


    $hide_referee_when_done = 1;
    
    $phases = getAllPhases($xml);

    $scale = autoscaleTableau($xml); // Automatically decide start and stop columns

    $a = array(); 
    $b = array();

    foreach ($phases as $phase)
    if ($phase->localName =='PhaseDeTableaux')
    {

	$rank =rangEntreeTableau ($phase);  
	//	echo renderSettingForm( $phase );

	foreach($phase->getElementsByTagName('SuiteDeTableaux') as $sdt)
	{
	    
	    $a = array(); 
	    $b = array();
	    $col = 0;
	    $previous = array(); /* When a fencer has no opponent, should it appear as 1st or 2nd fencer? */
	    foreach($sdt->getElementsByTagName('Tableau') as $tab)
	    {
		$tabid    = $tab->getAttribute('ID');
		$taille   = $tab->getAttribute('Taille');
		$titre    = $tab->getAttribute('Titre');
		$tabStart = (isset($_GET['tabStart']))? $_GET['tabStart'] : $scale['tabStart'];
		$tabEnd   = (isset($_GET['tabEnd']))?   $_GET['tabEnd']   : $scale['tabEnd'];
		if ($full)
		    $tabEnd=1;

		if ($taille >= $tabEnd && $taille <= $tabStart )  // AUTOSCALE
		{
                    $entree  = ($taille == $tabStart) || ($col==0);
		    //		echo "COLONNE:$col<br>";// var_dump($previous); echo "<br>";
		    $pat     = getTableauPattern($col+1);
		    //		var_dump($pat);	echo "<br>";
		    $b[$col*3]   = array();
		    $a[$col*3]   = '';
		    $b[$col*3+1] = array();
		    $a[$col*3+1] = 'T'.$taille; //$titre;
		    $b[$col*3+2] = array();
		    $a[$col*3+2] = '';

		    $narow = 0;  // Row counter for narrow part
		    $wirow = 0;  // Row counter for wide part
		    $nbm   = 0;  // Number of matches
		    
		    foreach($tab->getElementsByTagName('Match') as $m)
		    {
			$nbm++;
			$maid    = 1*$m->getAttribute('ID');
			$tireurs = $m->getElementsByTagName('Tireur');
			$arbitre = $m->getElementsByTagName('Arbitre');
			$len     = $tireurs->length;

			// Last match ID this fencer was in
			$pre  = (isset($tireurs[0]) && (isset($previous[$tireurs[0]->getAttribute('REF')]))) ?
				$previous[$tireurs[0]->getAttribute('REF')] : 0;
			
			$tst0 = ($len==2);
			$tst1 = ($len==1 &&  ($col==0 && ($nbm <= $taille/4)));
			$tst2 = ($len==1 &&  ($col>0  && ($previous[$tireurs[0]->getAttribute('REF')] % 2 == 1)));
			$tst3 = ($len==1 &&  ($col==0 && ($nbm > $taille/4)));
			$tst4 = ($len==1 &&  ($col>0  && ($previous[$tireurs[0]->getAttribute('REF')] % 2 == 0)));
			//  echo "COL:$col MAID:$maid LEN:$len PRE:$pre T0:$tst0 T1:$tst1  T2:$tst2  T3:$tst3 T4:$tst4<br>";

			// Narrow column
			for ($k = 0; $k < $pat['net']; $k++) $b[$col*3][$narow++] = array('class' => 'Tableau_net');  // Empty Top
			for ($k = 0; $k < $pat['nrt']; $k++) $b[$col*3][$narow++] = array('class' => 'Tableau_nrt');
			for ($k = 0; $k < $pat['nvt']; $k++) $b[$col*3][$narow++] = array('class' => 'Tableau_nvt');
			for ($k = 0; $k < $pat['nem']; $k++) $b[$col*3][$narow++] = array('class' => 'Tableau_nem');  // Empty Mid
			for ($k = 0; $k < $pat['nvb']; $k++) $b[$col*3][$narow++] = array('class' => 'Tableau_nvb');
			for ($k = 0; $k < $pat['nrb']; $k++) $b[$col*3][$narow++] = array('class' => 'Tableau_nrb');
			for ($k = 0; $k < $pat['neb']; $k++) $b[$col*3][$narow++] = array('class' => 'Tableau_neb');  // Empty Bot

			// Wide column
			for ($k = 0; $k < $pat['wet']; $k++)
			{
			    $b[$col*3+1][$wirow]   = array('class' => 'Tableau_wet');  // Empty Top
			    $b[$col*3+2][$wirow++] = array('class' => 'Tableau_wet');  // Empty Top
			}
			
			// Top Tireur
			if ( $tst0 || $tst1 || $tst2)
			{
			    if ($len==2) // Two opponents
			    {
				$b[$col*3+1][$wirow]   = array('class'  => 'Tableau_wto_lef',
							       'REF'    => $tireurs[0]->getAttribute('REF'),
							       'Ran'    => $rank[$tireurs[0]->getAttribute('REF')],
                                                               'Fla' => $entree );
				$b[$col*3+2][$wirow] = array('class'  => 'Tableau_wto',
							     'Statut' => $tireurs[0]->getAttribute('Statut'),
							     'Score'  => $tireurs[0]->getAttribute('Score'));
				if($tireurs[0]->getAttribute('Statut')=='')
				    $b[$col*3+2][$wirow]['Pi']    = $m->getAttribute('Piste');
				$wirow++;
			    }
			    else // Less than two opponents, therefore no score 
			    {
				$b[$col*3+1][$wirow]   = array('class'  => 'Tableau_wto_lef',
							       'REF'    => $tireurs[0]->getAttribute('REF'),
							       'Ran'    => $rank[$tireurs[0]->getAttribute('REF')] ,
                                                               'Fla' => $entree );
                                
				$b[$col*3+2][$wirow] = array('class'  => 'Tableau_wto');
                                $wirow++;
			    }
			    
			    $previous[$tireurs[0]->getAttribute('REF')] = $maid;
			}
			else
			{
			    $b[$col*3+1][$wirow]   = array('class'  => 'Tableau_wto_lef');
			    $b[$col*3+2][$wirow++] = array('class'  => 'Tableau_wto');
			}
			for ($nu=0;$nu<NB_ROWS_TIREUR-1;$nu++)
			{
			    $b[$col*3+1][$wirow]   = array('class'  => 'Tableau_nul');
			    $b[$col*3+2][$wirow++] = array('class'  => 'Tableau_nul');
			}

			if (NB_ROWS_TIREUR==2)
			{
			    // Mid Info
			    $arbref = '';
			    if (isset($arbitre[0]))
			    {
				$arbref = $arbitre[0]->getAttribute('REF');
			    }
			    
			    $b[$col*3+1][$wirow] = array('class' => 'Tableau_wmi_lef');
			    $b[$col*3+2][$wirow] = array('class' => 'Tableau_wmi',
							 'ID'    => $m->getAttribute('ID')  );

			    
			    if(isset($tireurs[0]) && $tireurs[0]->getAttribute('Statut')!='')
			    {
				//	$b[$col*3+2][$wirow]['Pi']    = $m->getAttribute('Piste');
				//	$b[$col*3+2][$wirow]['Da']    = $m->getAttribute('Date');
				
			    }
			    else
			    {
				$b[$col*3+1][$wirow]['ArRef'] = $arbref;

			    }
			    $wirow++;

			    $b[$col*3+1][$wirow]   = array('class'  => 'Tableau_nul');
			    $b[$col*3+2][$wirow++] = array('class'  => 'Tableau_nul');
			}
			

			// Bottom Tireur
			if ( $tst3 || $tst4) // Gagne sans combattre, donc pas de score
			{
			    $b[$col*3+1][$wirow]   = array('class'  => 'Tableau_wbo_lef',
							   'REF'    => $tireurs[0]->getAttribute('REF'),
							   'Ran'    => $rank[$tireurs[0]->getAttribute('REF')],
                                                           'Fla' => $entree );
			    
			    $b[$col*3+2][$wirow] = array('class'  => 'Tableau_wbo');
                                                            if ($m->getAttribute('Piste')=='')
                                    $b[$col*3+2][$wirow]['DaApprox'] = 1;
                            $b[$col*3+2][$wirow]['Da']       = $m->getAttribute('Date');
                            $wirow++;
			    $previous[$tireurs[0]->getAttribute('REF')] = $maid;
			}
			else if ($tireurs->length==2)
			{
			    $b[$col*3+1][$wirow]   = array('class'  => 'Tableau_wbo_lef',
							   'REF'    => $tireurs[1]->getAttribute('REF'),
							   'Ran'    => $rank[$tireurs[1]->getAttribute('REF')],
                                                           'Fla' => $entree );

			    $b[$col*3+2][$wirow] = array('class'  => 'Tableau_wbo',
							 'Statut' => $tireurs[1]->getAttribute('Statut'),
							 'Score'  => $tireurs[1]->getAttribute('Score'));
			    $previous[$tireurs[1]->getAttribute('REF')] = $maid;

			    if ($tireurs[1]->getAttribute('Statut')=='')
                            {
				$b[$col*3+2][$wirow]['Da']     = $m->getAttribute('Date');
                                if ($m->getAttribute('Piste')=='')
                                    $b[$col*3+2][$wirow]['DaApprox'] = 1;
                            }
			    $wirow++;
			}
			else
			{
			    $b[$col*3+1][$wirow]   = array('class'  => 'Tableau_wbo_lef');
			    $b[$col*3+2][$wirow] = array('class'  => 'Tableau_wbo');
                            if ($m->getAttribute('Piste')=='')
                                $b[$col*3+2][$wirow]['DaApprox'] = 1;
                            $b[$col*3+2][$wirow]['Da']       = $m->getAttribute('Date');
                            $wirow++;
			}
			
			for ($nu=0;$nu<NB_ROWS_TIREUR-1;$nu++)
			{
			    $b[$col*3+1][$wirow]   = array('class'  => 'Tableau_nul');
			    $b[$col*3+2][$wirow++] = array('class'  => 'Tableau_nul');
			}
			
			// Empty Bottom
			for ($k = 0; $k < $pat['web']; $k++)
			{
			    $b[$col*3+1][$wirow]   = array('class' => 'Tableau_web');
			    $b[$col*3+2][$wirow++] = array('class' => 'Tableau_web');
			}
			
			//		    echo "-----NAROW:$narow WIROW:$wirow<br>";
		    } // Foreach match
		    //		echo "NAROW:$narow WIROW:$wirow<br>";
		    $col++;
		} // if $cnt>3
	    }//foreach tableau
	} // SuiteDeTableau
    } // PhaseDeTableu


    $d = array ( 'Tableau_net' => 'net', 
		 'Tableau_nrt' => 'nrt',
		 'Tableau_nvt' => 'nvt',
		 'Tableau_nvb' => 'nvb',
		 'Tableau_nem' => 'nem',
		 'Tableau_neb' => 'neb',
		 'Tableau_nrb' => 'nrb',
		 'Tableau_wet' => 'wet',
		 'Tableau_nul' => 'nul',

		 'Tableau_wet'     => 'wet',
		 'Tableau_wet_lef' => 'wetl',
		 'Tableau_wto'     => 'wto' ,
		 'Tableau_wto_lef' => 'wtol',
		 'Tableau_wmi'     => 'wmi' ,
		 'Tableau_wmi_lef' => 'wmil',
		 'Tableau_web'     => 'web' ,
		 'Tableau_web_lef' => 'webl',
		 'Tableau_wbo'     => 'wbo' ,
		 'Tableau_wbo_lef' => 'wbol'
    );

    $s = array ( 'net' => '', 
		 'nrt' => 'border-top : solid black 3px; border-right : solid black 3px;',
		 'nvt' => 'border-right : solid black 3px;',
		 'nvb' => 'border-right : solid black 3px;',
		 'nem' => '',
		 'neb' => '',
		 'nrb' => 'border-bottom : solid black 3px; border-right : solid black 3px;',
		 'wet' => '',
		 'wto' => 'border-top : solid black 3px; border-right : solid black 3px;',
		 'wtol' => 'border-top : solid black 3px; ',
		 'wmi' => 'border-right : solid black 3px;',
		 'wmil' => '',
		 'web' => '',
		 'nul' => '',
		 'wbo' => 'border-bottom : solid black 3px; border-right : solid black 3px;',
		 'wbol' => 'border-bottom : solid black 3px; ',
    );

    if($verbose)
	echo "<table style='border-collapse:collapse;'>";
    
    if (count($b)>0)
	for ($r = 0; $r<count($b[0]); $r++)
    {
	$rs = NB_ROWS_TIREUR;
	if($verbose)
	    echo "<tr><td>R:$r</td>";
	for ($c = 0; $c<count($b); $c++)
	{
	    $tag = $d[$b[$c][$r]['class']];
	    $sty = $s[$tag];
	    $sty .= ' border-collapse: collapse;';
	    $con = "$r:$c $tag";
	    $row = '';
	    $inl = '';
	    if ($tag=='nul')
	    {
	    }
	    else
	    {
		if ($tag=='wto' || $tag=='wbo' || $tag=='wtol' || $tag=='wbol')
	    	    $inl .= " rowspan='$rs' style='$sty' ";
		else if ($tag=='wmi' || $tag=='wmil')
		    $inl .= " rowspan='2' style='$sty' ";
		else
		    $inl .= " style='$sty' ";

		if ($tag=='wto' || $tag=='wbo' || $tag=='wtol' || $tag=='wbol')
		    $row=" rowspan='$rs' ";
		if ($tag=='wmi' || $tag=='wmil')
		    $row=" rowspan='2' ";

		if($verbose)
		    echo "<td $inl>$con</td>";
	    }
	    //	    $b[$c][$r]['sty']=$sty;
	    $b[$c][$r]['tag']=$tag;
	    $b[$c][$r]['inl']=$inl;
	    $b[$c][$r]['con']=$con;
	    $b[$c][$r]['row']=$row;
	    
	}
	if($verbose)
	    echo "</tr>";
    }
    if($verbose)
	echo "</table>";

    return array('a'=>$a, 'b' => $b);
}


function origami ($a,$b,$showfinal)
{
    $aa = array();
    $bb = array();

    $nbc = count($b)-2;
    
    if ($nbc<4)
        return array('a'=>$a, 'b' => $b);
    $nbr = count($b[0]);
    $hnr = ceil($nbr/2);
    for ($col=0;$col<$nbc;$col++)
    {
	$new_col =2*$nbc-$col-1;
	$aa[$col]     = $a[$col];
	$aa[$new_col] = $a[$col];           
        
	for ($row=0;$row<$hnr;$row++)
	{
	    $old_row = $hnr + $row;
	    $bb[$col][$row]     = $b[$col][$row];
	    $bb[$new_col][$row] = $b[$col][$old_row];
	    $bb[$new_col][$row]['class'] .=  '_flip';
	}
    }

    // erase vertical line originally routing to final
    for ($row = 0; $row<$hnr/2;$row++)
    {
        $bb[$nbc][$row]['class'] = 'Tableau_net';
        $bb[$nbc-1][$row]['class'] = 'Tableau_net';
    }
    
    if (!$showfinal)
    {
        for ($row = 0; $row<$hnr;$row++)
        {
            $bb[$nbc-1][$row]['class']   = 'Tableau_net';
        }
    }
    // Greffe les finalistes quelque part
    // Finaliste gauche
    
    if ($showfinal)
    {
        $bb[$nbc][($hnr/2)]['class'] = 'Tableau_nrt_flip';
        $vrow = $hnr-3;
        for ($kr = 0;$kr<4;$kr++)
        {
            for ($kc = 0; $kc <2; $kc++)
            {
                $bb[$nbc-3+$kc][$vrow+$kr] = $b[$nbc+$kc][$hnr+$kr-3];
                $bb[$nbc-3+$kc][$vrow+$kr]['class'] .= '_final';
            }
        }

        // Finaliste droite
        for ($kr = 0;$kr<4;$kr++)
        {
            for ($kc = 0; $kc <2; $kc++)
            {
                $bb[$nbc+2-$kc][$vrow+$kr] = $b[$nbc+$kc][$hnr+$kr+1];
                $bb[$nbc+2-$kc][$vrow+$kr]['class'] .= '_flip_final';
            }
        }
    }
    return array('a'=>$aa, 'b' => $bb);
}

function origami2 ($a,$b)
{
    $aa = array();
    $bb = array();
    $nbc = count($b);
    $nbr = count($b[0]);
    $hnr = ceil($nbr/2);
    for ($col=0;$col<$nbc;$col++)
    {
	$new_col      = $nbc+$col+1;
	$aa[$col]     = $a[$col];
	$aa[$new_col] = $a[$col];           
        $aa[$nbc]     = '   ';
	for ($row=0;$row<$hnr;$row++)
	{
	    $old_row = $hnr + $row;
	    $bb[$nbc][$row]     = array('class'  => 'Tableau_sep');
	    $bb[$col][$row]     = $b[$col][$row];
	    $bb[$new_col][$row] = $b[$col][$old_row];
	}
    }
    return array('a'=>$aa, 'b' => $bb);
}


function getTitre($xml)
{
    $r = '';
    
    $comp = $xml->getElementsByTagName('CompetitionIndividuelle');
    $date = $comp[0]->getAttribute('Date');
    $sexe = $comp[0]->getAttribute('Sexe');
    $arme = $comp[0]->getAttribute('Arme');
$cate =     $comp[0]->getAttribute('Categorie');
    $sexe = ($sexe=='M')?'Hommes':'Femmes';
    switch ($arme)
    {
        case 'E': $arme = 'Épée'; break;
        case 'F': $arme = 'FleuretÉ'; break;
        case 'S': $arme = 'Sabre'; break;
    }
    
    $r = "$arme $sexe $cate $date";
    
    return $r;
}