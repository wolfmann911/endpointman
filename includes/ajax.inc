<?PHP
/*
Endpoint Manager V2
Copyright (C) 2009-2010  Ed Macri, John Mullinix and Andrew Nagy 

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
*/
include LOCAL_PATH . 'includes/jsonwrapper.php';

ini_set('display_errors', 1);
error_reporting(E_ALL);
$sql = "SELECT * FROM endpointman_model_list WHERE enabled = 1 AND brand =". $_GET['id'];
$result = mysql_query($sql);
;
$i=0;
while ($row = mysql_fetch_assoc($result)) {
	$out[$i]['optionValue'] = $row['id'];
	$out[$i]['optionDisplay'] = $row['model'];
	$i++;
}
echo json_encode($out);
?>