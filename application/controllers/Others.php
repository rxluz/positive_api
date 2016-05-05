<?php
defined('BASEPATH') OR exit('No direct script access allowed');
include("application/controllers/Common.php");
/**
 * traboxapp sign functions
 */
class Others extends Common {


	protected function getBanners(){
		//sleep(15);
		$this->db->from("banners");
		$this->db->where("banhome", "s");
		//$this->db->where("status", "r");
		$this->db->where("datafim >=", $this->setMysqlTimestamp("now"));
		//$this->db->limit(10, 0);
		$this->db->order_by("id", "DESC");
		$get=$this->db->get();

		$data=array();
		if($get->num_rows()>0):
			foreach($get->result() as $row):
				array_push($data, urlImgBanner.$row->imagem);
			endforeach;
		endif;

		echo json_encode(array("status"=>"success", "data"=>$data));
	}

	/*
		Busca as informações da página sobre da trabox, essas informações são um param
	*/
	protected function getAbout(){
		echo json_encode(array("status"=>"success", "value"=>OthersAbout));
	}

	/*
		Busca as informações da página termos e privacidade, essas informações são um param
	*/
	protected function getTerms(){
		echo json_encode(array("status"=>"success", "value"=>OthersTerms));
	}


	/*
		Grava as informações informadas no formulário de contato
	*/
	protected function setContact(){
		$data=array(
			//"name", "name_performer", "email", "telephone", "obs"
			"name"=>$this->inputVars["name"],
			"email"=>$this->inputVars["email"],
			"telephone"=>$this->inputVars["telephone"],
			"message"=>$this->inputVars["message"],
			"ip_added"=>$this->getIp()
		);

		$this->db->insert("others_contact_form", $data);

		// //print_r($this->configEmail);
		//
		// $this->load->library('email', $this->configEmail);
		// $this->email->set_newline("\r\n");
		//
		// // Set to, from, message, etc.
		// $this->email->from('rx@appock.co', 'Ricardo Santos');
		// $this->email->to('ricardo.out@gmail.com');
		// $this->email->cc('rcsantosricardo@aol.com');
		// //$this->email->bcc('them@their-example.com');
		//
		// $this->email->subject('Email Test');
		// $this->email->message('Testing the email class.');
		//
		// $result = $this->email->send();

		echo json_encode(array("status"=>"success"));
	}



	/*
		Busca as informações da página termos e privacidade, essas informações são um param
	*/
	protected function getAdvertising(){
		echo json_encode(array("status"=>"success", "value"=>OthersAdvertising));
	}



}
