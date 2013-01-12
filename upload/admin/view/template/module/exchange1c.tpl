<?php echo $header; ?>

<div id="content">

  <div class="breadcrumb">
    <?php foreach ($breadcrumbs as $breadcrumb) { ?>
    <?php echo $breadcrumb['separator']; ?><a href="<?php echo $breadcrumb['href']; ?>"><?php echo $breadcrumb['text']; ?></a>
    <?php } ?>
  </div>

  <?php if ($error_warning) { ?>
  <div class="warning"><?php echo $error_warning; ?></div>
  <?php } ?>

  <div class="box">
    <div class="left"></div>
    <div class="right"></div>
    <div class="heading">
      <h1 style="background-image: url('view/image/feed.png') no-repeat;"><?php echo $heading_title; ?></h1>
      <div class="buttons"><a onclick="$('#form').submit();" class="button"><span><?php echo $button_save; ?></span></a><a onclick="location = '<?php echo $cancel; ?>';" class="button"><span><?php echo $button_cancel; ?></span></a></div>
    </div>
    <div class="content">
      <div id="tabs" class="htabs">
        <a href="#tab-general"><?php echo $text_tab_general; ?></a>
        <a href="#tab-product"><?php echo $text_tab_product; ?></a>
        <a href="#tab-order"><?php echo $text_tab_order; ?></a>
        <a href="#tab-manual"><?php echo $text_tab_manual; ?></a>
      </div>

    
      <form action="<?php echo $action; ?>" method="post" enctype="multipart/form-data" id="form">
        
        <div id="tab-general">
          <table class="form">
            <tr>
              <td><?php echo $entry_username; ?></td>
              <td><input name="exchange1c_username" type="text" value="<?php echo $exchange1c_username; ?>" /></td>
            </tr>        
            <tr>
              <td><?php echo $entry_password; ?></td>
              <td><input name="exchange1c_password" type="password" value="<?php echo $exchange1c_password; ?>" /></td>
            </tr>
            
            <tr>
              <td><?php echo $entry_status; ?></td>
              <td><select name="exchange1c_status">
                  <?php if ($exchange1c_status) { ?>
                    <option value="1" selected="selected"><?php echo $text_enabled; ?></option>
                    <option value="0"><?php echo $text_disabled; ?></option>
                  <?php } else { ?>
                    <option value="1"><?php echo $text_enabled; ?></option>
                    <option value="0" selected="selected"><?php echo $text_disabled; ?></option>
                  <?php } ?>
                </select></td>
            </tr>

            <tr>
              <td><?php echo $entry_allow_ip; ?></td>
              <td>
                <textarea name="exchange1c_allow_ip" style="width: 200px; height: 50px;"><?php echo $exchange1c_allow_ip; ?></textarea>
              </td>
            </tr>
          </table>
        </div>
        
        <div id="tab-product">
          <table class="form">

            <tr>
              <td><?php echo $entry_price_type; ?></td>
              <td>
                <input name="exchange1c_price_type" type="text" value="<?php echo $exchange1c_price_type; ?>" />
              </td>
            </tr>

            <tr>
              <td><label for="exchange1c_flush_product"><?php echo $entry_flush_product; ?></label></td>
              <td>
                <input type="checkbox" value="1" id="exchange1c_flush_product" name="exchange1c_flush_product" <?php echo ($exchange1c_flush_product == 1)? 'checked' : ''; ?>>
              </td>
            </tr>

            <tr>
              <td><label for="exchange1c_flush_category"><?php echo $entry_flush_category; ?></label></td>
              <td>
                <input type="checkbox" value="1" id="exchange1c_flush_category" name="exchange1c_flush_category" <?php echo ($exchange1c_flush_category == 1)? 'checked' : ''; ?>>
              </td>
            </tr>
            
            <tr>
              <td><label for="exchange1c_flush_manufacturer"><?php echo $entry_flush_manufacturer; ?></label></td>
              <td>
                <input type="checkbox" value="1" id="exchange1c_flush_manufacturer" name="exchange1c_flush_manufacturer" <?php echo ($exchange1c_flush_manufacturer == 1)? 'checked' : ''; ?>>
              </td>
            </tr>

            <tr>
              <td><label for="exchange1c_flush_attribute"><?php echo $entry_flush_attribute; ?></label></td>
              <td>
                <input type="checkbox" value="1" id="exchange1c_flush_attribute" name="exchange1c_flush_attribute" <?php echo ($exchange1c_flush_attribute == 1)? 'checked' : ''; ?>>
              </td>
            </tr>
    
            <tr>
              <td><label for="exchange1c_flush_quantity"><?php echo $entry_flush_quantity; ?></label></td>
              <td>
                <input type="checkbox" value="1" id="exchange1c_flush_quantity" name="exchange1c_flush_quantity" <?php echo ($exchange1c_flush_quantity == 1)? 'checked' : ''; ?>>
              </td>
            </tr>

            <tr>
              <td><label for="exchange1c_fill_parent_cats"><?php echo $entry_fill_parent_cats; ?></label></td>
              <td>
                <input type="checkbox" value="1" id="exchange1c_fill_parent_cats" name="exchange1c_fill_parent_cats" <?php echo ($exchange1c_fill_parent_cats == 1)? 'checked' : ''; ?>>
              </td>
            </tr>

            <tr>
              <td><label for="exchange1c_seo_url"><?php echo $entry_seo_url; ?></label></td>
              <td>
                <input type="checkbox" value="1" id="exchange1c_seo_url" name="exchange1c_seo_url" <?php echo ($exchange1c_seo_url == 1)? 'checked' : ''; ?>>
              </td>
            </tr>
          </table>
        </div>

        <div id="tab-order">
          <table class="form">
            
            <tr>
              <td><?php echo $entry_order_status; ?></td>
              <td>
                <select name="exchange1c_order_status">
                  <?php foreach ($order_statuses as $order_status) { ?>
                    <option value="<?php echo $order_status['order_status_id'];?>" <?php echo ($exchange1c_order_status == $order_status['order_status_id'])? 'selected' : '' ;?>><?php echo $order_status['name']; ?></option>
                  <?php } ?>
                </select>
              </td>
            </tr>

            <tr>
              <td><label for="exchange1c_order_currency"><?php echo $entry_order_currency; ?></label></td>
              <td>
                <input type="text" name="exchange1c_order_currency" value="<?php echo $exchange1c_order_currency; ?>">
              </td>
            </tr>

            <tr>
              <td><label for="exchange1c_order_notify"><?php echo $entry_order_notify; ?></label></td>
              <td>
                <input type="checkbox" value="1" id="exchange1c_order_notify" name="exchange1c_order_notify" <?php echo ($exchange1c_order_notify == 1)? 'checked' : ''; ?>>
              </td>
            </tr>

          </table>
        </div>

        <div id="tab-manual">
          <table class="form">
            <tr>
              <td>
                <?php echo $entry_upload; ?>
              </td>
              <td>
                <a id="button-upload" class="button"><?php echo $button_upload; ?></a>
              </td>
              <td>
                <?php echo $text_max_filesize; ?>
              </td>
            </tr>
          </table>
        </div>

      </form>


    </div>
    
    <div style="text-align:center; opacity: .5">
      <p><?php echo $version; ?> | <a href="http://zenwalker.ru/lab/opencart-exchange1c"><?php echo $text_homepage; ?></a></p>
    </div>
  </div>
</div>

<script type="text/javascript"><!--
$('#tabs a').tabs(); 
//--></script>

<script type="text/javascript" src="view/javascript/jquery/ajaxupload.js"></script> 
<script type="text/javascript"><!--
new AjaxUpload('#button-upload', {
  action: 'index.php?route=module/exchange1c/manualImport&token=<?php echo $token; ?>',
  name: 'file',
  autoSubmit: true,
  responseType: 'json',
  onSubmit: function(file, extension) {
    $('#button-upload').after('<img src="view/image/loading.gif" class="loading" style="padding-left: 5px;" />');
    $('#button-upload').attr('disabled', true);
  },
  onComplete: function(file, json) {
    $('#button-upload').attr('disabled', false);
    $('.loading').remove();
    
    if (json['success']) {
      alert(json['success']);
    }
    
    if (json['error']) {
      alert(json['error']);
    }
    
    
  }
});
//--></script> 


<?php echo $footer; ?>