<?php

class GPS_Coordinate
{
   var $dir = "";
   var $deg = "";
   var $min = "";
   var $sec = "";
   var $wholeSec = "";
   var $dec = 0;
   var $sign = "";
   var $type;

   function GPS_Coordinate($coords, $type = "lat")
   {
	$this->parse($coords, $type);
   }

   function parse($coords, $type) 
   {
        $this->cset['unparsed'] = $coords;     
	$this->type = $type;
	$this->sign = "";

//	echo "matching '$coords'\n";

	$matches = array();

	// "sa(N38.8600667,W9.2310667"
	if (preg_match("/([NWSE\-])?[^0-9NWSE\-]{0,3}([0-9]{1,3})[^0-9]{1,3}([0-9]{1,2})[^0-9]{1,3}([0-9]{1,3})([NWSE])?/i", $coords, $matches))
        {
	    // GPS Coordinates

	    $this->dir = $matches[1];
	    $this->deg = $matches[2] + 0;
	    $this->min = $matches[3] + 0;

	    # can be 600, 60 or 6
	    $this->sec = ("." . $matches[4]) + 0.0;

	    if ($matches[1] == "" && $matches[2] != "")
	    {
	      $this->dir = $matches[2];
	    }

        $this->dec = ($this->min + $this->sec)/60.0;

//	    echo "GPS match in GPSCoordinate $coords, parsed to dec: " . $this->dec . ", deg " . $this->deg . ", min " . $this->min . ", sec " . $this->sec . "<BR>\n";

	}
	else
	{
//	    echo "dec match in GPSCoordinate $coords<BR>\n";

	    // Decimal coordinates

            preg_match("/([NWSE\-])?[^0-9NWSE\-]*(([0-9]{1,3})\.([0-9]{1,}))/i", $coords, $matches);

            // 0 -71.46755
            // 1 -
 	    // 2 71.46755
	    // 3 71
	    // 4 46755

//	    echo "matches: " .  implode($matches, "', '");
	
	    // 1 = sign, 2 = deg, 3 = dec

	    $this->dir = $matches[1];
	    $this->deg = $matches[3] + 0;
            $this->dec = "0." . $matches[4];

	    $this->min = ($matches[2] - $matches[3]) * 60.0;
	    $this->sec = ($this->min - floor($this->min));
	    $this->min = floor($this->min);

//	    echo "dec match in GPSCoordinate $coords, parsed to dec: " . $this->dec . ", deg " . $this->deg . ", min " . $this->min . ", sec " . $this->sec . "<BR>\n";

        }

	// common parsing

        $this->dir = strtoupper(trim($this->dir));

	if ($this->dir == "S" || $this->dir == "W")
	{
	  $this->sign = "-";
	}
	else if ($this->dir == "-")
        {
           $this->sign = "-";
	   if ($type == "lat")
           {
	      $this->dir = "S";
	   }
	   else
           {
	      $this->dir = "W";
	   }
        }
	else
        {
	   $this->dir = "";
	   if ($type == "lat")
           {
	      $this->dir = "N";
	   }
	   else
           {
	      $this->dir = "E";
	   }
	}

	$this->wholeSec = floor(($this->sec * 60.0));

   }	

   function toGPSString()
   {
	return sprintf("%s%d %06.3f", $this->dir, $this->deg, $this->min + $this->sec);
   }

   function toDecString()
   {
	return $this->sign . ($this->deg + $this->dec);
   }

   function toDec()
   {
	return ($this->sign . ($this->deg + $this->dec)) + 0.0;
   }

   // TODO: this doesn't handle going from W to E or N to S
   function addGPS($deg, $min)
   {
	$deg = $this->deg + $deg;
	
	$newmin = $this->min + $this->sec + $min;
	if ($newmin > 60)
	{
	  $deg++;
	  $newmin -= 60;
	}
	else if ($newmin < 0)
	{
	  $deg--;
	  $newmin += 60;
	}
 	
	$newstr = sprintf("%s %d %02.3f ", $this->dir, $deg, $newmin);
//	echo "parsing $newstr\n";
	$this->parse($newstr, $this->type);
   }

}

?>
