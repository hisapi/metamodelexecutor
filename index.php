<?php
ini_set('display_errors', 'On');
error_reporting(E_ALL);

if ( file_exists("cache/cache.classes.php") )
{
	$dict=unserialize(file_get_contents("cache/cache.classes.php"));
}
else
{
	$dict=array();
}

include("model.database.php");
$db=new Database_Mysql();
include("config.php");
$db->connect();
include("controller.action.php");
$q=$db->query("SELECT * FROM rels;");

$APP=array();
$APP['db']=$db;


// 2-line front controller
$BASE_PATH = str_replace("index.php","",$_SERVER['SCRIPT_NAME']);

$RESOURCE_REQUEST = substr($_SERVER['REQUEST_URI'],strlen($BASE_PATH),strlen($_SERVER['REQUEST_URI'])-strlen($BASE_PATH));
if ($RESOURCE_REQUEST=="?"){$RESOURCE_REQUEST="";}
if (strstr($RESOURCE_REQUEST,"?")!==false)
{
	$RESOURCE_REQUEST=explode("?",$RESOURCE_REQUEST);
	$RESOURCE_REQUEST=$RESOURCE_REQUEST[0];
}

$reserved=array();
include("reserved_words.php.php");
include("reserved_words.net.php");
include("functions.php");

function merge($operation,$o1,$o2,$dk,$dv)
{
	global $dict;
	global $functions;
	$retval=array();

	if ($dk==$o1 && $operation=="is_a_kind_of")
	{
		if (!isset($dict[$o1]->_supertypes))
		{
			$dict[$o1]->_supertypes=array();
		}
		if ( !isset($dict[$o1]->_supertypes[$o2]) )
		{
			$dict[$o1]->_supertypes[$o2]=$o1;
		}
	}
	if ($dk==$o1 && $operation=="selected_from")
	{
	
		$propname="id_".$o2;
		$dict[$o1]->$propname="value";
	}
	// LOOP THROUGH THE PROPERTIES INSIDE OF EACH ENTITY TYPE
	foreach ($dv as $sk=>&$sv)
	{
		if ($sk==$o1 && $operation=="selected_from")
		{
			// helps address string "selected from" entries - a behavioral trait
			if (!isset($dv->_from))
			{
				$dv->_from=array();
			}
			if (!isset($dv->_from[$o1]))
			{
				$dv->_from[$o1]=$o2;
				$dict[$o2]->id="value";
			}
		}
		if ($sk==$o1 && $operation=="plural_of")
		{
			$sv=array();
			$dv->id="value";
			if (!isset($dv->_hash))
			{
				$dv->_hash="id";
			}
			$sv['_list_of']=$o2;
			$idv="id_".$dk;
			if ($dk!=$o2)
			{
				new_type($o2);
			}
			$dict[$o2]->$idv="value";
			// BORROWING SELECTED_FROM in order to create linkage b/t id_abc field and parent table
			if (!isset($dict[$o2]->_from))
			{
				$dict[$o2]->_from=array();
			}
			if (!isset($dict[$o2]->_from[$idv]))
			{
				$dict[$o2]->_from[$idv]=$dk;
			}
			$dict[$o2]->_hash=$idv;
			$dict[$o2]->_index="hashrange";

			// END BORROW
			$dict[$o2]->id="value";
		}
	}
	return $retval;
}

foreach ($q as $aq)
{
	$obj1=str_replace(" ","_",$aq['obj1']);
	$obj2=str_replace(" ","_",$aq['obj2']);
	$obj3=str_replace(" ","_",$aq['obj3']);
	$obj1=fix_reserved($obj1);
	$obj3=fix_reserved($obj3);

	if ($obj2=="is_a_kind_of")
	{
		new_type($obj3);
		$dict[$obj3]->id="value";
		$dict[$obj3]->_hash="id";
		if (!isset($dict[$obj3]->_index))
		{
			$dict[$obj3]->_index="hash";
		}
	}
	if ($obj2=="has" || $obj2=="have")
	{
		new_type($obj1);
		$dict[$obj1]->$obj3="value";
		if (!isset($dict[$obj1]->_index))
		{
			$dict[$obj1]->_index="hash";
		}
	}
} // END FOREACH

$eval_passes=array();
$eval_passes[]="plural_of";
$eval_passes[]="selected_from";
$eval_passes[]="is_a_kind_of";
foreach ($eval_passes as $to_eval)
{
	foreach ($q as $aq)
	{
		$obj1=str_replace(" ","_",$aq['obj1']);
		$obj2=str_replace(" ","_",$aq['obj2']);
		$obj3=str_replace(" ","_",$aq['obj3']);
		$obj1=fix_reserved($obj1);
		$obj3=fix_reserved($obj3);

	
		if ($obj2==$to_eval )
		{
			foreach ($dict as $dk=>$dv)
			{
				$af=merge($obj2,$obj1,$obj3,$dk,$dv);
			}
		}
	}
} // END FOREADCH

