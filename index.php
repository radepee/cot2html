<?php
session_start();

require_once( "tools.php" );
require_once( "my_phase_pointage.php" );
require_once( "my6.php" ); // my3
require_once( "functions.php" );
require_once( "pays.php" );

$_SESSION[ 'cotcotFiles' ] = explorer( 'cotcot/' );
//fillSessionWithTitreLong();
	if( count ($_SESSION[ 'cotcotFiles' ]) < 1)
	{
	    echo  "Aucun fichier<br></body></html>";
            exit();
	}
$files = $_SESSION[ 'cotcotFiles' ]; 
arsort ($files);
foreach ($files as $key => $filename) break;
//       echo "FILE:".$filename."<br>";
$xml = new DOMDocument( "1.0", "utf-8" );
$xml->load( $filename);
      
$head_title = 'Cotcot';
$good_zoom  = 1;
$item   = isset($_GET['item'])?$_GET['item']:'menu';
$tour   = isset($_GET['tour'])?$_GET['tour']:1;
$ncol   = isset($_GET['ncol'])?intval($_GET['ncol']):1;
$detail = isset($_GET['pack'])?0:1;
$fold   = isset($_GET['fold'])?intval($_GET['fold']):0;
$abc    = isset($_GET['ABC'])?intval($_GET['ABC']):0;
$scroll = isset($_GET['scroll'])?$_GET['scroll']:0;
$class = '';
switch( $item )
{
    case 'lst' : $head_title = 'Liste de Présence';
        $good_zoom = ($ncol==1)?2.5:0.75;
    break;

    case 'poudet':
    case 'pou': $head_title = 'Poules';
        $good_zoom = 0.5;
    break;

    case 'clapou': $head_title = 'Classement Poules';
        $good_zoom = ($ncol==1)?2:0.75;
    break;

    case 'clatab': $head_title = 'Classement Tableau';
        $good_zoom = ($ncol==1)?2:0.75;
    break;

    case 'tab': $head_title = 'Tableau';
        $good_zoom = 0.5;
    break;

    case 'flag':
        $class = 'flag_page';
        break;
    
    case 'menu':
        $head_title = getTitre($xml);
    break;
}   

function dump_UI($tabl) {
    $tabStart = isset($_GET['tabStart']) ? intval($_GET['tabStart']) : 256;
    $tabEnd = isset($_GET['tabEnd']) ? intval($_GET['tabEnd']) : 2;
    $scale = isset($_GET['zoom'])?floatval($_GET['zoom']):1;
    $item   = isset($_GET['item'])?$_GET['item']:'menu';

    echo "<div id='autohide' class='autohide'>";
    
    if(!IE())
    {
        echo "<div class='slider-zoom' id='slider-zoom'></div><br>";
        echo "<br>";
    }
    else
    {
        echo "<h1>Because of its outdated design, Internet Explorer cannot fully render this site.<br>";
        echo "Try to visit us again with a more recent browser.</h1><br>";
    }
    echo "<button class='buttonicon' onClick='scro(0)'>&check;</button>";
    echo "<button class='buttonicon' onClick='scro(1)'>&udarr;</button>";
    echo "<button class='buttonicon' onClick='scro(2)'>&olarr;</button>";

    $T = '"lst"'; $S = ($item=='lst')?'_dis':'';
    echo "<button class='buttonicon$S'  onClick='item($T)'> <img class='buttoniconi$S' src='liste.svg'/> </button>\n";
    $T = '"pou"'; $S = ($item=='pou')?'_dis':'';
    echo "<button class='buttonicon$S'  onClick='item($T)'> <img class='buttoniconi$S' src='pou.svg'/>   </button>\n";
    $T = '"poudet"'; $S = ($item=='poudet')?'_dis':'';
    echo "<button class='buttonicon$S'  onClick='item($T)'> <img class='buttoniconi$S' src='poudet.svg'/>   </button>\n";
    $T = '"clapou"'; $S = ($item=='clapou')?'_dis':'';
    echo "<button class='buttonicon$S'  onClick='item($T)'> <img class='buttoniconi$S' src='clapou.svg'/></button>\n";

    $T = '"tab"'; $S = ($item=='tab')?'_dis':'';
    echo "<button class='buttonicon$S'  onClick='item($T)'> <img class='buttoniconi$S' src='tab1.svg'/>  </button>\n";

$T = '"clatab"'; $S = ($item=='clatab')?'_dis':'';
    echo "<button class='buttonicon$S'  onClick='item($T)'> <img class='buttoniconi$S' src='clatab.svg'/></button>\n";

        if ($tabl)
    {
        echo "<div class='slider-tableau' id='slider-tableau'></div>";  
    }

    echo "</div>";
}

?>


