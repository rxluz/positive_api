<?php
defined('BASEPATH') OR exit('No direct script access allowed');
include("application/controllers/Common.php");
/**
 * traboxapp sign functions
 */
class Sign extends Common {

	/*
		Essa função envia para o email do usuário cadastrado um link para restauração da senha (que vale por algumas horas somente), a chave de restauração deve ser guardada na tabela users_key com um tipo diferente da chave de login
	*/
	protected function setPasswordForgot(){
		//verifica se o código pertence a um usuário válido
		$this->db->where("code", $this->inputVars["code"]);
		//o usuário não pode ser do tipo cancelado
		$this->db->where_in("status", array(0, 1, 2, 3));
		$result=$this->db->get("users");

		if($result->num_rows()>0):
			$row=$result->row();

			$SignPasswordRestoreValidity=strtotime(SignPasswordRestoreValidity);

			$linkRestore=(DEVMODE?"http://localhost:8080//restaurar/":"http://app.trabox.com.br//restaurar/");

			$this->sendEmail('Restaurar sua senha na Trabox', $this->load->view("sign_email_restore", array("linkRestore"=>$linkRestore.$this->generateUserKey(1, $row->id), $row->email), true));

			$email=explode("@", $row->email);


			echo json_encode(array("status"=>"success", "email"=>"********@".$email[1]));
			return true;
		endif;

		echo json_encode(array("status"=>"user_notfound"));
		return false;

	}

	/*
		Essa função restaura a senha do usuário se for informada uma chave que autoriza isso, o usuário recebe essa chave no email dele, e ela tem validade por apenas algum tempo
	*/
	public function setPasswordRestore(){
		//verifica se a chave existe e se ela está dentro da validade
		$this->db->where("key_auth", $this->inputVars["auth_key_restore"]);
		$this->db->where("key_type", 1);
		$result=$this->db->get("users_keys");

		if($result->num_rows()>0):
			//a chave existe, é necessário verificar se ela é valida
			$row=$result->row();
			if(strtotime($row->key_validity)<strtotime("now")):
				echo json_encode(array("status"=>"expired", "keystr"=>strtotime($row->key_validity), "keydata"=>$row->key_validity, "now"=>strtotime("now")));
				return false;
			endif;

			//restaura a senha do usuário para aquela definida na var password
			$this->db->where("id", $row->id_user);
			//a inputvars password já está criptografada
			$this->db->update("users", array("password"=>$this->inputVars["password"]));

			//expira essa chave para que ela não possa ser usada novamente
			$this->expireKey($this->inputVars["auth_key_restore"], "key", $row->id_user);

			echo json_encode(array("status"=>"success"));
			return false;
		endif;

		echo json_encode(array("status"=>"not_allowed"));
		return false;

	}


	/*
	* Função para verificar se o o login é válido ou não, essa função deve ter alguma trava de segurança para que um mesmo ip não fique verificando infinitamente um usuário e senha, talvez essa função possa ser feita no security_ips_lockouts
	*/
	public function setLogin($fromInputVar=false)
	{

		//sleep(15);
		/*requer:
			- username
			- password

			//a cada verificada que dá erro armazena o ip na lista de tentativas
			//após 50 tentativas incorretas bloqueia o ip

			Em caso de sucesso gera uma chave para login que combina dados do usuario e do computador dele e retorna para o navegador, essa chave será valida pelo tempo definido no param TimeMaxLogin, se o param não existir a chave é válida por um dia
			//Caso o usuário esteja desativado ou bloqueado é retornado essa informação via json, e a tabela de limites é alimentada
			//a senha não é criptografada com md5 porque o md5 é inseguro, as senhas são criptografas com a função password_hash, nativa do próprio php
		*/

		//echo password_hash("espetaculo", PASSWORD_DEFAULT);
		//exit;

		$username=$this->inputVars["code"];
		$password=$this->inputVars["password_orign"];
		$public=false;
		//$type=$this->inputVars["type"];

		if(trim($password)=="" || trim($username)==""):
			return false;
		endif;

		$this->db->from("users");
		$this->db->where(" (code='".$username."' OR email='".$username."') ", NULL, false);
		//$this->db->where("type", $type);
		//$this->db->where_in("status", $this->userStatusListActive);
		//$this->db->where("password", md5($password));

		$result=$this->db->get();

		if($result->num_rows()>0):
			//echo "entrou";
			$user=$result->row();

			//echo $password;

			if (password_verify($password, $user->password)):
				$this->userLogged=true;
				$this->userId=$user->id;
				$this->userType=$user->type;
				$this->userPublic=false;

				echo json_encode(array(
					"status"=>"success",
					"key_auth"=>$this->generateUserKey(0),
					"key_user_type"=>$user->type,
					"user_name"=>$user->name,
					"user_email"=>$user->email,
					"user_telephone"=>$user->telephone
				));
				return true;
			endif;
		endif;

		//se chegar até aqui significa que o login não foi válido, logo registra essa tentativa de login invalido
		$this->insertAttempts($username);
		echo json_encode(array("status"=>"fail"));
		return false;
	}


