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
      <form action="<?php echo $action; ?>" method="post" enctype="multipart/form-data" id="form">
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
            <td><?php echo $entry_flush_category; ?></td>
            <td><select name="exchange1c_flush_category">
                <?php if ($exchange1c_flush_category) { ?>
                <option value="1" selected="selected"><?php echo $text_yes; ?></option>
                <option value="0"><?php echo $text_no; ?></option>
                <?php } else { ?>
                <option value="1"><?php echo $text_yes; ?></option>
                <option value="0" selected="selected"><?php echo $text_no; ?></option>
                <?php } ?>
              </select>
            </td>
          </tr>
          
          <tr>
            <td><?php echo $entry_flush_manufacturer; ?></td>
            <td><select name="exchange1c_flush_manufacturer">
                <?php if ($exchange1c_flush_manufacturer) { ?>
                <option value="1" selected="selected"><?php echo $text_yes; ?></option>
                <option value="0"><?php echo $text_no; ?></option>
                <?php } else { ?>
                <option value="1"><?php echo $text_yes; ?></option>
                <option value="0" selected="selected"><?php echo $text_no; ?></option>
                <?php } ?>
              </select>
            </td>
          </tr>
  
          <tr>
            <td><?php echo $entry_flush_quantity; ?></td>
            <td><select name="exchange1c_flush_quantity">
                <?php if ($exchange1c_flush_quantity) { ?>
                <option value="1" selected="selected"><?php echo $text_yes; ?></option>
                <option value="0"><?php echo $text_no; ?></option>
                <?php } else { ?>
                <option value="1"><?php echo $text_yes; ?></option>
                <option value="0" selected="selected"><?php echo $text_no; ?></option>
                <?php } ?>
              </select></td>
          </tr>

        </table>
      </form>
    </div>
    
    <div>
      <p>При возникновении вопросов, фичреквестов или проблем, можно получить консультацию или поддержку от автора (нахаляву, да).</p>
      <p>Так же модуль может быть модифицирован под любую конфигурацию, добавлены любые фишки на вкус и цвет заказчика.</p>
      <p>Контакты разработчика есть на страничке <a href="http://eth1.ru">http://eth1.ru/</a>.</p>
    </div>
  </div>
</div>
<?php echo $footer; ?>