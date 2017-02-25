<?php 
//?ids=285001151561011,346211398819387&fields=posts.since(2016-08-26){message}
//utf8_decode
function Y_page($page,$token){
	date_default_timezone_set('UTC');
	$date = date("Y-m-d H:i:s");
	$date = new DateTime($date);
	$date = Y_dateformat($date);
	
	$query = "$page?fields=category,category_list,cover,description,emails,fan_count,id,name,link,location,phone,products,website,about,picture,username";
	
	$c = 0;
	$X = FB_get($query,$token);
	if( $X['status'] ){
		if(!isset($X['data'])){$X['data'][0] = $X;}
		
		foreach($X['data'] as $D){
			if(isset($D['username'])){
				$r['data'][$c]['username'] = ($D['username']);
			}else{
				$username = explode("/",$D['link']);
				$r['data'][$c]['username'] = ($username[3]);
			}
			
			$r['data'][$c]['id_page'] = (int) $D['id'];
			$r['data'][$c]['name'] = ($D['name']);
			$r['data'][$c]['category'] = $D['category'];
			
			$r['data'][$c]['category_list'] = "";
			if(isset($D['category_list'])){
			$r['data'][$c]['category_list'] = array();
			foreach($D['category_list'] as $cat){
				array_push($r['data'][$c]['category_list'],$cat['name']);
			}$r['data'][$c]['category_list'] = implode(";",$r['data'][$c]['category_list']);
			}
			
			$r['data'][$c]['link'] = "";
			if(isset($D['link'])){$r['data'][$c]['link'] = $D['link'];}
			
			$r['data'][$c]['fan_count'] = $D['fan_count'];
			
			$r['data'][$c]['website'] = "";
			if(isset($D['website'])){$r['data'][$c]['website'] = $D['website'];}
			
			$r['data'][$c]['cover'] = "";
			if(isset($D['cover']['source'])){$r['data'][$c]['cover'] = $D['cover']['source'];}
	
			$r['data'][$c]['picture'] = "";
			if(isset($D['picture']['data']['url'])){$r['data'][$c]['picture'] = $D['picture']['data']['url'];}
			
			$r['data'][$c]['about'] = "";
			if(isset($D['about'])){$r['data'][$c]['about'] = ($D['about']);}
			
			$r['data'][$c]['phone'] = "";
			if(isset($D['phone'])){$r['data'][$c]['phone'] = $D['phone'];}
			
			$r['data'][$c]['emails'] = "";
			if(isset($D['emails'])){
				$r['data'][$c]['emails'] = implode(";",$D['emails']);
			}
			
			$description = "";
			if(isset($D['description'])){
				$descripion = $D['description'];
			}$r['data'][$c]['description'] = ($description);
			
			$variable = "";
			if(isset($D['location']['city'])){
				$variable = $D['location']['city'];
			}$r['data'][$c]['city'] = ($variable);
			
			$variable = "";
			if(isset($D['location']['country'])){
				$variable = $D['location']['country'];
			}$r['data'][$c]['country'] = ($variable);
			
			$variable = "";
			if(isset($D['location']['latitude'])){
				$variable = $D['location']['latitude'];
			}$r['data'][$c]['latitude'] = $variable;
			
			$variable = "";
			if(isset($D['location']['longitude'])){
				$variable = $D['location']['longitude'];
			}$r['data'][$c]['longitude'] = $variable;
			
			$variable = "";
			if(isset($D['location']['street'])){
				$variable = $D['location']['street'];
			}$r['data'][$c]['street'] = ($variable);
			
			$variable = "";
			if(isset($D['location']['zip'])){
				$variable = $D['location']['zip'];
			}$r['data'][$c]['zip'] = ($variable);
			
			$variable = "";
			if(isset($D['products'])){
				$variable = $D['products'];
			}$r['data'][$c]['products'] = ($variable);
			
			$c++;
		}
	}
	
	$r['status'] = $X['status'];
	$r['message'] = $X['message'];
	return $r;
}

