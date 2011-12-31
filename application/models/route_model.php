<?php
class Route_Model extends CI_Model {
	
	function __construct()
    {
        parent::__construct();
		$this->load->database("default");
		$this->config->load("api");
    }
	
	function create($data)
	{
		foreach ( $data as $key => $value ) {
			// TODO: ERROR CHECKING HERE
			$this->db->set($key,$value);
		}
		if ( $this->db->insert("route") ) {
			$last_id = $this->db->insert_id();
			return $last_id;
		}
		else return FALSE;
	}
	
	function remove($route_id)
	{
		return $this->db->delete("route",array("route_id" => $route_id));
	}
	
	function details($route_id)
	{
		$this->db->select("*")->where("route_id",$route_id);
		$details = $this->db->get("route")->result();
		$this->load->model("Place_Model","place");
		if ( $details ) {
			$result = (array)$details[0];
			$result["origin"] = $this->place->details($result["origin_id"]);
			$result["destination"] = $this->place->details($result["destination_id"]);
 
			return $result;
		}
		else return FALSE;
	}
	
	function modify($route_id,$data = array())
	{
		foreach ( $data as $key => $value ) {
			//TODO: ERROR CHECKING HERE
			$this->db->set($key,$value);
		}
		return $this->db->where("route_id",$route_id)->update("route");
	}
	
	// ROUTE RIDERS
	
	function riderAdd($route_id,$person_id,$place_id = NULL,$phone_id = NULL)
	{
		if ( $route_id == NULL ) return FALSE;
		if ( $person_id == NULL ) return FALSE;
		if ( $place_id == NULL AND $phone_id == NULL ) return FALSE;
		
		$this->db->set("route_id",$route_id);
		$this->db->set("rider_id",$person_id);
		$this->db->set("active",TRUE);
		
		// add by place
		if ( $place_id != NULL ) {
			$this->db->set("place_id",$place_id);
			$this->db->set("phone_id",NULL);
			if (!$this->db->insert("rider")) return FALSE;
		}
		// add by phone location
		else if ( $phone_id != NULL ) {
			$this->db->set("place_id",NULL);
			$this->db->set("phone_id",$phone_id);
			if (!$this->db->insert("rider")) return FALSE;
		}
		// must be one of the above!
		else return FALSE;
		
		// re-optimize route
		return $this->orderOptimize($route_id);
		
	}
	
	function riderRemove($route_id,$person_id)
	{
		if ( $this->db->delete("rider",array("route_id" => $route_id, "person_id" => $person_id)) ) {
			return $this->orderClean($route_id);
		}
		else return FALSE;
	}
	
	function riderList($route_id)
	{
		$stops = $this->getStops($route_id);
		$riders = array();
		$this->load->model("Person_Model","person");
		foreach ( $stops as $stop ) {
			if ( $stop["type"] == "rider_place" OR $stop["type"] == "rider_phone" ) {
				$rider = $this->person->details($stop["rider_id"]);				
				$rider["rider_id"] = $stop["rider_id"];
				$riders[] = $rider;
			}
		}
		return $riders;
	}
	
	// ROUTE PLACES (POI)
	
	function placeAdd($route_id,$place_id)
	{
		$this->db->set("route_id",$route_id);
		$this->db->set("place_id",$place_id);
		$this->db->set("active",TRUE);
		if ( $this->db->insert("poi") ) {
			//return $this->orderOptimize($route_id);
			return TRUE;
		}
		else return FALSE;
	}
	
	function placeRemove($route_id,$place_id)
	{
		if ( $this->db->delete("poi",array("route_id" => $route_id, "place_id" => $place_id)) ) {
			$this->orderClean($route_id);
		}
		else return FALSE;
	}
	
	// ROUTE STOPS
	
	function stopList($route_id)
	{
		$ttl = $this->config->item("stoplist_ttl");
		// load the cache 
		$this->load->driver('cache');
		$unique = md5("stopList-Route-".$route_id);
		// try to load data from cache
		if ( !$result = $this->cache->file->get($unique) ) {
		
			$result = array();
			$groutes = $this->runRouting($route_id,FALSE);
			$result["stops"] = $this->getRoute($route_id,$groutes);
			$result["mapdata"] = $this->getMapPoints($route_id,$groutes);
			$result["cache"] = array("cached" => FALSE, "expires" => time()+$ttl);
		
			// save update result to cache
			$this->cache->file->save($unique,$result,$ttl) or die("Unable to save data to cache!.");
		}
		else $result["cache"]["cached"] = TRUE;
		return $result;		
	}
	