<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="fr">
    <head>
        <title> <?php echo $head_title ?> </title>
        <!--        <meta http-equiv="refresh" content="5" >-->
        <style>
         :root{
	     --rescale : <?php
			 $scale = isset($_GET['zoom'])?floatval($_GET['zoom']):$good_zoom;
			 printf("%5.3f",$scale);
			 ?> ;
	 }
         .spu23hp
	 {
             height:3vh;
	     font-size:3vh;
             font-weight:bolder;
             color:red;
	     max-width:85vw;
	     max-height:50px;
	     margin-left:2vw;
	     margin-right:2vw;
	     margin-top:2vh;
	     margin-bottom:2vh;
	 }
         .u23hp
	 {
	     height:8vh;
	     max-width:85vw;
	     max-height:50px;
	     margin-left:2vw;
	     margin-right:2vw;
	     margin-top:2vh;
	     margin-bottom:2vh;
	 }
	 .button_div
	 {
	     margin-left:2vw;
	     margin-right:2vw;
	     margin-top:2vh;
	     margin-bottom:2vh;
	     text-decoration: none;
	     background-color: #EEEBEE;
	     color: #333333;
	     padding:0.5vh;
	 }
	 .button
	 {
	     margin:auto auto;
	     font: bold 18px Arial;
	     text-decoration: none;
	 }

	</style>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <?php
        if (!IE())
        {
           echo '<link rel="stylesheet"  type="text/css" title="Design de base" href="const.css" />';
           echo '<link rel="stylesheet" type="text/css" title="Design de base" href="flag_icons.css" />';
        }
        else        
        {
            echo '<link rel="stylesheet"  type="text/css" title="Design de base" href="ie.css" />';
             echo '<link rel="stylesheet" type="text/css" title="Design de base" href="flag_icons_ie.css" />';
        }
        ?>
        <link href="nouislider.css" rel="stylesheet">
    </head>
    <!-- 
         <meta http-equiv="refresh" content="15" />
    -->
    
