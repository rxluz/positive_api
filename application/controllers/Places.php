<?php
defined('BASEPATH') OR exit('No direct script access allowed');
include("application/controllers/Common.php");
include("application/libraries/Uploadcare/File.php");
include("application/libraries/Uploadcare/FileIterator.php");
include("application/libraries/Uploadcare/Group.php");
include("application/libraries/Uploadcare/Helper.php");
include("application/libraries/Uploadcare/Uploader.php");
include("application/libraries/Uploadcare/Widget.php");
include("application/libraries/Uploadcare/Api.php");
use Uploadcare\Api;
$api = new Uploadcare\Api(UC_PUBLIC_KEY, UC_SECRET_KEY);

include("application/libraries/php-googleplaces-master/src/GooglePlacesClient.php");
include("application/libraries/php-googleplaces-master/src/GooglePlaces.php");

$google_places = new joshtronic\GooglePlaces(GOOGLE_API_KEY);


/**
 * traboxapp sign functions
 */
class Places extends Common {

	private $filters_info=false;
	private $limit=false;
	private $offset=false;
	private $page=false;
	private $order=false;
	private $filter_keyword=false;
	private $filter_location=false;
	private $result_ids=array();

	/*
		Retorna:
		0 Cultura
			aquarium
			art_gallery
			book_store
			museum
			park
		1 Saude
			doctor
			health
			hospital
			pharmacy
			physiotherapist
		2 Lazer
			amusement_park
			bicycle_store
			bowling_alley
			zoo
		3 Educação
			school
		4 Comércio
			accounting
			airport
			atm
			bakery
			bank
			bar
			beauty_salon
			bus_station
			cafe
			campground
			car_dealer
			car_rental
			car_repair
			car_wash
			casino
			cemetery
			church
			city_hall
			clothing_store
			convenience_store
			courthouse
			dentist
			department_store
			electrician
			electronics_store
			embassy
			establishment
			finance
			fire_station
			florist
			food
			funeral_home
			furniture_store
			gas_station
			general_contractor
			grocery_or_supermarket
			gym
			hair_care
			hardware_store
			hindu_temple
			home_goods_store
			insurance_agency
			jewelry_store
			laundry
			lawyer
			library
			liquor_store
			local_government_office
			locksmith
			lodging
			meal_delivery
			meal_takeaway
			mosque
			movie_rental
			movie_theater
			moving_company
			night_club
			painter
			parking
			pet_store
			place_of_worship
			plumber
			police
			post_office
			real_estate_agency
			restaurant
			roofing_contractor
			rv_park
			shoe_store
			shopping_mall
			spa
			stadium
			storage
			store
			subway_station
			synagogue
			taxi_stand
			train_station
			travel_agency
			university
			veterinary_care
		5 Obstáculo
		6 Recursos publicos de acessibilidade



		0 Cultura
		1 Saude
		2 Lazer
		3 Educação
		4 Comércio
		5 Obstáculo
		6 Recursos publicos de acessibilidade

	*/
	protected function getGoogleTypes($cats=false){
		$list=array('accounting', 'airport', 'atm', 'bakery', 'bank', 'bar', 'beauty_salon', 'bus_station', 'cafe', 'campground', 'car_dealer', 'car_rental', 'car_repair', 'car_wash', 'casino', 'cemetery', 'church', 'city_hall', 'clothing_store', 'convenience_store', 'courthouse', 'dentist', 'department_store', 'electrician', 'electronics_store', 'embassy', 'establishment', 'finance', 'fire_station', 'florist', 'food', 'funeral_home', 'furniture_store', 'gas_station', 'general_contractor', 'grocery_or_supermarket', 'gym', 'hair_care', 'hardware_store', 'hindu_temple', 'home_goods_store', 'insurance_agency', 'jewelry_store', 'laundry', 'lawyer', 'library', 'liquor_store', 'local_government_office', 'locksmith', 'lodging', 'meal_delivery', 'meal_takeaway', 'mosque', 'movie_rental', 'movie_theater', 'moving_company', 'night_club', 'painter', 'parking', 'pet_store', 'place_of_worship', 'plumber', 'police', 'post_office', 'real_estate_agency', 'restaurant', 'roofing_contractor', 'rv_park', 'shoe_store', 'shopping_mall', 'spa', 'stadium', 'storage', 'store', 'subway_station', 'synagogue', 'taxi_stand', 'train_station', 'travel_agency', 'university', 'veterinary_care','school','doctor', 'health', 'hospital', 'pharmacy', 'physiotherapist', 'amusement_park', 'bicycle_store', 'bowling_alley', 'zoo','aquarium', 'art_gallery', 'book_store', 'museum', 'park');
		$ret_all=implode("|", $list);

		$ret_filter="";

		if($cats!==false):
			$trading=array('accounting', 'airport', 'atm', 'bakery', 'bank', 'bar', 'beauty_salon', 'bus_station', 'cafe', 'campground', 'car_dealer', 'car_rental', 'car_repair', 'car_wash', 'casino', 'cemetery', 'church', 'city_hall', 'clothing_store', 'convenience_store', 'courthouse', 'dentist', 'department_store', 'electrician', 'electronics_store', 'embassy', 'establishment', 'finance', 'fire_station', 'florist', 'food', 'funeral_home', 'furniture_store', 'gas_station', 'general_contractor', 'grocery_or_supermarket', 'gym', 'hair_care', 'hardware_store', 'hindu_temple', 'home_goods_store', 'insurance_agency', 'jewelry_store', 'laundry', 'lawyer', 'library', 'liquor_store', 'local_government_office', 'locksmith', 'lodging', 'meal_delivery', 'meal_takeaway', 'mosque', 'movie_rental', 'movie_theater', 'moving_company', 'night_club', 'painter', 'parking', 'pet_store', 'place_of_worship', 'plumber', 'police', 'post_office', 'real_estate_agency', 'restaurant', 'roofing_contractor', 'rv_park', 'shoe_store', 'shopping_mall', 'spa', 'stadium', 'storage', 'store', 'subway_station', 'synagogue', 'taxi_stand', 'train_station', 'travel_agency', 'university', 'veterinary_care');
			$trading_filters=implode("|", $trading);

			$education=array('school');
			$education_filters=implode("|", $education);

			$health=array('doctor', 'health', 'hospital', 'pharmacy', 'physiotherapist');
			$health_filters=implode("|", $health);

			$recreation=array('amusement_park', 'bicycle_store', 'bowling_alley', 'zoo');
			$recreation_filters=implode("|", $recreation);

			$culture=array('aquarium', 'art_gallery', 'book_store', 'museum', 'park');
			$culture_filters=implode("|", $culture);


			if($cats->culture==true):
				$ret_filter.="|".$culture_filters;
			endif;

			if($cats->education==true):
				$ret_filter.="|".$education_filters;
			endif;

			if($cats->health==true):
				$ret_filter.="|".$health_filters;
			endif;

			if($cats->recreation==true):
				$ret_filter.="|".$recreation_filters;
			endif;

			if($cats->trading==true):
				$ret_filter.="|".$trading_filters;
			endif;
		endif;

		return $ret_filter==""?$ret_all:$ret_filter;
	}

