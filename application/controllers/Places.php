<?php
defined('BASEPATH') OR exit('No direct script access allowed');
include("application/controllers/Common.php");
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
		Busca as informações básicas do perfil de um profissional
		Essa função é publica, qualquer um pode acessar, deve haver uma limitação por ip diaria de visualização de perfil
	*/
	protected function getInfosBasic(){
		//busca: zip_code, name, lastname, email, telephone_list, professions_list, topics_list, description, qualification, featured_jobs, cities_list, payments_list, social_links

		$this->db->select("users.id, name, lastname, company, code, email, telephone_list, zip_code, professions_list, categories_list, company, topics_list, description, qualifications, featured_jobs, cities_list, photos_list, payments_list, social_links, status");
		$this->db->join("providers", "users.id=providers.id_user", "left");
		$query=$this->db->get_where("users", array(
			"url"=>$this->inputVars["url_provider"]
		));

		$photos_list=array();
		$x=1;

		if($query->num_rows()>0):
			$row=$query->row();

			if($row->status!=$this->userStatusPublic):
				echo json_encode(array("status"=>"user_notpublished", "mandatory_fields"=>json_decode(ProviderCompleteFields)));
				return false;
			endif;

			if(is_array(json_decode($row->photos_list))):
				foreach(json_decode($row->photos_list) as $photo):
					//$photos_list[$x]=$photo;
					if($photo!==null):
						array_push($photos_list, $photo);
					endif;
					$x++;
				endforeach;
			endif;

			$data=array(
				"name" => $row->name,
				"lastname" => $row->lastname,
				"code" => $row->code,
				"company" => trim($row->company),
				"email" => $row->email,
				"telephone_list" => json_decode($row->telephone_list),
				"zip_code" => $row->zip_code,
				"topics_list" => json_decode($row->topics_list),
				"professions_list" => json_decode($row->professions_list),
				"qualifications" => $row->qualifications,
				"featured_jobs" => $row->featured_jobs,
				"description" => $row->description,
				"categories_list" => json_decode($row->categories_list),
				"cities_list" => json_decode($row->cities_list),
				"cities_total" => count(json_decode($row->cities_list)),
				"photos_list" => $photos_list,
				"payments_list" => json_decode($row->payments_list),
				"social_links" => json_decode($row->social_links),
				"bookmark" => $this->getStar($row->id)
			);
			echo json_encode(array("status"=>"success", "data"=>$data));
			return false;

		endif;

		echo json_encode(array("status"=>"user_notfound"));
		return false;
	}


	/*
		Envia um email com uma solicitação de adição de artista, armazena essas infos na lista de performers_add
		Impede que o mesmo usuário envie várias solicitações num periodo de 24 horas, essa verificação é feita pelo telefone e pelo email
	*/
	public function setPerformer(){
		//"name", "name_performer", "email", "telephone", "obs"
		if($this->getPerformerCount()==false):
			echo json_encode(array("status"=>"success"));
			return false;
		endif;

		$data=array(
			"name" => $this->inputVars["name"],
			"name_performer" => $this->inputVars["name_performer"],
			"email" => $this->inputVars["email"],
			"telephone" => $this->inputVars["telephone"],
			"obs" => $this->inputVars["obs"],
			"ip_added" => USER_IP
		);

		$this->db->insert("performer_add", $data);

		$this->sendEmail(
			"Solicitação de adição de artista pelo app Show Business",
			$this->load->view(
				"profile_email_contact",
				$data,
				true
			),
			"rx@appock.co"
		);


		echo json_encode(array("status"=>"success"));
		return true;
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
	protected function getNearby(){
		$data[0]=array(
			"id" => 0,
			"lat" => "-23.5507871",
			"lng" => "-46.6457438",
			"name" => "Museu Judaico de São Paulo",
			"type" => "0",
			"distance" => "200m",
			"address" => "R. Martinho Prado, 128",
			"rating_avg" => array(
				"iconOn" =>  'ion-ios-star',
				"iconOff" =>  'ion-ios-star-outline',
				"iconOnColor" =>  'rgb(200, 200, 100)',
				"iconOffColor" =>  'rgb(200, 100, 100)',
				"rating" =>  rand(2, 5),
				"minRating" => 1
			),
			"resources" => array(
				"whell" => true,
				"visual" => false,
				"hearing" => true,
				"mental" => false

			),
			"comments_sum" => "33"
		);

		$data[1]=array(
			"id" => 15,
			"lat" => "-23.5439632",
			"lng" => "-46.6428717",
			"name" => "Museu da Diversidade Sexual",
			"type" => "0",
			"distance" => "10m",
			"address" => "Estação República do Metro - R. do Arouche, 24 - República, São Paulo - SP",
			"rating_avg" => array(
				"iconOn" =>  'ion-ios-star',
				"iconOff" =>  'ion-ios-star-outline',
				"iconOnColor" =>  'rgb(200, 200, 100)',
				"iconOffColor" =>  'rgb(200, 100, 100)',
				"rating" =>  rand(2, 5),
				"minRating" => 1
			),
			"resources" => array(
				"whell" => true,
				"visual" => false,
				"hearing" => true,
				"mental" => true
			),
			"comments_sum" => "12"
		);


		$data[2]=array(
			"id" => 16,
			"lat" => "-23.5439652",
			"lng" => "-46.6428797",
			"name" => "Museu da Diversidade Sexual (2)",
			"type" => "0",
			"distance" => "10m",
			"address" => "Estação República do Metro - R. do Arouche, 24 - República, São Paulo - SP",
			"rating_avg" => array(
				"iconOn" =>  'ion-ios-star',
				"iconOff" =>  'ion-ios-star-outline',
				"iconOnColor" =>  'rgb(200, 200, 100)',
				"iconOffColor" =>  'rgb(200, 100, 100)',
				"rating" =>  rand(2, 5),
				"minRating" => 1
			),
			"resources" => array(
				"whell" => true,
				"visual" => false,
				"hearing" => true,
				"mental" => true
			),
			"comments_sum" => "12"
		);

		$data[3]=array(
			"id" => 19,
			"lat" => "-23.5439652",
			"lng" => "-46.6428797",
			"name" => "Museu da Diversidade Sexual (3)",
			"type" => "0",
			"distance" => "10m",
			"address" => "Estação República do Metro - R. do Arouche, 24 - República, São Paulo - SP",
			"rating_avg" => array(
				"iconOn" =>  'ion-ios-star',
				"iconOff" =>  'ion-ios-star-outline',
				"iconOnColor" =>  'rgb(200, 200, 100)',
				"iconOffColor" =>  'rgb(200, 100, 100)',
				"rating" =>  rand(2, 5),
				"minRating" => 1
			),
			"resources" => array(
				"whell" => true,
				"visual" => false,
				"hearing" => true,
				"mental" => true
			),
			"comments_sum" => "12"
		);


		$data[4]=array(
			"id" => 20,
			"lat" => "-23.5439612",
			"lng" => "-46.6428781",
			"name" => "Museu da Diversidade Sexual (4)",
			"type" => "0",
			"distance" => "10m",
			"address" => "Estação República do Metro - R. do Arouche, 24 - República, São Paulo - SP",
			"rating_avg" => array(
				"iconOn" =>  'ion-ios-star',
				"iconOff" =>  'ion-ios-star-outline',
				"iconOnColor" =>  'rgb(200, 200, 100)',
				"iconOffColor" =>  'rgb(200, 100, 100)',
				"rating" =>  rand(2, 5),
				"minRating" => 1
			),
			"resources" => array(
				"whell" => true,
				"visual" => false,
				"hearing" => true,
				"mental" => true
			),
			"comments_sum" => "12"
		);

		$data[5]=array(
			"id" => 22,
			"lat" => "-23.5439612",
			"lng" => "-46.6428781",
			"name" => "Museu da Diversidade Sexual (5)",
			"type" => "0",
			"distance" => "10m",
			"address" => "Estação República do Metro - R. do Arouche, 24 - República, São Paulo - SP",
			"rating_avg" => array(
				"iconOn" =>  'ion-ios-star',
				"iconOff" =>  'ion-ios-star-outline',
				"iconOnColor" =>  'rgb(200, 200, 100)',
				"iconOffColor" =>  'rgb(200, 100, 100)',
				"rating" =>  rand(2, 5),
				"minRating" => 1
			),
			"resources" => array(
				"whell" => true,
				"visual" => false,
				"hearing" => true,
				"mental" => true
			),
			"comments_sum" => "12"
		);



		echo json_encode(array("status"=>"success", "data"=>$data));
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
		$data[0]=array(
			"id" => 0,
			"lat" => "-23.5507871",
			"lng" => "-46.6457438",
			"name" => "Museu Judaico de São Paulo",
			"type" => "0",
			"distance" => "200m",
			"address" => "R. Martinho Prado, 128",
			"rating_avg" => array(
				"iconOn" =>  'ion-ios-star',
				"iconOff" =>  'ion-ios-star-outline',
				"iconOnColor" =>  'rgb(200, 200, 100)',
				"iconOffColor" =>  'rgb(200, 100, 100)',
				"rating" =>  rand(2, 5),
				"minRating" => 1
			),
			"resources" => array(
				"whell" => true,
				"visual" => false,
				"hearing" => true,
				"mental" => false

			),
			"comments_sum" => "33"
		);

		$data[1]=array(
			"id" => 15,
			"lat" => "-23.5439632",
			"lng" => "-46.6428717",
			"name" => "Museu da Diversidade Sexual",
			"type" => "0",
			"distance" => "10m",
			"address" => "Estação República do Metro - R. do Arouche, 24 - República, São Paulo - SP",
			"rating_avg" => array(
				"iconOn" =>  'ion-ios-star',
				"iconOff" =>  'ion-ios-star-outline',
				"iconOnColor" =>  'rgb(200, 200, 100)',
				"iconOffColor" =>  'rgb(200, 100, 100)',
				"rating" =>  rand(2, 5),
				"minRating" => 1
			),
			"resources" => array(
				"whell" => true,
				"visual" => false,
				"hearing" => true,
				"mental" => true
			),
			"comments_sum" => "12"
		);


		$data[2]=array(
			"id" => 16,
			"lat" => "-23.5439652",
			"lng" => "-46.6428797",
			"name" => "Museu da Diversidade Sexual (2)",
			"type" => "0",
			"distance" => "10m",
			"address" => "Estação República do Metro - R. do Arouche, 24 - República, São Paulo - SP",
			"rating_avg" => array(
				"iconOn" =>  'ion-ios-star',
				"iconOff" =>  'ion-ios-star-outline',
				"iconOnColor" =>  'rgb(200, 200, 100)',
				"iconOffColor" =>  'rgb(200, 100, 100)',
				"rating" =>  rand(2, 5),
				"minRating" => 1
			),
			"resources" => array(
				"whell" => true,
				"visual" => false,
				"hearing" => true,
				"mental" => true
			),
			"comments_sum" => "12"
		);

		$data[3]=array(
			"id" => 19,
			"lat" => "-23.5439652",
			"lng" => "-46.6428797",
			"name" => "Museu da Diversidade Sexual (3)",
			"type" => "0",
			"distance" => "10m",
			"address" => "Estação República do Metro - R. do Arouche, 24 - República, São Paulo - SP",
			"rating_avg" => array(
				"iconOn" =>  'ion-ios-star',
				"iconOff" =>  'ion-ios-star-outline',
				"iconOnColor" =>  'rgb(200, 200, 100)',
				"iconOffColor" =>  'rgb(200, 100, 100)',
				"rating" =>  rand(2, 5),
				"minRating" => 1
			),
			"resources" => array(
				"whell" => true,
				"visual" => false,
				"hearing" => true,
				"mental" => true
			),
			"comments_sum" => "12"
		);


		$data[4]=array(
			"id" => 20,
			"lat" => "-23.5439612",
			"lng" => "-46.6428781",
			"name" => "Museu da Diversidade Sexual (4)",
			"type" => "0",
			"distance" => "10m",
			"address" => "Estação República do Metro - R. do Arouche, 24 - República, São Paulo - SP",
			"rating_avg" => array(
				"iconOn" =>  'ion-ios-star',
				"iconOff" =>  'ion-ios-star-outline',
				"iconOnColor" =>  'rgb(200, 200, 100)',
				"iconOffColor" =>  'rgb(200, 100, 100)',
				"rating" =>  rand(2, 5),
				"minRating" => 1
			),
			"resources" => array(
				"whell" => true,
				"visual" => false,
				"hearing" => true,
				"mental" => true
			),
			"comments_sum" => "12"
		);

		$data[5]=array(
			"id" => 22,
			"lat" => "-23.5439612",
			"lng" => "-46.6428781",
			"name" => "Museu da Diversidade Sexual (5)",
			"type" => "0",
			"distance" => "10m",
			"address" => "Estação República do Metro - R. do Arouche, 24 - República, São Paulo - SP",
			"rating_avg" => array(
				"iconOn" =>  'ion-ios-star',
				"iconOff" =>  'ion-ios-star-outline',
				"iconOnColor" =>  'rgb(200, 200, 100)',
				"iconOffColor" =>  'rgb(200, 100, 100)',
				"rating" =>  rand(2, 5),
				"minRating" => 1
			),
			"resources" => array(
				"whell" => true,
				"visual" => false,
				"hearing" => true,
				"mental" => true
			),
			"comments_sum" => "12"
		);

		echo json_encode(array("status"=>"success", "data"=>$data));
	}


	/*
		salva um obstaculo no banco de dados, o é ncessário que seja um usuário identificado para executar essa ação
		precisa:
		"lat",
		"lng",
		"type",
		"address",
		"obs"
	*/
	protected function setHoldback(){
		echo json_encode(array("status"=>"success"));
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


	protected function getLocal(){
		echo json_encode(array("status"=>"success"));
	}

	protected function setComment(){
		echo json_encode(array("status"=>"success"));
	}

	protected function setCommentLike(){
		echo json_encode(array("status"=>"success"));
	}

	protected function setCommentLikeRemove(){
		echo json_encode(array("status"=>"success"));
	}


	protected function setCommentFlag(){
		echo json_encode(array("status"=>"success"));
	}

	protected function setPhoto(){
		echo json_encode(array("status"=>"success"));
	}

	protected function getComments(){
		echo json_encode(array("status"=>"success"));
	}







}
