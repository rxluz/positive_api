<?php
defined('BASEPATH') OR exit('No direct script access allowed');
include("application/controllers/Common.php");
/**
 * traboxapp sign functions
 */
class Search extends Common {

	private $filters_info=false;
	private $limit=false;
	private $offset=false;
	private $page=false;
	private $order=false;
	private $filter_keyword=false;
	private $filter_grade=false;
	private $filter_payment=false;
	private $filter_location=false;
	private $result_ids=array();


	/*
		Busca a lista de anuncios -- possui:
		order = se não for especificado busca a ProfileAdsDefaultOrder
		limit = se não for especificado busca a ProfileAdsDefaultLimit
		page = se não for especificado vai pra pagina 1
		filter_keyword = array com a lista de filtros por palavra chava
		filter_location = array com a lista de cidades do filtro
	*/
	public function getSearch(){
		//$this->getCityNearby();
		$query=$this->setSearchQuery();
		$data=array();
		$x=0;

		//print_r($query->result());
		//return false;
		// users.name AS name,
		// providers.id_user AS id,
		// providers.company,
		// providers.topics_list,
		// providers.photos_list,
		if($query->num_rows()>0):
			foreach($query->result() as $row):
				array_push($data, array(
					"id_provider"=>$row->id,
					"name"=>$row->name,
					"grade"=>$row->grade,
					"url"=>$row->url,
					"company"=>$row->company,
					"topics_list"=>json_decode($row->topics_list),
					"photos_list"=>json_decode($row->photos_list)
				));

				array_push($this->result_ids, $row->id);
			endforeach;
		endif;

		//grava o resultado dessa pesquisa
		//$this->setSearchHistory("ads", $this->inputVars["filters"], $this->result_ids);
		//print_r($this->limit);
		//return false;
		echo json_encode(array(
			"status"=>"success",
			"total_page"=>$query->num_rows(),
			"total"=>$this->setSearchQuery(true),
			"total_page"=>ceil($this->setSearchQuery(true)/$this->limit),
			"limit"=>$this->limit,
			"order"=>($this->order!="grade DESC"?"nearby":"grade"),
			"page"=>$this->page,
			"filter_keyword"=>$this->filter_keyword,
			"filter_location"=>$this->filter_location,
			"filter_payment"=>$this->filter_payment,
			"filter_grade"=>$this->filter_grade,
			"data"=>$data
		));
	}



