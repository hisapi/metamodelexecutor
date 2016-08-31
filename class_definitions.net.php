<?php
/*
Public Class Line

    Private mstrLine As String

    Property Line() As String
        Get
           Return mstrLine
        End Get
        Set(ByVal Value As String)
            mstrLine = Value
        End Set
    End Property
     
    ReadOnly Property Length() As Integer
       Get
           Return mstrLine.Length
       End Get
    End Property
End Class
*/

$nc="";
foreach ($dict as $de)
{

	$nc.="Public Class ".fix_reserved($de->_type);
	$nc.="\n";

	foreach ($de->_attribute_order as $da)
	{
		if ( substr($da,0,1)!="_")
		{
	
			$da_final=fix_reserved($da);


			if ( is_array($de->$da) )
			{
				$ary=$de->$da;
				$of_what = $ary['_list_of'];
				$of_what = fix_reserved($of_what);

				$nc.="\t";
				$nc.="Public $da_final as List(of $of_what) = new List(of $of_what)";
				// Private mstrLine As String
				$nc.="\n";
			}
			else
			{
				$nc.="\t";
				$nc.="Public $da_final as String = \"value\"";
				// Private mstrLine As String
				$nc.="\n";
			}
		}
	}

	$nc.="End Class";
	$nc.="\n";

}