	protected function getCategoryPlace($cat_from_google){
		$comercio=array('accounting', 'airport', 'atm', 'bakery', 'bank', 'bar', 'beauty_salon', 'bus_station', 'cafe', 'campground', 'car_dealer', 'car_rental', 'car_repair', 'car_wash', 'casino', 'cemetery', 'church', 'city_hall', 'clothing_store', 'convenience_store', 'courthouse', 'dentist', 'department_store', 'electrician', 'electronics_store', 'embassy', 'establishment', 'finance', 'fire_station', 'florist', 'food', 'funeral_home', 'furniture_store', 'gas_station', 'general_contractor', 'grocery_or_supermarket', 'gym', 'hair_care', 'hardware_store', 'hindu_temple', 'home_goods_store', 'insurance_agency', 'jewelry_store', 'laundry', 'lawyer', 'library', 'liquor_store', 'local_government_office', 'locksmith', 'lodging', 'meal_delivery', 'meal_takeaway', 'mosque', 'movie_rental', 'movie_theater', 'moving_company', 'night_club', 'painter', 'parking', 'pet_store', 'place_of_worship', 'plumber', 'police', 'post_office', 'real_estate_agency', 'restaurant', 'roofing_contractor', 'rv_park', 'shoe_store', 'shopping_mall', 'spa', 'stadium', 'storage', 'store', 'subway_station', 'synagogue', 'taxi_stand', 'train_station', 'travel_agency', 'university', 'veterinary_care');

		$educacao=array('school');

		$saude=array('doctor', 'health', 'hospital', 'pharmacy', 'physiotherapist');

		$lazer=array('amusement_park', 'bicycle_store', 'bowling_alley', 'zoo');

		$cultura=array('aquarium', 'art_gallery', 'book_store', 'museum', 'park');


		$type=4;
		foreach($cat_from_google as $t):
			//verifica se é cultura
			foreach($cultura as $c):
				if($t==$c):
					return 0;
				endif;
			endforeach;

			//verifica se é saude
			foreach($saude as $s):
				if($t==$s):
					return 1;
				endif;
			endforeach;

			//verifica se é educação
			foreach($educacao as $e):
				if($t==$e):
					return 3;
				endif;
			endforeach;

			//verifica se é lazer
			foreach($lazer as $l):
				if($t==$l):
					return 2;
				endif;
			endforeach;

			//verifica se é comercio
			foreach($comercio as $co):
				if($t==$co):
					return 4;
				endif;
			endforeach;
		endforeach;

		return 4;


	}


	protected function setGoogleRequest($type, $page=false){


		//return $results;
		//print_r($results);
	}


	protected function setData($origin_data=array(), $results, $filters=false){
		$data=$origin_data;

		foreach($results["results"] as $res):
			$bookmark=$this->isBookmark($res["place_id"]);
			$comments_sum=$this->getCommentsTotal($res["place_id"]);
			$rating_avg_number=$this->getReviewAvg($res["place_id"]);

			$physical=$this->getResources('physical', $res["place_id"]);
			$visual=$this->getResources('visual', $res["place_id"]);
			$hearing=$this->getResources('hearing', $res["place_id"]);
			$mental=$this->getResources('mental', $res["place_id"]);

			$data_added=array(
				"id" => $res["place_id"],
				"lat" => $res["geometry"]["location"]["lat"],
				"lng" => $res["geometry"]["location"]["lng"],
				"location" => $res["geometry"]["location"]["lat"].", ".$res["geometry"]["location"]["lng"],
				"name" => $res["name"],
				"type" => $this->getCategoryPlace($res["types"]),
				"distance" => $this->getDistance($res["geometry"]["location"]["lat"],$res["geometry"]["location"]["lng"], "K", true),
				"address" => $res["vicinity"],
				"rating_avg" => $this->getStar($rating_avg_number),
				"rating_avg_number" => $rating_avg_number,
				"resources" => array(
					"physical" => $physical,
					"visual" => $visual,
					"hearing" => $hearing,
					"mental" => $mental
				),
				"bookmark" => $bookmark,
				"comments_sum" => $comments_sum
			);

			$put=true;

			$disability=$filters->disability;

			$category=$filters->category;

			//print_r($filters);


			if($disability->physical==1 && $physical==''):
				$put=false;
			endif;

			if($disability->visual==1 && $visual==''):
				$put=false;
			endif;

			if($disability->hearing==1 && $hearing==''):
				$put=false;
			endif;

			if($disability->mental==1 && $mental==''):
				$put=false;
			endif;




			if($put):
				if($rating_avg_number>0 || $bookmark || $comments_sum >0 || $this->isPhotoLocal($res["place_id"])):
					array_unshift($data, $data_added);
				else:
					array_push($data, $data_added);
				endif;
			endif;

		endforeach;

		return $data;
	}

