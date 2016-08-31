<?php
$cc="<?php\n".$odm_content."\n\n";
$cc="";
foreach ($dict as $de)
{

	$de_final=$de->_type;
	if ( in_array(strtolower($de_final),array_map('strtolower',$reserved['php'])) )
	if ( in_array($de_final,$reserved['php']) )
	{
		$de_final="_$de_final";
	}

	$cc.="class ".$de_final." extends object_database_mapping";
	$cc.="\n";
	$cc.="{";
	$cc.="\n";


	foreach ($de->_attribute_order as $da)
	{
		if ( substr($da,0,1)!="_")
		{
	
			$da_final=$da;

			if ( in_array(strtolower($da_final),array_map('strtolower',$reserved['php'])) )
			{
				$da_final="_$da_final";
			}

			if ( is_array($de->$da) )
			{
				$cc.="\t";
				$cc.="public \$$da_final=array();";
				$cc.="\n";
			}
			else
			{
				$cc.="\t";
				$cc.="public \$$da_final='value';";
				$cc.="\n";
			}
		}
	}

	$cc.="}";
	$cc.="\n";

}
