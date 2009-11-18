<?php

function WesnothMap( $maptext ) 
{
	global $phpbb_root_path;
	$maptext = htmlentities($maptext);
	$revalue="";
	//look if it's 1.2 or 1.3 map format
	if (strpos($maptext,","))
	{
		//check if there is a header
		$header_and_map=SplitHeaderMap( $maptext );
//		echo $header_and_map;
		$header=$header_and_map[0];
		$map=$header_and_map[1];
		if (trim($map)==""||!$map)
		{
			return "No map data supplied.";
		}
		if (sizeof($header)==0)
		{
			$revalue=WesnothMapNew($map,0); //the map data has no borders
		}
		else
		{
			if( $header["border_size"]==1 )
			{
				$revalue=WesnothMapNew($map,1); //the map data includes borders which have to be removed
			}
			else
			{
				return "Invalid Data: border_size has to be 1";
			}
		}
		return $revalue.'<br><a href="data:application/octet-stream,'. rawurlencode($maptext) .'">Download this map</a><br>';
	}
	else 
	{
		return WesnothMapOld($maptext);
	}
}

function SplitHeaderMap( $maptext ) 
{
	$lines=explode("&lt;br /&gt;", $maptext);
	$header=array();
	$map_data="";
	$is_map_data=0;
	foreach($lines as $line)
	{
		if (trim($line)=="")
		{
			continue;
		}
		if ($is_map_data==0)
		{
			$key_and_value=explode("=", $line);
			if( sizeof($key_and_value) == 2)
			{
				$key=trim($key_and_value[0]);
				$value=trim($key_and_value[1]);
				$header[$key]=$value;
			}
			elseif (!strpos($line, "="))
			{
				$is_map_data=1;
				$map_data.=$line."\n";
			}
			else
			{
				return "Invalid Data: ".$line;
			}
		}
		else
		{
			$map_data.=$line."\n";
		}
	}
	
	$header_and_map=array($header,$map_data);
	return $header_and_map;
}

/**
 * Nathan Codding - August 24, 2000.
 * Takes a string, and does the reverse of the PHP standard function
 * htmlspecialchars().
 */

// Released under the GPL v2.0 by the author, Sparr.
// Version 1.2

// This function takes what should be a wesnoth map file
// and converts it to a css-arranged grid of tiles.

