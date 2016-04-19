<?php
defined('BASEPATH') OR exit('No direct script access allowed');
include("application/controllers/Common.php");
/**
 * traboxapp sign functions
 */
class Profile extends Common {

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
		Envia um email com uma solicitação de adição de artista, armazena essas infos na lista de performers_add
		Impede que o mesmo usuário envie várias solicitações num periodo de 24 horas, essa verificação é feita pelo telefone e pelo email
	*/
	public function setContact(){
		//"name", "name_performer", "email", "telephone", "obs"
		if($this->getPerformerCount()==false):
			echo json_encode(array("status"=>"success"));
			return false;
		endif;

		$data=array(
			"name" => $this->inputVars["name"],
			"email" => $this->inputVars["email"],
			"telephone" => $this->inputVars["telephone"],
			"obs" => $this->inputVars["obs"],
			"ip_added" => USER_IP
		);

		$this->db->insert("performer_contact", $data);

		$this->sendEmail(
			"Solicitação de contato pelo app Show Business",
			$this->load->view(
				"profile_contact",
				$data,
				true
			),
			"rx@appock.co"
		);


		echo json_encode(array("status"=>"success"));
		return true;
	}


	private function getPerformerCount(){
		$this->db->from("performer_add");
		$this->db->where("email", $this->inputVars["email"]);
		$count_email=$this->db->get();
		if($count_email->num_rows()>3):
			return false;
		endif;

		$this->db->from("performer_add");
		$this->db->where("telephone", $this->inputVars["telephone"]);
		$count_phone=$this->db->get();
		if(($count_email->num_rows()+$count_phone->num_rows())>3):
			return false;
		endif;

		return true;
	}

	/*
		config the getPerformers function to return only users performers bookmarks
	*/
	public function getUserBookmarkPerformers(){
		$this->getPerformers(true);
	}

	protected function getPerformersSearch(){
		$this->getPerformers(false, true);
	}

	public function getPerformers($onlyBookmarks=false, $search=false){
		$this->db->select("artistas.id AS id");
		$this->db->select("artistas.nome AS name_performer");
		$this->db->select("artistas.imagem AS img_performer");
		$this->db->select("managers.nome AS manager");
		$this->db->select("managers.estado AS state");
		$this->db->select("managers.cidade AS city");
		$this->db->select("managers.telefone AS telephone");
		$this->db->select("IF( artistas.imagem =  '',  '1',  '0' ) AS img_exists");

		$this->db->from("artistas");
		$this->db->join("artista_manegers", "artistas.id=artista_manegers.id_artista");
		$this->db->join("managers", "artista_manegers.id_managers=managers.id");
		$this->db->where("artistas.ativo", "s");

		if($onlyBookmarks):
			$this->db->join("users_bookmarks", "artistas.id=users_bookmarks.id_bookmark");
			$this->db->where("type", "0");
			$this->db->where("id_user", $this->userId);
		endif;

		$this->getPerformersFilters($search);

		$this->db->order_by("img_exists", "ASC");
		$this->db->order_by("datacad", "DESC");

		// echo $this->inputVars["page"];
		$page=(($this->inputVars["page"]*20)*1);

		//echo $page;

		$this->db->limit(20, $page);

		$get=$this->db->get();
		$data=array();

		if($get->num_rows()>0):
			foreach($get->result() as $row):
				array_push($data, array(
					"id" => $row->id,
					"name_performer" => $row->name_performer,
					"img_performer" => trim($row->img_performer),
					"manager" => utf8_decode($row->manager),
					"state" => $row->state,
					"city" => utf8_decode($row->city),
					"telephone" => $row->telephone,
					"bookmark" => $this->getUserBookmark($row->id, 0)
				));
			endforeach;
		endif;

		echo json_encode(array("status"=>"success", "total"=>$get->num_rows(), "data"=>$data));
	}


	/*
		config the getAgents function to return only users agents bookmarks
	*/
	public function getUserBookmarkAgents(){
		$this->getAgents(true);
	}

	public function getAgentsSearch(){
		$this->getAgents(false, true);
	}