	function stopReorder($route_id,$newpos = array())
	{
		$stops = $this->getStops($route_id);
		if ( count($stops) != count($newpos) ) return FALSE;
		$used = array_fill(0,count($stops),FALSE);	
		for ( $oldpos = 0; $oldpos < count($newpos); $oldpos++ ) {
			// check that new position is within bounds and not already used
			if ( $newpos[$oldpos] < 0 OR $newpos[$oldpos] >= count($stops) ) return FALSE;
			if ( $used[$newpos[$oldpos]] == TRUE ) return FALSE;
			// find matching stop entry
			foreach( $stops as $stop ) {
				// find based on current position
				if ( $stop["position"] == $oldpos ) {
					// change rider
					if ( $stop["type"] == "rider_place" OR $stop["type"] == "rider_phone" ) {
						$this->db->set("position",$newpos[$oldpos]);
						$this->db->where("route_id",$route_id)->where("rider_id",$stop["rider_id"])->update("rider");
						break;
					}
					// change poi
					else if ( $stop["type"] == "place" ) {
						$this->db->set("position",$newpos[$oldpos]);
						$this->db->where("route_id",$route_id)->where("place_id",$stop["place_id"])->update("poi");
						break;
					}
					else return FALSE;
				}
			}
			// mark position as in-use
			$used[$newpos[$oldpos]] == TRUE;
		}
		return TRUE;
	}
	
	function stopOptimize($route_id)
	{
		return $this->orderOptimize($route_id);
	}
	
	function stopEta($route_id,$position)
	{
		return 120;
	}
	
	#
	# PRIVATE HELPERS
	#
	
	// STOP HELPERS

	private function stopExists($route_id,$place_id = NULL,$person_id = NULL)
	{
		// lookup by place
		if ( $place_id != NULL ) {
			$this->db->select("*")->where("route_id",$route_id)->where("place_id",$place_id);
			if ( count($this->db->get("poi")->result()) > 0 ) return TRUE;
		}
		// lookup by rider
		if ( $person_id != NULL ) {
			$this->db->select("*")->where("route_id",$route_id)->where("rider_id",$person_id);
			if ( count($this->db->get("rider")->result()) > 0 ) return TRUE;
		}
		return FALSE;
	}
	
	// get a list of all stops (no origin or destination)
	private function getStops($route_id)
	{
		if ( !$details = $this->details($route_id) ) return FALSE;
		$this->load->model("Place_Model","place");
		$this->load->model("Person_Model","person");
		// get all riders
		$riders = array();
		$this->db->select("*")->where("route_id",$route_id);
		$query = $this->db->get("rider");
		foreach($query->result() as $row) {
			$rider = (array)$row;
			// use place if not null
			if ( $rider["place_id"] != NULL ) {
				$rider["type"] = "rider_place";
				$rider["place"] = $this->place->details($rider["place_id"]);
			}
			// else use phone location
			else if ( $rider["phone_id"] != NULL ) {
				$this->load->model("Phone_Model","phone");
				$rider["type"] = "rider_phone";
				$rider["location"] = $this->phone->locationGet($rider["phone_id"]);
			}
			// else there's a problem!
			else return FALSE;
			$riders[] = $rider;
		}
		// check for multiple riders at the same place and combine them
		$temp = array();
		foreach ( $riders as $rider ) {
			if ( $rider["type"] == "rider_place" ) {
				if ( isset($temp[$rider["place_id"]]) ) {
					$prev = $temp[$rider["place_id"]];			
					$all = array();
					$all["type"] = "rider_place_multi";
					$all["place"] = $rider["place"];
					$all["place_id"] = $rider["place_id"];
					$all["position"] = $rider["position"];
					if ( isset($prev["riders"]) ) {
						$all["riders"] = $prev["riders"];
					}
					else {
						$all["riders"] = array();
						$all["riders"][] = $prev["rider_id"];
					}
					$all["riders"][] = $rider["rider_id"];
					$all["rider_count"] = count($all["riders"]);
					$temp[$rider["place_id"]] = $all;
				}
				else $temp[$rider["place_id"]] = $rider;
			}
			else $temp[$rider["place_id"]] = $rider;
		}
		$riders = $temp;
		// get all pois
		$pois = array();
		$this->db->select("*")->where("route_id",$route_id);
		$query = $this->db->get("poi");
		foreach($query->result() as $row) {
			$poi = (array)$row;
			$poi["type"] = "place";
			$poi["place"] = $this->place->details($poi["place_id"]);
			$pois[] = $poi;
		}
		// order by stop numbers
		$stops = array_merge($riders,$pois);
		usort($stops,array("Route_Model","CompareByStopPosition"));
		// remove a few things
		$temp = array();
		foreach ( $stops as $stop ) {
			if ( isset($stop["route_id"] ) ) unset($stop["route_id"]);
			if ( isset($stop["active"] ) ) unset($stop["active"]);
			$temp[] = $stop;
		}
		return $temp;
	}
	