	//efetua o login via facebook
	public function setLoginFacebook(){
		$facebook_id=$this->inputVars["code"];
		$stringend="082b82c6e8fcfba13ac0abebcd391a5e";

		//caso a string não esteja no padrão de string do facebook ele retorna false para o login via facebook
		if($this->endsWith($facebook_id, $stringend)===false):
			return false;
		endif;

		$facebook_id=str_replace($stringend, "", $this->inputVars["code"]);
		$public=false;

		$this->db->from("users");
		$this->db->where("facebook_profile_id", $facebook_id);

		$result=$this->db->get();

		if($result->num_rows()>0):
			$user=$result->row();
			$this->userLogged=true;
			$this->userId=$user->id;
			$this->userType=$user->type;
			$this->userPublic=false;

			echo json_encode(array(
				"status"=>"success",
				"key_auth"=>$this->generateUserKey(0),
				"key_user_type"=>$user->type,
				"user_name"=>$user->name,
				"user_email"=>$user->email,
				"user_telephone"=>$user->telephone,
				"user_facebook_id"=>$user->facebook_profile_id
			));
			//essa função se retornar true retorna com um exit porque ela tem que interromper o script
			return false;
		endif;

		echo json_encode(array("status"=>"fail"));
		return false;

	}

	protected function setLogout(){
		$this->expireKey(true);
		echo json_encode(array("status"=>"success"));
	}

	/* grava o usuario no banco de dados e faz as validações em comum com o cliente e o prestador, gera a auth_key para o usuário novo, o usuário nesse momento que é gravado é gravado como não confirmado para o prestador */
	protected function setUser(){
		//alem de gravar o usuário no banco retorna uma chave de sessão válida que será utilizada pelo usuário para os próximos cadastros
		//envia um email para o usuário pedindo a confirmação do cadastro, o usuário recebe no email uma chave de confirmação igual a chave de restauração, a data de validade dela é mais longa que uma chave de restauração

		//precisa: name, lastname, code, email, telephone_list, password, zipcode
		//verifica se o email não existe (através da função que existe na common)
		//se o code não existe (função na common também)
		//se não existir

		$type=$this->inputVars["type"];

		if(!$this->verifyEmail(false, true)):
			echo json_encode(array("status"=>"email_invalid"));
			return false;
		endif;

		$data=array(
			"name" => $this->inputVars["name"],
			"email" => $this->inputVars["email"],
			//"telephone" => $this->inputVars["telephone"],
			//o password já está criptografado na inputvars
			"password" => $this->inputVars["password"],
			"facebook_profile_id" => $this->inputVars["facebook_profile_id"],
			"status" => 3,
			"type" => $type,
			"ip_added" => USER_IP,
			"last_login" => $this->setMysqlTimestamp("now"),
			"last_ip" => USER_IP
		);

		//verifica se não existe esse usuário como cancelado, se existir esvazia os dados e gera um id para atualizar
		//$clearUserCanceledInfos=$this->clearUserCanceledInfos($this->inputVars["code"]);

		// if($clearUserCanceledInfos===false):
		// 	$this->db->insert("users", $data);
		// else:
		// 	$this->db->where("id", $clearUserCanceledInfos);
		// 	$this->db->update("users", $data);
		// endif;
		$this->db->insert("users", $data);

		$this->userLogged=true;
		$this->userId=$this->db->insert_id();
		$this->userType=$type;
		$this->userName=$this->inputVars["name"];
		$this->userEmail=$this->inputVars["email"];
		//$this->userURL=($type==0?false:$this->inputVars["url"]);
		//$this->userCode=$this->inputVars["code"];
		$this->userStatus=3;
		//gera uma chave publica por segurança
		$this->userPublic=false;

		//$this->setEmailConfirm();

		echo json_encode(
		array(
			"status"=>"success",
			"key_auth"=>$this->generateUserKey(),
			"key_user_type"=>$type,
			"user_name"=>$this->userName,
			//"user_telephone"=>$this->inputVars["telephone"],
			"user_email"=>$this->userEmail
			)
		);
		return true;
	}

