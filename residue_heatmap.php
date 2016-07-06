<!DOCTYPE html>
<html>
<body>
<style>
	* {

	}
	table { border-collapse:collapse; table-layout:fixed; }
	table td 
{
  table-layout:fixed;
  width:20px;
  overflow:hidden;
  word-wrap:break-word;
}


</style>
<?php
$canonical_sites = array();

$canonical_sites['A'] = array(
	122, 124, 126, 130,131,132,133, 135, 137, 138, 140, 142,143,144,145,146, 150,152, 168
);

$canonical_sites['Ae'] = array(
	71, 72, 98, 127, 141, 148, 149, 151, 255
);

$canonical_sites['B'] = array(
	128, 129, 155,156,157,158,159,160, 163,164,165, 186,187,188,189,190, 192,193,194, 196,197,198
);

$canonical_sites['Be'] = array(
	161, 162, 199
);

$canonical_sites['C'] = array(
	44, 45, 46, 47, 48, 50, 51, 53, 54, 273, 275, 276, 278, 279, 280, 294, 297, 299, 300, 304, 305, 307, 308, 309, 310, 311, 312
);

$canonical_sites['Ce'] = array(
	41, 42, 43, 49, 52, 55, 271, 272, 274, 282, 284, 285, 287, 288, 289, 290, 291, 293, 295, 296, 298, 301, 302, 303, 313, 314
);


$canonical_sites['D'] = array(
	96, 102, 103, 117, 121, 167,170, 171, 172, 173, 174, 175, 176, 177, 179, 182, 201, 203,207, 208, 209, 212, 213, 214, 215, 216, 217, 218, 219, 226, 227, 228, 229, 230,238, 240, 242, 244, 246, 247, 248
);

$canonical_sites['De'] = array(
	95, 97, 99, 100, 101, 104, 105, 107, 118, 120, 166, 169, 178, 180, 183, 184, 200, 204, 205, 206,210, 211, 220, 221, 222, 223, 244, 225, 231, 232, 233, 234, 235, 236,239, 243, 245, 257, 258
);

$canonical_sites['E'] = array(
	57, 59, 62, 63, 67, 75, 78,80,81,82,83, 86,87,88, 91, 92, 94,109, 260,261,262, 265
);

$canonical_sites['Ee'] = array(
	56, 58, 60, 64,65, 68,70,73,74, 76,77, 79, 84,85,89,90, 93, 110,111,112,113,114,115, 119, 259,263,264, 267,268,269,270
);
#include "../../inc.php";
error_reporting(E_ALL ^ E_STRICT);
ini_set('max_execution_time', 0); //300 seconds = 5 minutes

#$pdb = '2YP2';
#$pdb = '2YP7';
$pdb = '1HGDt';
if(isset($_GET['pdb']) && !empty($_GET['pdb'])) {
	$pdb = $_GET['pdb'];
}

//get all residues
function get_residue_list() {
	global $db;
	$transition_sql = $db->select()
					->from(array('s' => 'foldx_single_mutations'), array('residue_number'))
					->group('residue_number');
	$data =	$transition_sql->query()->fetchall();
	$residues = array();
	foreach($data as $k => $v) {
		$residues[] = $v['residue_number'];
	}
	return $residues;
}

function get_reversions_list() {
	global $db;
	$transition_sql = $db->select()
					->from(array('s' => 'foldx_single_mutations'))
					->order('transition_id ASC');
	$data =	$transition_sql->query()->fetchall();
	$mutations = array();
	foreach($data as $residue) {
		$mutations[] = $residue['from_residue'].$residue['residue_number'].$residue['to_residue'];
	}
	
	$reversion_list_start = array();
	$reversion_list_end = array();
	foreach($mutations as $i => $mutation_i) {
		foreach($mutations as $j => $mutation_j) {
			if($j > $i) {
				$residue_i = filter_var($mutation_i, FILTER_SANITIZE_NUMBER_INT);
				$residue_j = filter_var($mutation_j, FILTER_SANITIZE_NUMBER_INT);
				if($mutation_i[0] == $mutation_j[strlen($mutation_j)-1] && $residue_i == $residue_j) {
					$reversion_list_start[] = $mutation_i;
					$reversion_list_end[] = $mutation_j;
				}
			}
		}
	}
	
	return array('start' => $reversion_list_start, 'end' => $reversion_list_end);
}

function get_transitions_list() {
	global $db;
	$transition_sql = $db->select()
					->from(array('t' => 'foldx_strain_transitions'), array('id'))
					->join(array('s1' => 'foldx_strains'), 's1.id = t.from_strain', array('from_strain' => 'strain_name'))
					->join(array('s2' => 'foldx_strains'), 's2.id = t.to_strain', array('to_strain' => 'strain_name'));		
	$data =	$transition_sql->query()->fetchall();
	return $data;
}