	/*
		Busca todos os locais pertos do usuário que tiveram algum informe de acessibilidade,
		essa lista serve tanto para mapas quanto para a lista

		//verifica se a localização do usuário foi alterada a mais de 10 metros, caso tenha sido faz uma nova requisição
		//caso o tempo entre a ultima requisição e a atual seja maior que 1 dia faz uma nova requisição


		retorna uma array de data com os seguintes dados:
		id
		lat
		lng
		name
		type
		distance
		address
		rating_avg
		comments_sum
		resources_whell
		resources_visual
		resources_hearing
		resources_mental

	*/
	protected function getNearby($type="DISTANCE", $returnJson=true){

		$results=$this->setGoogleRequest($type);

		global $google_places;
		$lat=$this->inputVars["lat"];
		$lng=$this->inputVars["lng"];

		$filters=json_decode(base64_decode($this->inputVars["filters"]));

		//print_r($filters->disability);

		$google_places->location = array($lat, $lng);
		$google_places->rankby   = $type;
		$google_places->radius   = '6.000';
		$google_places->types   = $this->getGoogleTypes($filters->category);

		$results                 = $google_places->nearbySearch();

		unset($google_places->location);
		unset($google_places->radius);
		unset($google_places->types);

		// $google_places->pagetoken=$results["next_page_token"];
		// $data_next = $google_places->nearbySearch();

		//print_r($data_next);
		//echo "ola mundo";

		// $google_places->pagetoken=$data_next["next_page_token"];
		// $data_next_2 = $google_places->nearbySearch();


		$data=$this->setData(array(), $results, $filters);
		//$data=$this->setData($data, $data_next);
		// array_push($data,
		// 	$this->setData(array(),
		// 		$data_next
		// 	)
		// );

		// array_push($data,
		// 	$this->setData(
		// 		$data_next_2
		// 	)
		// );



		if($returnJson):
			echo json_encode(array("status"=>"success", "count"=>count($data), "data"=>$data,  "data_holdback"=>$this->getHoldback()));
			return true;
		endif;

		return $data;
	}

	//retorna a lista de holdbacks cadastrados
	protected function getHoldback(){
		$this->db->select("users.name AS user_name");
		$this->db->select("places_holdbacks.id AS id");
		$this->db->select("places_holdbacks.location AS location");
		$this->db->select("places_holdbacks.lat_added AS lat_added");
		$this->db->select("places_holdbacks.lng_added AS lng_added");
		$this->db->select("places_holdbacks.type AS type");
		$this->db->select("places_holdbacks.obs AS obs");
		$this->db->select("places_holdbacks.date_added AS date_added");

		$this->db->from("places_holdbacks");
		$this->db->join("users", "users.id=places_holdbacks.id_user");
		$this->db->where("places_holdbacks.status", 1);

		$get=$this->db->get();

		$data=array();
		if($get->num_rows()>0):
			foreach($get->result() as $row):
				if($this->isHoldbackFlag($row->id)<3):
					array_push($data, array(
						"id" => $row->id,
						"location" => $row->location,
						"lat" => $row->lat_added,
						"lng" => $row->lng_added,
						"type" => $row->type,
						"obs" => $row->obs,
						"user_name" => $row->user_name,
						"date_added" => $this->setDateBRFormat($row->date_added)
					));
				endif;
			endforeach;
		endif;



		return $data;



	}


	/*
		Busca todos os locais pertos do usuário que tiveram algum informe de acessibilidade,
		essa lista serve tanto para mapas quanto para a lista

		//verifica se a localização do usuário foi alterada a mais de 10 metros, caso tenha sido faz uma nova requisição
		//caso o tempo entre a ultima requisição e a atual seja maior que 1 dia faz uma nova requisição


		retorna uma array de data com os seguintes dados:
		id
		lat
		lng
		name
		type
		distance
		address
		rating_avg
		comments_sum
		resources_whell
		resources_visual
		resources_hearing
		resources_mental

	*/
	protected function setSearch(){
		//echo "ola mundo";
		global $google_places;
		$lat=$this->inputVars["lat"];
		$lng=$this->inputVars["lng"];

		$google_places->location = array($lat, $lng);
		//$google_places->radius   = '50.000';
		$google_places->radius   = '4.000';
		$google_places->types   = $this->getGoogleTypes();
		$google_places->language   = 'pt-BR';

		$google_places->query   = urlencode($this->inputVars["query"]);
		//$google_places->types    = 'restaurant'; // Requires keyword, name or types
		$results                 = $google_places->textSearch();
		//print_r($results);


		//return false;

		$data=array();

		foreach($results["results"] as $res):
			$rating_avg_number=$this->getReviewAvg($res["place_id"]);
			array_push($data, array(
					"id" => $res["place_id"],
					"lat" => $res["geometry"]["location"]["lat"],
					"lng" => $res["geometry"]["location"]["lng"],
					"name" => $res["name"],
					"type" => $this->getCategoryPlace($res["types"]),
					"distance" => $this->getDistance($res["geometry"]["location"]["lat"],$res["geometry"]["location"]["lng"], "K", true),
					"address" => $res["formatted_address"],
					"rating_avg" => $this->getStar($rating_avg_number),
					"rating_avg_number" => $rating_avg_number,
					"resources" => array(
						"physical" => $this->getResources('physical', $res["place_id"]),
						"visual" => $this->getResources('visual', $res["place_id"]),
						"hearing" => $this->getResources('hearing', $res["place_id"]),
						"mental" => $this->getResources('mental', $res["place_id"])
					),
					"bookmark" => $this->isBookmark($res["place_id"]),
					"comments_sum" => $this->getCommentsTotal($res["place_id"])
				)
			);

		endforeach;

		echo json_encode(array("status"=>"success", "data"=>$data));
	}