function Y_feed($fanpage,$token,$since = "",$until = "",$limit = 100,$fan_count = 0){
	date_default_timezone_set('UTC');
	$date = date("Y-m-d H:i:s");
	$date = new DateTime($date);
	
	$query = "$fanpage/posts?fields=message,reactions.summary(true),";
	$query .= "comments{comment_count},attachments,shares,from,full_picture,created_time,";
	$query .= "id,object_id,link,type,picture,permalink_url&limit=$limit";
	
	if($since!=""){$query .= "&since=$since";}
	if($until!=""){$query .= "&until=$until";}
	
	$r['data'] = array();
	$c = 0;
	$r['until'] = "";
	$r['since'] = "";
	
	$fbdata = FB_get($query,$token);
	if( $fbdata['status'] ){
		if( sizeof($fbdata['data']!=0) ){
			$r['status'] = true;
			foreach($fbdata['data'] as $x){
				$object_id = explode("_",$x['id']);
				$id_post = (int) $object_id[1]; 
				$created_time = $x['created_time'];
				$id_page = (int) $x['from']['id'];
				$permalink_url = $x['permalink_url'];
				
				$link = "";
				if(isset($x['link'])){$link = $x['link'];}
				
				//Asignar tipo
				$type = $x['type'];
				$gif = explode(".",$link);
				$gif = $gif[sizeof($gif)-1];
				if($gif=="GIF" || $gif=="gif" || $gif == "Gif"){
					$type = "gif";
				}
				if (strpos($link, 'giphy.com/') !== false) {$type = "gif";}
				if (strpos($link, 'gph.is/') !== false) {$type = "gif";}
				if (strpos($link, '.gif') !== false) {$type = "gif";}
				if (strpos($link, 'youtube.com/') !== false) {$type = "video";}
				if (strpos($link, 'youtu.be/') !== false) {$type = "video";}
				if (strpos($link, 'veoh.com/') !== false) {$type = "video";}
				if (strpos($link, 'soundcloud.com/') !== false) {$type = "audio";}
				if (strpos($link, 'spotify.com/') !== false) {$type = "audio";}
				
				
				$message = "";
				if(isset($x['message'])){$message = utf8_decode($x['message']);}
				
				$picture = "";
				if(isset($x['picture'])){$picture = $x['picture'];}
				
				$reactions = 0;
				if(isset($x['reactions']['summary']['total_count'])){
					$reactions = $x['reactions']['summary']['total_count'];
				}
				
				$shares = 0;
				if(isset($x['shares']['count'])){
					$shares = $x['shares']['count'];
				}
				
				$comments = 0;
				if(isset($x['comments']['data'][0]['comment_count'])){
					$comments = $x['comments']['data'][0]['comment_count'];
				}	
				
				$full_picture = "";
				if(isset($x['full_picture'])){$full_picture = $x['full_picture'];}	
				
				$elements = 1;
				//galería de fotos
				if(isset( $x['attachments']['data'][0]['subattachments'] )){
					$type = "album";
					$photos = array();
					foreach( $x['attachments']['data'][0]['subattachments']['data'] as $media) {
						//echo sizeof($media)."<br />";
						array_push($photos,$media['media']['image']['src']);
					}
					$elements = sizeof($photos);
					$full_picture = implode(";",$photos);
				}	
				
				//Pasar a un array
				$r['data'][$c]['id_post'] = $id_post;
				$r['data'][$c]['created_time'] = $created_time;
				$r['data'][$c]['updated_time'] = date_format($date,"Y-m-d")."T".date_format($date,"H:i:s+0000");
				$r['data'][$c]['id_page_post'] = $id_page;
				$r['data'][$c]['link'] = $link;
				$r['data'][$c]['permalink_url'] = $permalink_url;
				$r['data'][$c]['type'] = $type;
				$r['data'][$c]['message'] = $message;
				$r['data'][$c]['picture'] = $picture;
				$r['data'][$c]['full_picture'] = $full_picture;
				$r['data'][$c]['elements'] = $elements;
				
				$r['data'][$c]['reactions'] = $reactions;
				$r['data'][$c]['shares'] = $shares;
				$r['data'][$c]['comments'] = $comments;
				
				if($fan_count == 0){
					$r['data'][$c]['reactions_rate'] = 0;
					$r['data'][$c]['shares_rate'] = 0;
					$r['data'][$c]['comments_rate'] = 0;
				}else{
					$r['data'][$c]['reactions_rate'] = round($reactions/$fan_count,5)*100;
					$r['data'][$c]['shares_rate'] = round($shares/$fan_count,5)*100;
					$r['data'][$c]['comments_rate'] = round($comments/$fan_count,5)*100;
				}
				$c++;
			}
		}
	}
	$r['status'] = $fbdata['status'];
	$r['message'] = $fbdata['message'];
	return $r;
}