	/*
		verifica se esse usuário está cancelado, se estiver retorna o id dele e esvazia as tabelas, mas mantem os reviews
	*/
	private function clearUserCanceledInfos($code){
		$query=$this->db->get_where("users", array("code"=>$code, "status"=>$this->userStatusCanceled));
		if($query->num_rows()>0):
			$row=$query->row();
			$data=array(
				"validation_infos"=>""
			);

			$this->db->where("id", $row->id);
			$this->db->update("users", $data);

			//apaga os dados na tabela providers
			$this->db->where("id_user", $row->id);
			$this->db->delete("providers");
			return $row->id;
		endif;

		return false;
	}



	/* grava a primeira etapa do cadastro do prestador de serviço, nesse momento o authkey já deve estar setado */
	protected function setProviderStepOne(){
		//"professions_list", "categories_list", "company", "topic_1", "topic_2", "topic_3", "description", "qualif"
		//verifica se já não existe um registro na tabela provider para esse usuário, se existir apaga o mesmo e coloca esse no lugar
		//echo "ola mundo";
		$this->db->where("id_user", $this->userId);
		$this->db->delete("providers");

		$data=array(
			"id_user" => $this->userId,
			"professions_list" => $this->inputVars["professions_list"],
			"categories_list" => $this->inputVars["categories_list"],
			"company" => $this->inputVars["company"],
			"topics_list" => json_encode(array($this->inputVars["topic_1"], $this->inputVars["topic_2"], $this->inputVars["topic_3"])),
			"description" => $this->inputVars["description"],
			"qualifications" => $this->inputVars["qualifications"],
			"ip_added" => USER_IP
		);

		$this->db->insert("providers", $data);
		echo json_encode(array("status"=>"success"));
	}

	/* grava a segunda etapa do cadastro do prestador de serviço, nesse momento o auth_key já deve estar setado */
	public function setProviderStepTwo(){
		//so é possível gravar a steptwo se já houver o registro na providers, esse registro será sempre um update
		$this->db->where("id_user", $this->userId);
		$result=$this->db->get("providers");
		if(!$result->num_rows()>0):
			echo json_encode(array("status"=>"not_allowed"));
			return false;
		endif;

		$row=$result->row();

		//precisa: photos_list, cities_list, payments_list, profile_fb, profile_instagram, profile_twitter, profile_website
		$data=array(
			"photos_list" => $this->inputVars["photos_list"],
			"cities_list" => $this->inputVars["cities_list"],
			"payments_list" => $this->inputVars["payments_list"],
			"social_links" => json_encode(
				array(
					"facebook"=>$this->inputVars["profile_fb"],
					"instagram"=>$this->inputVars["profile_instagram"],
					"twitter"=>$this->inputVars["profile_twitter"],
					"website"=>$this->inputVars["profile_website"]
				)
			),
			"ip_added" => USER_IP
		);

		$this->db->where("id_user", $this->userId);
		$this->db->update("providers", $data);
		echo json_encode(array("status"=>"success"));

	}


}