function idfordersort($a,$b)
{
	if ($a == $b) {
		return 0;
	}
	
	$has_id_check=(strpos($a,"id")!==false && strpos($b,"id")===false);

	$both_are_id=(strpos($a,"id")!==false && strpos($b,"id")!==false);
	if ($both_are_id)
	{
		$has_id_check=($a=="id" && $b!="id");
	}
	
	return $has_id_check ? -1 : 1;
}

foreach ($dict as $dk=>$dv)
{
	$keys=array();
	foreach ($dv as $dvk=>$dvv)
	{
		if ( substr($dvk,0,1)=="_") continue;
		if (isset($dv->_hash) && $dvk==$dv->_hash) continue;
		$keys[]=$dvk;
	}
	usort($keys, "idfordersort");
	if ( isset($dv->_hash) )
	{
		array_unshift($keys,$dv->_hash);
	}
	$dict[$dk]->_attribute_order=$keys;
	$dict[$dk]->_property_order=array();
	foreach ($dict[$dk]->_attribute_order as $ao)
	{
		if ( isset($dict[$dk]->$ao) && !is_array($dict[$dk]->$ao) )
		{
			$dict[$dk]->_property_order[]=$ao;
		}
	}
}


if (isset($_GET['action']) && $_GET['action']=="new")
{
	file_put_contents("cache/cache.classes.php",serialize($dict));
}
/*
file_put_contents("cache/cache.objects.php","<?php \$objects=".var_export($objects,true));
file_put_contents("cache/cache.controllers.php","<?php \$objects=".var_export($controllers,true));
*/

$odm_content="
\$APP=array();
global \$APP;
\$APP['db']=false; // database
\$APP['fs']=false; // file storage
\$APP['ms']=false; // messaging

\$dbsetting=array();
\$dbsetting['DBHOST']='localhost';
\$dbsetting['DBUSER']='username';
\$dbsetting['DBPASS']='mypass';
\$dbsetting['DBNAME']='mydb';
\$dbsetting['DBTABLEPREFIX']='abc_';
\$dbsetting['dbtype']='mysql';
";

$odm_content.=file_get_contents("model.database.php");

$odm_content.= <<<EOL

\$DBA=new Array_Database_Adapter(\$dbsetting);
\$APP['db']=\$DBA->database;

EOL;

$odm_content.=file_get_contents("object_database_mapping.php");
$odm_content=str_replace("<?php","",$odm_content);

$cc="";
include("class_definitions.php.php");
$nc="";
include("class_definitions.net.php");

$eval_code = (str_replace("<?php","",file_get_contents("object_database_mapping.php")).";".$cc);
//echo "<textarea>";
//echo $eval_code;
//echo "</textarea>";
eval($eval_code);

$explode_resource=explode("/",$RESOURCE_REQUEST);
$domain = $explode_resource[0];
$instance= "";
if ( count($explode_resource)>1 )
{
	$instance = $explode_resource[1];
}

$actions=array(
	'create'
);

if ($domain=="")
{
	foreach ($dict as $dk=>$dv)
	{
		if ($dv->_index!="hash") continue;
		echo "<pre>";
		//print_r($dv);
		echo $dk;

		foreach ($actions as $action)
		{
			echo "[";
			echo "<a href='$dk/$action'>";
			echo ucfirst($action);
			echo "</a>";
			echo "]";
		}
		echo "<br/>";

	}
} // END IF (LIST OF TOP-LEVEL ENTITIES)

if ($instance=="new")
{
	$NEW=new $domain();
	if (!isset($_POST['id']))
	{
		$_POST['id']=sha1(microtime());
	}
	$NEW->create($_POST);
	$hv=$dict[$domain]->_hash;
	if ($dict[$domain]->_index=="hash")
	{
		header("Location: ".$BASE_PATH.$domain."/".$NEW->$hv);
	}
	else
	{
		header("Location: ".$BASE_PATH.$domain."/".$NEW->$hv."-".$NEW->id);
	}
}


if ( isset($_GET['back']) )
{
	$BRESOURCE_REQUEST = substr($_GET['back'],strlen($BASE_PATH),strlen($_GET['back'])-strlen($BASE_PATH));
	if ($BRESOURCE_REQUEST=="?"){$BRESOURCE_REQUEST="";}
	if (strstr($BRESOURCE_REQUEST,"?")!==false)
	{
		$BRESOURCE_REQUEST=explode("?",$BRESOURCE_REQUEST);
		$BRESOURCE_REQUEST=$BRESOURCE_REQUEST[0];
	}
	echo "<a href='".$_GET['back']."'>";
	echo "Back"; // to ".$BRESOURCE_REQUEST;
	echo "</a>";
	echo "<br/>";
	echo "<br/>";
}

