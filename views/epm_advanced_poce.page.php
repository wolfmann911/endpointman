<?php
	if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }
?>
<br />
<div class="container-fluid">
    <div class="row">
        <div class="col-sm-9">
        <?php
        	echo load_view(__DIR__.'/epm_advanced/poce.views.button.up.files.php', array('request' => $_REQUEST));
        	echo load_view(__DIR__.'/epm_advanced/poce.views.textarea.file.php', array('request' => $_REQUEST));
        ?>
        </div>
        <div class="col-sm-3 bootnav">
			<?php
				//$sql = 'SELECT * FROM endpointman_product_list WHERE hidden = 0 AND id > 0 ORDER BY long_name ASC';
				//$sql = 'SELECT * FROM endpointman_product_list WHERE hidden = 0 AND id > 0 AND brand IN (SELECT id FROM asterisk.endpointman_brand_list where hidden = 0) ORDER BY long_name ASC';
				$sql = 'SELECT * FROM endpointman_product_list WHERE hidden = 0 AND id IN (SELECT DISTINCT product_id FROM asterisk.endpointman_model_list where enabled = 1) AND brand IN (SELECT id FROM asterisk.endpointman_brand_list where hidden = 0) ORDER BY long_name ASC';
				$product_list = sql($sql, 'getAll', DB_FETCHMODE_ASSOC);
				unset ($sql);
				
				echo load_view(__DIR__.'/epm_advanced/poce.views.bootnav.php', array('request' => $_REQUEST, 'product_list' => $product_list));
				unset ($product_list);
			?>
        </div>
    </div>
</div>









<?php 

/*
 <a href="config.php?display=epm_advanced&amp;subpage=poce&sendid=<?php echo $sendidt; ?>&amp;filename=<?php echo $filename; ?>&amp;product_select=<?php echo $_REQUEST['product_select']; ?>&amp;<?php echo $type.'='.$sendidt; ?>">
 * */
?>


<!--



<form method="post" action="config.php?type=tool&amp;display=epm_advanced&amp;subpage=poce&amp;product_select={$product_selected}&amp;phone_options=true">
{if condition="isset($options)"}	
{$options}
{/if}
</form>
-->