function get_transition_single_mutations($pdb, $transition_id) {
	global $db;
	$transition_sql = $db->select()
					->from(array('p' => 'foldx_pdb_single_mutations'), array('energy_change', 'energy_change_reverse'))
					->join(array('s' => 'foldx_single_mutations'), 'p.single_mutation_id = s.id', array('residue_number', 'from_residue', 'to_residue'))
					->where('s.residue_number > ?', 5)
					->where('p.pdb = ?', $pdb)
					->where('p.transition_id = ?', $transition_id);
	#var_dump( $transition_sql->__toString() );die();
	$data =	$transition_sql->query()->fetchall();
	
	$residue_data = array();
	foreach($data as $k => $v) {
		$residue_data[$v['residue_number']] = $v;
	}
	return $residue_data;
}

function get_transition_mutations($pdb, $transition_id) {
	global $db;
	$transition_sql = $db->select()
					->from(array('p' => 'foldx_pdb_transitions'), array('energy_change', 'energy_change_reverse'))
					->where('p.pdb = ?', $pdb)
					->where('p.transition_id = ?', $transition_id);
	$data =	$transition_sql->query()->fetch();	
	return $data;
}

$residue_list = get_residue_list();
#print_r($residue_list);

//now get a list of all the transitions and their single mutations
$transitions_list = get_transitions_list();
#print_r($transitions_list);

$result = array();

//their single mutations for each transition and their energy changes
foreach($transitions_list as $transition) {
	$tmp = array();
	$tmp['transition'] = $transition;
	$tmp['single_mutation'] = get_transition_single_mutations($pdb, $transition['id']);
	$tmp['transition_mutation'] = get_transition_mutations($pdb, $transition['id']);
	$result[$transition['id']] = $tmp;
	/*print_r($transition);
	$single_mutation_energy_changes = get_transition_single_mutations($pdb, $transition['id']);
	$transition_mutation_energy_change = get_transition_mutations($pdb, $transition['id']);
	print_r($transition_mutation_energy_change);
	print_r($single_mutation_energy_changes);
	die();*/
}
#print_r($result);
#die();

/*

diagrams needed

mutations and the order of mutation
spheres and their energy change
	
*/

function color_highlight($value) {
	$background = '#fff';
	$color = '#000';
	if($value > 0) {
		$background = '#FFCCCC';
	}
	if($value > 2) {
		$background = '#FF0000';
	}
	
	if($value < 0) {
		$background = '#99CCFF';
	}
	if($value < -2) {
		$background = 'blue';
		$color = '#fff';
	}
	
	if($value > -0.5 && $value < 0.5) {
		$background = '#fff';
	}
	
	return "background: $background; color: $color";
}
function aasort (&$array, $key) {
    $sorter=array();
    $ret=array();
    reset($array);
    foreach ($array as $ii => $va) {
        $sorter[$ii]=$va[$key];
    }
    asort($sorter);
    foreach ($sorter as $ii => $va) {
        $ret[$ii]=$array[$ii];
    }
    $array=$ret;
}


function show_residue_value($residue) {
	$residue = $residue['from_residue'].$residue['residue_number'].$residue['to_residue'];
	return $residue;
}
function show_canonical($residue) {
	global $canonical_sites;	
	$data = array();
	$canonical_site = '-';
	foreach($canonical_sites as $k => $v) {
		if( in_array($residue, $v) ) {
			$canonical_site = $k;
		}
	}
	return $canonical_site;
}

function show_sphere($from_strain, $to_strain, $residue) {
	global $william_clusters;	
	$canonical_site = '-';
	foreach($william_clusters as $k => $v) {
		if($v['Strain1'] == $from_strain && $v['Strain2'] == $to_strain && $v['Location'] == $residue) {
			$canonical_site = $v['Cluster'];
			break;
		}
	}
	return $canonical_site;
}

function get_william_clusters() {
	global $db;
	ini_set('display_errors', 0);
	require_once 'excel_reader2.php';
	$data = new Spreadsheet_Excel_Reader("siteclass_out.xls");
	$cells = $data->sheets[0]['cells'];
	
	$headers = array();
	$data = array();
	foreach($cells as $k => $v) {
		$tmp = array();
		if($k == 1) {
			$headers = $v;
		} else {
			foreach($v as $ki => $vi) {
				$tmp[ $headers[$ki] ] = $vi;
			}
			$data[] = $tmp;
		}
	}
	return $data;
	
}
$william_clusters = get_william_clusters();
$reversions = get_reversions_list();