<?php 

    if ($item == 'menu')
    {
    ?>
        <body>
                <div  class="spu23hp"><?php echo "$head_title </div>
	<a href='index.php?item=lst&tabStart=256&tabEnd=16&zoom=0.8&scroll=$scroll'>
	    <div class='button_div'>
		<img src='ban_listel.svg' class='u23hp'>
	    </div>
	</a>
	<a href='index.php?item=poudet&tabStart=256&tabEnd=16&zoom=0.6&scroll=$scroll'>
	    <div class='button_div'>
		<img src='ban_pou.svg' class='u23hp'>
	    </div>
	</a>
	<a href='index.php?item=clapou&tabStart=256&tabEnd=16&zoom=0.63&scroll=$scroll'>
	    <div class='button_div'>
		<img src='ban_clapou.svg' class='u23hp'>
	    </div>
	</a>
	<a href='index.php?item=tab&tabStart=256&tabEnd=2&zoom=0.5&scroll=$scroll'>
	    <div class='button_div'>
		<img src='ban_tab.svg' class='u23hp'>
	    </div>
	</a>
	<a href='index.php?item=clatab&tabStart=256&tabEnd=2&zoom=0.7&scroll=$scroll'>
	    <div class='button_div'>
		<img src='ban_clatab.svg' class='u23hp'>
	    </div>
	</a>";
?>
	    <div class="button_div">
		<img src="pointe.svg" class="u23hp">
	    </div>

    </body>
  </html>

<?php     
exit();
    }
    else
        echo        "<body class='$class' onload='startit($scroll);' onmousem2ove='m2ickey(); '>"; 
               
 /*       if (IE())
        {
            echo "<h1 class='home'>Apparently you use Internet Explorer (IE).<br>";
            echo "Sadly, IE is outdated and does do render this site correctly.<br>";
            echo "Please come back again with a modern browser.</h1><br>";
            echo "</body></html>";
            exit();
        } */
        $burst_length = 6;
        $burst_extra_delay = 100;
        $burst_speed = 18;
        $burst_end_delay = 300; 
        $burst_timer = 18;
        $intra_burst_delay=1;
        
        $GLOBALS['TH'] = 0;

      $titre= "INTERNATIONAL LAUSANNE FENCING CHALLENGE";

	    switch( $item )
	    {
        case 'lst' :
            dump_UI(0);
            $etape = 0;
            echo afficheClassementPoules($xml, $ncol, 1, $etape, 'LISTE DE PRÉSENCE');
            break;

        case 'pou':
            dump_UI(0);
            echo affichePoules($xml, $tour, 0);
            break;
        
        case 'poudet':
            dump_UI(0);
            echo affichePoules($xml, $tour, 1);
            break;

        case 'clapou':
            dump_UI(0);

            $etape = 1;
//            $burst_speed = 4;
            echo afficheClassementPoules($xml, $ncol, $abc, $etape, 'CLASSEMENT POULES');
            break;

        case 'clatab':
            dump_UI(0);
            $etape = -1;
            repairTableau($xml);
            echo afficheClassementPoules($xml, $ncol, $abc, $etape, 'CLASSEMENT TABLEAU');
            break;

        case 'flag':
            repairTableau($xml);
            echo drapeauxPodium($xml);
            break;

        case 'tab':
            /*                    $burst_length = 1;
              $burst_speed = 6;
              $intra_burst_delay = 1;
              $burst_timer = 100;
              $burst_extra_delay = 1;
             */
            dump_UI(1);
            $burst_end_delay = 10;
            repairTableau($xml);
            echo renderMyTableau($xml, $detail, $fold, 'TABLEAU');
            break;

        case 'clafin':
            echo renderClassement($xml);
            break;

        case 'menu':
        default:
            echo "Fichier en cours $filename <br>";
            
$te = IE()?2:16;

$mixte= mixteMaleFemale ($xml);
switch ($mixte)
{
    case 'F':
    echo "<a class='home' href='index.php?item=lst&tabStart=256&tabEnd=$te'>Tireuses</a><br>";
    break;  
    case 'FM':
    echo "<a class='home' href='index.php?item=lst&tabStart=256&tabEnd=$te'>Tireuses et tireurs</a><br>";
    break;  
    case 'M':
    echo "<a class='home' href='index.php?item=lst&tabStart=256&tabEnd=$te'>Tireurs</a><br>";
    break;  
}

if ($mixte != 'E')
{
echo "<a class='home' href='index.php?item=pou&tabStart=256&tabEnd=$te'>Poules</a><br>";
echo "<a class='home' href='index.php?item=clapou&tabStart=256&tabEnd=$te'>Classement poules</a><br>";
echo "<a class='home' href='index.php?item=tab&tabStart=256&tabEnd=$te'>Tableau</a><br>";
echo "<a class='home' href='index.php?item=clatab&tabStart=256&tabEnd=$te'>Classement tableau</a><br>";
}
?>
	    <?php
	    break;
	    }
	    //	    echo '</div>';
	    
	    
	    echo '</div>';

	    ?>
    </body>
            <script src="nouislider.js"></script>
        <script language="javascript" src="functions.js" type="text/javascript"></script>

    <?php
    
    echo "<script>\n";
    echo "var intra_burst_delay=$intra_burst_delay;\n";
    echo "var speed=$burst_speed;\n";
    echo "var burst_timer=$burst_timer;\n";
    echo "var glob_burst_length=$burst_length;\n";
    echo "var intra_burst_delay=3;\n";
    echo "var extra_burst_delay=$burst_extra_delay;\n";
    echo "var end_delay=$burst_end_delay;\n";
    echo "</script>\n";
 
    function IE()
    {
        $ua = htmlentities($_SERVER['HTTP_USER_AGENT'], ENT_QUOTES, 'UTF-8');
        if (preg_match('~MSIE|Internet Explorer~i', $ua) || (strpos($ua, 'Trident/7.0; rv:11.0') !== false)) {
            // do stuff for IE
            return 1;
        }
        return 0;
    }
    
    
    function Tsvg($l1,$l2,$alpha)
    {
       return "<svg
   viewBox='-6 -6 134 134'
   height='100%'
   width='100%'
   id='tab1'
   version='1.1'>
  <path
     visibility='hidden'
     d='m 0,0 0,128 128,0 0,-128 z'
     id='path7'
     style='visibility:hidden;' />
  <text
     id='text3366'
     y='45.669491'
     x='62.703388'
     style='font-style:normal;font-variant:normal;font-weight:900;font-stretch:normal;font-size:40px;line-height:125%;font-family:\"Arial Black\";text-align:center;letter-spacing:0px;word-spacing:0px;writing-mode:lr-tb;text-anchor:middle;fill:#000000;fill-opacity:$alpha;stroke:none;stroke-width:1px;stroke-linecap:butt;stroke-linejoin:miter;stroke-opacity:1'
     xml:space='preserve'><tspan
       y='45.669491'
       x='62.703388'
       id='tspan3368'>$l1</tspan></text>
  <text
     id='text3370'
     y='105.85595'
     x='62.614101'
     style='font-style:normal;font-variant:normal;font-weight:900;font-stretch:normal;font-size:67.37400055px;line-height:125%;font-family:\"Arial Black\";text-align:center;letter-spacing:0px;word-spacing:0px;writing-mode:lr-tb;text-anchor:middle;fill:#000000;fill-opacity:$alpha;stroke:none;stroke-width:1px;stroke-linecap:butt;stroke-linejoin:miter;stroke-opacity:1'
     xml:space='preserve'><tspan
       y='105.85595'
       x='62.614101'
       id='tspan3372'>$l2</tspan></text>
</svg>";


    }
    ?>
</html>