	// get a list of the entire route (including origin and destination)
	private function getRoute($route_id)
	{
		$this->load->model("Place_Model","place");
		$stops = $this->getStops($route_id);
		$details = $this->details($route_id);
		// get origin
		$origin = array();
		if ( !$place = $this->place->details($details["origin_id"]) ) return FALSE;
		$origin["type"] = "origin";
		$origin["place_id"] = $place["place_id"];
		$origin["place"] = $place;
		// get destination
		if ( !$place = $this->place->details($details["destination_id"]) ) return FALSE;
		$destination = array();
		$destination["type"] = "destination";
		$destination["place_id"] = $place["place_id"];
		$destination["place"] = $place;
		// combine origin, stops, and destination
		$route = array();
		$route[] = $origin;
		foreach ( $stops as $stop ) {
			$route[] = $stop;
		}
		$route[] = $destination;
		// get stop status information
		$this->load->model("Person_Model","person");
		$this->load->helper('url');
		$temp = array();
		foreach ( $route as $stop ) {
			if ( $stop["type"] == "origin" OR $stop["type"] == "destination" OR $stop["type"] == "place" ) {
				$stop["lat"] = $stop["place"]["gps_lat"];
				$stop["lon"] = $stop["place"]["gps_lon"];
				$stop["name"] = $stop["place"]["name"];
				if ( $stop["place"]["photo_id"] ) {
					$stop["has_photo"] = TRUE;
					$stop["photo_url"] = site_url("/photo/download/photo_id/".$stop["place"]["photo_id"]);
				} else $stop["has_photo"] = FALSE;
			}
			else if ( $stop["type"] == "rider_place" ) {
				$stop["lat"] = $stop["place"]["gps_lat"];
				$stop["lon"] = $stop["place"]["gps_lon"];
				$rider = $this->person->details($stop["rider_id"]);
				$stop["rider"] = $rider;
				$stop["name"] = $rider["fullname"];
				$stop["ready"] = $rider["ready"];
				if ( $rider["photo_id"] ) {
					$stop["has_photo"] = TRUE;
					$stop["photo_url"] = site_url("/photo/download/photo_id/".$rider["photo_id"]);
				} else $stop["has_photo"] = FALSE;
			}
			else if ( $stop["type"] == "rider_phone" ) {
				$stop["lat"] = $stop["location"]["gps_lat"];
				$stop["lon"] = $stop["location"]["gps_lon"];
				$rider = $this->person->details($stop["rider_id"]);
				$stop["rider"] = $rider;
				$stop["name"] = $rider["fullname"];
				$stop["ready"] = $rider["ready"];
				if ( $rider["photo_id"] ) {
					$stop["has_photo"] = TRUE;
					$stop["photo_url"] = site_url("/photo/download/photo_id/".$rider["photo_id"]);
				} else $stop["has_photo"] = FALSE;
			}
			else if ( $stop["type"] == "rider_place_multi" ) {
				$stop["lat"] = $stop["place"]["gps_lat"];
				$stop["lon"] = $stop["place"]["gps_lon"];
				$stop["name"] = $stop["place"]["name"];
				if ( $stop["place"]["photo_id"] ) {
					$stop["has_photo"] = TRUE;
					$stop["photo_url"] = site_url("/photo/download/photo_id/".$stop["place"]["photo_id"]);
				} else $stop["has_photo"] = FALSE;
				$riders = $stop["riders"];
				$stop["riders"] = array();
				foreach ( $riders as $rider_id ) {
					$rider = $this->person->details($rider_id);
					if ( $rider["photo_id"] ) {
						$rider["has_photo"] = TRUE;
						$rider["photo_url"] = site_url("/photo/download/photo_id/".$rider["photo_id"]);
					} else $rider["has_photo"] = FALSE;
					$stop["riders"][$rider_id] = $rider;
				}
			}
			else return FALSE;
			$temp[] = $stop;
		}
		$oldroute = $temp;
		
		// figure out arrival times for each un-arrived-at stop
		$this->load->model("Car_Model","car");
		
		$route = $this->stopStatus($details["car_id"],$oldroute);
		$car_loc = $this->car->locationGet($details["car_id"]);
		
		if ( $route AND $car_loc ) {
		
			// time estimate settings
			$stopped_time = $this->config->item("stopped_time");
			$precision = $this->config->item("gps_precisions");
			
			// get a groute for the entire route
			$this->load->library("GoogleMaps");
			if (!$groutes = $this->googlemaps->getRoute($route,FALSE)) return FALSE;
			// prepare to calculate route timings
			$total_time = 0;
			$finalroute = array();
			foreach ( $route as $stop ) {
				// do we need to estimate an arrival time?
				if ( $stop["arrived"] == FALSE ) {
					// is the first un-arrived-at stop?
					if ( empty($finalroute) OR $finalroute[sizeof($finalroute)-1]["arrived"] == TRUE ) {
						// calculate time from car's current location to this stop
						$partial_time = $this->getPartialTime($car_loc,$stop);
						// were we able to get a partial time?
						if ( $partial_time === FALSE ) {
							$stop["estimated_arrival"] = -1;
						}
						else {
							$stop["estimated_arrival"] = time() + $partial_time;
							$total_time = $partial_time;
						}
					}
					// now estimate the rest of the stops
					else {
						// search for the correct leg (someplace --> this stop)
						$leg_time = 0;
						foreach ( $groutes as $groute ) {
							foreach ( $groute["legs"] as $leg ) {
								// is this the correct leg?
								$lat_from_equal = round($leg["start_location"]["lat"],$precision) == round($finalroute[sizeof($finalroute)-1]["lat"],$precision);
								$lon_from_equal = round($leg["start_location"]["lng"],$precision) == round($finalroute[sizeof($finalroute)-1]["lon"],$precision);
								$lat_to_equal = round($leg["end_location"]["lat"],$precision) == round($stop["lat"],$precision);
								$lon_to_equal = round($leg["end_location"]["lng"],$precision) == round($stop["lon"],$precision);
								if ( $lat_to_equal AND $lon_to_equal AND $lat_from_equal AND $lon_from_equal ) $leg_time = $leg["duration"]["value"];
							}
						}
						// estimate arrival time based on leg_time and total trip time so far
						if ( $leg_time > 0 ) {
							$stop["estimated_arrival"] = time() + $total_time + $leg_time + $stopped_time;
							$total_time = $total_time + $leg_time + $stopped_time;
						}
						// ugh, we were unable to estimate a time
						else $stop["estimated_arrival"] = -1;
					}
					// add a humam readable arrival date
					if ( $stop["estimated_arrival"] != -1 ) $stop["estimated_arrival_date"] = date($this->config->item("date_format"),$stop["estimated_arrival"]);
				}
				// push it to the final route
				$finalroute[] = $stop;
			}
			
		}
		// no recent car history! unable to make time estimates :(
		else {
			$temp = array();
			foreach ( $oldroute as $stop ) {
				$stop["arrived"] = FALSE;
				$stop["estimated_arrival"] = -1;
				$temp[] = $stop;
			}
			$finalroute = $temp;
		}
		// return completed route
		return $finalroute;
	}
	