if ($instance=="create")
{
	if ( isset($dict[$domain]) )
	{

		//echo "<pre>";
		//print_r($dict[$domain]);
		echo "<div style='font-weight:bold;text-decoration:underline;'>";
		echo "Create ".$domain;
		echo "</div>";
		echo "<div style='padding-left:20px;'>";
		echo "<form action='$BASE_PATH$domain/new' method='post' style='display:inline;'>";
		foreach ($dict[$domain]->_attribute_order as $dk)
		{
			if ($dk=="id") continue;
			if ( strpos($dk,"_")===0 ) continue;
			$dv=$dict[$domain]->$dk;
			if (isset($dict[$domain]->_hash) && $dict[$domain]->_hash==$dk) 
			{
				if (!isset($dict[$domain]->_from[$dk]))
				{
					continue;
				}
			}
			//if ( is_array($dv) ) continue;

			echo $dk.": ";
			echo "<br/>";
			if ( isset($dict[$domain]->_from[$dk]) )
			{
				echo "<select name='$dk'>";
				if (!isset($_GET[$dk]))
				{
					$entname=$dict[$domain]->_from[$dk];
					$retval=$APP['db']->select_table($entname,$dict[$entname]->_property_order,array());
					// open selection
					foreach ($retval as $row)
					{
						echo "<option";
						echo " value='";
						echo $row['id'];
						echo "'";
						echo ">";
						echo $row['id'];
						if ( isset($row['name']) )
						{
							echo " - ";
							echo $row['name'];
						}
						echo "</option>";
					}
				}
				else
				{
					// closed selection
					$val=$_GET[$dk];
					if (strstr($_GET[$dk],"-")!==false)
					{
						$hy_split=explode("-",$_GET[$dk]);
						$val=$hy_split[1];
					}
					echo "<option value='".$val."'>".$val."</option>";
				}
				echo "</select>";
			}
			else
			{
				if ( is_array($dict[$domain]->$dk) )
				{
					echo "<div style='padding-left:20px;'>";
					echo "Create this $domain first, then you can add $dk";
					echo "</div>";
				}
				else
				{
					if (!isset($_GET[$dk]))
					{
						echo "<input type='text' name='$dk' value=''/>";
					}
					else
					{
						echo "<input readonly='readonly' type='text' name='$dk' value='".htmlspecialchars($_GET[$dk])."'/>";
					}
				}
			}
			echo "<br/>";
		}
		echo "<input type='submit' value='Create $domain'/>";
		echo "</form>";
		echo "</div>";
	}
} // END IF (INSTANCE==CREATE)

if ($instance!='new' && $instance!='create')
{
	if ( isset($dict[$domain]) )
	{
		//print_r($dict[$domain]);
		$NEW=new $domain();
		//$NEW->obj_debug=true;

		$all_records=array();
		if ($dict[$domain]->_index=="hash"||strstr($instance,"-")===false)
		{
			$all_records=$NEW->get_from_hashrange($instance);
		}
		else
		{
			$parts=explode("-",$instance);
			$all_records=$NEW->get_from_hashrange($parts[0],$parts[1]);
		}

		if ($all_records)
		{
			foreach ($all_records as $ar)
			{
				echo "<b style='font-size:16px;'>";
				echo "Displaying a $domain";
				echo "</b>";
				echo "<br/>\n";
				echo "<br/>\n";
				echo "<div style='padding-left:20px;'>";

				$NEW=new $domain();
				$NEW->set($ar);
				foreach ($NEW->member_list($NEW) as $nm)
				{
					if (!is_array($NEW->$nm))
					{
						echo $nm;
						echo ":";
						echo $NEW->$nm;
						echo "<br/>\n";
					}
				}
				foreach ($NEW->member_list($NEW) as $nm)
				{
					if (is_array($NEW->$nm))
					{
						echo $nm;
						echo ":";
						echo "<div style='padding-left:20px;'>";
		
						// show existing array entries
						$ent_lo = $dict[$domain]->$nm;
						$ent_add = $ent_lo['_list_of'];
						$hash_existing = $instance;
						if ($dict[$domain]->_index=="hashrange")
						{
							$hash_existing=$NEW->id;
						}
	
						echo "<a href='$BASE_PATH$ent_add/$hash_existing?back=".$_SERVER['REQUEST_URI']."'>";
						echo "List all $nm";
						echo "</a>";
	
						echo "&nbsp;";
						echo "&nbsp;";
	
	
						// show form to submit new entry to array
						//print_r($NEW->$nm);
						//echo "<br/>";
						$ent_lo = $dict[$domain]->$nm;
						$ent_add = $ent_lo['_list_of'];
						$default_select="?id_$domain=$hash_existing";
						echo "<a href='$BASE_PATH$ent_add/create$default_select&back=".$_SERVER['REQUEST_URI']."'>";
						echo "Click to Add";
						echo "</a>";
	
						/*
	
						$url = "http://".$_SERVER['HTTP_HOST'].$BASE_PATH.$ent_add."/create";
						$url=$url.$default_select;
						echo file_get_contents($url);
						*/
						echo "</div>";
					}
				}

				echo "</div>";
				echo "<br/>";

			} // end foreach

		}

		//print_r($dict[$domain]);
	}
}	
include("big_table.php");
