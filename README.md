# cot2html

A propos des champs de l'URL
"http://127.0.0.1/dist/index.php?file=cotcot%2Ftst2.cotcot&item=poudet&tabStart=256&tabEnd=16&zoom=0.6&scroll=1"

<b>file=cotcot%2Ftst2.cotcot</b> (Sélectionne un cotcot, dans cet exemple <b>cotcot/tst2.cotcot</b>)

<b>item=lst</b> (liste d'inscription)

<b>item =pou</b> (poules vue simple)

<b>item =poudet</b> (poules vue complète)

<b>item =clapou</b> (plus que le classement des poules. Une vue listing utile avant, durant et après les poules, avec heures et pistes). Note que le contenu exact change selon l'avancement des phases dans le cotcot. Donne aussi un classement dynamique durant la phase de poule.

<b>item =tab</b> (la vue du tableau, réglable avec tabStart et tabEnd (en puissance de 2: 256,128,64,32)

<b>item =clatab</b> (plus que le classement final, une vue qui liste le déroulement du tableau, avec heures et pistes et classement). Note que le contenu exact change selon l'avancement des phases dans le cotcot.

zoom une variable qui influence la tailles des éléments du CSS

<b>scroll=1</b> (active le scrolling et le reload automatique de la page

<b>ABC=1</b>  force un ordre alphabétique dans la vue classement des poules (item=clapou)

Un click de souris dans la barre de titre donne accès à un mini GUI, avec un curseur pour gérer le niveau de zoom, et une navigation vers les autres vue. C'est utile pour piloter les pages sans un clavier.
