<?php

namespace Pages;

use Lightning\Tools\Blog;
use Lightning\Tools\ClientUser;
use Lightning\Tools\Database;
use Lightning\Tools\Request;
use Lightning\Tools\Table;
use Lightning\Tools\Template;

$blog_id = Request::get('id', 'int') | Request::get('blog_id', 'int');
$blog_url = Request::get('request');
$action = Request::get('action');

// AUTHORIZE A BLOG COMMENT
switch ($action) {
  case 'post_comment_check':
    echo md5($_POST['email'].$_POST['name'].$_POST['comment']);
    exit;

  case 'post_comment':
    // FIRST CHECK FOR SPAM
    if($_POST['check_val'] == md5($_POST['email'].$_POST['name'].$_POST['comment'])){
      $values = array(
          'blog_id' => $blog_id,
          'ip_address' => Request::server('ip_int'),
          'email_address' => Request::post('email', 'email'),
          'name' => Request::post('name'),
          'comment' => Request::post('comment'),
          'time' => time(),
      );
      Database::getInstance()->insert('blog_comment', $values);
      echo "success";
    }
    else {
      echo "spam error";
    }
    exit;

  case 'remove_blog_comment':
    $user = ClientUser::getInstance();
    if ($user->details['type'] >= 5 && $_POST['blog_comment_id'] > 0) {
      Database::getInstance()->delete('blog_comment', array('blog_comment_id' => Request::post('blog_comment_id', 'int')));
      echo "ok";
    } else {
      echo "access denied";
    }
    exit;

  case 'approve_blog_comment':
    $user = ClientUser::getInstance();
    if ($user->details['type'] >= 5 && $_POST['blog_comment_id'] > 0) {
      Database::getInstance()->update('blog_comment', array('approved' => 1), array('blog_comment_id' => Request::post('blog_comment_id', 'int')));
      echo "ok";
      exit;
    }
}

// SEE IF A SPECIFIC BLOG ARTICLE IS BEING REQUESTED
$blog = Blog::getInstance();
if($blog_id > 0) {
  $blog_id = $blog->fetch_blog_id($blog_id);
}
elseif (!empty($blog_url)) {
  $blogroll = explode("/",$blog_url);
  if($blogroll[0] == "blog" && $blogroll[1] == 'page'){
    $blog->page = intval($blogroll[2]);
  } else {
    $blog_id = $blog->fetch_blog_url($blog_url);
  }
}
if($blog->id > 0) {
  $page_time = max($blog->posts[0]['time'],$blog->posts[0]['comments'][count($blog->posts[0]['comments'])-1]['time']);
}

// SEE IF A SPECIFIC CATEGORY IS BEING REQUESTED
if (isset($_GET['blog_cat'])) {
  $blog->category = $_GET['blog_cat'];
}

// SEE IF THE ARCHIVE IS BEING REQUESTED
if (isset($_GET['archive'])) {
  $archive = str_replace('.htm','',$_GET['archive']);
  $archive = explode("-",$archive);
  if(count($archive) == 2)
    $blog->page = intval($archive[1]);
  $archive = explode("/",$archive[0]);
  $blog->y = intval($archive[0]);
  $blog->m = intval($archive[1]);
}

$template = Template::getInstance();
if($user->details['type'] >= 5){
  $template->set('full_width',TRUE);
  // IF THIS IS AN ADMIN USER, LET THEM EDIT THE BLOG
//  include 'include/class_table.php';

  $blog_table = new Table(array(
    'trusted' => true,
    'action_file' => '/blog',
    'table' => 'blog',
    'key' => 'blog_id',
    'sort' => 'time DESC',
    'preset' => array(
      'url' => array("display_function"=>function($row){ blog::create_url($row['title']); }),
      'time' => array("Type"=>"datetime"),
      'user_id' => array("Type"=>"hidden","Value"=>$user->id),
      'blog_id' => array("Type"=>"hidden"),
      'url' => array("unlisted"=>true),
      'body' => array("width"=>"full","Type"=>"div"),
    ),
    'links' => array(
      'categories' => array('index'=>'blog_categories','key'=>'cat_id',"display_column"=>"category","list"=>"true"),
    ),
  ));
//  $blog_table->post_actions['after_post'] = 'post_to_facebook';
  $template->set('blog_table',$blog_table);
  if ($blog_id == 0) {
    $template->set('page_section','table');
  } elseif (Request::type() == 'GET') {
    $blog_table->id = $blog_id;
    $blog_table->action = 'edit';
    $template->set('page_section','blog');
  }
  $blog_table->execute_task();
} elseif ($blog_id > 0) {
  // IF THERE IS A SPECIFIC BLOG, SHOW IT
  $template->set('page_section','blog');
} else {
  // SHOW THE BLOGROLL OR ARCHIVE
  $blog->list_post();
  if(count($blog->posts) > 1)
    $blog->shorten_body = true;
  $template->set('page_section','blog_list');
}
if(count($blog->posts) == 1){
  $page_description = htmlspecialchars($blog->body($blog->posts[0]['body'],true));
  $page_title = htmlspecialchars($blog->posts[0]['title'])." : ".$page_title;
  if($blog->posts[0]['keywords'] != "")
    $page_keywords = str_replace("*", $page_keywords, $blog->posts[0]['keywords']);
}

$template->set('content', 'blog');

function post_to_facebook($blog_data) {
  require_once HOME_PATH . '/include/facebook/facebook.php';

  $facebook = new Facebook(array(
    'appId'  => FACEBOOK_APP_ID,
    'secret' => FACEBOOK_APP_SECRET,
    'cookie' => true,
    'scope' => 'manage_pages',
  ));

  $user_id = $facebook->getUser();
  print_r($user_id);
  $access_token = $facebook->getAccessToken();
  print_r($access_token);

  $attachment = array(
    'access_token' => $access_token,
    'message' => 'this is my message',
    'name' => 'name',
    'link' => ROOT_URL . $blog_data['url'] . '.htm',
    'caption' => $blog_data['title'],
    'description' => $blog_data['title'],
  );
  if ($image = get_first_image($blog_data['body'])) {
    $attachment['picture'] = $image;
  }
  $facebook->api(FACEBOOK_BLOG_PAGE . '/feed', 'post', $attachment);
}

function get_first_image($source) {
  preg_match('/<img(.*)>/i', $source, $results);
  preg_match('/src[= "\']+(.*)["\']/i', $results[1], $results);
  $url = $results[1];
  if (preg_match('%^/%', $url)) {
    $url = ROOT_URL . $url;
  }
  return $url;
}

require_once 'include/footer.php';
