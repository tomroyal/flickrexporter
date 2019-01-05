<?php

// get original url, title, desc, tags, privacy for all photos in flickr account

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

// work out number of pages to process
$pics_proc = 0;
$meta = getPhotos(50,1); // perpage, page
$total_pages = $meta->photos->pages;

echo("\r\n Total pages: ".$total_pages);

// iterate by page
for ($this_page = 1; ($this_page <= $total_pages); $this_page++) {
    echo("\r\n Doing page: ".$this_page." pics done so far :".$pics_proc);
    // get photos
    $page_pics = getPhotos(50,$this_page); // perpage, page
    // iterate photos
    foreach($page_pics->photos->photo AS $a_photo){
      $pics_proc++;  
      // get original pic url
      $a_photo -> full_url = buildPicUrl($a_photo);
      //  now store the info from $a_photo in your database, and download $a_photo->full_url via CURL  
    }
};       



?>