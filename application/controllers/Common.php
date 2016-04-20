<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * traboxapp common functions
 */
class Common extends CI_Controller {

	/**
	 * Index Page for this controller.
	 *
	 * Maps to the following URL
	 * 		http://example.com/index.php/welcome
	 *	- or -
	 * 		http://example.com/index.php/welcome/index
	 *	- or -
	 * Since this controller is set as the default controller in
	 * config/routes.php, it's displayed at http://example.com/
	 *
	 * So any other public methods not prefixed with an underscore will
	 * map to /index.php/welcome/<method_name>
	 * @see https://codeigniter.com/user_guide/general/urls.html
	 */
	public function index()
	{
		echo "Hello developer, if you find any errors in our application please send a message to admin@appock.co // Ola desenvolvedor, se você encontrar algum erro na nossa aplicação por gentileza envie uma mensagem para admin@appock.co";
		//$this->getUserInfos();
		//echo strtotime("+55 days");
		//if(DEVMODE==true): echo "dev mode"; endif;

		exit;
	}




	protected $userLogged = false;
	protected $userId = false;
	protected $userType = false;
	protected $userName = false;
	protected $userEmail = false;
	protected $userCode = false;
	protected $userURL = false;
	protected $userPublic = false;
	protected $userStatus = false;
	protected $inputVars = array();
	protected $configEmail = array(
    'protocol' => 'smtp',
    'smtp_host' => 'ssl://smtp.googlemail.com',
    'smtp_port' => 465,
    'smtp_user' => 'rx@multside.com.br',
    'smtp_pass' => 'teifnjireocoxycj',
    'mailtype'  => 'html',
    'charset'   => 'iso-8859-1'
	);

	protected $searchSynonymous=array();

	// 0 incomplete (non confirmed),
	// 1 incomplete (confirmed),
	// 2 complete (not confirmed),
	// 3 complete (confirmed) - public,
	protected $userStatusListActive=array(0, 1, 2, 3);
	// 4 canceled
	protected $userStatusCanceled=4;
	protected $userStatusPublic=3;


	/*
	O usuário não pode acessar nenhuma função caso o ip dele esteja na lista de lockouts
	O usuário não pode acessar nenhuma função caso ele informe uma user_auth errada
	Nesse momento já verifica se o usuário está logado ou não
	*/
	public function __construct() {
			parent::__construct();
			$this->getAppParam();
			$this->getSecurityIpsLockouts();
			$this->setUserInfos();

	}

	/*
	define na memoria todos os parametros do app
	*/
	private function getAppParam(){
		//busca todos os parametros cadastrados na others_params e seta eles como constants
		//$this->db->from("others_params");
		$query=$this->db->get("others_params");
		if(!$query->num_rows()>0):
			return false;
		endif;

		foreach($query->result() as $row):
			define($row->name, $row->value);
		endforeach;

		define("USER_IP", $this->getIp());

		return true;
	}





	/*
		Verifica se a variavel user_auth está setada, se estiver verifica se ela é válida, se for valida seta a global user id, se não for limpa as var de user
	*/
	private function setUserInfos(){
		//echo "entrou";
		//busca a keyauth de uma maneira que não interrompa o script

		$keyUnique=$this->input("key_unique");
		$keyAuth=$this->input("key_auth", false);

		$this->keyUnique=$keyUnique;

		//se retornar falso limpa as var do user e retorna falso
		if(!$keyAuth):
			//echo "entrour";
			$this->userLogged=false;
			$this->userId=false;
			$this->userType=false;
			return $this->userLogged;
			//echo "entrou aqui";
		endif;

		//a partir desse ponto se presume que é um usuário logado, portanto se a chave informada for invalida o script é interrompido

		//chama a setUserId que chama a verify key que verifica se a chave é valida e seta o usuário dono da chave
		$this->setUserId();

		//seta o tipo de usuário
		$this->setUserType();

		return $this->userLogged;
	}


	/*
		Essa função é chamada pelas funções que só podem ser acessadas por usuários logado para bloquear o acesso a informações privadas
	*/
	protected function onlyUserLogged($tp=false){
		//seta as informações do usuário (userLogged, userType, userId)
		$this->setUserInfos();

		if($tp!=false && $tp!=($this->userType==0?"client":"provider")):
			echo json_encode(array("status"=>"not_allowed_type"));
			exit;
		endif;

		//se o usuário não estiver setado interrompe o script
		if($this->userLogged==false):
			echo json_encode(array("status"=>"not_logged"));
			exit;
		endif;
	}

	/*
		Essa função bloqueia o acesso para usuários que estão logados e não são clientes
	*/
	protected function onlyUserClientLogged(){
		$this->onlyUserLogged("client");
	}

	/*
		Essa função bloqueia o acesso para usuários que estão logados e não são clientes
	*/
	protected function onlyUserProviderLogged(){
		$this->onlyUserLogged("provider");
	}

	/*
		verifica os inputs predefinidos se eles estão preenchidos, se não estiverem bloqueia o acesso a função
		o unico meio de acessar um input nas classes fora da common será pela inputVars
	*/
	private function verifyInputs($nameFunc){

		if(defined($nameFunc."Inputs")):
			$inputList=json_decode(constant($nameFunc."Inputs"));

			if(isset($inputList->get)):
				//$inputListPost=$inputList->post;
				foreach($inputList->get as $inputGet):
					$this->inputVars[$inputGet->name]=$inputGet->value;
					//echo $inputGet->name. "zzzzz";
				endforeach;
			endif;

			if(isset($inputList->post) || (!isset($inputList->get) && sizeof($inputList)>0)):
				foreach((isset($inputList->get)?$inputList->post:$inputList) as $input):
					$this->input($input);
				endforeach;
			endif;
		endif;


		return true;
	}

