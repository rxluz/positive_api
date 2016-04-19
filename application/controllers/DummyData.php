<?php
defined('BASEPATH') OR exit('No direct script access allowed');
include("application/controllers/Common.php");
/**
 * traboxapp sign functions
 */
class DummyData extends Common {
	/*
	0 nome
	1 sobrenome
	2 cpf
	3 profissao
	4 atrbuição
	5 cep
	*/
	public function getRandomData($type, $delete=false){
		$this->db->from("names");
		$this->db->where("type", $type);
		$this->db->order_by("id", "RANDOM");
		$query=$this->db->get();

		$row=$query->row();

		if($delete):
			$this->db->where("id", $row->id);
			$this->db->update("names", array("type"=>$type.$type));
			//$this->db->delete("names");
		endif;

		return strtolower($row->name);
	}


	public function getCities(){
		$this->db->from("others_cities");
		$this->db->order_by("id", "RANDOM");
		$query=$this->db->get();

		$row=$query->row();

		return strtolower($row->name);
	}
	public function getPhone(){
		$ramal=rand(10, 99);

		$phone1=rand(3000, 9999);
		$phone2=rand(1000, 9999);

		return  $ramal.$phone1.$phone2;
	}


	public function getEmail($name){
		$domains=array("terra.com.br", "uol.com.br", "ig.com.br", "globo.com", "gmail.com", "yahoo.com.br");

		return $name.rand(1000, 10000)."@".$domains[rand(0, 5)];
		//echo $this->getRandomData(0, )
	}

	public function getCategories(){
		$cat=json_decode('["Assistência técnica","Aulas","Consultoria","Reformas","Saúde","Serviços domésticos","Tecnologia"]');
		return array($cat[rand(0, 2)], $cat[rand(3, 4)], $cat[rand(5, 6)]);
	}


	public function getPhoto(){
		$end=array("-/crop/640x400/", "-/crop/320x200/", "-/crop/480x300/");
		$end=$end[rand(0, 2)];
		$photos=array(
			"https://ucarecdn.com/9fcb933d-edb5-4cf3-bf98-c02b6c5a7660/",
			"https://ucarecdn.com/1a2d9a60-c7fb-44ba-8399-18c0f3d94dff/",
			"https://ucarecdn.com/84f4bd43-b858-434e-8e51-fb6e6705082e/",
			"https://ucarecdn.com/19281791-4856-4e48-b2d0-6b5c522d4aac/",
			"https://ucarecdn.com/d786ebac-ebdf-4991-8cac-1a5d33c665fe/",
			"https://ucarecdn.com/dfe25a81-367e-42a1-bdcd-2ed21884b96f/",
			"https://ucarecdn.com/12e7e33d-64b3-4235-9f30-c8e8e145139b/",
			"https://ucarecdn.com/a92b4045-e832-4417-8cc4-baed2c7810b7/",
			"https://ucarecdn.com/f1746903-472e-4561-82de-3a5072a265b3/",
			"https://ucarecdn.com/2d75dc66-2ec6-4cc6-b074-4276c8916853/",
			"https://ucarecdn.com/62f672ff-f65d-40ca-96c5-97826c6090df/",
			"https://ucarecdn.com/6e961e25-c0af-4696-8da3-29a9b68da997/",
			"https://ucarecdn.com/eeb84bde-4a1c-46ae-938f-e0d07fb5634c/",
			"https://ucarecdn.com/b34f362c-5338-4e82-9ccb-f46aa38584a0/",
			"https://ucarecdn.com/079e4237-9ac4-4d81-9247-1c8b75743c82/",
			"https://ucarecdn.com/ac2f2082-f4cf-4f07-ba73-1afc4a3444de/",
			"https://ucarecdn.com/b2d4cea8-7fa5-43d9-b386-4f48b115aae0/",
			"https://ucarecdn.com/073aec6d-db2b-4865-a415-886695ee0891/",
			"https://ucarecdn.com/8c54bc25-a0cb-4cbc-acc9-04bfc9c936fb/",
			"https://ucarecdn.com/1ac2fa72-37b2-4ca0-a4f1-84b0a8a2de94/",
			"https://ucarecdn.com/3498c60c-b221-4926-941d-8e71a92f88ca/",
			"https://ucarecdn.com/87ef93c5-ee0d-4775-a832-a1f6fb8dc8a8/",
			"https://ucarecdn.com/15b2ba49-a51a-4129-abfc-5cca8491e7bd/",
			"https://ucarecdn.com/21e60032-7b1f-47a0-832f-1246a2e3cafd/"
		);

		return array($photos[rand(0, 22)].$end, $photos[rand(7, 14)].$end, $photos[rand(14, 22)].$end);
	}

