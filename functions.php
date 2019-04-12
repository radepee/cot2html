<?php

/********************************************************/
/*                         POULES                       */
/********************************************************/
/*
   define( 'RANK_INIT',     0 );
   define( 'NO_IN_POULE',   1 );
   define( 'NB_VICT',       2 );
   define( 'NB_MATCH',      3 );
   define( 'TD',            4 );
   define( 'TR',            5 );
   define( 'RANK_IN_POULE', 6 );
   define( 'RANK_FIN',      7 );
   define( 'STATUT',        8 );
   define( 'FIRST_RES',     9 );
 */

// Be kind to var_dump!
define( 'RANK_INIT',    'RANK_INIT');
define( 'NO_IN_POULE',  'NO_IN_POULE');
define( 'NB_VICT',      'NB_VICT');
define( 'NB_MATCH',     'NB_MATCH');
define( 'TD',           'TD');
define( 'TR',           'TR');
define( 'RANK_IN_POULE','RANK_IN_POULE');
define( 'RANK_FIN',     'RANK_FIN');
define( 'STATUT',       'STATUT');
define( 'FIRST_RES',    9);



function getTireurList( $domXml )
{
    $tireurList = array();
    
    $equipesListXml = $domXml->getElementsByTagName( 'Equipes' );
    foreach( $equipesListXml as $equipesXml ) 
    {
	foreach( $equipesXml->childNodes as $equipeXml ) 
	{
	    if( get_class( $equipeXml ) == 'DOMElement' )
		$tireurList[ getAttribut( $equipeXml, 'ID' ) ] = $equipeXml;
	}
    }
    
    if( count( $tireurList ) == 0 )
    {
	$tireursXml	= $domXml->getElementsByTagName( 'Tireurs' );
	foreach ($tireursXml as $tireurs) 
	{
	    $tireurXml = $tireurs->getElementsByTagName( 'Tireur' );
	    foreach ($tireurXml as $tireur) 
	    {
		$tireurList[ getAttribut( $tireur, 'ID' ) ] = $tireur;
	    }
	}
    }
    return $tireurList;
}

function getTireurRankingList( $phaseXml )
{
    $tireurList = array();
    
    foreach( $phaseXml->childNodes as $phaseChild )
    {
	if( isset( $phaseChild->localName ) )
	{
	    if( $phaseChild->localName == 'Tireur' || $phaseChild->localName == 'Equipe' )
	    {
		$tireurList[ getAttribut( $phaseChild, 'REF' ) ] = array_fill( 0, 15, "" );
		$tireurList[ getAttribut( $phaseChild, 'REF' ) ][ RANK_INIT ] = getAttribut( $phaseChild, 'RangInitial' );
		$tireurList[ getAttribut( $phaseChild, 'REF' ) ][ RANK_FIN ] = getAttribut( $phaseChild, 'RangFinal' );
		$tireurList[ getAttribut( $phaseChild, 'REF' ) ][ STATUT ] = getAttribut( $phaseChild, 'Statut' );
	    }
	    else if( $phaseChild->localName == 'Poule' )
	    {
		foreach( $phaseChild->childNodes as $pouleChild )
		{

		    if( isset( $pouleChild->localName ) )
		    {

			$tireurList[ getAttribut( $pouleChild, 'REF' ) ][ NO_IN_POULE ] = getAttribut( $pouleChild, 'NoDansLaPoule' );
			$tireurList[ getAttribut( $pouleChild, 'REF' ) ][ NB_VICT ] = getAttribut( $pouleChild, 'NbVictoires' );

			//			$tireurList[ getAttribut( $pouleChild, 'REF' ) ][ NB_MATCH ] = getAttribut( $pouleChild, 'NbMatches' );
			$tireurList[ getAttribut( $pouleChild, 'REF' ) ][ NB_MATCH ] = 0; //LPo
			// When BellePoule writes NbMatches, it still counts Exclusion and Abandon in
			// This leads to wrong V/M indices. Instead, we will increment this field for each valid match we see
			$tireurList[ getAttribut( $pouleChild, 'REF' ) ][ TD ] = getAttribut( $pouleChild, 'TD' );
			$tireurList[ getAttribut( $pouleChild, 'REF' ) ][ TR ] = getAttribut( $pouleChild, 'TR' );
			$tireurList[ getAttribut( $pouleChild, 'REF' ) ][ RANK_IN_POULE ] = getAttribut( $pouleChild, 'RangPoule' );
		    }
		}
	    }
	}
    }
    
    $matchXml = $phaseXml->getElementsByTagName( 'Match' );
    foreach( $matchXml as $match ) 
    {
	//*** 2 tireurs par match
	$tireur1Ref = -1;
	$tireur1Pos = -1;
	$tireur1Mark = -1;
	$tireur2Ref = -1;
	$tireur2Pos = -1;
	$tireur2Mark = -1;
	
	$k = 1;
	foreach( $match->childNodes as $tireur )
	{
	    if( isset( $tireur->tagName ) )
	    {
		if( $k == 1 )
		{
		    $tireur1Ref = getAttribut( $tireur, 'REF' );
		    $tireur1Pos = $tireurList[ $tireur1Ref ][ NO_IN_POULE ];
		    if( getAttribut( $tireur, 'Statut' ) == POULE_STATUT_VICTOIRE )
		    {
			$tireur1Mark = getAttribut( $tireur, 'Statut' );
			$tireurList[ $tireur1Ref ][ NB_MATCH ] ++; //LPo
			
			if( getAttribut( $tireur, 'Score' ) != getAttribut( $phaseXml, 'ScoreMax' ) )
			    $tireur1Mark .= getAttribut( $tireur, 'Score' );
		    }
		    else if( getAttribut( $tireur, 'Statut' ) == POULE_STATUT_ABANDON )
		    {
			$tireur1Mark = POULE_STATUT_ABANDON;
			$tireur2Mark = POULE_STATUT_ABANDON;
		    }
		    else if( getAttribut( $tireur, 'Statut' ) == POULE_STATUT_EXPULSION )
		    {
			$tireur1Mark = POULE_STATUT_EXPULSION;
			$tireur2Mark = POULE_STATUT_EXPULSION;
		    }
		    else
		    {
			if( getAttribut( $tireur, 'Statut' ) == POULE_STATUT_DEFAITE )
		    	    $tireurList[ $tireur1Ref ][ NB_MATCH ] ++; //LPo
			$tireur1Mark = getAttribut( $tireur, 'Score' );
		    }
		}
		else // $k==2
		{
		    $tireur2Ref = getAttribut( $tireur, 'REF' );
		    $tireur2Pos = $tireurList[ $tireur2Ref ][ NO_IN_POULE ];
		    if( getAttribut( $tireur, 'Statut' ) == POULE_STATUT_VICTOIRE )
		    {
			$tireur2Mark = getAttribut( $tireur, 'Statut' );
			$tireurList[ $tireur2Ref ][ NB_MATCH ] ++; //LPo
			if( getAttribut( $tireur, 'Score' ) != getAttribut( $phaseXml, 'ScoreMax' ) )
			    $tireur2Mark .= getAttribut( $tireur, 'Score' );
		    }
		    else if( getAttribut( $tireur, 'Statut' ) == POULE_STATUT_ABANDON )
		    {	
			$tireur1Mark = POULE_STATUT_ABANDON;
			$tireur2Mark = POULE_STATUT_ABANDON;
		    }
		    else if( getAttribut( $tireur, 'Statut' ) == POULE_STATUT_EXPULSION )
		    {	
			$tireur1Mark = POULE_STATUT_EXPULSION;
			$tireur2Mark = POULE_STATUT_EXPULSION;
		    }
		    else if( $tireur2Mark != POULE_STATUT_ABANDON && $tireur2Mark != POULE_STATUT_EXPULSION )
		    {
			if( getAttribut( $tireur, 'Statut' ) == POULE_STATUT_DEFAITE )
			    $tireurList[ $tireur2Ref ][ NB_MATCH ] ++; //LPo
			$tireur2Mark = getAttribut( $tireur, 'Score' );
		    }
		}
		
		$k++;
	    }
	}
	
	$tireurList[ $tireur1Ref ][ FIRST_RES + $tireur2Pos - 1 ] = $tireur1Mark;
	$tireurList[ $tireur2Ref ][ FIRST_RES + $tireur1Pos - 1 ] = $tireur2Mark;
	
	//	echo $tireur1Ref . ' Vs ' . $tireur2Ref . ' -> ' . $tireur1Mark . ' (' . $tireur2Pos . ') ' . ' / ' . $tireur2Mark . ' (' . $tireur1Pos . ')<br/> ';
    }
    
    return $tireurList;
}