	/* exibe as funções que tem acesso publico */
	public function pub($nameFunc){
		$this->call($nameFunc);
	}

	/*
		faz uma pesquisa em todos os campos definidos na array fields
		também adiciona os sinonimos na busca
	*/
	protected function mysqlSearchFields($fields, $values, $like="all"){
		$values=$this->searchSynonymous($values);
		$where="(";

		//adiciona nas values os sinomimos tambem
		foreach($values as $value):
			foreach($fields as $field):
				$where.=($where!="("?" OR ":"")." $field like ('%".$value."%') ";
			endforeach;
		endforeach;
		$where.=") ";

		return $where;
	}

	/*
	carrega os sinonimos
	*/
	protected function getSynonymous(){
			$search_synonymous=$this->db->get("search_synonymous");
			if($search_synonymous->num_rows()>0):
				foreach($search_synonymous->result() as $row):
					array_push($this->searchSynonymous, array("term"=>$row->term, "term_related"=>$row->term_related));
				endforeach;
			endif;
	}

	/* procura os sinonimos cadastrados na tabela de sinonimos e adiciona eles na pesquisa */
	private function searchSynonymous($values){
		$values=is_string($values) ? array($values):$values;

		//para cada value busca sinonimos, e adiciona na lista de values
		//uma palavra pode tanto estar no term como no term_related da tabela search_synonymous
		foreach($values as $value):
				foreach($this->searchSynonymous as $rw):
					//procura o termo ou term_related na array, se não achar adiciona esse item

					if($rw["term"]==$value && !$this->containArrayValue($values, array($rw["term_related"]))):
						array_push($values, $rw["term_related"]);
					endif;

					if($rw["term_related"]==$value &&  !$this->containArrayValue($values, array($rw["term"]))):
						array_push($values, $rw["term"]);
					endif;

				endforeach;

				array_push($values, utf8_encode($value));
				array_push($values, utf8_decode($value));
				array_push($values, htmlentities($value));
			//endif;
		endforeach;

		return $values;
	}

	/*
	eu criei essa função porque a array_search do php vai retornar true se houver o termo como parte de uma value, e o que eu precisava era uma verificação completada de cada value
	*/
	protected function containArrayValue($array, $values)
	{

		$values=is_string($values) ? array($values):$values;
		$exists=false;

		foreach($array as $a):
			foreach($values as $v):
				if($a==$v):
					$exists=true;
				endif;
			endforeach;
		endforeach;

		return $exists;
	}

	/*
			grava a pesquisa do usuário
	*/
	protected function setSearchHistory($type="provider", $term, $result_ids){
		$data=array(
			"id_user" => $this->userId==false ? 0 : $this->userId,
			"term" => json_encode($term),
			"type" => ($type=="provider"?0:1),
			"link" => "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]",
			"result_ids" => json_encode($result_ids),
			"date_added" => $this->setMysqlTimestamp(),
			"ip_added" => USER_IP
		);

		$this->db->insert("search_history", $data);
		return true;
	}


	/* chama a função pedida pelo usuario depois de verificar as regras */
	private function call($nameFunc){

		if($this->verifyInputs($nameFunc)==true):
			//echo $nameFunc;
			$this->{$nameFunc}();
		else:
			echo json_encode(array("status"=>"not_allowed"));
		endif;
	}

	/* protege as funções que exigem user_auth de serem acessada sem credenciamento */
	public function priv($nameFunc){
		$this->onlyUserLogged();
		$this->call($nameFunc);
	}


	/* protege as funções que exigem user_auth de serem acessada sem credenciamento */
	public function provider($nameFunc){
		$this->onlyUserProviderLogged();
		$this->call($nameFunc);
	}

	/* protege as funções que exigem user_auth de serem acessada sem credenciamento */
	public function client($nameFunc){
		$this->onlyUserClientLogged();
		$this->call($nameFunc);
	}


	protected function getUserOrignInfos(){
		$user_os        =   $this->getOS();
		$user_browser   =   $this->getBrowser();

		$device_details =   "Browser: ".$user_browser."\\n\\r Operating System: ".$user_os;
		return $device_details;
	}




	private function getOS() {
			$user_agent     =   $_SERVER['HTTP_USER_AGENT'];
			$os_platform    =   "Unknown OS Platform";
			$os_array       =   array(
															'/windows nt 10/i'     =>  'Windows 10',
															'/windows nt 6.3/i'     =>  'Windows 8.1',
															'/windows nt 6.2/i'     =>  'Windows 8',
															'/windows nt 6.1/i'     =>  'Windows 7',
															'/windows nt 6.0/i'     =>  'Windows Vista',
															'/windows nt 5.2/i'     =>  'Windows Server 2003/XP x64',
															'/windows nt 5.1/i'     =>  'Windows XP',
															'/windows xp/i'         =>  'Windows XP',
															'/windows nt 5.0/i'     =>  'Windows 2000',
															'/windows me/i'         =>  'Windows ME',
															'/win98/i'              =>  'Windows 98',
															'/win95/i'              =>  'Windows 95',
															'/win16/i'              =>  'Windows 3.11',
															'/macintosh|mac os x/i' =>  'Mac OS X',
															'/mac_powerpc/i'        =>  'Mac OS 9',
															'/linux/i'              =>  'Linux',
															'/ubuntu/i'             =>  'Ubuntu',
															'/iphone/i'             =>  'iPhone',
															'/ipod/i'               =>  'iPod',
															'/ipad/i'               =>  'iPad',
															'/android/i'            =>  'Android',
															'/blackberry/i'         =>  'BlackBerry',
															'/webos/i'              =>  'Mobile'
													);

			foreach ($os_array as $regex => $value) {
					if (preg_match($regex, $user_agent)) {
							$os_platform    =   $value;
					}
			}

			return $os_platform;
	}