function Y_dateformat($phpdate){
 return date_format($phpdate,"Y-m-d")."T".date_format($phpdate,"H:i:s+0000");
}

function Q_dateformat($phpdate){
 return date_format($phpdate,"Y-m-d")." ".date_format($phpdate,"H:i:s");
}

function menu_redes($data,$file = "cuentas.php",$get = ""){
  foreach($data as $p){
	  echo '<a href="'.$file.'?page='.$p['id_page'].$get.'">
	  <div class="row option">
		<div class="c2 txc pa5">
		  <img class="suave res w7" src="'.$p['picture'].'" width="160" height="160" /> 
		</div>
		<div class="c10 text">
		  <strong>'.utf8_encode($p['name']).'</strong><br />
		  '.number_format($p['fan_count'],0,","," ").' Likes
		</div>
	  </div>
	  </a>';
   }
}

function enteros($x){
	return number_format($x,0,","," ");
}

function dobles($x){
	return number_format($x,2,"."," ");
}

function Y_get_posts($page,$token,$since = "",$until = "",$limit = 80){	
	//Decidir si elegir por id_page o por username
	$id_page = Q_select("tPages",array("id_page","username","fan_count"),array( "id_page" => $page) );
	if( sizeof($id_page['data'] ) == 0 ){
		unset($id_page);
		$id_page = Q_select("tPages",array("id_page","username","fan_count"),array( "username" => $page) );
	}
	$fan_count = $id_page['data'][0]['fan_count'];
	$id_page = $id_page['data'][0]['id_page'];
	
	
	//Conseguir Post actuales
	$sele = Q_select("tPosts", array("id_post","id_page_post"), array("id_page_post" => $id_page,
	"ORDER BY id_post DESC LIMIT 110") );
	$stored = array();
	foreach($sele['data'] as $d){
		array_push($stored,$d['id_post']);
	}
	
	//Conseguir Posts nuevos
	$posts = Y_feed($page,$token,$since,$until,$limit,$fan_count);
	if( $posts['status'] ){
		//Insertar o Update cada Post
		$posts['id_page'] = $id_page;
		$fields = Q_fields("tPosts");
		foreach($posts['data'] as $data){
			if( in_array($data["id_post"],$stored) ){
				$upd = Q_update("tPosts",$data,$data["id_post"],array(
					"updated_time",
					"reactions",
					"shares",
					"link",
					"permalink_url",
					"comments",
					"reactions_rate",
					"shares_rate",
					"comments_rate"));
			}else{
				$ins = Q_insert("tPosts",$data,$fields);
			}
		}
	}
	
	return $posts;
}

function Y_job_set( $object_id, $command, $minutes = 720 , $executed = 0){
	date_default_timezone_set('UTC');
	$date = date("Y-m-d H:i:s"); $date = new DateTime($date);
	$program_date = Y_dateformat($date);
	$minutes = $minutes;
	$date->add(new DateInterval('PT' . $minutes . 'M'));
	$schedule_date = Y_dateformat($date);

	$fields = array("schedule_date","program_date","command","object_id","executed");
	
	$data["program_date"] = $program_date;
	$data["schedule_date"] = $schedule_date;
	$data["command"] = $command;
	$data["object_id"] = $object_id;
	$data["executed"] = $executed;
	
	$ins = Q_insert("tSchedule",$data,$fields);
	
	return (bool) $ins['status'];
}
function Y_job_executed($id_schedule,$delete = false){
	date_default_timezone_set('UTC');
	$date = date("Y-m-d H:i:s"); $date = new DateTime($date);
	$date = Y_dateformat($date);
	
	$data['executed'] = 1;
	$data['executed_date'] = $date;
	
	if($delete){
		$com['status'] = Q_delete("tSchedule",array("id_schedule" => $id_schedule));
	}else{
		$com = Q_update("tSchedule",$data,$id_schedule,array("executed","executed_date"));
	}
	
	
	return (bool) $com['status'];
}


?>