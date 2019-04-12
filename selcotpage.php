<!DOCTYPE html>
<!--
To change this license header, choose License Headers in Project Properties.
To change this template file, choose Tools | Templates
and open the template in the editor.
-->
<html>
    <head>
        <meta charset="UTF-8">
        <title>SELECTION</title>
        <style>
            table
            {
                   border-collapse: collapse;
            }
            table td 
            {
                border : 1px solid black;
            }
            table tr:hover 
            {
                background-color : #AAA;
            }
            
        </style>
    </head>
    <body>
        <h1>Selection cotcot</h1>
        <?php
            require_once( "selcot.php" );
            echo selcot_table(scancot());
        // put your code here
        ?>
    </body>
</html>