function WesnothMapNew( $maptext, $has_border ) {
	$origmaptext = $maptext;
	$maxrowwidth = 0;
	$maparray[0][0];
	$newmaparray=array();
	
	//convert the map text into a 2d array
	$lines=explode("\n", $maptext);
	$x=0;
	foreach($lines as $line)
	{
		//split the line into terrains and remove whitespaces
		if (trim($line)=="")
		{
			continue;
		}
		
		$linearray=explode(",",$line);
		$y=0;
		foreach ($linearray as $terrain)
		{
			$linearray[$y]=trim($terrain);
			$y+=1;
		}
		$maparray[$x]=$linearray;
		$x+=1;
	}
	
	if ($has_border==1)
	{
		array_shift($maparray); //remove the first row
		array_pop($maparray); //remove the last row
		
		for($i = 0; $i<=sizeof($maparray)-1; $i++)
		{
			array_shift($maparray[$i]); //remove the first column
			array_pop($maparray[$i]); //remove the last column
		}
	}
		

	// Split each row of the map into two half-rows
	// Thanks to zircu and Ox41464b from irc://irc.freenode.net/#php
	// for help with the logic here
	foreach ($maparray as $linearray)
	{
		$even=array();
		$odd=array();
		$even[]='{+';// tags for odd and even line css
		$odd[]='{-';
		$i=0;
		foreach ($linearray as $terrain)
		{
			if ($terrain >= ' ')
				if ($i % 2) $odd[]=$terrain;
				else $even[]=$terrain;
			$i+=1;
		}
		if ($i > $maxrowwidth) $maxrowwidth = $i;
		$even[]='}';// markup, applied later.
		$odd[]='}';
		//append $even and $odd to $newmaparray which will look like this: ["{+","Gg","Gg","Gg","}","{-","Gg","Gg","Gg","}"]
		foreach ($even as $content) $newmaparray[]=$content;
		foreach ($odd as $content) $newmaparray[]=$content;
	} 

	$maparray = $newmaparray;

	// calculate tile sizes, try to keep the map under 720px wide
	$quartersize = min(floor(270 / $maxrowwidth),9); // 9 is full size
	$halfsize = $quartersize*2;
	$threesize = $quartersize*3;
	$size = $quartersize*4; // width of a full square tile
	
	// if the hexes are to be shrunk by the browser then we have to overlap them
	// to avoid problems with anti-aliased edges not covering each other
	if ($quartersize<9) $offset=1;
	
	// loop through the map, find each letter and convert it to an html
	// img tag with the appropriate filename and markup
	$newmaptext = '';
	$firsthalf = TRUE;
	$xcoord=-1;
	$ycoord=1;
	foreach ($maparray as $letters)
	{
		//if the terrain contains a player starting location, show only the player starting location, but make the text include the name
		$checkplayer=explode(" ",$letters);
		$player="NONE";
		if (($checkplayer[0] >= ' ') && ($checkplayer[1] >= ' ')) 
		{	
			$player=$checkplayer[0];
			$letters=$checkplayer[1];
		}
		switch ($letters)
		{
			case '{+': case '{-': case '}':
				$newmaptext .= $letters; break; // control characters from earlier
			//void
			case '_off^_usr':$image = 'void.png';$terrain = 'None'; break;
			case '_s': 		$image = 'void.png'; 	$terrain = 'Shroud'; break;
			case '_f': 		$image = 'void.png'; 	$terrain = 'Fog'; break;
			//snow
			case 'Ai': 		$image = 'ice.png'; 	$terrain = 'Ice'; break;
			case 'Aa': 		$image = 'snow2.png'; 	$terrain = 'Snow'; break;
			//bridges
			case 'Ww^Bw|': 	$image = 'bridge-n-s-tile.png'; $terrain = 'Bridge(Grassland,Shallow Water)'; break;
			case 'Ww^Bw/': 	$image = 'bridge-ne-sw-tile.png'; $terrain = 'Bridge(Grassland,Shallow Water)'; break;
			case 'Ww^Bw\\': $image = 'bridge-se-nw-tile.png'; $terrain = 'Bridge(Grassland,Shallow Water)'; break;
			case 'Wo^Bw|': 	$image = 'bridge-n-s-tile.png'; $terrain = 'Bridge(Grassland,Deep Water)'; break;//missing image
			case 'Wo^Bw/': 	$image = 'bridge-ne-sw-tile.png'; $terrain = 'Bridge(Grassland,Deep Water)'; break;//missing image
			case 'Wo^Bw\\': $image = 'bridge-se-nw-tile.png'; $terrain = 'Bridge(Grassland,Deep Water)'; break;//missing image
			case 'Ss^Bw|': 	$image = 'bridge-n-s-tile.png'; $terrain = 'Bridge(Grassland,Swamp)'; break;//missing image
			case 'Ss^Bw/': 	$image = 'bridge-ne-sw-tile.png'; $terrain = 'Bridge(Grassland,Swamp)'; break;//missing image
			case 'Ss^Bw\\': $image = 'bridge-se-nw-tile.png'; $terrain = 'Bridge(Grassland,Swamp)'; break;//missing image
			//castles
			case 'Ce': 		$image = 'encampment-tile.png'; $terrain = 'Encampment(Castle)'; break;
			case 'Ch': 		$image = 'castle-tile.png'; $terrain = 'Castle'; break;
			case 'Cv': 		$image = 'castle-tile.png'; $terrain = 'Elven Castle(Castle)'; break;
			case 'Cud': 	$image = 'dwarven_castle-tile.png'; $terrain = 'Dwarven Castle(Castle)'; break;
			case 'Chr': 	$image = 'castle-ruin-tile.png'; $terrain = 'Castle Ruin(Castle)'; break;
			case 'Chw': 	$image = 'castle-sunken-ruin-tile.png'; $terrain = 'Sunken Ruin(Castle,Shallow Water)'; break;
			case 'Chs': 	$image = 'castle-sunken-ruin-tile.png'; $terrain = 'Swamp Ruin(Castle,Swamp) '; break;//missing image
			case 'Ke': 		$image = 'keep-tile.png'; $terrain = 'Encampment Keep(Castle)'; break;//missing image
			case 'Kh': 		$image = 'keep-tile.png'; $terrain = 'Human Keep(Castle)'; break;
			case 'Kv': 		$image = 'keep-tile.png'; $terrain = 'Elven Keep(Castle)'; break;//missing image
			case 'Kud': 	$image = 'keep-tile.png'; $terrain = 'Dwarven Keep(Castle)'; break;//missing image
			case 'Khr': 	$image = 'keep-tile.png'; $terrain = 'Ruined Keep(Castle)'; break;//missing image
			case 'Khw': 	$image = 'keep-tile.png'; $terrain = 'Sunken Keep(Castle,Shallow Water)'; break;//missing image
			case 'Khs': 	$image = 'keep-tile.png'; $terrain = 'Swamp Keep(Castle,Swamp)'; break;//missing image
			//sand
			case 'Dd^Dc': 	$image = 'desert2.png'; $terrain = 'Desert Crater(Sand)'; break;//missing image
			case 'Dd': 		$image = 'desert2.png'; $terrain = 'Desert(Sand)'; break;
			case 'Dd^Dr': 	$image = 'desert2.png'; $terrain = 'Rubble(Hills)'; break;//missing image
			case 'Ds': 		$image = 'sand.png'; $terrain = 'Sand'; break;
			case 'Dd^Do': 	$image = 'desert-oasis.png'; $terrain = 'Oasis(Sand)'; break;
			//forest
			case 'Aa^Fpa': 	$image = 'snow-forest-tile.png'; $terrain = 'Snow Forest(Snow,Forest)'; break;
			case 'Gg^Fet': 	$image = 'great-tree-tile.png'; $terrain = 'Great Tree(Forest)'; break;
			case 'Gs^Fp': 	$image = 'forest-tile.png'; $terrain = 'Forest'; break;
			case 'Gs^Ft': 	$image = 'tropical-forest-tile.png'; $terrain = 'Tropcial Forest(Forest)'; break;
			//grassland
			case 'Gg': 		$image = 'grassland-r1.png'; $terrain = 'Grassland'; break;
			case 'Ggf': 	$image = 'grassland-r1.png'; $terrain = 'Grassland'; break;//grassland flowers, missing image
			case 'Gs': 		$image = 'savanna.png'; $terrain = 'Savanna(Grassland)'; break;
			case 'Rp': 		$image = 'road2.png'; $terrain = 'Road(Grassland)'; break;
			//hills
			case 'Ha': 		$image = 'snow-hills.png'; $terrain = 'Snow Hills(Snow,Hills)'; break;
			case 'Hd': 		$image = 'desert-hills.png'; $terrain = 'Dunes'; break;
			case 'Hh': 		$image = 'hills-variation1.png'; $terrain = 'Hills'; break;
			//mountains
			case 'Md': 		$image = 'desert-mountains.png'; $terrain = 'Desert Mountains(Mountains)'; break;
			case 'Mm': 		$image = 'mountain-tile.png'; $terrain = 'Mountains'; break;
			case 'Qxu': 	$image = 'chasm-tile.png'; $terrain = 'Chasm'; break;
			case 'Ql': 		$image = 'lava.png'; $terrain = 'Lava(Chasm)'; break;
			//road
			case 'Rd': 		$image = 'desert-road.png'; $terrain = 'Desert Road(Grassland)'; break;
			case 'Re': 		$image = 'dirt.png'; $terrain = 'Dirt(Grassland)'; break;
			case 'Rr': 		$image = 'road2.png'; $terrain = 'Road(Grassland)'; break;
			//swamp
			case 'Ss': 		$image = 'swampwater2.png'; $terrain = 'Swamp'; break;
			//underground
			case 'Uu': 		$image = 'cave-floor.png'; $terrain = 'Cave'; break;
			case 'Uu^Ii': 	$image = 'cave-beam-tile.png'; $terrain = 'Cave Lit(Cave)'; break;
			case 'Uu^Uf': 	$image = 'mushrooms-tile.png'; $terrain = 'Mushroom Grove'; break;
			case 'Re^Uf': 	$image = 'mushrooms-tile.png'; $terrain = 'Mushroom Grove'; break;//above mushrooms, missing image
			case 'Uh': 		$image = 'cave-hills-variation1.png'; $terrain = 'Rockbound Cave(Cave,Hills)'; break;
			case 'Uh^Ii': 	$image = 'cave-hills-variation1.png'; $terrain = 'Rockbound Cave Lit(Cave,Hills)'; break;//missing image
			//villages
			case 'Dd^Vda': 	$image = 'village-desert-tile.png'; $terrain = 'Village(Village,Desert)'; break;
			case 'Dd^Vdt': 	$image = 'village-desert2-tile.png'; $terrain = 'Village(Village,Desert)'; break;
			case 'Aa^Vea': 	$image = 'village-elven-snow-tile.png'; $terrain = 'Village'; break;
			case 'Gg^Ve': 	$image = 'village-elven-tile.png'; $terrain = 'Village'; break;
			case 'Aa^Vha': 	$image = 'village-snow-tile.png'; $terrain = 'Village'; break;
			case 'Gg^Vh': 	$image = 'village-human-tile.png'; $terrain = 'Village'; break;
			case 'Hh^Vhh': 	$image = 'village-human-hills-tile.png'; $terrain = 'Village(Village,Hills)'; break;
			case 'Ha^Vhha': $image = 'village-human-snow-hills-tile.png'; $terrain = 'Village(Village,Hills)'; break;
			case 'Mm^Vhh': 	$image = 'village-human-mountain-tile.png'; $terrain = 'Village(Village,Mountains)'; break;
			case 'Gs^Vht': 	$image = 'village-tropical-tile.png'; $terrain = 'Village'; break;
			case 'Uu^Vu': 	$image = 'village-cave-tile.png'; $terrain = 'Village(Village,Cave)'; break;
			case 'Uu^Vud': 	$image = 'village-dwarven-tile.png'; $terrain = 'Village(Village,Cave)'; break;
			case 'Ww^Vm': 	$image = 'village-coast-tile.png'; $terrain = 'Village(Shallow Water)'; break;
			case 'Ss^Vhs': 	$image = 'village-swampwater-tile.png'; $terrain = 'Village(Swamp Water)'; break;
			case 'Ss^Vm': 	$image = 'village-swampwater-tile.png'; $terrain = 'Village(Swamp Water)'; break;//swamp merfolk village, missing image
			//water
			case 'Wo': 		$image = 'ocean.png'; $terrain = 'Deep Water'; break;
			case 'Ww': 		$image = 'coast.png'; $terrain = 'Shallow Water'; break;
			case 'Wwf': 	$image = 'ford.png'; $terrain = 'Ford(Grassland, Shallow Water)'; break;
			//impassable
			case 'Mm^Xm': 	$image = 'impassable-mountains-tile.png'; $terrain = 'Impassable Mountains(Cavewall)'; break;
			case 'Md^Xm': 	$image = 'impassable-mountains-tile.png'; $terrain = 'Impassable Desert Mountains(Cavewall)'; break;//missing image
			case 'Xu': 		$image = 'cavewall.png'; $terrain = 'Cavewall'; break;
			case 'Xv':		$image = 'void.png'; $terrain = 'Void(Cavewall)'; break;
			default:		$image = 'unknown.png'; $terrain = 'Unknown - '.$letters; break;
		}
		switch ($player)
		{
			//players
			case '0': $image = 'keep-tile-p0.png'; $terrain = 'Player 10 - '.$terrain;break; // Player start positions.
			case '1': $image = 'keep-tile-p1.png'; $terrain = 'Player 1 - '.$terrain; break; // Doesnt always render as
			case '2': $image = 'keep-tile-p2.png'; $terrain = 'Player 2 - '.$terrain; break; // a keep in the game, but
			case '3': $image = 'keep-tile-p3.png'; $terrain = 'Player 3 - '.$terrain; break; // its the most likely tile
			case '4': $image = 'keep-tile-p4.png'; $terrain = 'Player 4 - '.$terrain; break; // to always use.
			case '5': $image = 'keep-tile-p5.png'; $terrain = 'Player 5 - '.$terrain; break; // Each tile has a colored
			case '6': $image = 'keep-tile-p6.png'; $terrain = 'Player 6 - '.$terrain; break; // numeral on it, indicating
			case '7': $image = 'keep-tile-p7.png'; $terrain = 'Player 7 - '.$terrain; break; // the player's number and
			case '8': $image = 'keep-tile-p8.png'; $terrain = 'Player 8 - '.$terrain; break; // color used during the
			case '9': $image = 'keep-tile-p9.png'; $terrain = 'Player 9 - '.$terrain; break; // game.
		}
		switch ($letters)
		{
			case '{+': case '{-':
				break; // control characters from earlier
			case '}':
				$firsthalf=!$firsthalf;
				if($firsthalf) {
					$ycoord++;
					$xcoord=-1;
				}
				else
				{
					$xcoord=0;
				}
				break;
			default:
				$xcoord+=2;
				$newmaptext .= '<img width=' . $size . ' height=' . $size . ' src="../images/maptiles/' . $image . '" style="vertical-align:bottom;margin-right:' . ($halfsize-$offset*2) . 'px;" alt="' . $maptext{$i} . '" title="' . $xcoord . ',' . $ycoord . ' : ' . $terrain . '"/>'; break;
		}
	}
	$maptext = $newmaptext;
	
	// insert the markup over all the { } (+ (- ) markers from earlier
	// thanks to GarethAdams from irc:// irc.freenode.net/#html for CSS help
	$maptext = str_replace('{+', '<div style="margin-top:-' . ($halfsize+$offset) . 'px;">', $maptext);
	$maptext = str_replace('{-', '<div style="margin-top:-' . ($halfsize+$offset) . 'px;margin-left:' . ($threesize-$offset) . 'px;">', $maptext);
	$maptext = str_replace('}', "</div>", $maptext);
	$maptext = '<div style="margin-top:' . ($halfsize+$offset) . 'px;">' . $maptext . '</div>';
	
	if ($has_border==0)
	{
		$version_text="<br>Map format: 1.3.9";
	}
	else
	{
		$version_text="<br>Map format: 1.3.10+";
	}
	
	// build the page given the original maptext and new maptext
	$maptext = $maptext . $version_text;
	return $maptext;
}
function WesnothMapOld( $maptext ) 
{
	global $phpbb_root_path;
	$origmaptext = $maptext;
	$maxrowwidth = 0;
	$newmaptext = '';
//	$maptext = htmlentities($maptext);
	// Split each row of the map into two half-rows
	// Thanks to zircu and Ox41464b from irc://irc.freenode.net/#php
	// for help with the logic here
	$lines=explode("&lt;br /&gt;", $maptext);
	foreach ($lines as $line)
	{
		$even = '';
		$odd = '';
		for ($i=0; $i < strlen($line); $i++)
		{
			if ($line{$i} >= ' ')
				if ($i % 2) $odd .= $line{$i};
				else $even .= $line{$i};
		}
		if ($i > $maxrowwidth) $maxrowwidth = $i;
		$newmaptext .= '{+' . $even . "}"; // tags for odd and even line css
		$newmaptext .= '{-' . $odd . "}";  // markup, applied later.
	} 

	$maptext = $newmaptext;
	// calculate tile sizes, try to keep the map under 720px wide
	$quartersize = min(floor(270 / $maxrowwidth),9); // 9 is full size
	$halfsize = $quartersize*2;
	$threesize = $quartersize*3;
	$size = $quartersize*4; // width of a full square tile
	$size = max($size,3);
	// if the hexes are to be shrunk by the browser then we have to overlap them
	// to avoid problems with anti-aliased edges not covering each other
	if ($quartersize<9) $offset=1;
	
	// loop through the map, find each letter and convert it to an html
	// img tag with the appropriate filename and markup
	$newmaptext = '';
	$firsthalf = TRUE;
	$xcoord=-1;
	$ycoord=1;
	for ($i = 0; $i < strlen($maptext); $i++)
	{
		switch ($maptext[$i])
		{
			case '{': case '}': case '+': case '-':
				$newmaptext .= $maptext{$i}; break; // control characters from earlier
			case 'A': $image = 'village-human-snow-hills-tile.png'; $terrain = 'Village (Hills)'; break;
			case 'a': $image = 'village-human-hills-tile.png'; $terrain = 'Village (Hills)'; break;
			case 'B': $image = 'village-desert-tile.png'; $terrain = 'Village (Desert)'; break;
			case 'b': $image = 'village-human-mountain-tile.png'; $terrain = 'Village (Mountains)'; break;
			case 'C': $image = 'castle-tile.png'; $terrain = 'Castle'; break;
			case 'c': $image = 'coast.png'; $terrain = 'Shallow Water'; break;
			case 'D': $image = 'village-cave-tile.png'; $terrain = 'Village (Cave)'; break;
			case 'd': $image = 'sand.png'; $terrain = 'Sand'; break;
			case 'E': $image = 'desert-road.png'; $terrain = 'Road (Grassland)'; break;
			case 'e': $image = 'village-elven-snow-tile.png'; $terrain = 'Village'; break;
			case 'F': $image = 'snow-forest-tile.png'; $terrain = 'Snow Forest'; break;
			case 'f': $image = 'forest-tile.png'; $terrain = 'Forest'; break;
			case 'G': $image = 'savanna.png'; $terrain = 'Savanna (Grassland)'; break;
			case 'g': $image = 'grassland-r1.png'; $terrain = 'Grassland'; break;
			case 'H': $image = 'snow-hills.png'; $terrain = 'Snow Hills'; break;
			case 'h': $image = 'hills-variation1.png'; $terrain = 'Hills'; break;
			case 'I': $image = 'desert2.png'; $terrain = 'Desert (Sand)'; break;
			case 'i': $image = 'ice.png'; $terrain = 'Ice (Snow)'; break;
			case 'J': $image = 'desert-hills.png'; $terrain = 'Dunes (Sand, Hills)'; break;
			case 'j': $image = 'unknown.png'; $terrain = '[future Snowy Mountains]'; break;
			case 'K': $image = 'keep-tile.png'; $terrain = 'Keep (Castle)'; break;
			case 'k': $image = 'ford.png'; $terrain = 'River Ford (Shallow Water, Grassland)'; break;
			case 'L': $image = 'village-tropical-tile.png'; $terrain = 'Village (Savanna)'; break;
			case 'l': $image = 'lava.png'; $terrain = 'Lava (Chasm)'; break;
			case 'M': $image = 'desert-mountains.png'; $terrain = 'Mountains'; break;
			case 'm': $image = 'mountain-tile.png'; $terrain = 'Mountains'; break;
			case 'N': $image = 'castle-ruin-tile.png'; $terrain = 'Ruin (Castle)'; break;
			case 'n': $image = 'encampment-tile.png'; $terrain = 'Encampment (Castle)'; break;
			case 'O': $image = 'unknown.png'; $terrain = '[future Orc Castle]'; break;
			case 'o': $image = 'dwarven_castle-tile.png'; $terrain = 'Dwarven Castle'; break;
			case 'P': $image = 'desert-oasis.png'; $terrain = 'Oasis (Desert)'; break;
			case 'p': $image = 'village-dwarven-tile.png'; $terrain = 'Village (Cave)'; break;
			case 'Q': $image = 'castle-sunken-ruin-tile.png'; $terrain = 'Sunken Ruin (Castle, Shallow Water)'; break;
			case 'q': $image = 'castle-swamp-ruin-tile.png'; $terrain = 'Swamp Ruin (Castle)'; break;
			case 'R': $image = 'road2.png'; $terrain = 'Road (Grassland)'; break;
			case 'r': $image = 'dirt.png'; $terrain = 'Dirt (Grassland)'; break;
			case 'S': $image = 'snow2.png'; $terrain = 'Snow'; break;
			case 's': $image = 'ocean.png'; $terrain = 'Deep Water'; break;
			case 'T': $image = 'tropical-forest-tile.png'; $terrain = 'Tropical Forest'; break;
			case 't': $image = 'village-elven-tile.png'; $terrain = 'Village'; break;
			case 'U': $image = 'village-desert2-tile.png'; $terrain = 'Village (Desert)'; break;
			case 'u': $image = 'cave-floor.png'; $terrain = 'Cave'; break;
			case 'V': $image = 'village-snow-tile.png'; $terrain = 'Village (Snow)'; break;
			case 'v': $image = 'village-human-tile.png'; $terrain = 'Village'; break;
			case 'W': $image = 'cavewall.png'; $terrain = 'Cave Wall'; break;
			case 'w': $image = 'swampwater2.png'; $terrain = 'Swamp'; break;
			case 'X': $image = 'chasm-tile.png'; $terrain = 'Chasm'; break;
			case 'x': $image = 'unknown.png'; $terrain = '[reserved for UMCs]'; break;
			case 'Y': $image = 'village-swampwater-tile.png'; $terrain = 'Village (Swamp)'; break;
			case 'y': $image = 'unknown.png'; $terrain = '[reserved for UMCs]'; break;
			case 'Z': $image = 'village-coast-tile.png'; $terrain = 'Village (Shallow Water)'; break;
			case 'z': $image = 'unknown.png'; $terrain = '[reserved for UMCs]'; break;
			case '/': $image = 'bridge-ne-sw-tile.png'; $terrain = 'Bridge  (Grassland, Shallow Water)'; break;
			case '|': $image = 'bridge-n-s-tile.png'; $terrain = 'Bridge  (Grassland, Shallow Water)'; break;
			case '\\':$image = 'bridge-se-nw-tile.png'; $terrain = 'Bridge  (Grassland, Shallow Water)'; break;
			case '~': $image = 'fog.png'; $terrain = 'Fog'; break;
			case ' ': $image = 'void.png'; $terrain = 'Shroud'; break; // the real void tile
			case '*': $image = 'unknown.png'; $terrain = '[reserved for UMCs]'; break;
			case '^': $image = 'unknown.png'; $terrain = '[reserved for UMCs]'; break;
			case '%': $image = 'unknown.png'; $terrain = '[reserved for UMCs]'; break;
			case '@': $image = 'unknown.png'; $terrain = '[reserved for UMCs]'; break;
			case '[': $image = 'cave-hills-variation1.png'; $terrain = 'Rockbound Cave (Cave, Hills)'; break;
			case ']': $image = 'mushrooms-tile.png'; $terrain = 'Mushroom Grove'; break;
			case '\'':$image = 'cave-beam-tile.png'; $terrain = 'Lit Cave'; break;
			case '?': $image = 'great-tree-tile.png'; $terrain = 'Great Tree (Forest)'; break;
			case '&': $image = 'impassable-mountains-tile.png'; $terrain = 'Impassable Mountains'; break;
			case '"': $image = 'unknown.png'; $terrain = '[unknown]'; break; // ???
			case '$': $image = 'unknown.png'; $terrain = '[unknown]'; break; // ???
			case '.': $image = 'unknown.png'; $terrain = '[unknown]'; break; // ???
			case ';': $image = 'unknown.png'; $terrain = '[unknown]'; break; // ???
			case ':': $image = 'unknown.png'; $terrain = '[unknown]'; break; // ???
			case '<': $image = 'unknown.png'; $terrain = '[unknown]'; break; // ???
			case '>': $image = 'unknown.png'; $terrain = '[unknown]'; break; // ???
			case '_': $image = 'unknown.png'; $terrain = '[unknown]'; break; // ???
			case '`': $image = 'unknown.png'; $terrain = '[unknown]'; break; // ???
			case '0': $image = 'keep-tile-p0.png'; $terrain = 'Keep [Player 10]';break; // Player start positions.
			case '1': $image = 'keep-tile-p1.png'; $terrain = 'Keep [Player 1]'; break; // Doesnt always render as
			case '2': $image = 'keep-tile-p2.png'; $terrain = 'Keep [Player 2]'; break; // a keep in the game, but
			case '3': $image = 'keep-tile-p3.png'; $terrain = 'Keep [Player 3]'; break; // its the most likely tile
			case '4': $image = 'keep-tile-p4.png'; $terrain = 'Keep [Player 4]'; break; // to always use.
			case '5': $image = 'keep-tile-p5.png'; $terrain = 'Keep [Player 5]'; break; // Each tile has a colored
			case '6': $image = 'keep-tile-p6.png'; $terrain = 'Keep [Player 6]'; break; // numeral on it, indicating
			case '7': $image = 'keep-tile-p7.png'; $terrain = 'Keep [Player 7]'; break; // the player's number and
			case '8': $image = 'keep-tile-p8.png'; $terrain = 'Keep [Player 8]'; break; // color used during the
			case '9': $image = 'keep-tile-p9.png'; $terrain = 'Keep [Player 9]'; break; // game.
			default:  return '<b>Error Parsing map contents, illegal character '.$maptext[$i].'</b>'; break; // illegal character
		}
		switch ($maptext[$i])
		{
			case '{': case '+': case '-':
				break; // control characters from earlier
			case '}':
				$firsthalf=!$firsthalf;
				if($firsthalf) {
					$ycoord++;
					$xcoord=-1;
				}
				else
				{
					$xcoord=0;
				}
				break;
			default:
				$xcoord+=2;
				$newmaptext .= '<img width=' . $size . ' height=' . $size . ' src="../images/maptiles/' . $image . '" style="vertical-align:bottom;margin-right:' . ($halfsize-$offset*2) . 'px;" alt="' . $maptext[$i] . '" title="' . $xcoord . ',' . $ycoord . ' : ' . $terrain . '"/>'; break;
		}
	}
	$maptext = $newmaptext;
	// insert the markup over all the { } (+ (- ) markers from earlier
	// thanks to GarethAdams from irc:// irc.freenode.net/#html for CSS help
	$maptext = str_replace('{+', '<div style="margin-top:-' . ($halfsize+$offset) . 'px;">', $maptext);
	$maptext = str_replace('{-', '<div style="margin-top:-' . ($halfsize+$offset) . 'px;margin-left:' . ($threesize-$offset) . 'px;">', $maptext);
	$maptext = str_replace('}', "</div>", $maptext);
	$maptext = '<div style="margin-top:' . ($halfsize+$offset) . 'px;">' . $maptext . '</div>';
	
	// build the page given the original maptext and new maptext
	$maptext = $maptext . '<br>Map format: 1.2.x<br><a href="data:application/octet-stream,'. rawurlencode(htmlentities($origmaptext)) .'">Download this map</a><br>';
	return $maptext;
}


$map = $_POST['wesnoth_map_data'];
echo WesnothMap($map);
?>

