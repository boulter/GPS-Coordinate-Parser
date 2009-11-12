<?php

require_once 'GPS/Coordinate.php';

class GPS_CoordinateSet {

   var $lat = "";
   var $lon = "";
   var $sortKey = 0;
   var $geolog;
   var $altitude;   
   var $rectX;
   var $rectY;
   var $rectZ;
   var $info;

   function GPS_CoordinateSet($coordStr, $altitude = 0)
   {
        // parse out each part
        $this->altitude = $altitude;

	global $DEBUG;

  	$coordStr = str_replace("\r\n", ' ', $coordStr);
  	$coordStr = str_replace("\n", ' ', $coordStr);
  	$coordStr = str_replace("\r", ' ', $coordStr);
	$coordStr = str_replace("&deg;", '', $coordStr);

	if (isset($DEBUG))
	{
	   echo "\"$coordStr\"\n";
	}

// GPS coordinates
        if (!preg_match("/([NWSE\-]?)([^0-9NWSE]{0,3}[0-9]{1,3}[^0-9]{1,4}[0-9]{1,2}[^0-9]{1,3}[0-9]{1,3})[^a-z0-9\-]*([NWSE\-]?)([^0-9NWSE]{0,3}[0-9]{1,3}[^0-9]{1,3}[0-9]{1,2}[^0-9]{1,3}[0-9]{1,3})[^NWSE]*([NWSE]?)/im", $coordStr, $matches))
{
	if (isset($DEBUG))
	{
	    echo "did not match GPS regex<BR>\n";
	}

   	   // decimal coordinates
   	   preg_match("/([NWSE\-]?[^0-9]*[0-9]{1,3}\.[0-9]{1,}[^a-z0-9\-]+)([NWSE\-]?[^0-9]*[0-9]{1,3}\.[0-9]{1,})/i", $coordStr, $matches);

	$lat = $matches[1];
	$lon = $matches[2];

//	echo "$lat $lon\n";

        }
	else
	{

		$latDir1 = $matches[1];
		$latDir2 = $matches[3];
		$lonDir1 = $matches[3];
		$lonDir2 = $matches[5];
		$lat = $matches[2];
		$lon = $matches[4];	

//		var_dump($matches);


		// we either use "-", "" or NWSE, can't use both
 		if (preg_match("/\-/", $latDir2) && preg_match("/[NWSE]/i", $latDir1))
		{
			$latDir1 = "";
		}

		// we want 1 and 1 or 2 and 2 or blank and 1 or 1 and blank
		if ($latDir1 != "" && $lonDir1 != "")
		{
		  $lat = $latDir1 . $lat;
		  $lon = $lonDir1 . $lon;
		}
		else if ($latDir2 != "" && $lonDir2 != "")
		{
		  $lat = $latDir2 . $lat;
		  $lon = $lonDir2 . $lon;
		}
		else if ($latDir1 == "" && $lonDir1 != "")
		{
		  $lat = "N" . $lat;
		  $lon = $lonDir1 . $lon;
		}
		else if ($latDir1 != "" && $lonDir1 == "")
		{
		  $lat = $latDir1 . $lat;
		  $lon = "E" . $lon;
		}
		else
		{
		  $lat = "N" . $lat;
		  $lon = "E" . $lon;
		}

	}
	if ($DEBUG)
	echo "coords " . $lat . " " . $lon . "<BR>\n";
	
       $this->lat = new GPS_Coordinate($lat, "lat");
       $this->lon = new GPS_Coordinate($lon, "lon");

       $this->calcRect();
   }

function calcRect()
{
//   echo "Lat: " . ($this->lat->toDecString());
       $latdec = deg2rad($this->lat->toDecString());
       $londec = deg2rad($this->lon->toDecString());
//   echo "dec: $latdec $londec\n";
    
   $er = 6378137.0;
   $f = 1.0/298.257223560;
   $ee =  2.0 * $f - $f * $f;
   $h = $this->altitude;
//   echo "Alt: $h\n";

   $b = $er / sqrt(1 - $ee * sin($latdec) * sin($latdec));
   $d = ($b + $h) * cos($latdec);
   $this->rectX = $d * cos($londec);
   $this->rectY = $d * sin($londec);
   $this->rectZ = ($b *(1 - $ee) + $h) * sin($latdec);
}

// -------------------------------------------------------------------------
// METHOD:  CLatLon::VincentyDistance()
/*! 
   \brief  Calculates the distance and forward and reverse azimuths between 
           this point and P using the Vincenty method.

   \author fizzymagic
   \date   9/6/2003

   \return  [double] - Distance between this point and P in meters.

   \param P [CLatLon&] - Point to which to compute distance.
   \param pForwardAzimuth [double *] - Pointer to parameter to receive 
                                       forward azimuth in degrees.
   \param pReverseAzimuth [double *] - Pointer to parameter to receive
                                       reverse azimuth in degrees.


   This method computes a high-accuracy distance between this point and P
   using the Vincenty method and the WGS84 ellipsoid.  It also calculates 
   and returns the forward and reverse azimuths.
*/
// -------------------------------------------------------------------------

function vincentyDistance($cset, $units = "m")
{
  return $this->distance($cset, $units);
}

function distance($cset, $units = "m")
{
   $result['forward'] = 0;
   $result['reverse'] = 0;
   $result['distance'] = 0;

   if ($cset->toGPSString() == $this->toGPSString())
   {
	return $result;
   }

// Degrees to radians conversion.
   $Deg2Rad = 1.74532925199433E-02;

   $EPSILON = 5.e-14;

   $m_ellipsoidA = 6378137.00;
   $m_ellipsoidInv = 298.257223563;

   // Check to see if either latitude is 90 degrees exactly.
   // In that case, the distance is a closed-form expression!
   
   $thislat = $this->getLat();
   $thislon = $this->getLong();

   $plat = $cset->getLat();
   $plon = $cset->getLong();
   $dLat1  = $Deg2Rad * $thislat->toDec();
   $dLat2  = $Deg2Rad * $plat->toDec();
   $dLong1 = $Deg2Rad * $thislon->toDec();
   $dLong2 = $Deg2Rad * $plon->toDec();

/*
   var_dump($thislat->toDec());
   var_dump($thislon->toDec());
   var_dump($plat->toDec());
   var_dump($plon->toDec());
*/

   $a0 = $m_ellipsoidA;
   $flat = 1.0 / $m_ellipsoidInv;
   $r = 1.0 - $flat;
   $b0 = $a0 * $r;

   $tanu1 = $r * tan($dLat1);
   $tanu2 = $r * tan($dLat2);

   $dtmp = atan($tanu1);

   if (abs($thislat->toDec()) >= 90.0)
   {
	$dtmp = $dLat1;
   }

   $cosu1 = cos($dtmp);
   $sinu1 = sin($dtmp);

   $dtmp = atan($tanu2);
   if (abs($plat->toDec()) >= 90.0) 
   {
      $dtmp = $dLat2;
   }

   $cosu2 = cos($dtmp);
   $sinu2 = sin($dtmp);

   $omega = $dLong2 - $dLong1;
   $lambda = $omega;

   do {
      $testlambda = $lambda;
      $ss1 = $cosu2 * sin($lambda);
      $ss2 = $cosu1 * $sinu2 - $sinu1 * $cosu2 * cos($lambda);
      $ss = sqrt($ss1 * $ss1 + $ss2 * $ss2);
      $cs = $sinu1 * $sinu2 + $cosu1 * $cosu2 * cos($lambda);
      $tansigma = $ss / $cs;
      $sinalpha = $cosu1 * $cosu2 * sin($lambda) / $ss;
      $dtmp = asin($sinalpha);
      $cosalpha = cos($dtmp);
      $cosalpha2 = $cosalpha * $cosalpha; 
      $c2sm = $cs - 2.0*$sinu1*$sinu2/$cosalpha2;
      $c = $flat/16.0 * $cosalpha2*(4.0 + $flat*(4.0 - 3.0*$cosalpha2));
      $lambda = $omega + (1.0 - $c)*$flat*$sinalpha*(asin($ss) + $c*$ss*($c2sm + $c*$cs*(-1.0 + 2.0*$c2sm*$c2sm)));
      $dDeltaLambda = abs($testlambda - $lambda);
   } while ($dDeltaLambda > $EPSILON);

   $u2 = $cosalpha2 * ($a0*$a0 - $b0*$b0)/($b0*$b0);
   $a = 1.0 + ($u2 / 16384.0) * (4096.0 + $u2 * (-768.0 + $u2 * (320.0 - 175.0 * $u2)));
   $b = ($u2 / 1024.0) * (256.0 + $u2 * (-128.0 + $u2 * (74.0 - 47.0 * $u2)));

   $dsigma = $b * $ss * ($c2sm + ($b / 4.0) * ($cs * (-1.0 + 2.0 * $c2sm*$c2sm) 
                 - ($b / 6.0) * $c2sm * (-3.0 + 4.0 * $ss*$ss) * (-3.0 + 4.0 * $c2sm*$c2sm)));

   $s = $b0 * $a * (asin($ss) - $dsigma);

   $alpha12 = atan2($cosu2 * sin($lambda), ($cosu1 * $sinu2 - $sinu1 * $cosu2 * cos($lambda)))/$Deg2Rad;
   $alpha21 = atan2($cosu1 * sin($lambda), (-$sinu1 * $cosu2 + $cosu1 * $sinu2 * cos($lambda)))/$Deg2Rad;

   $result['distance'] = $this->convertUnits($s, $units);

   $result['forward'] = ($alpha12 + 360.0) % 360.0;
   $result['reverse'] = ($alpha21 + 180.0) % 360.0;

   return $result;
}

function convertUnits($s, $units)
{

   $units = strtoupper($units);

   if ($units == "K")
	return $s / 1000.00;
   else if ($units == "NM")
	return $s / 1852.0; 
   else 
	return $s / 1609.344; 
}