#file_put_contents("/www/htdocs/3fx.us/bio/foldx/contents/reversions.json", json_encode($reversions));


$action = '';
if(isset($_GET['action']) && !empty($_GET['action'])) {
	$action = $_GET['action'];
}
#print_r($reversions);die();
?>

<div class="row">
	
	<div class="span12">
<div class="btn-group">
				<a href="#" class="btn"><?= $pdb ?></a>
				<a href="#" data-toggle="dropdown" class="btn dropdown-toggle">
				<span class="caret"></span>
				</a>
				<ul class="dropdown-menu" id="fixations_dropdown">
						<li><a href="?action=<?= $action ?>&pdb=1HGD">1HGD</a></li>						
						<li><a href="?action=<?= $action ?>&pdb=2YP2">2YP2</a></li>						
						<li><a href="?action=<?= $action ?>&pdb=2YP7">2YP7</a></li>				
						<li><a href="?action=<?= $action ?>&pdb=1HGDt">1HGD TRIMER</a></li>				
				</ul>
			</div>
			
<a href="?action=show_resn&pdb=<?= $pdb ?>">show residue numbers</a> | <a href="?action=show_energies&pdb=<?= $pdb ?>">show energies</a> | <a href="?action=show_canonical&pdb=<?= $pdb ?>">show canonical sites</a> | <a href="?action=show_reversions&pdb=<?= $pdb ?>">show reversions</a> | <a href="?action=show_spheres&pdb=<?= $pdb ?>">show spheres</a>
</div>
</div>
<br />
<div class="row">
	
	<div class="span12">
<table border="1" >

	<tr>
		<td style="display: block;width: 350px;">Transitions</td>
		<? for($i = 0; $i < 14; $i++) : ?>
			<td></td>
		<? endfor; ?>
		<td>energy change</td>
	</tr>
<? $total_change = 0; ?>
<? foreach($result as $transition_key => $transition_info) : ?>
	<?
	if($transition_info['transition_mutation']['energy_change'] == 0) {
		$transition_info['transition_mutation']['energy_change'] = -$transition_info['transition_mutation']['energy_change_reverse'];
	}
	$total_change += $transition_info['transition_mutation']['energy_change'];
	?>
	<tr>
		<td><?= $transition_info['transition']['from_strain'] ?> <?= $transition_info['transition']['to_strain'] ?></td>
		<? aasort($transition_info['single_mutation'],"energy_change"); ?>
		<? foreach($transition_info['single_mutation'] as $residue) : ?>
			<?
				$value = show_residue_value($residue);
				if($action == 'show_reversions') {
					if(in_array($value, $reversions['start']) || in_array($value, $reversions['end'])) {
					
					} else {
						continue;
					}
				}
				
				if($action == 'show_energies') {
					$value = number_format($residue['energy_change'], 2);
				}				
				if($action == 'show_canonical') {
					$value = show_canonical($residue['residue_number']);
				}
				if($action == 'show_spheres') {
					$value = show_sphere($transition_info['transition']['from_strain'], $transition_info['transition']['to_strain'], $residue['residue_number']);
				}
			?>
			<td style="<?= color_highlight($residue['energy_change']) ?>" title="<?= $residue['residue_number'] ?> : <?= $residue['energy_change'] ?>"><?= $value ?></td>
		<? endforeach; ?>
		<? for($i = 0; $i < 14-count($transition_info['single_mutation']); $i++) : ?>
			<td></td>
		<? endfor; ?>
		<? if($action != 'show_reversions') { ?>
		<td style="<?= color_highlight($transition_info['transition_mutation']['energy_change']) ?>"><?= number_format($transition_info['transition_mutation']['energy_change'], 2) ?></td>
		<? } ?>
	</tr>

<? endforeach; ?>

	<tr>
		<td>Total change</td>
		<? for($i = 0; $i < 14; $i++) : ?>
			<td></td>
		<? endfor; ?>
		<td><?= number_format($total_change, 2) ?></td>
	</tr>
	
</table> 
	</div>


</div>
<br />
<div class="row">
	
	<div class="span6">
		<div class="alert alert-info">
			<b>ΔΔG(change) > 0 : the mutation is <span style="color: #ff0000"> destabilizing </span><br />
			ΔΔG(change) < 0 : the mutation is  <span style="color: blue"> stabilizing </span> </b>
		</div>
	</div>
	
	<div class="span6">
		<div class="alert">
			<b  style="font-weight: normal; color: #000;">ΔΔG(change) > 2 highlighted in <span style="font-weight: bold; color: #000;"> black </span></b>
		</div>
	</div>


</div>

</body>
</html>
