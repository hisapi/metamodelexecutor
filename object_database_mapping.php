<?php

class object_database_mapping
{
	public $obj_key_type='hash';
	public $obj_version;
	public function __construct()
	{
		global $schema_version;
		$this->obj_version = $schema_version;
	}
	public function create_from_xml_array($data)
	{
		$this->fromobjectxml($data);
	}
	public function rcreate($props,$mode_raw=false)
	{
		$this->create($props,$mode_raw);
	}
	public function member_value_array()
	{
		$retval=array();
		$localprops="";
		$member_list=$this->member_list($this);
		foreach ($member_list as $member)
		{
			$retval[$member]=$this->$member;
		}
		return $retval;
	}
	public function form()
	{
		$member_list=$this->member_list($this);
		$atomic_members=array();
		foreach ($member_list as $member)
		{
			if (!is_array($this->$member))
			{
				$atomic_members[]=$member;
			}
		}
		$retval="<form action='?action=add-".get_class($this)."' method='post'>";
		foreach ($atomic_members as $am)
		{
			$retval.="$am:";
			$retval.="<input type='text' name='$am'/>";
			$retval.="<br/>";
		}
		$retval.="<input type='submit'>";
		$retval.="</form>";
		return $retval;
	}
	public function fromobjectxml($data)
	{
		// data is an array'd xml

		$localprops="";
		$member_list=$this->member_list($this);
		foreach ($member_list as $member)
		{
			if ( isset($data['@attributes'][$member]) )
			{
				//echo "setting $member equal to ".($data['@attributes'][$member]);
				//echo "<br/>";
				if ($member!="str_file")
				{
					$this->$member = html_entity_decode($data['@attributes'][$member],ENT_XML1,"UTF-8");
				}
				else
				{
					$this->$member = base64_decode($data['@attributes'][$member]);
				}
			}
			else
			{
				if (is_array($data) && array_key_exists($member,$data['@attributes']))
				{
					if ( strpos($member,"int_")===0)
					{
						$this->$member="0";
					}
					else
					{
						$this->$member="";
					}
				}
				else
				{
					//echo "unable to find $member";
					//echo "<br/>";
					//print_r($data);
				}
			}
		}
	}
	public function toobjectxml()
	{
		$localprops="";
		$member_list=$this->member_list($this);
		foreach ($member_list as $member)
		{
			$member_val = $this->$member;
			$member_vals= toxmlvalue($this->$member);
			//$member_val = htmlentities($this->$member, ENT_DISALLOWED|ENT_XML1, "UTF-8");
			$localprops=$localprops." $member='".$member_val."'";
		}
		$retval="<".get_class($this).$localprops.">";
		//$retval = $retval.var_export($this,true);
		$retval=$retval."</".get_class($this).">\n";
		//echo $retval;
		return $retval;
	}
	public function send($queue,$data)
	{
		global $APP;
		$queue_name=$APP['ms']->queue_prefix."".get_class($this)."_".$queue;
		//echo "sending data to queue: ".$queue_name;
		$APP['ms']->send_message($queue_name,$data);
	}
	public function receive($queue)
	{
		global $APP;
		return $APP['ms']->read_message($APP['ms']->queue_prefix."".get_class($this)."_".$queue);
	}
	public function toxml()
	{
		$localprops="";
		$member_list=$this->member_list($this);
		foreach ($member_list as $member)
		{
			if ($member=="id_user") continue;
			if ($member=="id_resource") continue;
			if (strpos($member,"id_hf")===0) continue;
			if ($member=="id") continue;
			if ($member=="id_expr") continue;
			if (strpos($member,"str_")===0) continue;
			$localprops=$localprops." $member='".toxmlvalue($this->$member)."'";
		}
		if (!$this->obj_inherited)
		{
			$retval="<".get_class($this).$localprops.">";
			$retval=$retval."</".get_class($this).">\n";
		}
		return $retval;
	}
	public $obj_debug=false;
	public function build($obj_build_exclude=array())
	{
		global $APP;
		//echo "BUILDING...\n";
		$member_list=$this->member_list($this);
		foreach ($member_list as $member)
		{
			if ( strpos( $member, "str_")!==FALSE )
			{
				$obj_prop=str_replace("str_","obj_",$member);
				//echo "building $obj_prop\n";
				$this->$obj_prop=new strings();
				$hash_id=$this->$member;
				$this->$obj_prop->get_from_hashrange( $hash_id );
				if ($this->$obj_prop->id=='undefined' || in_array($obj_prop,$obj_build_exclude) )
				{
					//echo "SKIPPING $obj_prop";
					$this->$obj_prop=false;
				}
				else
				{
					$this->$obj_prop->build();
				}
			}
		}
	}
	public function create_raw($props)
	{
		$this->create($props,true);
	}
	public function create_table($props)
	{
		global $APP;
		
		$table_list = $APP['db']->get_tables();
		$this_table_name = get_class($this);

		if ( in_array($this_table_name,$table_list) )
		{
			return;
		}
		else
		{
			$member_list=$this->member_list($this);
			$atomic_members = array();
			foreach ($member_list as $member)
			{
				if ( !is_array($this->$member) )
				{
					$atomic_members[]=$member;
				}
			}
			$APP['db']->create_table($this_table_name,$atomic_members);
		}
	} // END FUNCTION
	public function set($props)
	{
		$props=$this->remove_non_members($props);
		foreach ($props as $prop_key=>$prop_value)
		{
			$this->$prop_key=$prop_value."";
		}
	}
	public function delete()
	{
		global $APP;
		$member_list=$this->member_list($this);
		$first_member=$member_list[0];

		$select_conditions=array();

		$select_condition=new SelectComparison();
		$select_condition->field=$first_member;
		$select_condition->comparison="EQUAL";
		$select_condition->value=$this->$first_member;
		$select_conditions[]=$select_condition;
		if ($this->obj_key_type=="hashrange")
		{
				$second_member=$member_list[1];
				$select_condition=new SelectComparison();
				$select_condition->field=$second_member;
				$select_condition->comparison="EQUAL";
				$select_condition->value=$this->$second_member;
				$select_conditions[]=$select_condition;
		}
		
		foreach ($member_list as $each_member)
		{
			if (strpos($each_member,"str_")===0)
			{
				$obj_member = str_replace("str_","obj_",$each_member);
				if ( isset($this->$obj_member) && $this->$obj_member)
				{
					$this->$obj_member->delete();
				}
			}
		}

		if ($this->obj_debug)
		{
			$APP['db']->debug=true;
		}
		$APP['db']->delete(get_class($this),$select_conditions);
	}
	public function create($props,$mode_raw=false)
	{
		global $APP;
		if ($this->obj_debug)
		{
			echo "before remove";
			print_r($props);
		}
		$props=$this->remove_non_members($props);

		if ($this->obj_debug)
		{
			echo "after remove";
			print_r($props);
		}
		$props_keys=array_keys($props);
		for($i=0;$i<count($props_keys);$i++)
		{
			$prop_key=$props_keys[$i];
			if ( strlen(trim($prop_key))==0)
			{
				continue;
			}
			$prop_val=$props[$props_keys[$i]];
			if (!$mode_raw)
			{
				$prop_val=$this->special_storage($prop_key,$prop_val);
				$props[$prop_key]=$prop_val;
			}
		}
		$this->create_table(get_class($this),$props);

		if ($this->obj_debug)
		{
			echo "<pre>";
			echo "CREATE()<br/>";
			echo "PROPS:<br/>";
			print_r($props);
			$APP['db']->debug=true;
		}
		$APP['db']->insert(get_class($this),$props);
		foreach ($props as $prop_key=>$prop_val)
		{
			//$prop_val=str_replace("'","\'",$prop_val); // "'
			$this->$prop_key=$prop_val."";
		} // end foreach
		if ($this->obj_debug)
		{
			print_r($this);
		}
	} // end function
	public function special_storage($prop_key,$prop_val)
	{
		global $APP;
		if ( strpos($prop_key,"str_") !== FALSE )
		{
			if ($this->obj_debug)
			{
				echo "In Special_storage function() for prop: $prop_key<br/><br/>";
			}
			$content_mime_type="text/plain";
			$content_extension="txt";

			$original_content_extension = $content_extension;


			$sha1_string=sha1(microtime().$prop_key.$prop_val.rand(3,5));
			$keyname=$GLOBALS['settings'][$APP['fs']->kind]['paths']['strings']['@attributes']['value']."/".$sha1_string.".".$content_extension;
			$bucket_name=$GLOBALS['settings'][$APP['fs']->kind][$APP['fs']->bucket_syntax()]['@attributes']['value'];
			$content_detection_info = get_mime_and_extension($prop_val);
			// GIVES MIME TYPE, EXTENSION
			$content_mime_type = $content_detection_info[0];
			$content_extension = $content_detection_info[1];

			if ( !stringEndsWith($keyname,$content_extension) )
			{
				$keyname = str_lreplace($original_content_extension,$content_extension,$keyname);
			}

			$APP['fs']->create_object(false,$bucket_name,$keyname,$prop_val,$content_mime_type);
			$string_url=$APP['fs']->key_url($bucket_name,$keyname);
			$sha1_string2=sha1($prop_key.microtime().$prop_val.rand(1,20));
			$props_string=array();
			$props_string['id']=$sha1_string2;
			$props_string['val']=$string_url;
			$new_string=new strings();
			$new_string->create($props_string);

			$prop_val=$sha1_string2;
		} // end if
		return $prop_val;
	} // end function
	public function update_raw($props)
	{
		$this->update($props,true);
	}
	public function remove_non_members($props)
	{
		$member_list=$this->member_list($this);
		$props_keys=array_keys($props);
		foreach ($props_keys as $prop_key)
		{
				$found_member=false;
				foreach ($member_list as $member)
				{
						if ($prop_key==$member)
						{
								$found_member=true;
								break;
						}
				}
				if (!$found_member)
				{
						unset($props[$prop_key]);
				}
		}
		return $props;
	}
	public function update($new_props,$mode_raw=false)
	{
		global $APP;
		if ( !is_array($new_props) )
		{
			echo "model.classes.php Update() function expects array as input";
		}
		if ($this->obj_debug)
		{
			echo "UPDATE called in class ".get_class($this);
			print_r($new_props);
			$APP['db']->debug=true;
		}
		$bucket_name=$GLOBALS['settings'][$APP['fs']->kind][$APP['fs']->bucket_syntax()]['@attributes']['value'];
		$member_list=$this->member_list($this);
		$first_member=$member_list[0];
		$second_member="";
		if (!$mode_raw)
		{
			$new_props_keys=array_keys($new_props);
			for ($i=0;$i<count($new_props_keys);$i++)
			{
				$new_props[$new_props_keys[$i]]=$this->special_storage($new_props_keys[$i],$new_props[$new_props_keys[$i]]);
			}
		}
		$select_conditions=array();

		$select_condition=new SelectComparison();
		$select_condition->field=$first_member;
		$select_condition->comparison="EQUAL";
		$select_condition->value=$this->$first_member;
		$select_conditions[]=$select_condition;
		if ($this->obj_key_type=="hashrange")
		{
			$second_member=$member_list[1];
			$select_condition=new SelectComparison();
			$select_condition->field=$second_member;
			$select_condition->comparison="EQUAL";
			$select_condition->value=$this->$second_member;
			$select_conditions[]=$select_condition;
		}
		$APP['db']->update( get_class($this),$new_props,$select_conditions );
		foreach ($new_props as $new_prop_key=>$new_prop_val)
		{
			$this->$new_prop_key=$new_prop_val."";
		}
	}
	public function get_from_hashrange($hash,$range=false,$comparison="EQUAL",$count=0)
	{
		global $APP;
		// comparison == EQUAL or BEGINS_WITH
		if ($hash=="undefined")
		{
			return;
		}
		if ($range=="undefined")
		{
			return;
		}
		if ($this->obj_debug)
		{
			echo "Searching for hash: ".$hash;
			echo "<br/>";
			echo "in table: ".get_class($this);
			echo "<br/>";
			echo "range: ".$range;
			echo "<br/>";
			echo "Comparison: ".$comparison;
			echo "<br/>";
			echo "Count: ".$count;
			echo "<br/>";
			echo "<br/>";
		}
		$member_list=$this->member_list($this);
		$the_members=array();
		foreach ($member_list as $member_name)
		{
			if (!is_array($this->$member_name) )
			{
				$the_members[$member_name]=$member_name;
			}
		}
		$the_members=$this->remove_non_members($the_members);

		$the_member_keys=array_keys($the_members);
		$select_props=array();
		$new_comparison=new SelectComparison();
		$new_comparison->field=$the_member_keys[0];
		$new_comparison->comparison="EQUAL";
		$new_comparison->value=$hash;
		$new_comparison->tabletype=$this->obj_key_type;
		$select_props[]=$new_comparison;
		if ( strlen($range)>0 )
		{
			$new_comparison=new SelectComparison();
			$new_comparison->field=$the_member_keys[1];
			$new_comparison->comparison=$comparison;
			$new_comparison->value=$range;
			$new_comparison->tabletype=$this->obj_key_type;
			$select_props[]=$new_comparison;
		}
		if ($this->obj_debug)
		{
			$APP['db']->debug=true;
		}
		$retval=$APP['db']->select_table(get_class($this),$the_member_keys,$select_props,$count);
		//echo "CALLED by ".get_class($this)."<br/>";
		//echo "RETURNING:<br/>";
		//echo "<ul>";
		//echo "<pre>";
		//print_r($retval);
		//echo "<pre>";
		//echo "</ul>";
		if ($retval)
		{
			if ($this->obj_debug)
			{
				/*
				echo "result from hf:";
				print_r($retval);
				*/
			}
			foreach ($retval as $single)
			{
				$i=0;
				foreach ($single as $retval_key=>$retval_val)
				{
					if ($i==0)
					{
						if ($retval_val=="undefined")
						{
						}
					}
					$this->$retval_key=$retval_val."";
					$i=$i+1;
				}
			}
		}
		return $retval;
	}
	public function member_list($instance)
	{
		$retval=array();
		$class_name=get_class($instance);
		$reflector = new ReflectionClass($class_name);
		$properties = $reflector->getProperties();
		foreach($properties as $property)
		{
			$prop_name=$property->getName();
			if ( strpos($prop_name,"obj_")===FALSE )
			{
				$retval[]=$prop_name;
			}
		}
		return $retval;
	}
}
class SelectComparison
{
        public $field;
        public $comparison; // EQUAL or BEGINS_WITH
        public $value;
        public function __construct()
        {
        }

}