   function distance3D($cset, $units = "m")
   {
	$xd = $cset->getRectX() - $this->getRectX();
	$yd = $cset->getRectY() - $this->getRectY();
	$zd = $cset->getRectZ() - $this->getRectZ();

	return $this->convertUnits(sqrt($xd*$xd + $yd*$yd + $zd*$zd), $units);
   }

   function toRectString()
   {
      return $this->rectX . ", " . $this->rectY . ", " . $this->rectZ;
   }

   function getRectX()
   {
      return $this->rectX;
   }
   function getRectY()
   {
      return $this->rectY;
   }
   function getRectZ()
   {
      return $this->rectZ;
   }

   function gcDistance($cset, $units)
   {
//	echo "calculating distance from " . gettype($cset) . " to $this<BR>";

	$lt1 = $cset->getLat();
	$ln1 = $cset->getLong();
	
	$lon1 = $ln1->toDecString();
	$lat1 = $lt1->toDecString();

	$lt2 = $this->lat;
	$ln2 = $this->lon;

	$lon2 = $ln2->toDecString();
	$lat2 = $lt2->toDecString();

//	echo "calculating distance from $lat1, $lon1 to $lat2, $lon2<BR>";

	$theta = $lon1 - $lon2; 
 	$dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta)); 
 	$dist = acos($dist); 
 	$dist = rad2deg($dist); 
 	$miles = $dist * 60 * 1.1515; 
	$unit = strtoupper($unit); 

        if ($unit == "K") { 
        	return ($miles * 1.609344); 
 	} else if ($unit == "N") { 
        	return ($miles * 0.8684); 
	} else { 
        	return $miles; 
       	} 
   }

   function compareBySortKey($a, $b)
   { 
//	echo "comparing " . $a->getSortKey() . " " . $b->getSortKey() . "<BR>";

        if ($a->getSortKey() == $b->getSortKey()) {
          return 0;
        }

        return ($a->getSortKey() < $b->getSortKey()) ? -1 : 1;
   }

   function getLat()
   {
	return $this->lat;
   }

   function getSortKey()
   {
	return $this->sortKey;
   }

   function setSortKey($key)
   {
	return $this->sortKey = $key;
   }

   function addGPS($latDeg, $latMin, $lonDeg, $lonMin)
   {
	$this->lat->addGPS($latDeg, $latMin);
	$this->lon->addGPS($lonDeg, $lonMin);
        $this->calcRect();
   }

   function getLong()
   {
	return $this->lon;
   }

   function isValid()
   {
	return (($this->lat->toDec() + $this->lon->toDec()) != 0);
   }

   function toGPSString()
   {
	return $this->lat->toGPSString() . "  " . $this->lon->toGPSString();
   }

   function toDecString()
   {
	return $this->lat->toDecString() . "  " . $this->lon->toDecString();

   }
}

?>
