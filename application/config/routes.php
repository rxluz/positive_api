<?php

defined('BASEPATH') OR exit('No direct script access allowed');

define('UC_PUBLIC_KEY', '1dadf66d7c60cd08b42d');
define('UC_SECRET_KEY', '7b2cad4f9b08080af420');
define('GOOGLE_API_KEY', 'AIzaSyAazjyszAtACOYxtQ_t3UaGTrMBVHvz51M');
define('GOOGLE_API_STREET_KEY', 'AIzaSyAazjyszAtACOYxtQ_t3UaGTrMBVHvz51M');
// define('GOOGLE_API_KEY', 'AIzaSyDy71PV5ohLpAy0rryHmHAMRRgrJKuQ-fk');
// define('GOOGLE_API_STREET_KEY', 'AIzaSyDy71PV5ohLpAy0rryHmHAMRRgrJKuQ-fk');



define("PUB", "pub");
//echo PUB;
define("PRIV", "priv");
define("CLIENT", "client");
define("PROVIDER", "provider");
//define("DEVMODE", (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false));
define("DEVMODE", true);

function def($cont, $visibility, $name, $arr)
{
  if($arr):
    define($name."Inputs", json_encode($arr));
  endif;

  return "$cont/$visibility/$name";
}

function decode($url) {
  //return utf8_encode(urldecode($url));
  return (utf8_encode(base64_decode(urldecode($url))));

}

$route['default_controller'] = 'welcome';
$route['404_override'] = '';
$route['translate_uri_dashes'] = FALSE;

/*common redirects */
$route['common/verify/code'] = def(
  "common", PUB, "verifyCode",
  array("code")
);

$route['common/verify/email'] = def(
  "common", PUB, "verifyEmail",
  array("email")
);


$route['common/verify/url'] = def(
  "common", PUB, "verifyUrl",
  array("url")
);


$route['common/verify/newsletter'] = 'common/verifyNewsletter'; //ok

$route['common/get/category'] = 'common/getCategory'; //ok
$route['common/get/providers/title'] = 'common/getProvidersTitle'; //ok
$route['common/get/param'] = 'common/getParamPublic'; //ok
$route['common/get/history'] = 'common/getHistory'; //ok

$route['common/set/newsletter'] = 'common/setNewsletter'; //ok


/* common register place */
$route['common/set/location'] = def(
  "search", PUB, "setLocation",
  array(
    "lat",
    "lng"
  )
);

/*sign redirects */

//precisa: code (email ou cpf)
$route["sign/set/password/forgot"]=def("sign", PUB, "setPasswordForgot", array("code"));

//precisa: auth_restore_key e password
$route["sign/set/password/restore"]=def("sign", PUB, "setPasswordRestore", array("auth_key_restore", "password"));

//precisa: name, lastname, code, email, telephone_list, password, zipcode, url
$route['sign/login'] = def(
  "sign", PUB, "setLogin",
  array(
    "code", "password"
  )
);

$route['sign/login/facebook'] = def(
  "sign", PUB, "setLoginFacebook",
  array(
    "code",
    "fbToken"
  )
);

//precisa: name, lastname, code, email, telephone_list, password, zipcode, url
$route['sign/logout'] = def(
  "sign", PRIV, "setLogout",
  array()
);


//precisa: name, lastname, code, email, telephone_list, password, zipcode
$route['sign/set/user'] = def(
  "sign", PUB, "setUser",
  array(
    "blood_type", "city", "name", "email", "donor", "facebook_profile_id", "fbToken"
  )
);




/*user redirects */
/*
priv/ = only users logged
client/ = only clients logged
provider/ = only providers logged
*/

$route['users/set/friends'] = def(
  "users", PRIV, "setFriends",
  array(
    "friends_data"
  )
);


$route['users/get/friends/responsible'] = def(
  "users", PRIV, "getFriendsResponsible",
  array()
);


$route['users/set/hospital'] = def(
  "users", PRIV, "setHospital",
  array(
    "code", "birth_date", "allow_hospital"
  )
);

//precisa: auth_key_email
$route['users/set/confirm'] = def(
  "users", PUB, "setConfirm",
  array(
    "auth_key_email"
  )
);

//precisa: auth_key_email
$route['users/set/restore'] = def(
  "users", PUB, "setRestore",
  array(
    "code"
  )
);

//precisa: password
$route['users/set/cancel'] = def(
  "users", PRIV, "setCancel",
  array(
    "password",
    "cause"
  )
);

//only clients logged