	private function getBrowser() {

			$user_agent     =   $_SERVER['HTTP_USER_AGENT'];

			$browser        =   "Unknown Browser";

			$browser_array  =   array(
															'/msie/i'       =>  'Internet Explorer',
															'/firefox/i'    =>  'Firefox',
															'/safari/i'     =>  'Safari',
															'/chrome/i'     =>  'Chrome',
															'/edge/i'       =>  'Edge',
															'/opera/i'      =>  'Opera',
															'/netscape/i'   =>  'Netscape',
															'/maxthon/i'    =>  'Maxthon',
															'/konqueror/i'  =>  'Konqueror',
															'/mobile/i'     =>  'Handheld Browser'
													);

			foreach ($browser_array as $regex => $value) {

					if (preg_match($regex, $user_agent)) {
							$browser    =   $value;
					}

			}

			return $browser;

	}

	/*
	* define o ip mais provavel do usuário
	*/
	protected function getIp(){
		if  (! empty ( $_SERVER['HTTP_CLIENT_IP'])):
		    $ip = $_SERVER['HTTP_CLIENT_IP'];
		elseif(!empty($_SERVER ['HTTP_X_FORWARDED_FOR'])):
		    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		else:
		    $ip = $_SERVER['REMOTE_ADDR'];
		endif;
		//echo $ip;
		//print_r($_SERVER);
		return $ip;
	}

	/*
	* Verifica se o ip que está solicitando não está na lista dos bloquados, se tiver interrompe todo o processo aqui
	*/
	private function setSecurityIpsLockouts($dateLimit){
		//antes de inserir verifica se o mesmo já não existe, se existir exclui o mesmo
		$this->db->where("ip", USER_IP);
		$this->db->delete("security_ips_lockouts");

		$data=array(
			"ip"=>(USER_IP!="::1"?USER_IP:""),
			"limit_date"=>$dateLimit,
			"ip_added"=>USER_IP
		);

		$this->db->insert("security_ips_lockouts", $data);
		return true;

	}

	/*
	* Verifica se o ip que está solicitando não está na lista dos bloquados, se tiver interrompe todo o processo aqui
	*/
	private function getSecurityIpsLockouts(){
		$ip=USER_IP;

		//$strtotime=;

		$this->db->from("security_ips_lockouts");
		$this->db->where("ip", $ip);
		$this->db->where("limit_date>", strtotime("now"));

		$result=$this->db->get();

		if($result->num_rows()>0):
			echo json_encode(array("status"=>"blocked"));
			exit;
		endif;
	}


	/*
		retorna o input valido ou se a solicitação for invalida interrompe o script
		armazena na variavel inputVars o valor do input solicitado
	*/
	private function input($name, $block=true){
		//echo "here";
		global $HTTP_RAW_POST_DATA;
		$request=json_decode($HTTP_RAW_POST_DATA);

		if(isset($request->$name)):
			$this->inputVars[$name]=$this->cleanMe($request->$name);
			$this->inputVars[$name]=$this->validate($name, $this->inputVars[$name], $block);

			//na inputvars o password está sempre criptografado, quando se precisa de acessar ele sem criptografia se usa a input. mas a input so pode ser usada dentro da common

			if($name=="password"):
				$password=$this->inputVars[$name];

				$this->inputVars[$name."_orign"]=$password;
				$this->inputVars[$name]=password_hash($password, PASSWORD_DEFAULT);

				return $password;
			endif;

			return $this->inputVars[$name];
			//array_filter($_POST, 'trim_value');
		else:
			//echo "saiu".$name;
			if($block):
				echo json_encode(array("status"=>"invalid", "dev"=>(DEVMODE?$name:"")));
				exit;
			endif;

			return false;
		endif;
	}

	/*
	valida os campos para que as classes não tenham que fazer isso, a validação será baseada no nome do campo
	*/
	private function validate($name, $value, $block=true){
		switch($name):
			case 'email':
				if (!filter_var($value, FILTER_VALIDATE_EMAIL)):
					if($block):
						echo json_encode(array("status"=>"email_invalid"));
						exit;
					endif;

					return false;
				endif;
			break;


			case 'password':
				//uma senha nunca pode ser em branco ou ter menos que 5 caracteres
				if(trim($value)==""):
					if($block):
						echo json_encode(array("status"=>"password_invalid"));
						exit;
					endif;
				endif;
			break;
		endswitch;

		//todos os campos terminados em _list devem ser enviados em base64, pois se tratam de uma array json, esse procedimento é nessário para que uma array json não se confunda com a array json original, como estamos trabalhando com text puro nas requisições são necessários esses tipos de cuidados
		if($this->endswith($name, "_list")):
				$value=utf8_encode(base64_decode($value));
		endif;

		return $value;
	}

	protected function endswith($string, $test) {
	    $strlen = strlen($string);
	    $testlen = strlen($test);
	    if ($testlen > $strlen) return false;
	    return substr_compare($string, $test, $strlen - $testlen, $testlen) === 0;
	}