	// check an ordered list of stops for current vehicle pickup status
	private function stopStatus($car_id,$stops) {
		if ( sizeof($stops) < 2 ) return FALSE;
		
		// set search radius 
		$radius = $this->config->item("gps_search_radius");
		
		// get car's recent history
		$history = array();
		$this->db->select("*");
		$this->db->where("car_id",$car_id);
		$cutoff = $this->config->item("max_route_time");
		$this->db->where("timestamp >",time()-($cutoff*60*60));
		$this->db->order_by("timestamp","desc");
		$points = $this->db->get("car_history")->result();
		// convert from stdClass to Array
		$history = array();
		if ( $points ) {
			foreach ( $points as $point ) {
				$point = (array)$point;
				$point["date"] = date($this->config->item("date_format"),$point["timestamp"]);
				$history[] = (array)$point;
			}
		}
		else return FALSE;
		
		// setup special test condition for testing
		$ignore_start = $this->config->item("ignore_start");
		$ignore_end = $this->config->item("ignore_end");
		$manual_end_of_route = $this->config->item("manual_end_of_route");
		
		// get the origin
		$origin = $stops[0];
		if ( $origin["type"] == "origin" ) $origin = $origin["place"];
		else return FALSE;
		
		// locate latest occurance of origin
		$count = 1;
		foreach( $history as $point ) {
			
			$distance = $this->gpsDistance($point["gps_lat"],$point["gps_lon"],$origin["gps_lat"],$origin["gps_lon"],"m");
			$test_condition = ($point["timestamp"] < $ignore_start OR $point["timestamp"] > $ignore_end );
			// is this point near the origin?
			if ( $distance < $radius AND $test_condition ) {
				break;
			}
			else $count++;
			// else keep looking
			
		}
		// shorten working history and sort by timestamp ASC
		$history = array_slice($history,0,$count);
		$history = array_reverse($history);
		
		// now, match all stops to history points (if they exist)
		$temp = array();
		foreach ( $stops as $stop ) {
			$stop["arrived"] = FALSE;
			$count = 0;
			foreach( $history as $point ) {
				$test_condition = ($point["timestamp"] < $manual_end_of_route OR $manual_end_of_route == 0 );
				if ( $this->gpsDistance($point["gps_lat"],$point["gps_lon"],$stop["lat"],$stop["lon"],"m") < $radius AND $test_condition) {
					// we need some special conditions if this stop is the destination
					if ( ($stop["type"] != "destination") OR ($stop["type"] == "destination" AND $temp[sizeof($temp)-1]["arrived"] == TRUE) ) {
						$stop["arrived"] = TRUE;
						$stop["actual_arrival"] = intval($point["timestamp"]);
						$stop["actual_arrival_date"] = date($this->config->item("date_format"),$point["timestamp"]);
						// found it, shorten search history and break out of loop
						$history = array_slice($history,-(sizeof($history)-$count));
						break;
					}
				}
				$count++;
			}
			
			
			// perform foursquare checkin if we just got to a stop
			$fsq_username = $this->config->item("fsq_username");
			$fsq_password = $this->config->item("fsq_password");
			$fsq_search_rate = $this->config->item("fsq_search_rate");
			// use the cache to limit queries
			$this->load->driver('cache');
			$unique = md5("foursuare-car-".$car_id);
			if ( !$result = $this->cache->file->get($unique) ) {
				
				// get recent car history
				$min_timestamp = time()-$fsq_search_rate;
				$max_timestamp = time();
				$history = $this->car->locationHistory($car_id,$min_timestamp,$max_timestamp);
				
				// search for stop arrivals
				foreach ( $history as $point ) {
					if ( $this->gpsDistance($point["gps_lat"],$point["gps_lon"],$stop["lat"],$stop["lon"],"m") < $radius ) {
						$this->load->library("FourSquare");
						// determine a message based on stop type
						if ( $stop["type"] == "origin" ) $message = "Just left ".$stop["name"];
						else if ( $stop["type"] == "destination" ) $message = "Just arrived at ".$stop["name"];
						else if ( $stop["type"] == "rider_place" OR $stop["type"] == "rider_phone" ) $message = "Just picked up ".$stop["name"];
						else $message = "Just stopped at ".$stop["name"];
						// perform the checkin
						$result = $this->foursquare->checkin($fsq_username,$fsq_password,$message,$point["gps_lat"],$point["gps_lon"]);
						$stop["foursquare_checkin_status"] = $result;
						// only allow one checkin per run
						break 1;
					}
				}
			
				$this->cache->file->save($unique,$result,$fsq_search_rate) or die("Unable to save data to cache!.");
			}
			
			$temp[] = $stop;
			
			
		}
		// now, clean up stops that may have been missed and mark them as arrived at
		$stops_reverse = array_reverse($temp);
		$arrived = FALSE;
		$temp = array();
		foreach( $stops_reverse as $stop ) {
			if ( $stop["arrived"] == TRUE ) {
				$arrived = TRUE;
			}
			if ( $arrived == TRUE ) {
				$stop["arrived"] = TRUE;
				if ( !isset($stop["actual_arrival"]) ) $stop["actual_arrival"] = -1;
			}
			$temp[] = $stop;
		}
		// put it back in the correct order and return it
		$stops = array_reverse($temp);
		return $stops;
	}
	
