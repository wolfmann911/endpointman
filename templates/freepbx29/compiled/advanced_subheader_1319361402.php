<?php if(!defined('IN_RAINTPL')){exit('Hacker attempt');}?><table align='center' width='90%'>
<tr>
    <td align='center'><?php
	if( $var["subhead_area"] == 'settings' ){
?><h4 style="color:#ff9933;"><?php echo _('Settings')?></h4><?php
	}
	else{
?><h4><a href='config.php?type=tool&amp;display=epm_advanced&amp;subpage=settings'><?php echo _('Settings')?></a></h4><?php
	}
?></td>
    <td align='center'><?php
	if( $var["subhead_area"] == 'oui_manager' ){
?><h4 style="color:#ff9933;"><?php echo _('OUI Manager')?></h4><?php
	}
	else{
?><h4><a href='config.php?type=tool&amp;display=epm_advanced&amp;subpage=oui_manager'><?php echo _('OUI Manager')?></a></h4><?php
	}
?></td>
    <td align='center'><?php
	if( $var["subhead_area"] == 'poce' ){
?><h4 style="color:#ff9933;"><?php echo _('Product Options/Configuration Editor')?></h4><?php
	}
	else{
?><h4><a href='config.php?type=tool&amp;display=epm_advanced&amp;subpage=poce'><?php echo _('Product Options/Configuration Editor')?></a></h4><?php
	}
?></td>
</tr>
<tr>
    <td align='center'><?php
	if( $var["subhead_area"] == 'iedl' ){
?><h4 style="color:#ff9933;"><?php echo _('Import/Export My Devices List')?></h4><?php
	}
	else{
?><h4><a href='config.php?type=tool&amp;display=epm_advanced&amp;subpage=iedl'><?php echo _('Import/Export My Devices List')?></a></h4><?php
	}
?></td>
<td align='center'><?php
	if( $var["subhead_area"] == 'manual_upload' ){
?><h4 style="color:#ff9933;"><?php echo _('Manual Endpoint Modules Upload/Export')?></h4><?php
	}
	else{
?><h4><a href='config.php?type=tool&amp;display=epm_advanced&amp;subpage=manual_upload'><?php echo _('Manual Endpoint Modules Upload/Export')?></a></h4><?php
	}
?></td>
<td align='center'><?php
	if( $var["subhead_area"] == 'sh_manager' ){
?><h4 style="color:#ff9933;"><?php echo _('Show/Hide Brands/Models')?></h4><?php
	}
	else{
?><h4><a href='config.php?type=tool&amp;display=epm_advanced&amp;subpage=sh_manager'><?php echo _('Show/Hide Brands/Models')?></a></h4><?php
	}
?></td>
<td></td>
</tr>
</table>
<hr width='90%'>