	/*
	Essa função retira todos os codigos maliciosos e palavras improprias
	*/
	private function cleanMe($input) {
	   //$input = mysql_real_escape_string($input);
		 $replace = [
	    '&lt;' => '', '&gt;' => '', '&#039;' => '', '&amp;' => '',
	    '&quot;' => '', 'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'Ae',
	    '&Auml;' => 'A', 'Å' => 'A', 'Ā' => 'A', 'Ą' => 'A', 'Ă' => 'A', 'Æ' => 'Ae',
	    'Ç' => 'C', 'Ć' => 'C', 'Č' => 'C', 'Ĉ' => 'C', 'Ċ' => 'C', 'Ď' => 'D', 'Đ' => 'D',
	    'Ð' => 'D', 'È' => 'E', 'É' => 'E', 'Ê' => 'E', 'Ë' => 'E', 'Ē' => 'E',
	    'Ę' => 'E', 'Ě' => 'E', 'Ĕ' => 'E', 'Ė' => 'E', 'Ĝ' => 'G', 'Ğ' => 'G',
	    'Ġ' => 'G', 'Ģ' => 'G', 'Ĥ' => 'H', 'Ħ' => 'H', 'Ì' => 'I', 'Í' => 'I',
	    'Î' => 'I', 'Ï' => 'I', 'Ī' => 'I', 'Ĩ' => 'I', 'Ĭ' => 'I', 'Į' => 'I',
	    'İ' => 'I', 'Ĳ' => 'IJ', 'Ĵ' => 'J', 'Ķ' => 'K', 'Ł' => 'K', 'Ľ' => 'K',
	    'Ĺ' => 'K', 'Ļ' => 'K', 'Ŀ' => 'K', 'Ñ' => 'N', 'Ń' => 'N', 'Ň' => 'N',
	    'Ņ' => 'N', 'Ŋ' => 'N', 'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O',
	    'Ö' => 'Oe', '&Ouml;' => 'Oe', 'Ø' => 'O', 'Ō' => 'O', 'Ő' => 'O', 'Ŏ' => 'O',
	    'Œ' => 'OE', 'Ŕ' => 'R', 'Ř' => 'R', 'Ŗ' => 'R', 'Ś' => 'S', 'Š' => 'S',
	    'Ş' => 'S', 'Ŝ' => 'S', 'Ș' => 'S', 'Ť' => 'T', 'Ţ' => 'T', 'Ŧ' => 'T',
	    'Ț' => 'T', 'Ù' => 'U', 'Ú' => 'U', 'Û' => 'U', 'Ü' => 'Ue', 'Ū' => 'U',
	    '&Uuml;' => 'Ue', 'Ů' => 'U', 'Ű' => 'U', 'Ŭ' => 'U', 'Ũ' => 'U', 'Ų' => 'U',
	    'Ŵ' => 'W', 'Ý' => 'Y', 'Ŷ' => 'Y', 'Ÿ' => 'Y', 'Ź' => 'Z', 'Ž' => 'Z',
	    'Ż' => 'Z', 'Þ' => 'T', 'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a',
	    'ä' => 'ae', '&auml;' => 'ae', 'å' => 'a', 'ā' => 'a', 'ą' => 'a', 'ă' => 'a',
	    'æ' => 'ae', 'ç' => 'c', 'ć' => 'c', 'č' => 'c', 'ĉ' => 'c', 'ċ' => 'c',
	    'ď' => 'd', 'đ' => 'd', 'ð' => 'd', 'è' => 'e', 'é' => 'e', 'ê' => 'e',
	    'ë' => 'e', 'ē' => 'e', 'ę' => 'e', 'ě' => 'e', 'ĕ' => 'e', 'ė' => 'e',
	    'ƒ' => 'f', 'ĝ' => 'g', 'ğ' => 'g', 'ġ' => 'g', 'ģ' => 'g', 'ĥ' => 'h',
	    'ħ' => 'h', 'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i', 'ī' => 'i',
	    'ĩ' => 'i', 'ĭ' => 'i', 'į' => 'i', 'ı' => 'i', 'ĳ' => 'ij', 'ĵ' => 'j',
	    'ķ' => 'k', 'ĸ' => 'k', 'ł' => 'l', 'ľ' => 'l', 'ĺ' => 'l', 'ļ' => 'l',
	    'ŀ' => 'l', 'ñ' => 'n', 'ń' => 'n', 'ň' => 'n', 'ņ' => 'n', 'ŉ' => 'n',
	    'ŋ' => 'n', 'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'oe',
	    '&ouml;' => 'oe', 'ø' => 'o', 'ō' => 'o', 'ő' => 'o', 'ŏ' => 'o', 'œ' => 'oe',
	    'ŕ' => 'r', 'ř' => 'r', 'ŗ' => 'r', 'š' => 's', 'ù' => 'u', 'ú' => 'u',
	    'û' => 'u', 'ü' => 'ue', 'ū' => 'u', '&uuml;' => 'ue', 'ů' => 'u', 'ű' => 'u',
	    'ŭ' => 'u', 'ũ' => 'u', 'ų' => 'u', 'ŵ' => 'w', 'ý' => 'y', 'ÿ' => 'y',
	    'ŷ' => 'y', 'ž' => 'z', 'ż' => 'z', 'ź' => 'z', 'þ' => 't', 'ß' => 'ss',
	    'ſ' => 'ss', 'ый' => 'iy', 'А' => 'A', 'Б' => 'B', 'В' => 'V', 'Г' => 'G',
	    'Д' => 'D', 'Е' => 'E', 'Ё' => 'YO', 'Ж' => 'ZH', 'З' => 'Z', 'И' => 'I',
	    'Й' => 'Y', 'К' => 'K', 'Л' => 'L', 'М' => 'M', 'Н' => 'N', 'О' => 'O',
	    'П' => 'P', 'Р' => 'R', 'С' => 'S', 'Т' => 'T', 'У' => 'U', 'Ф' => 'F',
	    'Х' => 'H', 'Ц' => 'C', 'Ч' => 'CH', 'Ш' => 'SH', 'Щ' => 'SCH', 'Ъ' => '',
	    'Ы' => 'Y', 'Ь' => '', 'Э' => 'E', 'Ю' => 'YU', 'Я' => 'YA', 'а' => 'a',
	    'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e', 'ё' => 'yo',
	    'ж' => 'zh', 'з' => 'z', 'и' => 'i', 'й' => 'y', 'к' => 'k', 'л' => 'l',
	    'м' => 'm', 'н' => 'n', 'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's',
	    'т' => 't', 'у' => 'u', 'ф' => 'f', 'х' => 'h', 'ц' => 'c', 'ч' => 'ch',
	    'ш' => 'sh', 'щ' => 'sch', 'ъ' => '', 'ы' => 'y', 'ь' => '', 'э' => 'e',
	    'ю' => 'yu', 'я' => 'ya'
		];

		 $TermsBlockedList=json_decode(TermsBlockedList);
		 //$TermsBlockedListLow = array_map('strtolower', $TermsBlockedList);


	   $input = htmlspecialchars($input, ENT_IGNORE, 'utf-8');
	   $input = strip_tags($input);
	   $input = stripslashes($input);

		 //faz a substituição simples, que permite que palavras multiplas sejam substituidas
		 $input=str_ireplace($TermsBlockedList, "[bloqueado]", $input);

		 //o problema dessa função é que ela vai tirar os acentos de onde não devia tirar, ainda não consegui pensar numa maneira de conciliar ela com o resto da aplicação


		 //divide a array original em palavras (split em espaços em brancos), e cada palavra testa sem acento, se for palavrão elimina, se não for mantem
		 $inputFinal="";
		 $words=explode(" ", $input);

		 //if($words!== false):
			 foreach($words as $word):
				 //echo "entrou e a word é ".$word;
				 $wordClean=str_replace(array_keys($replace), $replace, $word);
				 $wordClean=str_ireplace($TermsBlockedList, "[bloqueado]", $wordClean);
				 //echo "wordclean: ".$wordClean;
				 if(strpos($wordClean, "[bloqueado]") !== false):
					 $inputFinal=$inputFinal." ".$wordClean;
				 else:
					 $inputFinal=$inputFinal." ".$word;
				 endif;
			 endforeach;

	   return trim($inputFinal);
	}