	private function getStar($number){
		return array(
			"iconOn" =>  'ion-ios-star',
			"iconOff" =>  'ion-ios-star-outline',
			"iconOnColor" =>  'rgb(200, 200, 100)',
			"iconOffColor" =>  'rgb(200, 100, 100)',
			"rating" =>  $number,
			"minRating" => 1
		);
	}

	/*
		salva um obstaculo no banco de dados, o é ncessário que seja um usuário identificado para executar essa ação
		precisa:
		"lat",
		"lng",
		"type",
		"location",
		"obs"
	*/
	protected function setHoldback(){

		$latlng=$this->getLatLngFromAddress($this->inputVars["location"]);

		$data=array(
			"id_user" => $this->userId,
			"location" => $this->inputVars["location"],
			"type" => $this->inputVars["type"],
			"obs" => $this->inputVars["obs"],
			"date_added" => $this->setMysqlTimestamp("now"),
			"ip_added" => USER_IP,
			"lat_added" => $latlng["lat"],
			"lng_added" => $latlng["lng"],
			"status" => "1"
		);

		$this->db->insert("places_holdbacks", $data);
		echo json_encode(array("status"=>"success"));
	}


	protected function setHoldbackFlag(){
		$this->db->from("places_holdbacks");
		$this->db->where("id_user", $this->userId);
		$this->db->where("id", $this->inputVars["id"]);

		$isOwner=$this->db->get();

		if($isOwner->num_rows()>0):
			$data=array("status"=>"0");
			$this->db->where("id", $this->inputVars["id"]);
			$this->db->update("places_holdbacks", $data);
		else:
			$data=array(
				"id_places_holdbacks" => $this->inputVars["id"],
				"id_user" => $this->userId,
				"date_added" => $this->setMysqlTimestamp("now"),
				"ip_added" => USER_IP,
				"lat_added" => $this->inputVars["lat"],
				"lng_added" => $this->inputVars["lng"]
			);

			$this->db->insert("places_holdbacks_flags", $data);
		endif;

		echo json_encode(array("status"=>"success"));
	}

	protected function setPhotoFlag(){
		//desativa uma foto se o usuario for o dono dela, se não for adiciona na tabela places_photos_flags que essa foto foi denunciada
		//as fotos que possuem flags devem ser exibidas por ultimas
		//fotos com mais de 3 flags não serão mais exibidas
		//um usuario que denunciou uma foto não pode mais ver a foto denunciada
		$this->db->from("places_photos");
		$this->db->where("photo_url", base64_decode($this->inputVars["photo_url"]));
		$this->db->where("id_user", $this->userId);
		//$this->db->where("id", $this->inputVars["id"]);

		$isOwner=$this->db->get();

		if($isOwner->num_rows()>0):
			$data=array("status"=>"0");
			$this->db->where("photo_url", base64_decode($this->inputVars["photo_url"]));
			$this->db->update("places_photos", $data);
		else:
			$data=array(
				"photo_url" => base64_decode($this->inputVars["photo_url"]),
				"id_user" => $this->userId,
				"date_added" => $this->setMysqlTimestamp("now"),
				"ip_added" => USER_IP,
				"lat_added" => $this->inputVars["lat"],
				"lng_added" => $this->inputVars["lng"]
			);

			$this->db->insert("places_photos_flags", $data);
		endif;

		//echo json_encode(array("status"=>"success"));


		echo json_encode(array("status"=>"success", "url"=>base64_decode($this->inputVars["photo_url"])));
	}


	protected function isPhotoLocal($id_google){
		$this->db->from("places_photos");
		$this->db->where("status", "1");
		$this->db->where("id_google", $id_google);
		$get=$this->db->get();

		return $get->num_rows()>0?true:false;
	}

	/*
		retorna a quantidade de vezes que uma photo foi denunciada
	*/
	protected function isPhotoFlag($photo_url){
		$this->db->from("places_photos_flags");
		$this->db->where("photo_url", $photo_url);
		$get=$this->db->get();

		$isUserFlag=false;
		//caso o usuario tenha flag essa foto retorna um numero alto para ela não aparecer na lista

		if($get->num_rows()>0):
			foreach($get->result() as $row):
				if($row->id_user==$this->userId):
					$isUserFlag=true;
				endif;
			endforeach;
		endif;

		return $isUserFlag==false?$get->num_rows():90;
	}

	/*
		retorna a quantidade de vezes que um obstaculo foi marcado como incorreto
	*/
	protected function isHoldbackFlag($id_places_holdbacks){
		$this->db->from("places_holdbacks_flags");
		$this->db->where("id_places_holdbacks", $id_places_holdbacks);
		$get=$this->db->get();

		$isUserFlag=false;
		//caso o usuario tenha flag essa foto retorna um numero alto para ela não aparecer na lista

		if($get->num_rows()>0):
			foreach($get->result() as $row):
				if($row->id_user==$this->userId):
					$isUserFlag=true;
				endif;
			endforeach;
		endif;

		return $isUserFlag==false?$get->num_rows():90;
	}