	public function getAgents($onlyBookmarks=false, $search=false){
		$this->db->select("produtores.id AS id");
		$this->db->select("produtores.nome AS name_agent");
		$this->db->select("produtores.uf AS state");
		$this->db->select("produtores.cidade AS city");
		$this->db->select("produtores_auxs.tel AS telephone");

		$this->db->from("produtores");
		$this->db->join("produtores_auxs", "produtores.id=produtores_auxs.id_produtores");
		$this->db->where("produtores.status", "s");

		if($onlyBookmarks):
			$this->db->join("users_bookmarks", "produtores.id=users_bookmarks.id_bookmark");
			$this->db->where("type", "1");
			$this->db->where("id_user", $this->userId);
		endif;

		$this->getAgentsFilters($search);

		$this->db->order_by("datacadas", "DESC");

		$page=(($this->inputVars["page"]*20)*1);

		$this->db->limit(20, $page);

		$get=$this->db->get();
		$data=array();

		if($get->num_rows()>0):
			foreach($get->result() as $row):
				array_push($data, array(
					"id" => $row->id,
					"name_agent" => utf8_decode($row->name_agent),
					"state" => $row->state,
					"city" => utf8_decode($row->city),
					"telephone" => $row->telephone,
					"bookmark" => $this->getUserBookmark($row->id, 1)
				));
			endforeach;
		endif;

		echo json_encode(array("status"=>"success", "total"=>$get->num_rows(), "data"=>$data));
	}

	//retorna se esse contato é favorito do usuário ou nao, o type é 0 para performer e 1 para agent
	private function getUserBookmark($id_bookmark, $type){
		if(!$this->userLogged):
			return false;
		endif;

		$this->db->from("users_bookmarks");
		$this->db->where("type", $type);
		$this->db->where("id_bookmark", $id_bookmark);
		$this->db->where("id_user", $this->userId);
		$get=$this->db->get();

		return $get->num_rows()<1 ? false : true;
	}


	private function getPerformersFilters($search=false){
		$city=$this->inputVars["city"];
		$state=$this->inputVars["state"];
		$letter=$this->inputVars["letter"];

		if(!$search):
			if(trim($city)!=="" && $city!==false):
				//echo "entrou aqui";

				$this->db->where($this->mysqlSearchFields(array("managers.cidade"), array($city)));
			endif;

			if(trim($state)!=="" && $state!==false):
				$this->db->like("managers.estado", $state);
			endif;

			if(trim($letter)!=="" && $letter!==false):
				$this->db->like("artistas.nome", $letter, 'after');
			endif;
		else:
			$this->db->where($this->mysqlSearchFields(array("artistas.nome", "managers.nome", "managers.estado", "managers.cidade", "managers.telefone"), array($letter)));
		endif;

	}

	private function getAgentsFilters($search){

		$city=$this->inputVars["city"];
		$state=$this->inputVars["state"];
		$letter=$this->inputVars["letter"];

		if($search===false):

			if(trim($city)!=="" && $city!==false):
				//echo "entrou aqui";

				$this->db->where($this->mysqlSearchFields(array("produtores.cidade"), array($city)));
			endif;

			if(trim($state)!=="" && $state!==false):
				$this->db->like("produtores.uf", $state);
			endif;

			if(trim($letter)!=="" && $letter!==false):
				$this->db->like("produtores.nome", $letter, 'after');
			endif;
		else:
			$this->db->where($this->mysqlSearchFields(array("produtores.nome", "produtores.uf", "produtores.cidade"), array($letter)));
		endif;

	}



	public function setEmail(){
		//"name", "name_performer", "email", "telephone", "obs"
		if($this->getEmailCount()==false):
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

		$this->db->insert("email_contact", $data);

		$this->sendEmail(
			"Solicitação de contato de artista pelo app Show Business",
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

	private function getEmailCount(){
		$this->db->from("email_contact");
		$this->db->where("email", $this->inputVars["email"]);
		$count_email=$this->db->get();
		if($count_email->num_rows()>3):
			return false;
		endif;

		$this->db->from("performer_add");
		$this->db->where("telephone", $this->inputVars["telephone"]);
		$count_phone=$this->db->get();
		if(($count_email->num_rows()+$count_phone->num_rows())>3):
			return false;
		endif;

		return true;
	}



}