	/*
	Essa função verifica se a chave informada pelo javascript é valida, se não for ele interrompe o processo, impedindo que páginas que exigem login sejam acessadas, se for valida ele libera o acesso
	*/
	protected function verifyKey($returnuser=false, $keyCustom=false, $type=false){
		$key_auth=($keyCustom==false?$this->input("key_auth"):$keyCustom);
		//busca a chave no banco de dados

		$this->db->from("users_keys");
		$this->db->where("key_auth", $key_auth);

		if($type!==false):
			$this->db->where("key_type", $type);
		endif;

		$result=$this->db->get();

		if($result->num_rows()>0):
			$row=$result->row();
			//verifica se a chave não expirou, isso é, se ela possui um valor maior que o strtotime atual
			if(strtotime($row->key_validity)<strtotime("now")):
				if($keyCustom==false):
					$this->clearUserInfo();
				endif;
				return false;
			endif;

			//verifica se a chave é para o computador atual
			if($row->key_user_info!=$this->getUserOrignInfos()):
				if($keyCustom==false):
					$this->clearUserInfo();
				endif;
				return false;
			endif;

			//caso a chave não tenha expirado e seja do computador atual segue adiante
			//caso esteja setado o returnuser como true busca o id do user dono dessa chave
			//só retorna o id do usuário se ele não tiver cancelado o perfil dele

			$this->db->from("users");
			$this->db->where("id", $row->id_user);
			//todos, menos cancelado
			$this->db->where_in("status", $this->userStatusListActive);
			$userInfo=$this->db->get();
			if($userInfo->num_rows()>0):
				$user=$userInfo->row();
				$this->userId=$row->id_user;
				$this->userLogged=true;
				$this->userType=$user->type;
				$this->userEmail=$user->email;
				$this->userName=$user->name;
				$this->userCode=$user->code;
				$this->userStatus=$user->status;
				$this->userURL=$user->url;
				if($returnuser):
					return $row->id_user;
				endif;
			else:
				if($keyCustom==false):
					$this->clearUserInfo();
				endif;
				return false;
			endif;

			$this->userId=$row->id_user;
			$this->userLogged=true;

			return true;
		endif;

		if($keyCustom==false):
			$this->clearUserInfo();
		endif;

		return false;
	}

	/* verifica se um usuário é provider ou nao */
	protected function isProvider($id_user=false){
		if(!$id_user):
			return ($this->userType==1?true:false);
		else:
			$this->db->where("id", $id_user);
			$result=$this->db->get("users");

			if(!$result->num_rows()>0):
				echo json_encode(array("status"=>"fatal_error"));
				exit;
			endif;

			$row=$result->row();

			return ($row->type==1?true:false);
		endif;
	}

	protected function getListFromBd($value){
		return json_decode($value=="" || $value=="{}"?"[]":$value);
	}

	/* verifica se o cadastro do provider está completo ou não */
	protected function isProviderComplete($id_user=false){
		//para verificar se o cadastro es!á completo primeiro verifica se o usuario atual é um provider
		if(!$this->isProvider()):
			return false;
		endif;

		//verifica se existe o cadastro desse provider na tabela providers, se não existir retorn falso
		$this->db->where("id_user", ($id_user!==false?$id_user:$this->userId));
		$result=$this->db->get("providers");

		if(!$result->num_rows()>0):
			return false;
		endif;

		$row=$result->row();
		//verifica se todos os campos estão preenchidos, se não estiverem retorna false;

		//busca a lista de campos obrigatorios para ser um provider complete
		$providerCompleteFields=json_decode(ProviderCompleteFields);
		foreach($providerCompleteFields as $field):
			//caso o nome do campo termine em list
			//echo "OLA MUNDO";
			//echo $field;

			if($this->endswith($field, "_list")):
				$errors = array_filter(json_decode($row->$field, true));
				if (empty($errors)):
					return false;
				endif;
			elseif(trim($row->$field)==""):
				//se op campo terminar em _list é necessário fazer uma analise se não se trata de uma array vazia

				//echo $id_user."deveria ter dado merda".$field;
				return false;
			endif;
		endforeach;

		return true;
	}




