<?php echo $header; ?>

<div id="content">
	<div class="breadcrumb">
		<?php foreach ($breadcrumbs as $breadcrumb) { ?>
			<?php echo $breadcrumb['separator']; ?><a href="<?php echo $breadcrumb['href']; ?>"><?php echo $breadcrumb['text']; ?></a>
		<?php } ?>
	</div>

	<div class="box">
		<div class="left"></div>
		<div class="right"></div>
		
		<div class="heading">
			<h1 style="background-image: url('view/image/feed.png') no-repeat;"><?php echo $lang['heading_title']; ?></h1>
			<div class="buttons">
				<!-- <a href="<?php echo $clear_relations; ?>" class="button clear-relations"><?php echo $lang['button_crear_relations']; ?></a> -->
				<a onclick="$('#form').submit();" class="button"><span><?php echo $lang['button_save']; ?></span></a>
				<a href="<?php echo $cancel; ?>" class="button"><span><?php echo $lang['button_cancel']; ?></span></a>
			</div>
		</div>
		
		<div class="content">
			<div id="tabs" class="htabs">
				<a href="#tab-import"><?php echo $lang['tab_import']; ?></a>
			</div>
			
			<form action="<?php echo $action; ?>" method="post" enctype="multipart/form-data" id="form">	
				<div id="tab-import">
					<table class="form">
						<tr>
							<td><?php echo $lang['entry_pricetype']; ?></td>
							<td><input name="e1c_import_pricetype" type="text" value="<?php echo $config['e1c_import_pricetype']; ?>" /></td>
						</tr>
					</table>
				</div>
			</form>
		</div>

		<div style="text-align: center; line-height: 1.4; padding-top: 20px;">
			<div><?php echo $lang['footer_version']; ?> <?php echo $version; ?></div>
			
			<?php if ($update) { ?>
				<div>
					<a href="https://github.com/ethernet1/opencart-exchange1c" target="_blank">
						<?php echo $lang['footer_updates_yes']; ?>
					</a>
				</div>
			<?php } ?>

		</div>
	</div>
</div>

<script type="text/javascript"><!--
$('#tabs a').tabs();
//--></script>

<script type="text/javascript"><!--
$('.clear-relations').click(function(e) {
	e.preventDefault();
	var href = $(this).attr('href');

	$.ajax(href, {
		method: 'get',
		success: function() {
			alert("<?php echo $lang['message_complete']; ?>");
		}
	});
});
//--></script>

<?php echo $footer; ?>