	/* busca os providers publicos, os seguintes campos:
	- photos_list
	- users.name
	- company
	- topics_list
	- avg_grade
	- telephone_list?
	*/
	private function setSearchQuery($total=false){
		//carrega os sinonimos, eles não são carregados automaticamente porque são usados somente em duas funções
		//$this->getSynonymous();

		$this->setSearchFilters();

		//exibe os anuncios da data atual e futuros primeiro (mas antes exibe os vencidos há 24 horas), depois exibir os anuncios dos proximos ProfileAdsShowExpiredAfterDaysFuture dias exibe os anuncios vencidos

		/*
			eu preciso de gerar um campo indicando a proximidade do usuario com esse anuncio
			para isso eu tenho que analistar a lista de cidades que esse provider atende
			cities_list e comparar com a lista de cidades proximas


			eu vou retornar uma lista de cidades proximas dele baseado na lat lng
			essas cidades são analisadas se existem na cities_list
			se existirem significa que esse provider está proximo do usuário

			na verdade eu preciso de analisar cidade a cidade da cities_list e ver se ela existe na lista de cidades proximas e qual é a mais proximas

			com isso eu consigo retornar um valor de texto de proximidade
		*/
		$lat=round($this->inputVars["lat"], 1, PHP_ROUND_HALF_UP);
		$lng=round($this->inputVars["lng"], 1, PHP_ROUND_HALF_UP);


		//eu tenho que busca a lista de cidades proximas do usuário

		//a lista de cidades proximas do usuário está em cityNearby
		//agora eu tenho que ver se existe alguma das cidades de cityNearby na cities_list, se hover eu retorno a distancia mais proxima, isso é, podem haver várias cidades, eu tenho que retornar aquela que está mais proxima

		$this->db->select("
			users.name AS name,
			users.url AS url,
			providers.id_user AS id,
			providers.company,
			providers.topics_list,
			providers.photos_list,
			COALESCE((
				select avg(grade) from providers_reviews where id_provider_user=users.id
			), 0) AS grade,
			COALESCE((
				select IFNULL(distance, 100)
				from others_cities_distance
				inner join others_cities
					on others_cities.id=others_cities_distance.cities_id
				where
					others_cities_distance.lat='".$lat."'
					and others_cities_distance.lng='".$lng."'
					and
					(
						providers.cities_list like CONCAT('%\"', others_cities.name ,'\"%')
						or
						providers.cities_list like CONCAT('%\"', others_cities.asciiName ,'\"%')
						or
						providers.cities_list like CONCAT('%\'', others_cities.name ,'\'%')
						or
						providers.cities_list like CONCAT('%\'', others_cities.asciiName ,'\'%')


					)
				order by distance asc
				limit 1
			), 100) AS nearby

		");
		$this->db->join("users", "providers.id_user=users.id");

		//caso seja ordem por data é asc, se for por pagamento é desc
		$this->db->order_by($this->order);

		/*
		os filtros por palavras chaves podem englobar qualquer campo
		os filtros por localizacao englobam aquelas localizacoes que estão no campo clients_ads.location, isso é, bairro e cidade
		*/
		if($this->filter_keyword):
			$this->db->where(
				$this->mysqlSearchFields(
					array("providers.professions_list", "providers.categories_list", "providers.company", "providers.topics_list","providers.description","providers.qualifications","providers.featured_jobs","providers.cities_list","providers.payments_list","providers.social_links","users.name","users.lastname","users.telephone_list","users.url"), $this->filter_keyword
				)
			);

		endif;

		if($this->filter_location):
			$this->db->where(
				$this->mysqlSearchFields(
					array("providers.professions_list", "providers.categories_list", "providers.company", "providers.topics_list","providers.description","providers.qualifications","providers.featured_jobs","providers.cities_list","providers.payments_list","providers.social_links","users.name","users.lastname","users.telephone_list","users.url"), $this->filter_keyword
				)
			);
		endif;

		if($this->filter_grade):
			$this->db->where("COALESCE((
				select avg(grade) from providers_reviews where id_provider_user=users.id
			), 0) >= ", $this->filter_grade-1);
		endif;

		// if($this->filter_payment):
		// 	$this->db->where("grade >= ", $this->filter_grade);
		// endif;

		$this->db->where("users.status", 3);

		$query=(!$total?$this->db->get("providers", $this->limit, $this->offset):$this->db->get("providers"));

		return (!$total?$query:$query->num_rows());
	}

	/* define o valor das variaveis que serão utilizadas de filtro */
	private function setSearchFilters(){
		$infos=json_decode($this->inputVars["filters"]);

		//se estiver setado o limit mostra ele, desde que o valor dele não seja maior que 20 e menor 1, do contrario exibe o ProfileAdsDefaultLimit
		$this->limit=isset($infos->limit) ? ($infos->limit>20 || $infos->limit<1?ProfileAdsDefaultLimit:$infos->limit) : ProfileAdsDefaultLimit;
		//se estiver setado infos order mostra ele, desde que o conteudo dele seja start date ou payment, do contrario exibe o ProfileAdsDefaultOrder
		$this->order=isset($infos->order) ? ($infos->order!="grade"?"nearby ASC":"grade DESC") : "grade DESC";

		//se estiver setado page mostra ele, do contrario exibe a pagina nro 1
		$this->page=isset($infos->page) ? $infos->page : 1;

		//se estiver setado as keywords mostra ela, do contrario marca o campo como false
		$this->filter_keyword=isset($infos->filter_keyword) ? $infos->filter_keyword : false;

		//se estiver setado a location mostra ela, do contraro marca o campo como false
		$this->filter_location=isset($infos->filter_location) ? $infos->filter_location : false;

		$this->filter_grade=isset($infos->filter_grade) && $infos->filter_grade!='false' ? $infos->filter_grade : false;

		$this->filter_payment=isset($infos->filter_payment) ? $infos->filter_payment : false;

		//o limite é a multiplcação do limit * page menos o proprio limit
		$this->offset=($this->limit*$this->page)-$this->limit;
		return true;
	}


	private function getGradeText($grade){
		if($grade<2) return "Péssimo";
		if($grade<3) return "Ruim";
		if($grade<4) return "Regular";
		if($grade<5) return "Bom";
		if($grade<6) return "Excelente";
	}


}
