<div id='content'>
<div id='inner-content'>
<? use Lightning\Pages\Page;

if ($editable): ?>
<div class="page_edit_links">
	<a href='page.php?action=new'>New Page</a> | <a href='#' onclick='edit_page();return false;'>Edit This Page</a>
</div>
	<script language='javascript' src='/ckeditor/ckeditor.js'>
	</script>
<? endif; ?>

	<? if ($editable): ?>
	<div class='page_edit' <? if (empty($action) || $action != 'new'): ?>style="display:none;"<? endif; ?>>
			<input type="button" name="submit" class='save_button' onclick="save_page()" value="Save" /><br />
		<? if (!empty($action) && $action == 'new'): ?>
			<input type="hidden" name="action" id='page_action' value="submit_new" />
		<? else: ?>
			<input type="hidden" name="action" id='page_action' value="update_page" />
		<? endif; ?>
			<input type="hidden" name="page_id" id='page_id' value="<?= !empty($full_page['page_id']) ? $full_page['page_id'] : 0 ?>" />
			<table border='0'>
			<tr><td>Title:</td><td><input type="text" name="title" id='page_title' value="<?=$full_page['title']?>" /></td></tr>
			<tr><td>URL:</td><td><input type="text" name="url" id='page_url' value="<?=$full_page['url']?>" /></td></tr>
			<tr><td>Description:</td><td><input type="text" name="description" id='page_description' value="<?=$full_page['description']?>" /></td></tr>
			<tr><td>Keywords:</td><td><input type="text" name="keywords" id='page_keywords' value="<?=$full_page['keywords']?>" /></td></tr>
			<tr><td>Include in site map:</td><td><input type="checkbox" name="sitemap" id='page_sitemap' value="1" <? if ( $full_page['site_map'] == 1):?>checked="true"<? endif; ?> /></td></tr>
			<tr><td>Hide Side Bar:</td><td><?= Page::layoutOptions($full_page['layout']); ?></td></tr>
			</table>
	</div>
	<? endif; ?>
  <?= \Lightning\Tools\CKEditor::editableDiv('page_display', array('spellcheck' => true, 'content' => $full_page['body'])); ?>
	<? if ($editable):?>
		<input type="button" name="submit" class='save_button page_edit' onclick="save_page();" value="Save" <? if (empty($action) || $action != 'new'):?>style="display:none;"<? endif; ?> /><br />
	<? endif; ?>

			<?= $this->_include('social_links'); ?>

  <div data-src="<?=$full_page['url']?>" class="OUTBRAIN" ></div>
  <script type="text/javascript">(function(){window.OB_platformType=8;window.OB_langJS="http://widgets.outbrain.com/lang_en.js";window.OBITm="1394419150171";window.OB_recMode="brn_strip";var ob=document.createElement("script");ob.type="text/javascript";ob.async=true;ob.src="http"+("https:"===document.location.protocol?"s":"")+"://widgets.outbrain.com/outbrainLT.js";var h=document.getElementsByTagName("script")[0];h.parentNode.insertBefore(ob,h);})();</script>

</div>
</div>