	// calculate distance between to (lat,lon) points - valid units are m (meters), km (kilometers), nm (natical miles), and mi (miles)
	private function gpsDistance($lat1,$lon1,$lat2,$lon2,$unit = "km") {
		$theta = $lon1 - $lon2; 
		$dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta)); 
		$dist = acos($dist); 
		$dist = rad2deg($dist); 
		$miles = $dist * 60 * 1.1515;
		if ($unit == "m") return ($miles * 1609.344); // meters
		else if ($unit == "km") return ($miles * 1.609344); // kilometers
		else if ($unit == "nm") return ($miles * 0.8684); // nautical miles
		else return $miles; // miles
	}
	
	// ROUTING HELPERS
	
	public function runRouting($route_id,$optimize = FALSE)
	{
		if (!$route = $this->getRoute($route_id)) return FALSE;
		// prepare locations for navigation
		$stops = array();
		foreach( $route as $stop ) {
			$location = array();
			if ( $stop["type"] == "rider_phone" ) {
				$location["lat"] = $stop["location"]["gps_lat"];
				$location["lon"] = $stop["location"]["gps_lon"];
			}
			else {
				$location["place"] = $stop["place"];
				$location["lat"] = $stop["place"]["gps_lat"];
				$location["lon"] = $stop["place"]["gps_lon"];
			}
			$stops[] = $location;
		}
		//run the routing request
		$this->load->library("GoogleMaps");
		if (!$groute = $this->googlemaps->getRoute($stops,$optimize)) return FALSE;
		return $groute;
	}
	
	public function getMapPoints($route_id,$groute = NULL)
	{
		if ( $groute == NULL ) {
			if ( !$groute = $this->runRouting($route_id,TRUE) ) return FALSE;
		}
		$mapdata = array();
		foreach( $groute as $route ) {
			if ( !isset($route["overview_polyline"]) ) return FALSE;
			return $route["overview_polyline"];
		}
	}
	
	// TIME AND POSITION HELPERS
	
	private function getPartialTime($location,$next_stop) {
	
		// build partial route
		$route = array();
		$route[0] = array("lat" => $location["gps_lat"], "lon" => $location["gps_lon"]);
		$route[1] = array("lat" => $next_stop["lat"], "lon" => $next_stop["lon"]);
	
		//run the routing request
		$this->load->library("GoogleMaps");			
		if (!$groute = $this->googlemaps->getRoute($route,FALSE)) return FALSE;
		
		// prepare results
		$time = 0;
		// parse groute for each leg
		foreach ( $groute as $route ) {
			foreach ( $route["legs"] as $leg ) {
				if ( !isset($leg["distance"]) or !isset($leg["duration"]) ) return FALSE;
				$time += $leg["duration"]["value"];
			}
		}
		return $time;
	}
	
	// ORDER HELPERS
	
	private function orderClean($route_id)
	{
		$stops = $this->getStops($route_id);
		$position = 0;
		// get ordered list of stops
		foreach ( $stops as $stop ) {
			if ( $stop["type"] == "rider_place" OR $stop["type"] == "rider_phone" ) {
				$this->db->set("position",$position);
				$this->db->where("route_id",$route_id)->where("rider_id",$stop["rider_id"])->update("rider");
			}
			else if ( $stop["type"] == "place" ) {
				$this->db->set("position",$position);
				$this->db->where("route_id",$route_id)->where("place_id",$stop["place_id"])->update("poi");
			}
			// else there's a problem!
			else return FALSE;
			// keep track of current position
			$position = $position + 1;
		}
	}
	
	private function orderOptimize($route_id)
	{
		if ( !$groutes = $this->runRouting($route_id,TRUE) ) return FALSE;
		// get waypoint order changes
		foreach ( $groutes as $route ) {
			if (isset($route["waypoint_order"])) {
				if ( $this->stopReorder($route_id,$route["waypoint_order"]) ) return TRUE;
				else return FALSE;
			}
			else return FALSE;
		}
	}
	
	private function CompareByStopPosition($a,$b) 
	{
		if ( $a["position"] == $b["position"] ) return 0;
		else return ( $a["position"] < $b["position"] ) ? -1 : 1;
	}
	
}
?>