	/*
		Retorna um local com a lista de fotos, informações gerais e principais comentários
		Retorna:
		- name
		- type
		- rating_avg,
		- comments_sum
		- address
		- resources_list
		- comments_list
		- distance
	*/
	protected function getPhotos($photos_array, $id_google){
		$photos=array();
		//busca as photos do google e do urban, mas fotos do urban serão mostradas primeiro

		//global $google_places;

		// $google_places->placeid = $this->inputVars["id"]; // Reference from search results
		// $details                  = $google_places->details();

		//busca as fotos do usuario e depois as outras de outros usuarios
		$this->db->from("places_photos");
		$this->db->where("id_google", $id_google);
		$this->db->where("status", "1");
		$this->db->where("id_user", $this->userId);
		$this->db->order_by("date_added", "DESC");

		$get=$this->db->get();

		if($get->num_rows()>0):
			foreach($get->result() as $row):
				array_push($photos, $row->photo_url);
			endforeach;
		endif;


		$this->db->from("places_photos");
		$this->db->where("id_google", $id_google);
		$this->db->where("status", "1");
		//$this->db->where("id_user", $this->userId);
		$this->db->order_by("date_added", "DESC");

		$get=$this->db->get();

		if($get->num_rows()>0):
			foreach($get->result() as $row):
				//nesse momento retorna apenas fotos que não possuem denuncias
				if($row->id_user!==$this->userId && $this->isPhotoFlag($row->photo_url)==0):
					array_push($photos, $row->photo_url);
				endif;
			endforeach;
		endif;


		if($photos_array!==false):
			foreach($photos_array as $p):
				//$google_places->photoreference=$p["photo_reference"];
				//$google_places->maxheight=800;

				//$photoG=$google_places->photo();

				//print_r($photoG);

				$photo_url="https://maps.googleapis.com/maps/api/place/photo?key=".GOOGLE_API_KEY."&photoreference=".$p["photo_reference"]."&maxheight=800";

				if($this->isPhotoFlag($photo_url)==0):
					array_push($photos,$photo_url);
				endif;
			endforeach;
		endif;



		//depois de colocar todas as fotos passa a exibir as fotos que possuem menos de 4 flags
		$this->db->from("places_photos");
		$this->db->where("id_google", $id_google);
		$this->db->where("status", "1");
		$this->db->order_by("date_added", "DESC");

		$get=$this->db->get();

		if($get->num_rows()>0):
			foreach($get->result() as $row):
				//nesse momento retorna apenas fotos que não possuem denuncias
				if($this->isPhotoFlag($row->photo_url)<4 && $this->isPhotoFlag($row->photo_url)>1):
					array_push($photos, $row->photo_url);
				endif;
			endforeach;
		endif;


		if($photos_array!==false):
			foreach($photos_array as $p):

				$photo_url="https://maps.googleapis.com/maps/api/place/photo?key=".GOOGLE_API_KEY."&photoreference=".$p["photo_reference"]."&maxheight=800";

				if($this->isPhotoFlag($photo_url)>0 && $this->isPhotoFlag($photo_url)<4):
					array_push($photos,$photo_url);
				endif;
			endforeach;
		endif;




		return $photos;

	}


	protected function getStreetPhoto($lat, $lng){
		return "https://maps.googleapis.com/maps/api/streetview?size=400x200&location=".$lat.", ".$lng."&fov=90&heading=235&pitch=10&key=".GOOGLE_API_STREET_KEY;
	}


	protected function getStreetUrl($lat, $lng){
		return "https://maps.google.com/maps?q=&layer=c&cbll=$lat,$lng&cbp=11,0,0,0,0&ll=$lat,$lng&z=10";

		 //
		//  "https://maps.googleapis.com/maps/api/streetview?size=400x200&location=".$lat.", ".$lng."&fov=90&heading=235&pitch=10&key=".GOOGLE_API_STREET_KEY;
	}


	protected function getLocal($id_google=false, $returnJson=true){
		global $google_places;

		$id_google=$id_google==false?$this->inputVars["id"]:$id_google;

		$google_places->placeid = $id_google; // Reference from search results
		$details                  = $google_places->details();

		//print_r($details);

		$res=$details["result"];
		$photos=isset($res["photos"])?$this->getPhotos($res["photos"], $id_google):$this->getPhotos(false, $id_google);

		$rating_avg_number=$this->getReviewAvg($res["place_id"]);
		$basic=array(
			"id" => $res["place_id"],
			"lat" => $res["geometry"]["location"]["lat"],
			"lng" => $res["geometry"]["location"]["lng"],
			"name" => $res["name"],
			"type" => $this->getCategoryPlace($res["types"]),
			"distance" => $this->getDistance($res["geometry"]["location"]["lat"],$res["geometry"]["location"]["lng"], "K", true),

			"address" => $res["vicinity"],
			"url" => $res["url"],
			"street_image" => $this->getStreetPhoto($res["geometry"]["location"]["lat"], $res["geometry"]["location"]["lng"]),
			"street_url" => $this->getStreetUrl($res["geometry"]["location"]["lat"], $res["geometry"]["location"]["lng"]),

			"rating_avg" => $this->getStar($rating_avg_number),
			"rating_avg_number" => $rating_avg_number,
			"resources" => array(
				"physical" => $this->getResources('physical', $res["place_id"]),
				"visual" => $this->getResources('visual', $res["place_id"]),
				"hearing" => $this->getResources('hearing', $res["place_id"]),
				"mental" => $this->getResources('mental', $res["place_id"])
			),
			"bookmark" => $this->isBookmark($res["place_id"]),
			"comments_sum" => $this->getCommentsTotal($res["place_id"])
		);

		$data=array(
			"id" => $res["place_id"],
			"distance_number" => $this->getDistance($res["geometry"]["location"]["lat"],$res["geometry"]["location"]["lng"], ";M", false),
			"basic" => $basic,
			"photos" => $photos
		);


		if($returnJson):
			echo json_encode(array("status"=>"success", "data"=>$data));
		endif;

		return $data;
	}