//only providers logged
//precisa: email, telephone_list, password, zipcode
$route['users/set/basic'] = def(
  "users", PRIV, "setBasic",
  array(
    "blood_type", "city", "name", "email", "donor"
  )
);

//precisa: password, new_password
$route['users/set/password'] = def(
  "users", PRIV, "setPassword",
  array(
    "password", "new_password"
  )
);

//precisa: password, new_password
$route['users/bookmark/add'] = def(
  "users", PRIV, "addBookmark",
  array(
    "id_bookmark", "type"
  )
);

$route['users/bookmark/remove'] = def(
  "users", PRIV, "removeBookmark",
  array(
    "id_bookmark", "type"
  )
);


//busca as informaçÕes do usuario logado atual
$route['users/get/infos/basic'] = def(
  "users", PRIV, "getInfosBasic",
  array()
);


/*search redirects */
$route['search/get/(:any)'] = function($fltrs)
{
  //print_r($filters);
  return def(
    "search", PUB, "getSearch",
    array
    (
      "get"=>array
      (
        array
        (
          "name"=>"filters",
          "value"=>($fltrs!="recentes"?decode($fltrs):false)
        )
      ),
      "post"=>array(
        "lat",
        "lng"
      )
    )
  );
};

/*others redirects */
//requer: nada
$route['others/get/about'] = 'others/pub/getAbout';

//requer: nada
$route['others/get/terms'] = 'others/pub/getTerms';


//requer: name, telephone, email, message
$route['others/set/newsletter'] = def(
  "others", PUB, "setNewsletter",
  array(
    "name",
    "email"
  )
);

//requer: nada
$route['others/get/advertising'] = 'others/pub/getAdvertising';

$route['others/banners'] = def(
  "others", PUB, "getBanners",
  array()
);


$route['others/set/contact'] = def(
  "others", PUB, "setContact",
  array(
    "name", "email", "telephone", "message"
  )
);




/*
 PLACE REDIRECTS
*/

//requer: name, telephone, email, message
$route['places/get/local'] = def(
  "places", PUB, "getLocal",
  array(
    "id",
    "lng",
    "lat"
  )
);

$route['places/set/bookmark'] = def(
  "places", PRIV, "setBookmark",
  array(
    "id",
    "lat",
    "lng"
  )
);

$route['places/set/bookmark/remove'] = def(
  "places", PRIV, "setBookmarkRemove",
  array(
    "id",
    "lat",
    "lng"
  )
);




$route['places/get/bookmarks'] = def(
  "places", PRIV, "getBookmarks",
  array(
    "lat",
    "lng"
  )
);

$route['places/get/comments'] = def(
  "places", PUB, "getComments",
  array(
    "id"
  )
);

$route['places/set/comment/like'] = def(
  "places", PRIV, "setCommentLike",
  array(
    "id",
    "lat",
    "lng"
  )
);


$route['places/set/comment/flag'] = def(
  "places", PRIV, "setCommentFlag",
  array(
    "id",
    "lat",
    "lng"
  )
);


$route['places/set/comment'] = def(
  "places", PRIV, "setComment",
  array(
    "id",
    "lat",
    "lng",
    "comment"
  )
);


$route['places/set/photo'] = def(
  "places", PRIV, "setPhoto",
  array(
    "id",
    "photo",
    "desc",
    "lat",
    "lng"
  )
);


$route['places/set/photo/flag'] = def(
  "places", PRIV, "setPhotoFlag",
  array(
    "id",
    "photo_url",
    "lat",
    "lng"
  )
);



//requer: name, telephone, email, message
$route['places/get/nearby'] = def(
  "places", PUB, "getNearby",
  array(
    "lat",
    "lng",
    "filters"
  )
);


//requer: name, telephone, email, message
$route['places/set/search'] = def(
  "places", PUB, "setSearch",
  array(
    "lat",
    "lng",
    "query"
  )
);


//requer: name, telephone, email, message
$route['places/set/holdback'] = def(
  "places", PRIV, "setHoldback",
  array(
    "lat",
    "lng",
    "type",
    "location",
    "obs"
  )
);

//requer: name, telephone, email, message
$route['places/set/holdback/flag'] = def(
  "places", PRIV, "setHoldbackFlag",
  array(
    "lat",
    "lng",
    "id"
  )
);


//requer: name, telephone, email, message
$route['places/set/review'] = def(
  "places", PRIV, "setReview",
  array(
    "lat",
    "lng",
    "id",
    "review_data"
  )
);

//requer: name, telephone, email, message
$route['places/get/review'] = def(
  "places", PRIV, "getReview",
  array(
    "id"
  )
);
