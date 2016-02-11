<?php
	if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }
	

	/*
	if ((isset($_REQUEST['oui_sub'])) AND ($_REQUEST['rb_brand'] > 0) AND ($_REQUEST['oui'] != "")) {
		$sql = "INSERT INTO  endpointman_oui_list (oui, brand, custom) VALUES ('" . $_REQUEST['oui'] . "',  '" . $_REQUEST['rb_brand'] . "',  '1')";
		$endpoint->eda->sql($sql);
		$endpoint->message['oui_manager'] = "Added!";
	} elseif (isset($_REQUEST['oui_sub'])) {
		$endpoint->error['oui_manager'] = "No OUI Set!";
	}
	if ((isset($_REQUEST['delete'])) AND ($_REQUEST['id'] > 0)) {
		$sql = "DELETE FROM endpointman_oui_list WHERE id = " . $_REQUEST['id'];
		$endpoint->eda->sql($sql);
		$endpoint->message['oui_manager'] = "Deleted!";
	}
	*/
	
	
	
	$sql = 'SELECT endpointman_oui_list.id, endpointman_oui_list.oui , endpointman_brand_list.name, endpointman_oui_list.custom FROM endpointman_oui_list , endpointman_brand_list WHERE endpointman_oui_list.brand = endpointman_brand_list.id ORDER BY endpointman_oui_list.oui ASC';
	$data = sql($sql, 'getAll', DB_FETCHMODE_ASSOC);
	
	$sql = 'SELECT * from endpointman_brand_list WHERE id > 0 ORDER BY id ASC';
	$brands = sql($sql, 'getAll', DB_FETCHMODE_ASSOC);
?>
<table align='center' width='30%' class='alt_table'>
	<tr>
		<td align='center'><h3>OUI</h3></td>
		<td align='center'><h3><?php echo _("Brand")?></h3></td>
		<td align='center'></td>
	</tr>
	<?php
	foreach ($data as $row) {
		echo "<tr>";
		echo "<td align='center'><code>".$row['oui']."</code></td>";
		echo "<td align='center'>".$row['name']."</td>";
		echo "<td align='center'>";
		if ($row['custom'] == 1) {
			echo "<a href='config.php?type=tool&amp;display=epm_advanced&amp;subpage=oui_manager&amp;delete=yes&amp;id=".$row['id']."'><i class='icon-remove red' title='"._('Delete')."' border='0'></i></a>";
		}
		echo "</td>";
		echo "</tr>";
	}
	?>
</table>
<table align='center' width='30%' class='alt_table'>
	<tr>
		<td colspan="3" align='center'><h3><?php echo _('Add Custom')?> OUI</h3></td>
	</tr>
	<tr>
		<td align='center'><input name="oui" type="text"  maxlength="6"/></td><td align='center'>
			<select name="rb_brand">
			<?php
			foreach ($brands as $row) {
				echo '<option value="'.$row['id'].'">'.$row['name'].'</option>';
			}
			?>
			</select>
		</td>
		<td align='center'>
			<button type="submit" name="oui_sub"><i class='icon-plus green'></i> <?php echo _('Add')?></button>
		</td>
	</tr>
</table>