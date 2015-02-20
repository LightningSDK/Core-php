<?php

if ($user->details['type'] < 5) {
	header("Location: user.php?redirect=blog_comments.php");
	exit;
}

$smarty->assign('full_width', TRUE);
	include 'include/class_table.php';

	$table = new table();

	$table->action_fields['approve'] = array("display_name"=>"Approve","type"=>"checkbox");
	$table->action_fields['delete'] = array("display_name"=>"Delete","type"=>"checkbox");

	$table->preset['time'] = array("Type"=>"datetime");
	$table->preset['blog_comment_id'] = array("Type"=>"hidden");
	$table->preset['blog_id'] = array("Type"=>"hidden");
	$table->preset['user_id'] = array("Type"=>"hidden");
	$table->preset['ip_address'] = array("List"=>"false");
	$table->preset['email_address'] = array("List"=>"false");
	$table->preset['website'] = array("List"=>"false");
	$table->preset['approved'] = array("List"=>"false");
  $table->row_click = FALSE;

	$table->table = "blog_comment";
	$table->key = "blog_comment_id";
	$table->sort = "time DESC";
	if ($_GET['all']==1)
		$table->list_where = "approved >= 0";
	else
		$table->list_where = "approved = 0";
	$table->execute_task();
	$smarty->assign('table',$table);

	if (is_array($_POST['taf_approve']))
		foreach($_POST['taf_approve'] as $id => $v)
			$db->query("UPDATE blog_comment SET approved = 1 WHERE blog_comment_id = $id");
	if (is_array($_POST['taf_delete']))
		foreach($_POST['taf_delete'] as $id => $v)
			$db->query("UPDATE blog_comment SET approved = -1 WHERE blog_comment_id = $id");


$page = "table";


include "include/footer.php";
