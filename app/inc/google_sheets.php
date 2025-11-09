<?php
require_once __DIR__ . '/functions.php';
function cc_sheets_cfg(){ $cfg=cc_env(); return $cfg['google_sheets'] ?? ['enabled'=>false]; }
function cc_sheets_enabled(){ $gs=cc_sheets_cfg(); return !empty($gs['enabled']) && !empty($gs['sheet_id']) && is_file($gs['service_account_json']); }
function cc_sheets_get_token(){
  $gs=cc_sheets_cfg(); $cache=sys_get_temp_dir().'/cc_google_token.json';
  if(is_file($cache)){ $d=json_decode(file_get_contents($cache),true); if(!empty($d['access_token']) && !empty($d['expires_at']) && time()<$d['expires_at']-60){ return $d['access_token']; } }
  $json=json_decode(file_get_contents($gs['service_account_json']),true); if(!$json) throw new Exception('Invalid service account JSON');
  $header=['alg'=>'RS256','typ'=>'JWT']; $now=time();
  $claim=['iss'=>$json['client_email'],'scope'=>'https://www.googleapis.com/auth/spreadsheets','aud'=>'https://oauth2.googleapis.com/token','exp'=>$now+3600,'iat'=>$now];
  $enc=function($o){ return rtrim(strtr(base64_encode(json_encode($o)), '+/','-_'), '='); };
  $segments=[$enc($header),$enc($claim)]; $signing_input=implode('.',$segments);
  $pkey=openssl_pkey_get_private($json['private_key']); if(!$pkey) throw new Exception('Invalid private key');
  $sig=''; openssl_sign($signing_input,$sig,$pkey,'sha256');
  $jwt=$signing_input.'.'.rtrim(strtr(base64_encode($sig), '+/','-_'), '=');
  $post=http_build_query(['grant_type'=>'urn:ietf:params:oauth:grant-type:jwt-bearer','assertion'=>$jwt]);
  $opts=['http'=>['method'=>'POST','header'=>"Content-Type: application/x-www-form-urlencoded\r\n",'content'=>$post,'timeout'=>10]];
  $resp=file_get_contents('https://oauth2.googleapis.com/token', false, stream_context_create($opts));
  if($resp===false) throw new Exception('Failed to fetch access token');
  $d=json_decode($resp,true); if(empty($d['access_token'])) throw new Exception('No access token: '.$resp);
  $d['expires_at']=$now+(int)($d['expires_in']??3600); file_put_contents($cache,json_encode($d)); return $d['access_token'];
}
function cc_sheets_append($sheetId,$range,$values,$valueInputOption='RAW'){
  $token=cc_sheets_get_token(); $url="https://sheets.googleapis.com/v4/spreadsheets/{$sheetId}/values/".rawurlencode($range).":append?valueInputOption=".$valueInputOption;
  $body=json_encode(['values'=>$values]);
  $opts=['http'=>['method'=>'POST','header'=>"Content-Type: application/json\r\nAuthorization: Bearer {$token}\r\n",'content'=>$body,'timeout'=>10]];
  $resp=file_get_contents($url,false,stream_context_create($opts)); if($resp===false) throw new Exception('Sheets append failed'); return json_decode($resp,true);
}
function cc_sheets_append_registration($row){
  if(!cc_sheets_enabled()) return false; $gs=cc_sheets_cfg();
  $values=[[
    $row['id'] ?? '', $row['registration_type'] ?? '', $row['team_name'] ?? '',
    $row['student_name'] ?? '', $row['grade'] ?? '', $row['school'] ?? '',
    $row['guardian_email'] ?? '', $row['category'] ?? '',
    $row['writer_count'] ?? '', $row['extra_writers'] ?? '', $row['fee'] ?? '',
    $row['created_at'] ?? date('c'),
  ]];
  return cc_sheets_append($gs['sheet_id'],$gs['range'],$values,$gs['value_input_option'] ?? 'RAW');
}