	protected function getResources($type, $id_google){
		$text="";

		$this->db->from("places_reviews");
		$this->db->where("id_google", $id_google);

		$get=$this->db->get();

		$physical_rampas=0;
		$physical_guias=0;
		$physical_banheiros=0;
		$physical_iluminacao=0;
		$physical_bebedouros=0;
		$physical_telefones=0;
		$physical_movimentacao=0;
		$physical_elevadores=0;
		$visual_direcional=0;
		$visual_tatil=0;
		$visual_semaforo=0;
		$visual_placas=0;
		$hearing_interprete=0;
		$hearing_audio=0;
		$mental_instrutores=0;
		$mental_tutores=0;



		if($get->num_rows()>0):
			foreach($get->result() as $row):
					if($row->physical_rampas==1):
						$physical_rampas++;
					endif;

					if($row->physical_guias==1):
						$physical_guias++;
					endif;


					if($row->physical_banheiros==1):
						$physical_banheiros++;
					endif;


					if($row->physical_iluminacao==1):
						$physical_iluminacao++;
					endif;


					if($row->physical_bebedouros==1):
						$physical_bebedouros++;
					endif;


					if($row->physical_telefones==1):
						$physical_telefones++;
					endif;


					if($row->physical_movimentacao==1):
						$physical_movimentacao++;
					endif;




					if($row->physical_elevadores==1):
						$physical_elevadores++;
					endif;


					if($row->visual_direcional==1):
						$visual_direcional++;
					endif;


					if($row->visual_tatil==1):
						$visual_tatil++;
					endif;


					if($row->visual_semaforo==1):
						$visual_semaforo++;
					endif;


					if($row->visual_placas==1):
						$visual_placas++;
					endif;



					if($row->hearing_interprete==1):
						$hearing_interprete++;
					endif;

					if($row->hearing_audio==1):
						$hearing_audio++;
					endif;

					if($row->mental_instrutores==1):
						$mental_instrutores++;
					endif;

					if($row->mental_tutores==1):
						$mental_tutores++;
					endif;

			endforeach;
		endif;


		switch($type):
			case 'physical':
				// $physical_rampas=0;
				// $physical_guias=0;
				// $physical_banheiros=0;
				// $physical_iluminacao=0;
				// $physical_bebedouros=0;
				// $physical_telefones=0;
				// $physical_movimentacao=0;
				// $physical_elevadores=0;

				if($physical_rampas>0):
						$text=$text."Rampas, ";
				endif;

				if($physical_guias>0):
					$text=$text."Guias rebaixadas, ";
				endif;

				if($physical_banheiros>0):
					$text=$text."Banheiros adaptados, ";
				endif;


				if($physical_iluminacao>0):
					$text=$text."Iluminação adequada, ";
				endif;


				if($physical_bebedouros>0):
					$text=$text."Bebedouros adaptados, ";
				endif;

				if($physical_telefones>0):
					$text=$text."Telefones publicos adaptados, ";
				endif;


				if($physical_movimentacao>0):
					$text=$text."Espaço para movimentação de cadeira de rodas, ";
				endif;


				if($physical_elevadores>0):
					$text=$text."Elevadores ou plataformas elevatórias, ";
				endif;

			break;

			case 'visual':
			// $visual_direcional=0;
			// $visual_tatil=0;
			// $visual_semaforo=0;
			// $visual_placas=0;

				if($visual_direcional>0):
						$text=$text."Piso direcional, ";
				endif;

				if($visual_tatil>0):
					$text=$text."Piso tátil, ";
				endif;

				if($visual_semaforo>0):
					$text=$text."Semaforo sonoro, ";
				endif;


				if($visual_placas>0):
					$text=$text."Placas de identificação em braile, ";
				endif;

			break;

			case 'hearing':
			// $hearing_interprete=0;
			// $hearing_audio=0;
				if($hearing_interprete>0):
					$text=$text."Interprete de libras, ";
				endif;


				if($hearing_audio>0):
					$text=$text."Audio descrição, ";
				endif;


			break;

			case 'mental':
			// $mental_instrutores=0;
			// $mental_tutores=0;

				if($mental_instrutores>0):
					$text=$text."Instrutores, ";
				endif;


				if($mental_tutores>0):
					$text=$text."Tutores, ";
				endif;

			break;
		endswitch;



		return $text;

	}

	/*
		 busca a lista de favoritos do usuario logado
	*/
	protected function getBookmarks($returnJson=true){
		$this->db->from("places_bookmarks");
		$this->db->where("id_user", $this->userId);
		$this->db->order_by("date_added", "desc");
		$get=$this->db->get();
		$data=array();

		if($get->num_rows()>0):
			foreach($get->result() as $row):
				/*
					a ideia é que a array seja ordenada de acordo com a distancia, para isso é necessário pedir ao google informação sobre cada local e calcular a distancia do local atual, depois mandar isso pro js, o js se encarrega de ordenar isso
				*/
				$infos=$this->getLocal($row->id_google, false);
				array_push($data, $infos);

			endforeach;
		endif;

		if($returnJson):
			echo json_encode(array("status"=>"success", "data"=>$data));
		endif;

		return $data;
	}


	/* busca se esse usuario deu like no comentario pedido */
	protected function isBookmark($id_google){
		$this->db->where("id_google", $id_google);
		$this->db->where("id_user", $this->userId);
		$this->db->from("places_bookmarks");

		$get=$this->db->get();

		return $get->num_rows()>0?true:false;
	}

	protected function setBookmarkRemove(){
		//verifica se o usuario ja deu like nesse comentario, se deu retira o like, de nao deu coloca o like

		if($this->isBookmark($this->inputVars["id"])):
			$this->db->where("id_user", $this->userId);
			$this->db->where("id_google", $this->inputVars["id"]);
			$this->db->delete("places_bookmarks");
		endif;

		$data=$this->getBookmarks(false);

		echo json_encode(array("status"=>"success", "data"=>$data));
	}