	/*
	Limpa as informações do user info
	*/
	protected function clearUserInfo(){
		$this->userId=false;
		$this->userType=false;
		$this->userLogged=false;
		$this->userEmail=false;
		$this->userName=false;
		$this->userCode=false;
		$this->userStatus=false;
		$this->userURL=false;
		$this->userPublic=false;
		echo json_encode(array("status"=>"fatal_error_key"));
		exit;
		return true;
	}

	/*
	Seta o id do usuario na userId
	*/
	protected function setUserId(){
		$this->verifyKey(true, false, '0');
		return $this->userId;
	}

	/*
		Seta o tipo do usuário na userType
	*/
	private function setUserType(){
		if($this->userId==false):
			$this->userType=false;

			return false;
		endif;

		$this->db->where("id", $this->userId);
		$result=$this->db->get("users");
		if($result->num_rows()>0):
			$row=$result->row();
			$this->userType=$row->type;
			return $this->userType;
		endif;

		$this->userType=false;
		return $this->userType;
	}


	/*
	gera uma chave para o usuario baseado as informaçoes do userId, userType, userLogged
	*/
	protected function generateUserKey($type=0, $userCustomId=false)
	{
		if($this->userLogged==false && $userCustomId==false):
			return false;
		endif;

		//caso a chave nova que está sendo gerada seja do tipo restore ou confirm email as chaves antigas devem ser expiradas
		if($type!=0):
			$this->expireKey($type, "type", $userCustomId);
		endif;


		$key_auth=$this->generateKey();

		$key_data=array(
			"id_user"=>($userCustomId==false?$this->userId:$userCustomId),
			"key_auth"=>$key_auth,
			"key_type"=>$type,
			"key_validity"=>$this->generateKeyValidity($type, $userCustomId),
			"key_user_info"=>$this->getUserOrignInfos(),
			"date_added"=> $this->setMysqlTimestamp(),
			"ip_added"=>USER_IP
		);

		$this->db->insert("users_keys", $key_data);

		return $key_auth;
	}

	/*
		gera a data de validade da chave conforme o tipo pedido
	*/
	private function generateKeyValidity($type=0, $userCustomId=false){
		switch($type):
			//para login busca a TimeMaxLogin
			case 0:
				//caso o usuário esteja em um local publico a chave vale apenas por 1 hora
				$key_validity=($this->userPublic==true?"+ 1 hour":TimeMaxLogin);
			break;

			//para password restor busca a SignPasswordRestoreValidity
			case 1:
				//gera uma chave de restauração com validade definida no SignPasswordRestoreValidity
				//antes de gerar uma nova senha de restauração define a data de expiração das senhas antigas para agora
				$this->db->where("id_user", ($userCustomId==false?$this->userId:$userCustomId));
				$this->db->where("key_type", "1");
				$this->db->update("users_keys", array("key_validity"=>$this->setMysqlTimestamp()));

				$key_validity=SignPasswordRestoreValidity;
			break;

			//para confirmação de email busca a UserConfirmEmailTime
			case 2:
				$key_validity=UserConfirmEmailTime;
			break;
		endswitch;

		return $this->setMysqlTimestamp($key_validity);
	}

	/*
	gera uma chave aleatoria que pode ser usada para login, confirmar ou restaurar senha
	*/
	protected function generateKey(){
		$key_auth_value=date('Ymdhis').rand(0, 50000).rand(0, 50000).rand(0, 50000).date('Ymdhis');
		$key_auth_value=md5($key_auth_value);
		$key_auth_value=substr($key_auth_value, 0, 50);
		$key_auth=password_hash($key_auth_value, PASSWORD_DEFAULT);
		$key_auth=substr($key_auth, 0, 250);

		$key_auth=str_replace("$", "", $key_auth);
		$key_auth=str_replace(".", "", $key_auth);
		$key_auth=str_replace("/", "", $key_auth);
		$key_auth=str_replace("\\", "", $key_auth);
		$key_auth=str_replace("=", "", $key_auth);
		$key_auth=urlencode($key_auth);


		return $key_auth;
	}


	/*
	seta a validade de uma chave para o timestamp now, se o usuário não for especificado usa o userId
	$keyortype=tipo da chave a ser expirada (0, 1, 2, 3) ou numero da chave a ser expirada, true para a key_auth atual
	$type=key para expirar uma chava especifica, type para expirar chaves de um determinado tipo

	*/
	protected function expireKey($keyortype, $tpe="key", $userCustomId=false){
		//$this->expireKey($type, true, $userCustomId);
		//caso o type seja key, expira a validade de uma chave em especifica, do contrário expira todas as chaves do usuário de um determinado tipo
		if($keyortype===true):
			//echo "ENTROU AQUI";
			$keyortype=$this->input("key_auth", false);
		endif;

		if($keyortype!="all"):
			//echo "entrou aqui e type é ".print_r($tpe) ;
			if($tpe=="key"):
				//echo "entrou em type key";
				$this->db->where("key_auth", $keyortype);
			else:
				//echo "entrou em type non key";
				$this->db->where("key_type", $keyortype);
			endif;

		endif;

		$this->db->where("id_user", ($userCustomId==false?$this->userId:$userCustomId));
		$this->db->update("users_keys", array("key_validity"=>$this->setMysqlTimestamp()));

		return true;

	}

	/*
	Responsável por enviar todos os emails do api seguindo um determinado padrão (config, from, template etc)
	*/
	protected function sendEmail($subject, $content, $to=false){
		//envia um email para o usuário com a chave de restauração
		$this->load->library('email', $this->configEmail);
		$this->email->set_newline("\r\n");

		// Set to, from, message, etc.
		$this->email->from('rx@appock.co', 'Ricardo Santos');

		$this->email->to(($to==false?$this->userEmail:$to));

		$this->email->subject(utf8_decode($subject));
		$this->email->message(utf8_decode($this->load->view("common_email", array("content"=>$content), true)));

		return $this->email->send();

	}


