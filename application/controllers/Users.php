<?php
defined('BASEPATH') OR exit('No direct script access allowed');
include("application/controllers/Common.php");
/**
 * traboxapp sign functions
 */
class Users extends Common {


	protected function setHospital(){
		//atualiza os campos allow_hospital, code, e birth_date
		$data=array(
			"allow_hospital"=>$this->inputVars["allow_hospital"],
			"code"=>$this->inputVars["code"],
			"birth_date"=>$this->brToMysqlFormat($this->inputVars["birth_date"])
		);

		$this->db->where("id", $this->userId);
		$this->db->update("users", $data);

		//$date=explode("T", $this->brToMysqlFormat($this->inputVars["birth_date"]));

		echo json_encode(array(
			"status"=>"success",
			"user_allow_hospital"=>$this->inputVars["allow_hospital"],
			"user_code"=>$this->inputVars["code"],
			"user_birth_date"=>$this->inputVars["birth_date"]
		));
	}
	/*
	Processo:
	- Busca o usuario que possua o email informado
	- Restaura a senha dele para uma senha simples (exemplo: RT456)
	- Envia um email para o usuario informando da restauração da senha

	*/
	public function setRestore(){
		$this->db->from("users");
		$this->db->where("email", $this->inputVars["code"]);
		$this->db->where_in("status", array("0", "1", "2", "3"));

		$get=$this->db->get();

		if($get->num_rows()>0):
			$row=$get->row();

			//restaura a senha
			$new_password=$this->generateRandomString(5);

			$this->db->where("id", $row->id);
			$this->db->update("users", array(
				"password"=>password_hash($new_password, PASSWORD_DEFAULT))
			);

			$this->sendEmail(
				'Restaurar sua senha no Urban Maps',
				$this->load->view(
					"sign_email_restore",
					array("pass"=>$new_password),
					true
				),
				$row->email
			);


			echo json_encode(array("status"=>"success"));


		else:
			echo json_encode(array("status"=>"fail"));
		endif;
	}


	protected function setFriends(){
		$friends_data=json_decode(base64_decode($this->inputVars["friends_data"]));

		//apaga todos os amigos relacionados desse usuario
		$this->db->where("id_user", $this->userId);
		$this->db->delete("users_trusted_friends");

		foreach($friends_data as $friend):
			$data_friend=array(
				"id_user" => $this->userId,
				"friend_name" => $friend->name,
				"friend_facebook_id" => $friend->id,
				"friend_picture" => $friend->picture
			);

			$this->db->insert("users_trusted_friends", $data_friend);

			//print_r($friend);
			//echo "---";
		endforeach;
		echo json_encode(array("status"=>"success"));
	}

	/*
	Essa função vai confirmar o cadastro do usuário, ela usa uma key gerara para isso que precisa de ser valida e estar dentro da periodo aceito
	Caso o usuário altere o email antes de confirmar a chave de confirmação anterior deve ser cancelada/expirada
	Se o usuário tiver cancelado a conta dele antes essa chave não é mais válida
	*/
	public function setConfirm(){
		$user_id=$this->verifyKey(true, $this->inputVars["auth_key_email"]);

		if(!$user_id):
			echo json_encode(array("status"=>"invalid_key_email"));
			return false;
		endif;

		//se o cadastro do usuario for de provider, é necessário verificar se ele preencheu os outros campos, se for cliente altera o status para confirmado

		$this->db->where("id", $user_id);

		//não altera os status de cadastros cancelados
		$this->db->where_in("status", $this->userStatusListActive);
		$this->db->update("users", array
			(
				"status"=>
					//se for cliente muda o status pra 3 (complete, public),
					//se for provider incomplete muda o status para 1 (incomplete confirmed)
					//se for provider complete muda o status para 3 (complete, public)
					($this->isProvider($user_id)?($this->isProviderComplete($user_id)?3:1):3)
			)
		);

		//expira essa chave para ela não ser mais usada
		$this->expireKey($this->inputVars["auth_key_email"], "key", $user_id);

		echo json_encode(array("status"=>"success", "key_auth"=>$this->generateUserKey(0, $user_id)));
	}


	/*
	Cancela a conta do usuário, as informações do usuário não são de fato excluídas, apenas o usuário tem o seu status setado como  cancelado
	*/
	protected function setCancel(){
		//muda o status da conta para cancelado (4) e expira todas as sessões do usuário
		$this->db->where("id", $this->userId);
		$result=$this->db->get("users");

		if($result->num_rows()>0):
			$user=$result->row();

			if (password_verify($this->inputVars["password_orign"], $user->password)):
				//se a senha estiver correta cancela a conta do usuario e expira todas as chaves

				$this->expireKey("all");
				$this->db->where("id", $this->userId);
				$this->db->update("users", array("status"=>"4", "cancel_cause"=>$this->inputVars["cause"]));

				echo json_encode(array("status"=>"success"));
				return true;
			endif;

			//se o usuário informar a senha incorreta armazena com uma tentativa de invasao na user_attempts
			//se chegar até aqui significa que o login não foi válido, logo registra essa tentativa de login invalido
			$this->insertAttempts($this->userCode);
			echo json_encode(array("status"=>"fail"));
			return false;
		endif;
	}