	protected function setBookmark(){
		//verifica se o usuario ja deu like nesse comentario, se deu retira o like, de nao deu coloca o like

		if($this->isBookmark($this->inputVars["id"])):
			$this->db->where("id_user", $this->userId);
			$this->db->where("id_google", $this->inputVars["id"]);
			$this->db->delete("places_bookmarks");
		else:
			$data=array(
				"id_google" => $this->inputVars["id"],
				"id_user" => $this->userId,
				"date_added" => $this->setMysqlTimestamp("now"),
				"ip_added" => USER_IP,
				"lat_added" => $this->inputVars["lat"],
				"lng_added" => $this->inputVars["lng"]
			);

			$this->db->insert("places_bookmarks", $data);
		endif;

		$data=array(
			"isBookmark" => $this->isBookmark($this->inputVars["id"])
		);

		echo json_encode(array("status"=>"success", "data"=>$data));
	}


	protected function setComment(){
		$data=array(
			"id_google" => $this->inputVars["id"],
			"id_user" => $this->userId,
			"comment" => $this->inputVars["comment"],
			"date_added" => $this->setMysqlTimestamp("now"),
			"ip_added" => USER_IP,
			"lat_added" => $this->inputVars["lat"],
			"lng_added" => $this->inputVars["lng"],
			"status" => "1"
		);

		$this->db->insert("places_comments", $data);

		echo json_encode(array("status"=>"success"));
	}


	protected function getCommentsLikeTotal($id_comment){
		$this->db->where("id_places_comments", $id_comment);
		//$this->db->where("id_user", $this->userId);
		$this->db->from("places_comments_likes");

		$get=$this->db->get();

		return $get->num_rows();
	}

	/* busca se esse usuario deu like no comentario pedido */
	protected function isCommentLike($id_comment){
		$this->db->where("id_places_comments", $id_comment);
		$this->db->where("id_user", $this->userId);
		$this->db->from("places_comments_likes");

		$get=$this->db->get();

		return $get->num_rows()>0?true:false;
	}

	protected function setCommentLike(){
		//verifica se o usuario ja deu like nesse comentario, se deu retira o like, de nao deu coloca o like

		if($this->isCommentLike($this->inputVars["id"])):
			$this->db->where("id_user", $this->userId);
			$this->db->where("id_places_comments", $this->inputVars["id"]);
			$this->db->delete("places_comments_likes");
		else:
			$data=array(
				"id_places_comments" => $this->inputVars["id"],
				"id_user" => $this->userId,
				"date_added" => $this->setMysqlTimestamp("now"),
				"ip_added" => USER_IP,
				"lat_added" => $this->inputVars["lat"],
				"lng_added" => $this->inputVars["lng"]
			);

			$this->db->insert("places_comments_likes", $data);
		endif;

		$data=array("isLike" => $this->isCommentLike($this->inputVars["id"]), ";totalLikes" => $this->getCommentsLikeTotal($this->inputVars["id"]));

		echo json_encode(array("status"=>"success", "data"=>$data));
	}





	protected function setCommentFlag(){
		//se o comentário for do usuario ele desativa, se ele for de outro usuario adiciona tabela de places_comments_flags
		$this->db->from("places_comments");
		$this->db->where("id_user", $this->userId);
		$this->db->where("id", $this->inputVars["id"]);

		$isOwner=$this->db->get();

		if($isOwner->num_rows()>0):
			$data=array("status"=>"0");
			$this->db->where("id", $this->inputVars["id"]);
			$this->db->update("places_comments", $data);
		else:
			$data=array(
				"id_places_comments" => $this->inputVars["id"],
				"id_user" => $this->userId,
				"date_added" => $this->setMysqlTimestamp("now"),
				"ip_added" => USER_IP,
				"lat_added" => $this->inputVars["lat"],
				"lng_added" => $this->inputVars["lng"]
			);

			$this->db->insert("places_comments_flags", $data);
		endif;

		echo json_encode(array("status"=>"success"));
	}

	protected function setPhoto(){
		global $api;
		$content=$this->inputVars["photo"];
		//$content=str_replace("data:image/jpeg;base64, ";, ";", $content);
		//$content =  base64_decode($this->inputVars["photo_data"]);
		$data = explode(',', $content);
		$file = $api->uploader->fromContent(base64_decode($data[1]), 'image/jpeg');
		$file->store();
		//echo $file->getUrl();

		$data=array(
			"id_google" => $this->inputVars["id"],
			"id_user" => $this->userId,
			"photo_url" => $file->getUrl(),
			"desc" => $this->inputVars["desc"],
			"date_added" => $this->setMysqlTimestamp("now"),
			"ip_added" => USER_IP,
			"lat_added" => $this->inputVars["lat"],
			"lng_added" => $this->inputVars["lng"],
			"status" => "1"
		);

		$this->db->insert("places_photos", $data);


		echo json_encode(array("status"=>"success"));
	}


	protected function getCommentsTotal($id_google){
		$this->db->select("users.name AS name");
		$this->db->select("users.facebook_profile_id AS facebook_profile_id");
		$this->db->select("places_comments.comment AS comment");
		$this->db->select("places_comments.date_added AS date_added");
		$this->db->from("places_comments");
		$this->db->join("users", "places_comments.id_user=users.id");
		$this->db->where("id_google", $id_google);

		$result=$this->db->get();

		return $result->num_rows();
	}