	protected function mask($val, $mask)
	{
		$maskared = '';
		$k = 0;
		for($i = 0; $i<=strlen($mask)-1; $i++)
		{
		 if($mask[$i] == '#')
		 {
		    if(isset($val[$k]))
		     $maskared .= $val[$k++];
		 }
		 else
		{
		 if(isset($mask[$i]))
		 $maskared .= $mask[$i];
		 }
		}
		return $maskared;
	}

	protected function formatPhone($phoneNumber){
		return $this->mask($phoneNumber, "(##) #####-#####");
	}

	/*
		Insere no param de login attempts essa tentativa de login, após isso salva o login attempts e chama a função verifyAttempts
	*/
	protected function insertAttempts($username){
		$loginAttempts=json_decode(LoginAttempts);
		//print_r($loginAttempts);
		//return;
		$ips=json_decode(json_encode($loginAttempts->ips), true);
		//$users=json_decode(json_encode($loginAttempts->users), true);

		$ip=USER_IP;

		$newdata[$ip]=array(
			"ip"=>$ip,
			"timestamp"=>strtotime("now"),
			"username"=>$username
		);

		$newdata[$ip]=array(
			"ip"=>$ip,
			"timestamp"=>strtotime("now"),
			"username"=>$username
		);

		array_push(
			$ips,
			$newdata[$ip]
		);

		$LoginAttemptsValue=json_encode(array("ips"=>$ips));

		$this->setParam("LoginAttempts", $LoginAttemptsValue);

		$this->verifyAttempts($username);
	}


	//verifica a quantidade de tentativas incorretas feitas por esse ip no periodo da ultima hora
	protected function verifyAttempts($username){

		//busca o json com a lista de tentativas de acesso de todos os usuários, as tentativas devem ser restritas por usuário e por ip
		//o param loginAttempts deve ter dois nós, um de ips e outro de usuários


		$loginAttempts=json_decode(LoginAttempts);
		$ips=json_decode(json_encode($loginAttempts->ips), true);
		//$users=json_decode(json_encode($loginAttempts->users), true);

		//numero maximo de tentativas de login no periodo
		$loginAttemptsLimit=LoginAttemptsLimit;

		//periodo de tempo para tentantivas de ip fracassadas
		$loginAttemptsPeriod=strtotime(LoginAttemptsPeriod);

		//tempo de punicao para diferentes ips que estao tentando o mesmo usuario
		$loginBlockUserPeriod=strtotime(LoginBlockUserPeriod);

		//tempo de punicao para ips não reincidentes
		$loginBlockIPPeriod=strtotime(LoginBlockIPPeriod);

		//tempo de punicao para ips reincidentes
		$loginBlockIPRecidivist=strtotime(LoginBlockIPRecidivist);

		$totalIPPeriod=0;
		$totalIP=0;

		//procura o total de tentativas que foram originarias desse ip na ultima hora
		//caso um usuario tenha tido diferentes tentativas de invasão de diferentes ips na ultima hora bloqueia o ip atual? bloqueia, mas por apenas 30 minutos
		//caso um ip tenha tentado invadir um mesmo usuário ou vários usuários bloqueia o ip por 10 dias


		//calcula o numero de vezes que esse ip tentou acessar no periodo de tempo definido na $loginAttemptsPeriod
		foreach($ips as $ip):
			if($ip["ip"]==USER_IP):
				//essa primeira variavel registra o historico de tentativas de login desse ip
				$totalIP++;
				if($ip["timestamp"]>$loginAttemptsPeriod):
					//essa var registra o total de tentativas de login dentro do periodo
					$totalIPPeriod++;
				endif;
			endif;
		endforeach;


		//caso o numero de tentativas seja maior ao permitido, bloqueia esse ip pelo periodo definido na $loginBlockIPPeriod
		//echo "Tentativas historicas: ".$totalIP." tentativas no periodo: ".$totalIPPeriod;

		//caso as tentativas de login tenham sido maiores que as permitidas envia esse ip para a blocklist pelo tempo determinado pelo tipo de punição
		if($totalIPPeriod>$loginAttemptsLimit):
			$this->setSecurityIpsLockouts(($totalIP>$totalIPPeriod?$loginBlockIPRecidivist:$loginBlockIPPeriod));
			return false;
			//echo ($totalIP>$totalIPPeriod?"reincidente":"nao e reincidente");
			//echo "vai bloquear pelo ip::";
		endif;

		$totalUsername=0;

		//busca quantas vezes esse usuário foi tentado acessar no periodo de tempo, se for maior que o numero de vezes permitido bloqueia esse ip pelo tempo definido na $loginBlockUserPeriod
		foreach($ips as $ip):
			if($ip["username"]==$username && $ip["timestamp"]>$loginAttemptsPeriod):
				//se houve alguma tentativa de acesso com o username solicitado no periodo incrementa o contador $totalUsername
				$totalUsername++;
			endif;
		endforeach;

		if($totalUsername>$loginAttemptsLimit):
			//bloqueia o usuário pela quantidade de tentativas que houve no periodo de acessar esse mesmo usuário
			$this->setSecurityIpsLockouts($loginBlockUserPeriod);
			return false;
			//echo "vai bloquear pelo usuário::";
		endif;

		return true;

	}



	/* retorna uma data no formato mysql, ela usa como parametro de entrada o srttotime */
	protected function setMysqlTimestamp($strtotime="now")
	{
		if($strtotime=="now"):
			//$strtotime=strtotime("now");
		endif;

		return date('Y-m-d H:i:s',strtotime($strtotime));
	}


