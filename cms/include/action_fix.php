<?php
namespace Cetera;
include('../include/common_bo.php');

define('STRUCTURE_BACKUP', 'dir_data_backup');

$res = array(
  	'success' => true, 
  	'text' => false
); 

if ($_REQUEST['db_structure']) {
    $schema = new Schema(); 
    $msg = '<div>'; 
  	$module = '';
  	$table = '';
  	$terror = false;
  	$tables = array(); 
    $types = array(); 
    $widgets = array(); 
       
    $result = $schema->compare_schemas($_REQUEST['ignore_fields'], $_REQUEST['ignore_keys']);
    
    if (sizeof($result)) foreach ($result as $error) {
    
    		if ($table != $error['table']) {
      			if (!$terror && $table) $msg .= ' <b class="ok">ОК</b>';
      			if ($table) $msg .= '<br />';
      			if ($module != $error['module']) $msg .= '<div class="hdr"><b><u>Модуль '.$error['module'].'</u></b></div>';
      			$msg .= '&nbsp;&nbsp;&nbsp;&nbsp;<span>Таблица '.$error['table'].'</span>';
      			$terror = false;
    		}
    		        
        if ( $error['error'] < Schema::TYPE_NOT_EXISTS ) {
    		
        		if (!isset($tables[$error['table']])) {
        		    $res = $schema->parseSchema($error['module']);
                $tables = array_merge($tables, $res['tables']);
        		}        
        
        		$query = $schema->get_fix_query($tables, $error);
        		
        		if ($query) {
    
        			mysql_query($query);
              
        			if (mysql_error()) {
        				if (!$terror) $msg .= '<b class="error">Ошибка</b>';
        				$terror = true;
        				$msg .= '<div class="note">'.mysql_error().'<pre>'.$query.'</pre></div>';
        			}
    
        		}
            
        } elseif ( $error['error'] < Schema::WIDGET_NOT_EXISTS ) {
        
    		    if (!isset($types[$error['table']])) {
        		    $res = $schema->parseSchema($error['module']);
                $types = array_merge($types, $res['types']);
                $schema->fixTypes($res['types']);
        		}          
        
        } else {
        
    		    if (!isset($widgets[$error['widget']])) {
        		    $res = $schema->parseSchema($error['module']);
                $widgets = array_merge($widgets, $res['widgets']);
                $schema->fixWidgets($res['widgets']);
        		}   
        
        }
    		
    		$module = $error['module'];
    		$table = $error['table'];    
    }
    
  	if (!$terror) $msg .= ' <b class="ok">OK</b>';
  	
  	$msg .= '</div>';
    
    $res['text'] = $msg;
}

if ($_REQUEST['cat_structure']) {
	
	// Backup
	fssql_query('DROP TABLE IF EXISTS '.STRUCTURE_BACKUP);
	$r = fssql_query('SHOW CREATE TABLE dir_structure');
	$f = mysql_fetch_row($r);
	$query = str_replace('dir_structure', STRUCTURE_BACKUP, $f[1]);
	fssql_query($query);
	fssql_query('INSERT INTO '.STRUCTURE_BACKUP.' SELECT * FROM dir_structure');
	
	$structure = array(
		1 => array(
			'data_id'   => 0,
			'parent_id' => 0,
			'children'  => 0,
			'lft'		=> 0,
			'rght' 		=> 0,
			'level'		=> 0,
		)
	);
	$r = fssql_query('SELECT * FROM dir_structure ORDER BY lft');
	while($f = mysql_fetch_assoc($r)) {
		$f['id']++;
		if ($f['data_id']) $struct[] = $f;
	}
	foreach ($struct as $id => $item) {
		$parent = 1;
		$i = $id-1;
		while($i >= 0) {
			if ($struct[$i]['rght'] > $item['lft']) {
			    $parent = $struct[$i]['id'];
				break;
			}
			$i--;
		} // while
		$structure[$item['id']] = array(
			'data_id'   => $item['data_id'],
			'parent_id' => $parent,
			'children'  => 0,
			'lft'		=> 0,
			'rght' 		=> 0,
			'level'		=> 0,
		);
		while($parent){
			$structure[$parent]['children']++;
			$parent = $structure[$parent]['parent_id'];
		} // while
	}
	$lft = 0;
	$rght = array();
	$prevlevel = 0;
	foreach($structure as $id => $item) {
		if ($structure[$id]['parent_id']) $structure[$id]['level'] = $structure[$structure[$id]['parent_id']]['level'] + 1;
		if (($structure[$id]['level']<=$prevlevel) && $rght[$structure[$id]['level']]) {
		    $lft = $rght[$structure[$id]['level']] + 1;
		} else {
			$lft++;
		}
		$structure[$id]['lft']   = $lft;
		$structure[$id]['rght']  = $structure[$id]['lft'] + $structure[$id]['children']*2 + 1;
		$rght[$structure[$id]['level']] = $structure[$id]['rght'];
		$prevlevel = $structure[$id]['level'];
	}
	
	fssql_query('TRUNCATE TABLE dir_structure');
	foreach($structure as $id => $item) 
		fssql_query('INSERT INTO dir_structure (data_id, lft, rght, level) VALUES ('.$item['data_id'].','.$item['lft'].','.$item['rght'].','.$item['level'].')');
	
	
	$res['text'] .= '<b class="ok">Структура разделов ОК</b></div><br clear="all" /><div class="note">Backup структуры сохранен в таблице '.STRUCTURE_BACKUP.'</div>';
  
  
}

echo json_encode($res); 