	/*
		Busca os comentários de um local, retorna:
		LISTA:
			id do comentário
			foto do usuário (se houver, se nao houver retorna uma foto padrao)
			nome do usuário
			data e hora da publicação
			comentario
			avaliacao
			numero de likes
	*/
	protected function getComments(){
		$flagCommentsByUser=$this->getCommentsFlagsByUser($this->inputVars["id"]);

		$this->db->select("users.name AS name");
		$this->db->select("users.id AS id_user");
		$this->db->select("users.facebook_profile_id AS facebook_profile_id");
		$this->db->select("places_comments.comment AS comment");
		$this->db->select("places_comments.id AS id");
		$this->db->select("places_comments.id AS id_merge");
		$this->db->select("places_comments.date_added AS date_added");
		// $this->db->select(" AS like_total");
		//
		// $this->db->select(" AS flags_total");

		$this->db->select("((select COUNT(*) from places_comments_likes
where id_places_comments=id_merge)-((select COUNT(*) from places_comments_flags
		where id_places_comments=id_merge)*1.5)) as real_like");
		$this->db->from("places_comments");
		$this->db->join("users", "places_comments.id_user=users.id");
		$this->db->where("id_google", $this->inputVars["id"]);

		$this->db->where_not_in("places_comments.id", $flagCommentsByUser);

		$this->db->where("places_comments.status", "1");
		$this->db->order_by("real_like", "DESC");

		$get=$this->db->get();

		$comments=array();

		if($get->num_rows()>0):
			foreach($get->result() as $row):
				$photo_user=$row->facebook_profile_id==='' || $row->facebook_profile_id==='0' ? 'none' : "https://graph.facebook.com/".$row->facebook_profile_id."/picture?width=300";

				array_push($comments, array(
					"id" => $row->id,
					"photo" => $photo_user,
					"isOwner" => $row->id_user == $this->userId ? true : false,
					"name" => $row->name,
					"date" => $this->setDateBRFormat($row->date_added),
					"comment" => $row->comment,
					"star" => $this->getStar(rand(1, 5)),
					"totalLikes" => $this->getCommentsLikeTotal($row->id),
					"isLike" => $this->isCommentLike($row->id)
				));
			endforeach;
		endif;

		echo json_encode(array("status"=>"success", "total"=>$get->num_rows(), "data"=>$comments));
	}

	//retorna uma array com a lista de comentarios que esse usuario
	protected function getCommentsFlagsByUser($id_google){
		$this->db->select("places_comments_flags.id_places_comments AS id");
		$this->db->from("places_comments_flags");
		$this->db->join("places_comments", "places_comments_flags.id_places_comments=places_comments.id");
		$this->db->where("places_comments_flags.id_user", $this->userId);
		$this->db->where("places_comments.id_google", $id_google);

		$get=$this->db->get();

		$list=array(0);

		if($get->num_rows()>0):
			foreach($get->result() as $row):
				array_push($list, $row->id);
			endforeach;
		endif;

		return $list;

	}



	protected function setReview(){
			$data=json_decode(base64_decode($this->inputVars["review_data"]));

			//verifica se esse usuario já não avaliou esse local, se avaliou exclui a avaliação anteriormente
			$this->db->from("places_reviews");
			$this->db->where("id_google", $this->inputVars["id"]);
			$this->db->where("id_user", $this->userId);

			$get=$this->db->get();

			if($get->num_rows()>0):
				$this->db->where("id_google", $this->inputVars["id"]);
				$this->db->where("id_user", $this->userId);
				$this->db->delete("places_reviews");
			endif;


			$data=array(
				"id_google" => $this->inputVars["id"],
				"id_user" => $this->userId,
				"physical_rating" => $data->physical->rating->rating,
				"mental_rating" => $data->mental->rating->rating,
				"hearing_rating" => $data->hearing->rating->rating,
				"visual_rating" => $data->visual->rating->rating,
				"physical_rampas" => $data->physical->items->rampas,
				"physical_guias" => $data->physical->items->guias,
				"physical_banheiros" => $data->physical->items->banheiros,
				"physical_iluminacao" => $data->physical->items->iluminacao,
				"physical_bebedouros" => $data->physical->items->bebedouros,
				"physical_telefones" => $data->physical->items->telefones,
				"physical_movimentacao" => $data->physical->items->movimentacao,
				"physical_elevadores" => $data->physical->items->elevadores,
				"visual_direcional" => $data->visual->items->direcional,
				"visual_tatil" => $data->visual->items->tatil,
				"visual_semaforo" => $data->visual->items->semaforo,
				"visual_placas" => $data->visual->items->placas,
				"hearing_interprete" => $data->hearing->items->interprete,
				"hearing_audio" => $data->hearing->items->audio,
				"mental_instrutores" => $data->mental->items->instrutores,
				"mental_tutores" => $data->mental->items->tutores,
				"date_added" => $this->setMysqlTimestamp("now"),
				"ip_added" => USER_IP,
				"lat_added" => $this->inputVars["lat"],
				"lng_added" => $this->inputVars["lng"],
				"status" => "1"
			);

			$this->db->insert("places_reviews", $data);
			echo json_encode(array("status"=>"success"));
	}


	/*
		obtem a avaliacao media de um local, ela leva em conta a soma das notas de fisico, visual, mental e auditivo dividido pelo numero de criterios avaliados, caso não encontre nenhuma avaliação retorna 0
	*/
	protected function getReviewAvg($id_google){
		$this->db->from("places_reviews");
		$this->db->where("id_google", $id_google);
		$get=$this->db->get();

		$review_avg=0;
		$review_total=0;

		if($get->num_rows()>0):
			foreach($get->result() as $row):
				if($row->physical_rating>0):
					$review_avg=$review_avg+$row->physical_rating;
					$review_total++;
				endif;

				if($row->visual_rating>0):
					$review_avg=$review_avg+$row->visual_rating;
					$review_total++;
				endif;

				if($row->hearing_rating>0):
					$review_avg=$review_avg+$row->hearing_rating;
					$review_total++;
				endif;

				if($row->mental_rating>0):
					$review_avg=$review_avg+$row->mental_rating;
					$review_total++;
				endif;
			endforeach;
			$review_avg=round($review_avg/$review_total);
		endif;

		return $review_avg;


	}




}
