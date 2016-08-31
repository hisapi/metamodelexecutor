<?php
if (isset($_GET['action']) && $_GET['action']=="new")
{
	for ($i=0;$i<10;$i++)
	{
		if ( isset($_POST[$i."_obj1"]) && isset($_POST[$i."_obj2"]) &&  isset($_POST[$i."_obj3"]))
		{
			$o1=$_POST[$i."_obj1"];
			$o2=$_POST[$i."_obj2"];
			$o3=$_POST[$i."_obj3"];
			if ( strlen($o1)>0 && strlen($o2)>0 && strlen($o3)>0 )
			{
				$o1=str_replace("'","\'",$o1);
				$o2=str_replace("'","\'",$o2);
				$o3=str_replace("'","\'",$o3);
				$q=$db->query("INSERT INTO rels (obj1,obj2,obj3) VALUES ('$o1','$o2','$o3');");
			}
		}
	}
}