	/*
	* Função para verificar se o cpf é existe ou não na base de dados, outra função que precisa ser protegida
	* caso o usuário esteja logado retira da verificação o cpf do usuário logado
	*/
	protected function verifyCode($json=true)
	{
		$code=$this->inputVars["code"];

		$this->db->where("code", $code);
		//todos, menos cancelado
		$this->db->where_in("status", $this->userStatusListActive);
		//esse if garante que o usuário logado não receberá mensagem que o seu próprio code já existe na base de dados
		if($this->userLogged==true):
			$this->db->where_not_in("code", array($this->userCode));
		endif;


		$this->db->from("users");
		$result=$this->db->get();

		if($json):
			echo json_encode(array("status"=>"success", "duplicated"=>($result->num_rows()>0?true:false)));
		endif;

		return ($result->num_rows()>0?false:true);
	}

	/*
	* Função para verificar se o email existe ou não na base de dados, outra função que precisa ser protegida
	* caso o usuário esteja logado retira da verificação o email do usuário
	*/
	public function verifyEmail($json=true, $logged=false)
	{
		$email=$this->inputVars["email"];

		$this->db->where("email", $email);
		//esse if garante que o usuário logado não receberá mensagem que o seu próprio email já existe na base de dados
		if($this->userLogged==true && $logged==false):
			$this->db->where_not_in("email", array($this->userEmail));
		endif;

		//todos, menos cancelado
		$this->db->where_in("status", $this->userStatusListActive);
		$this->db->from("users");
		$result=$this->db->get();

		if($json):
			echo json_encode(array("status"=>"success", "duplicated"=>($result->num_rows()>0?true:false)));
		endif;

		return ($result->num_rows()>0?false:true);
	}


	protected function nltoarray($value){
		if($value==""):
			$value="[]";
		endif;

		return $value;

	}




	/*
	* Busca a lista de categorias (param)
	*/
	public function getCategory(){
		echo json_encode(array("status"=>"success", "list"=>json_decode(CategoryList)));
	}


	/*
	* Busca a lista de profissões (param)
	*/
	public function getProvidersTitle(){
		echo json_encode(array("status"=>"success", "list"=>json_decode(ProvidersTitle)));
	}

	/*
	* atualiza um determinado param na tabela others_params, verifica se o parametro é atualizavel antes
	*/
	private function setParam($name, $value){
		if(!isset($name) || !isset($value)):
			return false;
		endif;

		$this->db->where("name", $name);
		$this->db->where("read_only", "0");
		$this->db->update("others_params", array("value"=>$value));
		//echo ($this->db->affected_rows()>0?"true":"false");
		return ($this->db->affected_rows()>0?true:false);
	}


	/*
	Envia o email de confirmação do cadastro, apenas se o cadastro não estiver confirmado
	*/
	protected function setEmailConfirm(){
		//so envia se o usuário tiver logado
		//
		if(!$this->userLogged):
			//echo "saiu";
			return false;
		endif;

		$this->db->where("id", $this->userId);
		//não lista cancelados
		$this->db->where_in("status", array(0, 2));
		$result=$this->db->get("users");

		//echo "entrouuu";
		if($result->num_rows()>0):
			//echo "entrouu1u";
			$row=$result->row();
			//envia um email de confirmação para o usuário

			$linkConfirm=(DEVMODE?"http://localhost:8080/cadastro/confirmar/".$this->generateUserKey(2):"http://trabox.com.br/cadastro/confirmar/".$this->generateUserKey(2));
			$this->sendEmail("Confirme o seu cadastro na Trabox", $this->load->view("common_email_confirm", array("linkConfirm"=>$linkConfirm), true));
		endif;

		return false;
	}


	/*
		Registra no banco de dados as cidades proximas do lat long atual, a precisão desse registro é de 1,1 km, o que significa que posições como -23.3566039 e -46.3650844 serão convertidas em
		-23.3000000 e -46.3000000

		//antes de registrar verifica se a posição atual já não foi registrada, se foi registrada não atualiza

		//registra numa tabela as cidades retornadas e em outra tabela o lat long o id da cidade e a distancia dela em relação ao ponto atual

	*/
	protected function setLocation(){
		$lat=round($this->inputVars["lat"], 1, PHP_ROUND_HALF_UP);
		$lng=round($this->inputVars["lng"], 1, PHP_ROUND_HALF_UP);

		//verifica se já existe esse lat lng no bd, se existir simplesmente retorna to_trusted
		$query=$this->db->get_where("others_cities_distance", array("lat"=>$lat, "lng"=>$lng));
		if($query->num_rows()>0):
			echo json_encode(array("status"=>"success"));
			return false;
		endif;

		//se não existir faz a req no geonames
		$uri="http://api.geonames.org/findNearbyPlaceNameJSON?lat=".$lat."&lng=".$lng."&style=full&cities=cities15000&radius=200&maxRows=120&username=rxluz";
		$raw_data = file_get_contents($uri);

		$data=json_decode($raw_data);
		foreach($data->geonames as $city):
			$this->db->insert("others_cities_distance",
				array(
					"lat"=>$lat,
					"lng"=>$lng,
					"cities_id" => $this->getCity($city),
					"distance" => $city->distance,
					"data_added" => $this->setMysqlTimestamp(),
					"ip_added" => USER_IP
				)
			);
		endforeach;

		echo json_encode(array("status"=>"success"));
	}


	//processo, para cada cidade encontrada busca o id dela, se não achar adiciona no bd
	private function getCity($city){
		$query=$this->db->get_where("others_cities", array("asciiName"=>$city->asciiName));
		if($query->num_rows()>0):
			$row=$query->row();
			return $row->id;
		else:
			$this->db->insert(
				"others_cities",
				array(
					"name"=>$city->toponymName,
					"asciiName"=>$city->asciiName,
					"lat" => $city->lat,
					"lng" => $city->lng,
					"data_added" => $this->setMysqlTimestamp(),
					"ip_added" => USER_IP
				)
			);

			return $this->db->insert_id();
		endif;
	}



}