/********************************************************/

/*                   CLASSEMENT GENERAL                 */

/********************************************************/
function renderClassement( $domXml )
{
    $list = '';
    
    $tireurCount = 0;
    
    $searchLabelParent = ( $domXml->documentElement->localName == 'CompetitionParEquipes' ) ? 'Equipes' : 'Tireurs';
    $searchLabelChildren = ( $domXml->documentElement->localName == 'CompetitionParEquipes' ) ? 'Equipe' : 'Tireur';
    
    $tireursXml	= $domXml->getElementsByTagName( $searchLabelParent );
    foreach ($tireursXml as $tireurs) 
    {
	$tireurXml = $tireurs->getElementsByTagName( $searchLabelChildren );
	$tireurCount = 0;
	
	foreach ($tireurXml as $tireur) 
	{
	    if( getAttribut( $tireur, 'Statut' ) != STATUT_ABSENT )
		$tireurCount++;
	}
    }
    
    $list .= '
	<table class="listeTireur">
		<tr>
			<th>Rang</th>
			<th>Nom</th>';
    
    if( $searchLabelChildren == 'Tireur' )
    {
	$list .= '
				<th>Prénom</th>';
    }
    
    $list .= '
			<th>Club</th>
		</tr>';
    
    $i = 1;
    $pair = "pair";
    while( $i <= $tireurCount )
    {
	foreach ($tireursXml as $tireurs) 
	{
	    $tireurXml = $tireurs->getElementsByTagName( $searchLabelChildren );
	    
	    foreach ($tireurXml as $tireur) 
	    {
		if( getAttribut( $tireur, 'Classement' ) == $i )
		{
		    $list .= '
						<tr class="'. $pair . '">
							<td>' . getAttribut( $tireur, 'Classement' ) . '</td>
							<td>' . getAttribut( $tireur, 'Nom' ) . '</td>';
		    
		    if( $searchLabelChildren == 'Tireur' )
		    {
			$list .= '
								<td>' . getAttribut( $tireur, 'Prenom' ) . '</td>';
		    }
		    
		    $list .= '
							<td>' . getAttribut( $tireur, 'Club' ) . '</td>
						</tr>';
		    
		    $pair = $pair == "pair" ? "impair" : "pair";
		}
	    }
	}

	$i++;
    }

    $list .= '
	</table>';

    return $list;

}
?>
