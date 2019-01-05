<?php

// get original url, title, desc, tags, privacy for all photos in flickr account
// store in db for later use

// flickr api key and secret
$fak = '';
$fas = '';

// user oauth key and secret
$oat = '';
$oas = '';

// flickr user id
$user_id = '';

function oa_enc($s){
    // encode urls to oauth standard
    $s = rawurlencode($s);
    $s = str_replace('%7E', '~', $s);
    return($s);
}

function signreq($cs,$ct,$method,$url,$params){
    // get signature for a flickr request
    // make key
    $key = $cs.'&'.$ct;
    
    // encode other bits
    $method = oa_enc($method);
    $url = oa_enc($url);
    $params = oa_enc($params);
    
    // build string
    $basestring = $method.'&'.$url.'&'.$params;
    // error_log('basestring '.$basestring);
    // sign
    $signature = base64_encode(hash_hmac('sha1', $basestring, $key, true));
    return($signature);
}

function getPhotos($per_page,$page){
  // get a page of photo data from api
  
  $r_nonce = md5(time()-51); // yes, this is very lazy..
  $r_time = time();
  global $fak;
  global $oat;
  global $oas;
  global $user_id;

  // build params
  $params = 'extras=date_taken,description,original_format,tags&format=json&oauth_consumer_key='.$fak.'&oauth_token='.$oat.'&method=flickr.people.getPhotos&nojsoncallback=1&page='.$page.'&per_page='.$per_page.'&user_id='.$user_id;
  $r_sig = signreq($fas,$oas,'GET','https://api.flickr.com/services/rest',$params);

  $url = 'https://api.flickr.com/services/rest?'.$params.'&oauth_signature='.$r_sig;  

  // fetch data
  $curl = curl_init();
  curl_setopt_array($curl, array(
      CURLOPT_RETURNTRANSFER => 1,
      CURLOPT_URL => $url
  ));
  $fl_resp = curl_exec($curl);
  curl_close($curl);

  // decode and return
  $pic_data = json_decode($fl_resp);
  return($pic_data);
};

function buildPicUrl($a_photo){
  // build url for original upload image
  // note - pics from 2004ish seem to not have these? Build an 'z' sized pic instead
  $pic_url = 'https://farm'.$a_photo->farm.'.staticflickr.com/'.$a_photo->server.'/'.$a_photo->id.'_'.$a_photo->originalsecret.'_o.'.$a_photo->originalformat.'';
  return($pic_url);
}

function buildPicPriv($a_photo){
  // convert multiple privacy settings to a single integer - 0 public, 1 friends, 2 family, 3 private
  if ($a_photo->ispublic == 1){
    $priv = 0;
  }
  else if ($a_photo->isfriend == 1){
    $priv = 1;
  }
  else if ($a_photo->isfamily == 1){
    $priv = 2;
  }
  else {
    $priv = 3;
  };
  return($priv);
}

function getSets(){
  // get all your photosets
  
  $r_nonce = md5(time()-51); // yes, this is very lazy..
  $r_time = time();
  global $fak;
  global $oat;
  global $oas;
  global $user_id;

  // build params
  $params = 'extras=date_taken,description,original_format,tags&format=json&oauth_consumer_key='.$fak.'&oauth_token='.$oat.'&method=flickr.photosets.getList&nojsoncallback=1&user_id='.$user_id;
  $r_sig = signreq($fas,$oas,'GET','https://api.flickr.com/services/rest',$params);

  $url = 'https://api.flickr.com/services/rest?'.$params.'&oauth_signature='.$r_sig;  

  // fetch data
  $curl = curl_init();
  curl_setopt_array($curl, array(
      CURLOPT_RETURNTRANSFER => 1,
      CURLOPT_URL => $url
  ));
  $fl_resp = curl_exec($curl);
  curl_close($curl);

  // decode and return
  $pic_data = json_decode($fl_resp);
  return($pic_data);
};

function getSetPics($theset){
  // get all the images in a set, as serialized data
  
  $r_nonce = md5(time()-51); // yes, this is very lazy..
  $r_time = time();
  global $fak;
  global $oat;
  global $oas;
  global $user_id;

  // build params
  $params = 'extras=date_taken,description,original_format,tags&format=json&oauth_consumer_key='.$fak.'&oauth_token='.$oat.'&method=flickr.photosets.getPhotos&nojsoncallback=1&photoset_id='.$theset.'&user_id='.$user_id;
  $r_sig = signreq($fas,$oas,'GET','https://api.flickr.com/services/rest',$params);

  $url = 'https://api.flickr.com/services/rest?'.$params.'&oauth_signature='.$r_sig;  

  // fetch data
  $curl = curl_init();
  curl_setopt_array($curl, array(
      CURLOPT_RETURNTRANSFER => 1,
      CURLOPT_URL => $url
  ));
  $fl_resp = curl_exec($curl);
  curl_close($curl);

  // decode and return
  $pic_data = json_decode($fl_resp);
  return($pic_data);
};

$sets = getSets();

foreach($sets->photosets->photoset AS $a_set){
    echo("\r\nid ".$a_set->id.' name '.$a_set->title->_content);
    $pics = getSetPics($a_set->id);
    $setpics = array();
    foreach($pics->photoset->photo AS $set_photo){
      array_push($setpics,$set_photo->id);
    };
    $serialpics = serialize($setpics);
    echo("\r\n".$serialpics);
    // .. and now stash id, title and serialpics in your db
}


?>