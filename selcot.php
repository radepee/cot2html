<?php

require_once( "tools.php" );

function check_filename($fn)
{
     $files = explorer( 'cotcot' );
     return in_array($fn,$files);
}

/* Select a COTCOT from the list $_SESSION[ 'cotcotFiles' ]  */
function scancot()
{
    $files = explorer( 'cotcot' );    
    $liste = array();
    foreach ($files as $key => $filename) 
    {
        $DOMxml = new DOMDocument( '1.0', 'utf-8' );
        $DOMxml->load( $filename );
        $competXml = $DOMxml->documentElement;
        $liste[$filename] = array
        (
            'TitreLong' => $competXml->getAttribute('TitreLong'),
            'Categorie' => $competXml->getAttribute('Categorie'),
            'Arme'      => $competXml->getAttribute('Arme'),
            'Sexe'      => $competXml->getAttribute('Sexe'),
            'Filename'  => $filename
        ); 
                
    }
    
   return $liste;
}

function selcot_table($liste)
{
    $tbl =  '<table class="liste_cots">';
    $tbl .= '<tr><th>COTCOT</th><th>Titre</th><th>Sexe</th><th>Catégorie</th><th>Arme</th></tr>';
    
      foreach ($liste as $filename => $data)
      {
        $href = "index.php?file=".urlencode($filename);
   
        $js = "onclick=\"document.location='$href';\"";
            
    //    echo "HREF:$href<br>";
    //    echo "JS:$js<br>";
              
        $tbl .= "<tr $js><td>" . $filename . '</td>';
        $tbl .= '<td>'.$data['TitreLong'].'</td>'; 
        $tbl .= '<td>'.$data['Sexe'].'</td>'; 
        $tbl .= '<td>'.$data['Categorie'].'</td>'; 
        $tbl .= '<td>'.$data['Arme'].'</td></tr>';              
      }
    $tbl .= '</table>';
    
    return $tbl;
}