	/*
		Edita as informações basicas
	*/
	protected function setBasic(){

		if(!$this->verifyEmail(false, false)):
			echo json_encode(array("status"=>"email_invalid"));
			return false;
		endif;

		$data=array(
			"name" => $this->inputVars["name"],
			"email" => $this->inputVars["email"],
			"blood_type" => $this->inputVars["blood_type"],
			"donor" => $this->inputVars["donor"],
			"city" => $this->inputVars["city"]
		);

		$this->db->where("id", $this->userId);
		$this->db->update("users", $data);

		echo json_encode(
		array(
			"status"=>"success",
			"user_name"=>$this->inputVars["name"],
			"user_blood_type" => $this->inputVars["blood_type"],
			"user_donor" => $this->inputVars["donor"],
			"user_city" => $this->inputVars["city"],
			"user_email"=>$this->userEmail
			)
		);
		return true;
	}



	protected function getFriendsResponsible(){
		echo json_encode(array("status"=>"success"));
	}


	/*
		Edita a senha do usuário, para alterar a senha o usuário tem que informar a senha atual
	*/
	protected function setPassword(){
		$this->db->where("id", $this->userId);
		$result=$this->db->get("users");

		if($result->num_rows()>0):
			$user=$result->row();

			if (password_verify($this->inputVars["password_orign"], $user->password)):
				//quando se altera a senha, se expira todas as chaves de login (0) e o usuário é obrigado a logar novamente, com excessão da sessão que solicitou a alteração da senha
				$this->expireKey(0, "type");
				$this->db->where("id", $this->userId);
				$this->db->update("users", array(
					"password"=>password_hash($this->inputVars["new_password"], PASSWORD_DEFAULT))
				);

				echo json_encode(array("status"=>"success", "key_auth"=>$this->generateUserKey(0)));
				return true;
			endif;

			//se o usuário informar a senha incorreta armazena com uma tentativa de invasao na user_attempts
			//se chegar até aqui significa que o login não foi válido, logo registra essa tentativa de login invalido
			$this->insertAttempts($this->userCode);
			echo json_encode(array("status"=>"fail"));
			return false;
		endif;

		echo json_encode(array("status"=>"fatal_error"));
		return false;
	}



	/*
		Busca as informações básicas do perfil de um profissional
		Essa função é publica, qualquer um pode acessar, deve haver uma limitação por ip diaria de visualização de perfil
	*/
	protected function getInfosBasic(){
		//busca: zip_code, name, lastname, email, telephone_list, professions_list, topics_list, description, qualification, featured_jobs, cities_list, payments_list, social_links

		$this->db->select("name, type, lastname, code, email, telephone_list, zip_code, url");
		$query=$this->db->get_where("users", array(
			"id"=>$this->userId
		));

		if($query->num_rows()>0):
			$row=$query->row();
			$data=array(
				"name" => $row->name,
				"lastname" => $row->lastname,
				"type" => $row->type,
				"code" => $row->code,
				"email" => $row->email,
				"url" => $row->url,
				//"telephone_list" => str_replace(" ", "", str_replace(")", "", str_replace("\"", "", str_replace("(", "", json_decode($row->telephone_list))))),
				"telephone_list" => $this->removeTel(json_decode($row->telephone_list)),
				"zip_code" => $row->zip_code
			);
			echo json_encode(array("status"=>"success", "data"=>$data));
			return false;
		endif;

		echo json_encode(array("status"=>"user_notfound"));
		return false;
	}

	protected function removeTel($json){
		$json=str_replace(" ", "", $json);
		$json=str_replace("(", "", $json);
		$json=str_replace(")", "", $json);
		$json=str_replace("-", "", $json);
		return $json;
	}

	/*
	0 nome
	1 sobrenome
	2 cpf
	3 profissao
	4 atrbuição
	*/
	public function getRandomData($type, $delete){
		$this->db->from("names");
		$this->db->where("type", $type);
		$this->db->order_by("id", "RANDOM");
		$query=$this->db->get();

		$row=$query->row();

		if($delete):
			$this->db->where("id", $row->id);
			$this->db->delete("names");
		endif;

		echo $row->name;
	}


	/*
		add a bookmark to a user logged, after verify if the bookmark doesnt exists
	*/
	protected function addBookmark(){
		$this->db->from("users_bookmarks");
		$this->db->where("type", $this->inputVars["type"]);
		$this->db->where("id_bookmark", $this->inputVars["id_bookmark"]);
		$this->db->where("id_user", $this->userId);
		$duplicated=$this->db->get();

		if($duplicated->num_rows()<1):
			$data=array(
				"type" => $this->inputVars["type"],
				"id_bookmark" => $this->inputVars["id_bookmark"],
				"id_user" => $this->userId,
				"ip_added" => USER_IP
			);

			$this->db->insert("users_bookmarks", $data);

		endif;

		echo json_encode(array("status"=>"success"));

	}


	/*
		remove a bookmark to a user logged
	*/
	protected function removeBookmark(){

		$this->db->where("type", $this->inputVars["type"]);
		$this->db->where("id_bookmark", $this->inputVars["id_bookmark"]);
		$this->db->where("id_user", $this->userId);
		$this->db->delete("users_bookmarks");

		echo json_encode(array("status"=>"success"));

	}




}
