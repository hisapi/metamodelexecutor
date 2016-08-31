<?php
$functions['init']= function()
{
				global $dict;

				global $functions;

				// new
                                if (isset($this->set))
                                {
					$this->set->bindTo($this);
				}
				// end new
				if (isset($this->_supertypes))
                                {
                                        foreach ($this->_supertypes as $stk=>$stv)
                                        {
                                                if (!isset($this->_supertype))
                                                {
                                                        $this->_supertype=array();
                                                }
                                                $this->_supertype[$stk]=clone $dict[$stk];
						$this->_supertype[$stk]->_init=$functions['init']->bindTo($this->_supertype[$stk]);
                                                $o=$this->_supertype[$stk]->_init;
						$o();
                                        }
                                }
};


function new_type($k)
{
	global $dict;
	if ( !isset($dict[$k]) )
	{
		$dict[$k]=new stdClass();
		$dict[$k]->_type=$k;
	}
	else
	{
		return false;
	}

	return "CREATE TABLE IF NOT EXISTS ...";
} // END FUNCTION


function fix_reserved($s)
{
	global $reserved;
	foreach ($reserved as $rkl=>$rkv)
	{
		if ( in_array(strtolower($s),array_map('strtolower',$reserved[$rkl])) )
		{
			$s="_$s";
		}
	}
	return $s;
}