	public function payment(){
		return array("check"=>$this->getTF(), "credit"=>$this->getTF(), "money"=>$this->getTF(), "debit"=>$this->getTF());
	}


	public function social($name){
		return array(
			"facebook" => "https://www.facebook.com/".$name,
			"instagram" => "https://www.instagram.com/".$name,
			"twitter" => "https://twitter.com/".$name,
			"website" => "http://wix.com/".$name

		);

	}


	public function getTF(){
		return rand(0,1)==0?false:true;
	}

	public function generate($number){
		set_time_limit(0);

		for($x = 1; $x < $number; $x++):
			//cria um usuário e para cada usuário cria as infos de providers
			$name=$this->getRandomData(0, true);
			$userdata=array(
				"type" => "1",
				"name" => $name,
				"lastname" => $this->getRandomData(1),
				"code" => $this->getRandomData(2, true),
				"email" => $this->getEmail($name),
				"telephone_list" => json_encode(array($this->getPhone(), $this->getPhone(), $this->getPhone(), $this->getPhone())),
				"password" => password_hash("trabox_test", PASSWORD_DEFAULT),
				"zip_code" => $this->getRandomData(5),
				"url" => $name,
				"status" => "3"
			);

			$this->db->insert("users", $userdata);



			//print_r($userdata);
			//echo "<br><br>";

			$companytitle=array(" & CIA ME", " & Associados LTDA", " LTDA", " ME", " Serviços SA");

			$providerData=array(
				"id_user" => $this->db->insert_id(),
				"professions_list" => json_encode(array($this->getRandomData(3), $this->getRandomData(3), $this->getRandomData(3))),
				"categories_list" => json_encode($this->getCategories()),
				"company" => $name.$companytitle[rand(0, 4)],
				"topics_list" => json_encode(array($this->getRandomData(3), $this->getRandomData(3), $this->getRandomData(3))),
				"description" => $this->getRandomData(3),
				"qualifications" => $this->getRandomData(3),
				"qualifications" => $this->getRandomData(3),
				"featured_jobs" => $name.$companytitle[rand(0, 4)],
				"photos_list" => json_encode($this->getPhoto()),
				"cities_list" => json_encode(array($this->getCities(), $this->getCities(), $this->getCities(), $this->getCities(), $this->getCities(), $this->getCities())),
				"payments_list" => json_encode($this->payment()),
				"social_links" => json_encode($this->social($name))
			);

			$this->db->insert("providers", $providerData);

			// print_r($providerData);
			//
			// echo "<br>";
			// echo "<br>";
			// echo "<br>";
			// echo "<br>";
			// echo "<br>";
			// echo "<br>";

		endfor;
		return false;



		//busca todos os usuarios que não tem perfil
		$this->db->from("users");
		$this->db->where("type", 1);
		$query=$this->db->get();

		foreach($query->result() as $row):
			$this->db->from("providers");
			$this->db->where("id_user", $row->id);
			$provider=$this->db->get();

			if($provider->num_rows()<1):
				$data=array(
					"id_user" => $row->id,
					"professions_list" => '["Ilustradora","Designer","UI Designer"]',
					"categories_list" => '["Consultoria","Técnologia"]',
					"company" => 'Appock.co',
					"topics_list" => '["Ilustra\u00e7\u00f5es em flat design","Design de websites","Design de aplicativos"]',
					"description" => "Melhor design do Brasil",
					"qualifications" => "Illustrator, Photoshop",
					"featured_jobs" => "Site da trabox, site do SP Acessível",
					"photos_list" => '[null,"https://ucarecdn.com/f40951d5-09b8-40af-9c01-c26869908c60/-/crop/1170x731/0,529/-/resize/640x400/","https://ucarecdn.com/21f4ef66-ac74-4dac-884e-9abaf19384ea/-/crop/640x400/0,42/-/preview/"]',
					"cities_list" => '["São Paulo","Guarulhos","Itaquaquecetuba","Osasco","Militar Belione"]',
					"payments_list" => '{"check":true,"credit":true,"money":true,"debit":true}',
					"social_links" => '{"facebook":"http://localhost:8080/perfil/editar/fotos-e-outras-infosfb","instagram":"http://localhost:8080/perfil/editar/fotos-e-outras-infosinsta","twitter":"http://localhost:8080/perfil/editar/fotos-e-outras-infostwi","website":"http://localhost:8080/perfil/editar/fotos-e-outras-infosweb"}'
				);

				$this->db->insert("providers", $data);




			endif;

		endforeach;
	}


}
