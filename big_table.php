<?php

echo "<form action='?action=new' method='post'><table style='position:absolute;top:0px;background-color:grey;left:500px;'>";
foreach ($q as $aq)
{
	$obj1=str_replace(" ","_",$aq['obj1']);
	$obj2=str_replace(" ","_",$aq['obj2']);
	$obj3=str_replace(" ","_",$aq['obj3']);
	$obj1=fix_reserved($obj1);
	$obj3=fix_reserved($obj3);
echo "<tr>";
echo "<td>";
echo "<input type='text' value=\"".str_replace("\"","&quot;",$obj1)."\">";
echo "</td>";
echo "<td>";
echo "<input type='text' value=\"".str_replace("\"","&quot;",$obj2)."\">";
echo "</td>";
echo "<td>";
echo "<input type='text' value=\"".str_replace("\"","&quot;",$obj3)."\">";
echo "</td>";
echo "</tr>";
}
for ($i=0;$i<10;$i++)
{
echo "<tr>";
echo "<td>";
echo "<input type='text' name='$i"."_obj1'/>";
echo "</td>";
echo "<td>";
echo "<input type='text' name='$i"."_obj2'/>";
echo "</td>";
echo "<td>";
echo "<input type='text' name='$i"."_obj3'/>";
echo "</td>";
echo "</tr>";
}
echo "<tr><td colspan='3' align='right'><input type='submit'/></td></tr>";
echo "<tr><td colspan='3'>";
echo "<b>";
echo "Suggested Relational Operators:";
echo "</b>";
echo "<br/>";
echo "<br/>";
echo "</td></tr>";
echo "<tr><td></td><td>";
echo "has<br/>";
echo "have<br/>";
echo "plural of<br/>";
echo "instance of<br/>";
echo "selected from<br/>";
echo "new<br/>";
echo "is a kind of<br/>";
echo "</td></tr>";
echo "<tr><td colspan='3'>";
echo "<br/>";
echo "<br/>";
echo "<b>";
echo "PHP Class Definitions:";
echo "</b>";
echo "<br/>";
echo "<textarea rows='10' style='width:100%;'>";
echo $cc;
echo "</textarea>";
echo "</td></tr>";
echo "<tr><td colspan='3'>";
echo "<br/>";
echo "<br/>";
echo "<b>";
echo ".NET Class Definitions:";
echo "</b>";
echo "<br/>";
echo "<textarea rows='10' style='width:100%;'>";
echo $nc;
echo "</textarea>";
echo "</td></tr>";
echo "</table></form>";
