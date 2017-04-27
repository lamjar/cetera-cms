<?php
/**
 * Cetera CMS 3 
 *
 * @package CeteraCMS
 * @version $Id$
 * @author Roman Romanov <nicodim@mail.ru> 
 **/
namespace Cetera; 

/**
 * Класс реализующий управление типами материалов системы 
 *
 * @property-read int $id идентификатор типа материалов
 * @property-read string $table таблица, в которой хранятся объекты
 * @property-read string $alias алиас типа материалов === таблице
 * @property-read string $description описание типа материалов
 * @property-read boolean $fixed cистемный тип материалов (нельзя удалить из админки)
 * @property-read string $plugin плагин, подключаемый в окне редактирования материалов данного типа
 * @property-read string $handler перехватчик, подключаемый при сорхранении материалов данного типа
 **/
class ObjectDefinition extends Base {

	/**
	 * Пользовательские классы для определенных типов материалов
	 * @internal
	 */  
    public static $userClasses = array();

	/**
	 * @internal
	 */    
    protected $_table = null;
	
	/**
	 * @internal
	 */	
    protected $_description = null; 
	
	/**
	 * @internal
	 */	
    protected $_fixed = null;
	
	/**
	 * @internal
	 */ 	
    protected $_plugin = null;
	
	/**
	 * @internal
	 */	
    protected $_handler = null; 
    
	/**
	 * @internal
	 */
    public static $reserved_aliases = array(
      	'materials',
      	'material_files',
      	'material_links',
      	'material_tags'
    );  
    
    /**
     * Описание полей типа материалов 
     *         
     * @var array    
     */  
    private $fields_def = null; 
    
	/**
	 * @internal
	 */	
    private $field_def = array(
        FIELD_TEXT     => 'varchar(%)',
        FIELD_LONGTEXT => 'MEDIUMTEXT',
        FIELD_HUGETEXT => 'LONGTEXT',
        FIELD_INTEGER  => 'int(11)',
        FIELD_DOUBLE   => 'double',
        FIELD_FILE     => 'varchar(1024)',
        FIELD_DATETIME => 'datetime',
        FIELD_LINK     => 'int(11)',
        FIELD_BOOLEAN  => "tinyint DEFAULT '0'",
        FIELD_ENUM     => "ENUM(%)",
        FIELD_MATERIAL => 'int(11)',
    );
    
	/**
	 * Найти тип материалов по имени таблицы в БД
	 *
	 * @param string $table Таблица БД типа материалов
	 * @return \Cetera\ObjectDefinition
	 */		
    public static function findByTable($table)
    {
        $od = new self(null, $table);
        $od->getId();
        return $od;
    }    
    
	/**
	 * Найти тип материалов по alias
	 *
	 * @param string $alias	Alias типа материалов 
	 * @return \Cetera\ObjectDefinition
	 */		
    public static function findByAlias($alias)
    {
        return self::findByTable($alias);
    }  
    
	/**
	 * Найти тип материалов по ID
	 *
	 * @param int $id ID типа материалов	 
	 * @return \Cetera\ObjectDefinition
	 */		
    public static function findById($id)
    {
        return new self($id);
    }   
	
	/**
	 * Найти тип материалов по ID
	 *
	 * @param int $id ID типа материалов	 
	 * @return \Cetera\ObjectDefinition
	 */			
    public static function getById($id)
    {
        return new self($id);
    } 	

	/**
	 * Зарегистрировать пользовательский класс для определенного типа материалов
	 *
	 * @param int $id ID типа материалов
     * @param string $className Имя класса. Класс должен быть наследником \Cetera\Material
	 * @return void
	 */		
    public static function registerClass($id, $className)
    {
		    if (! is_subclass_of($className, '\Cetera\Material') ) throw new \Exception('Класс '.$className.' должен быть наследником \Cetera\Material');
		    self::$userClasses[$id] = $className;
    }	
    
	/**
	 * Создает новый тип материалов
	 *
	 * @param array $params параметры типа материалов:<br>
	 * alias - alias типа, он же название создаваемой таблицы БД под этот тип материалов<br>
	 * fixed - системный тип (невозможно удалить из админки)<br>
	 * plugin - плагин, подключаемый в окне редактирования материалов данного типа<br>
	 * handler - перехватчик, подключаемый при сорхранении материалов данного типа<br>
	 * @return ObjectDefinition
	 * @throws Exception\CMS если тип с таким alias уже существует
	 * @throws Exception\CMS если alias зарезервирован
	 */		
    public static function create($params)
    {
        $params = self::fix_params($params);
        
      	if (!$params['fixed'] && in_array($params['alias'], self::$reserved_aliases)) {
            throw new Exception\CMS(Exception\CMS::TYPE_RESERVED);
        }
      	
        $r  = fssql_query("select id from types where alias='".$params['alias']."'");
        if (mysql_numrows($r)) throw new Exception\CMS(Exception\CMS::TYPE_EXISTS);
        
        fssql_query('DROP TABLE IF EXISTS '.$params['alias']);
        
      	fssql_query("create table ".$params['alias']." (
            id int(11) NOT NULL auto_increment, idcat int(11), dat datetime, dat_update datetime, name varchar(2048),
         		type int(11), autor int(11) DEFAULT '0' NOT NULL, tag int(11) DEFAULT '1' NOT NULL, alias varchar(255) NOT NULL, 
            PRIMARY KEY (id), KEY idcat (idcat), KEY dat (dat), KEY alias (alias)
        )"); 

        fssql_query("
			INSERT INTO types (alias,describ, fixed, handler, plugin) 
			values ('".mysql_real_escape_string($params['alias'])."','".mysql_real_escape_string($params['describ'])."', ".(int)$params['fixed'].", '".mysql_real_escape_string($params['handler'])."', '".mysql_real_escape_string($params['plugin'])."')");
        
		$id = mysql_insert_id();
            
        $translator = Application::getInstance()->getTranslator();              
            
    	fssql_query("insert into types_fields (id,name,type,describ,len,fixed,required,shw,tag) values ($id,'tag',        3,'".$translator->_('Сортировка')."',  1, 1, 0, 1, 1)");
    	fssql_query("insert into types_fields (id,name,type,describ,len,fixed,required,shw,tag) values ($id,'name',       1,'".$translator->_('Заголовок')."',       99, 1, 0, 1, 2)");
    	fssql_query("insert into types_fields (id,name,type,describ,len,fixed,required,shw,tag) values ($id,'alias',      1,'".$translator->_('Alias')."',     255, 1, 1, 1, 3)");
    	fssql_query("insert into types_fields (id,name,type,describ,len,fixed,required,shw,tag) values ($id,'dat',        5,'".$translator->_('Дата создания')."', 1, 1, 1, 0, 5)");
    	fssql_query("insert into types_fields (id,name,type,describ,len,fixed,required,shw,tag) values ($id,'dat_update', 5,'".$translator->_('Дата изменения')."', 1, 1, 1, 0, 6)");
    	fssql_query("insert into types_fields (id,name,type,describ,len,fixed,required,shw,tag,pseudo_type) values ($id,'autor', 6,'".$translator->_('Автор')."',      -2, 1, 1, 0, 4,1003)");
    	fssql_query("insert into types_fields (id,name,type,describ,len,fixed,required,shw,tag) values ($id,'type',       3,'".$translator->_('Свойства')."',               1, 1, 1, 0, 7)");
        fssql_query("insert into types_fields (id,name,type,describ,len,fixed,required,shw,tag) values ($id,'idcat',      3,'".$translator->_('Раздел')."',         1, 1, 1, 0, 8)");

        return new self($id, $params['alias']);
      
    }          

	/**
	 * @internal
	 */		
    public function __construct($id = null, $table = null) 
    {
    
        if (!$id && !$table) throw new Exception\CMS('One of $id or $table must be specified.');
        
        if (!$table && $id && !(int)$id) {
            $table = $id;
            $id = null;
        }
        
        if ($id && is_array($id)) {
            throw new \Exception('ID must be integer');
        }
    
        $this->_id = $id;
        $this->_table = $table;
    }
    
	/**
	 * @internal
	 */	
    public function getId()
    {
        if (!$this->_id) {
            $r = fssql_query('select * from types where alias="'.mysql_real_escape_string($this->table).'"');
    		    if (mysql_num_rows($r)) 
          			$this->setData(mysql_fetch_assoc($r));   	
                else throw new Exception\CMS('Type for table "'.$this->table.'" is not found.');        
        }
        return $this->_id;
    }
    
	/**
	 * @internal
	 */	
    private function fetchData()
    {
    		$r = fssql_query("select * from types where id=".$this->id);
        if (mysql_num_rows($r)) {
    			  $this->setData(mysql_fetch_assoc($r));                
    		} else throw new Exception\CMS('Materials table for type '.$this->id.' is not found.');     
    }
    
	/**
	 * @internal
	 */	
    private function setData($f) 
    {
        $this->_id          = $f['id']; 
        $this->_table       = $f['alias'];  
        $this->_description = $f['describ'];  
        $this->_fixed       = $f['fixed'];
        $this->_plugin      = $f['plugin']; 
        $this->_handler     = $f['handler'];      
    }
    
	/**
	 * @internal
	 */	
    public function getTable()
    {
        if (null === $this->_table) $this->fetchData();
        return $this->_table;    
    } 
    
	/**
	 * @internal
	 */	
    public function getAlias()
    {
        return $this->getTable();    
    }     
    
	/**
	 * @internal
	 */	
    public function getDescription()
    {
        if (null === $this->_description) $this->fetchData();
        return $this->_description;    
    } 
    
	/**
	 * @internal
	 */	
    public function getFixed()
    {
        if (null === $this->_fixed) $this->fetchData();
        return $this->_fixed;    
    } 
    
	/**
	 * @internal
	 */	
    public function getPlugin()
    {
        if (null === $this->_plugin) $this->fetchData();
        return $this->_plugin;    
    } 
    
	/**
	 * @internal
	 */	
    public function getHandler()
    {
        if (null === $this->_handler) $this->fetchData();
        return $this->_handler;    
    }                 
    
	/**
	 * Возвращает все поля данного типа материалов
	 *
	 * @param int|Catalog $dir если указан раздел, то учитывается видимость полей, заданная для этого раздела
	 * @return array
	 */		
    public function getFields( $dir = null ) {
        $inherit = false;
        if ($dir !== false && !is_a($dir, 'Cetera\\Catalog') && (int)$dir && $dir > 0) $dir = Catalog::getById($dir); 
        if (is_a($dir, 'Cetera\\Catalog'))
		{
            while ($dir->inheritFields && !$dir->isRoot()) $dir = $dir->getParent();
            if (!$dir->inheritFields)
			{
                $r = fssql_query("SELECT * FROM types_fields_catalogs WHERE type_id=".$this->id.' and catalog_id='.$dir->id);
                $inherit = array();
                while($f = mysql_fetch_assoc($r)) $inherit[$f['field_id']] = $f;
            }
        }   
        
        $res = $this->_get_fields();
        
        if ($inherit && count($inherit)) {
            foreach ($res as $key => $val) {
            
                if (isset($inherit[$val['field_id']])) {
                     if ($inherit[$val['field_id']]['force_hide'])
                         $res[$key]['shw'] = 0; 
                     if ($inherit[$val['field_id']]['force_show'])
                         $res[$key]['shw'] = 1; 
                }            
            
            }
        }
        
        return $res;
         
    } 
    
	/**
	 * Возвращает поле данного типа материалов
	 *
	 * @param string $fieldName имя поля
	 * @return ObjectField
	 */		
    public function getField($fieldName) {
        $fields = $this->_get_fields();
        if (isset($fields[$fieldName])) {
            return  $fields[$fieldName];
        } else {
            throw new \Exception('Поле "'.$fieldName.'" не найдено');
        }
    } 
    
	/**
	 * Удаляет тип материалов
	 */		
    public function delete() {
        $r = fssql_query( "select id from dir_data where typ=".$this->id);
        while ($f = mysql_fetch_row($r)) {
            $c = Catalog::getById($f[0]);
            $c->delete();
        }
        fssql_query("drop table ".$this->table);
        fssql_query("delete from types where id=".$this->id);
        fssql_query("delete from types_fields where id=".$this->id);
        
        // удалить все поля - ссылки на материалы этого типа
        $r = fssql_query('SELECT A.field_id, A.name, B.alias FROM types_fields A, types B WHERE A.type='.FIELD_MATSET.' and A.len='.$this->id.' and A.id=B.id');
        while($f = mysql_fetch_assoc($r)){ 
            fssql_query('DROP TABLE '.$f['alias'].'_'.$alias.'_'.$f['name']);
      	    fssql_query('DELETE FROM types_fields WHERE field_id='.$f['field_id']);
        }    
    }
    
	/**
	 * Изменяет тип материалов
	 *
	 * @param array $params параметры типа материалов:<br>
	 * alias - alias типа, он же название создаваемой таблицы БД под этот тип материалов<br>
	 * fixed - системный тип (невозможно удалить из админки)<br>
	 * plugin - плагин, подключаемый в окне редактирования материалов данного типа<br>
	 * handler - перехватчик, подключаемый при сорхранении материалов данного типа<br>
	 * @return ObjectDefinition	 
	 */		
    public function update($params) {
    
        $params = self::fix_params($params);
      
    	  $oldalias = $this->getAlias();
        $alias = $params['alias'];
         
        if ($alias != $oldalias) { // Переименуем тип
        
          	if (!$params['fixed'] && in_array($params['alias'], self::$reserved_aliases)) {
                throw new Exception\CMS(Exception\CMS::TYPE_RESERVED);
            }
              
        	  $r  = fssql_query("select id from types where alias='$alias'");
        	  if (mysql_numrows($r)) throw new Exception\CMS(Exception\CMS::TYPE_EXISTS);
    
        	  $r = fssql_query("select A.alias, B.name from types A, types_fields B, dir_data C where C.typ=".$this->id." and C.id=B.len and B.type=7 and A.id=B.id");
        	  while ($f = mysql_fetch_row($r)) {
        		    fssql_query('ALTER TABLE '.$f[0].'_'.$oldalias.'_'.$f[1].' RENAME '.$f[0].'_'.$alias.'_'.$f[1]);
        	  }
    
        	  $r = fssql_query("select A.alias, B.name from types A, types_fields B, dir_data C where C.typ=B.len and C.id=A.id and B.type=7 and B.id=".$this->id);
        	  while ($f = mysql_fetch_row($r)) {
        		    fssql_query('ALTER TABLE '.$oldalias.'_'.$f[0].'_'.$f[1].' RENAME '.$alias.'_'.$f[0].'_'.$f[1]);
        	  }
    
        	  $r = fssql_query("select A.alias, B.name from types A, types_fields B where B.type=8 and A.id=B.id and B.len=".$this->id);
        	  while ($f = mysql_fetch_row($r)) {
        		    fssql_query('ALTER TABLE '.$f[0].'_'.$oldalias.'_'.$f[1].' RENAME '.$f[0].'_'.$alias.'_'.$f[1]);
        	  }
    
        	  $r = fssql_query("select A.alias, B.name from types A, types_fields B where B.type=8 and B.id=".$this->id." and B.len=A.id");
        	  while ($f = mysql_fetch_row($r)) {
        		    fssql_query('ALTER TABLE '.$oldalias.'_'.$f[0].'_'.$f[1].' RENAME '.$alias.'_'.$f[0].'_'.$f[1]);
        	  }
    
        	  fssql_query("ALTER TABLE $oldalias RENAME $alias");
        	  fssql_query("update types set alias='".mysql_real_escape_string($alias)."' where id=".$this->id);
    	  } // if
    
        $sql = array();
        if (isset($params['fixed'])) $sql[] = 'fixed='.(int)$params['fixed'];
        if (isset($params['describ'])) $sql[] = 'describ="'.mysql_real_escape_string($params['describ']).'"';
        if (isset($params['handler'])) $sql[] = 'handler="'.mysql_real_escape_string($params['handler']).'"';
        if (isset($params['plugin'])) $sql[] = 'plugin="'.mysql_real_escape_string($params['plugin']).'"';
        
        if (count($sql)) fssql_query('update types set '.implode(',',$sql).' where id='.$this->id);
        
        return $this;
    }  
     
	/**
	 * Добавляет новое поле в тип материалов
	 *
	 * @internal
	 * @param array $params параметры поля
	 */	 
    public function addField($params)
    {
          
        $params = self::fix_field_params($params);
        
        if ($params['type'] > 0) {
        
            $r = fssql_query('SELECT COUNT(*) FROM types_fields WHERE id='.$this->id.' and name="'.$params['name'].'"');
            if (mysql_result($r,0)) throw new Exception\CMS(Exception\CMS::FIELD_EXISTS);
        
            $alias = $this->table;
            if ( $params['type'] != FIELD_LINKSET && $params['type'] != FIELD_MATSET )
			{					
                $params['len'] = stripslashes($params['len']);
                $def = str_replace('%',$params['len'],$this->field_def[$params['type']]);
                $sql = "ALTER TABLE $alias ADD ".$params['name']." $def";
                $params['len'] = (integer) $params['len'];
                fssql_query($sql);
            } 
			else 
			{    			
                self::create_link_table($alias, $params['name'], $params['type'], $params['len'], $this->id, $params['pseudo_type']);
            }
            
        }
        
        $r = fssql_query("select max(tag) from types_fields where id=".$this->id);
        $tag = mysql_result($r,0)+1;
        fssql_query("INSERT INTO types_fields 
        		     (tag,  name,        type,       pseudo_type,       len,       describ,        shw,        required,       fixed,       id,       editor,       editor_user, default_value, page) 
          VALUES ($tag,'".$params['name']."',".$params['type'].",".$params['pseudo_type'].",".$params['len'].",'".$params['describ']."',".$params['shw'].",".$params['required'].",".$params['fixed'].",".$this->id.", ".(int)$params['editor'].",'".$params['editor_user']."','".mysql_real_escape_string($params['default_value'])."','".mysql_real_escape_string($params['page'])."')");
    } 
       
	/**
	 * Изменяет поле в типе материалов
	 *
	 * @internal
	 * @param array $params параметры поля
	 */	 	   
    public function updateField($params)
    {
        
        $params = self::fix_field_params($params);

        $alias = $this->table;
            
		$r = fssql_query('SELECT type,len,name FROM types_fields WHERE field_id='.$params['field_id']);
        if (!$r || !mysql_num_rows($r)) throw new Exception\CMS(Exception\CMS::EDIT_FIELD);
        $f = mysql_fetch_row($r);
                
        $type_old = $f[0];
        $len_old  = $f[1];
        $name_old = $f[2];
          
		if (isset($params['type']) && $params['type'])
		{
			if ($type_old != $params['type'])
			{
					
						  // изменился тип поля
						
						  if ($params['type'] != FIELD_LINKSET && $params['type'] != FIELD_MATSET) 
						  {
						
							$params['len'] = stripslashes($params['len']);
							$def = str_replace('%',$params['len'],$this->field_def[$params['type']]);
							  $params['len'] = (integer) $params['len'];
							
								if ($type_old == FIELD_LINKSET || $type_old == FIELD_MATSET) {
								  
								if ($params['type'] >= 0) 
									$action = 'ADD';
									else $action = false;
								  
									self::drop_link_table($alias, $name_old, $type_old, $len_old, $this->id, $params['pseudo_type']);
								
							} else {
							
								if ($type_old >= 0) { 
							
									if ($params['type'] >= 0) 
									{
										$action = "CHANGE `".$name_old."`";
									}
									else
									{
										fssql_query("alter table `$alias` drop `".$name_old."`");
										$action = false;
									}
									
								} else {
								
									$action = 'ADD';
								
								}
									   
							}                     
							
							if ($action)
									fssql_query("alter table `$alias` $action `".$params['name']."` $def");
								
						  } else {
						
							if ($type_old >= 0) {
								if ($type_old != FIELD_LINKSET && $type_old != FIELD_MATSET ) {
									  fssql_query("alter table `$alias` drop `".$name_old."`");
									} else {
									  self::drop_link_table($alias, $name_old, $type_old, $len_old, $this->id, $params['pseudo_type']);
									}
							}
								self::create_link_table($alias, $params['name'], $params['type'], $params['len'], $this->id, $params['pseudo_type']);
							
						  }
					
			} 
			elseif ($type_old >= 0 && ($params['name'] != $name_old || $params['len'] != $len_old))
			{
					
						  if ($params['type']!=FIELD_LINKSET && $params['type']!=FIELD_MATSET) {
							$params['len'] = stripslashes($params['len']);
							$def = str_replace('%',$params['len'],$this->field_def[$params['type']]);
							$sql = "alter table `$alias` change `".trim($f[2])."` `".$params['name']."` $def";
							  $params['len'] = (integer) $params['len'];
						  } else {
							$tbl = self::get_table($params['type'], $params['len'], $this->id,$params['pseudo_type']);
							  $tbl1 = self::get_table($f[0],$f[1], $this->id,$params['pseudo_type']);
							  $sql = "alter table ".$alias."_".$tbl1."_".$f[2]." rename ".$alias."_".$tbl."_".$params['name'];
						  }
						  fssql_query($sql);
					  
			}
			
			$sql = "UPDATE types_fields 
					SET name='".$params['name']."',
						type=".$params['type'].",
						pseudo_type=".$params['pseudo_type'].",
						len=".$params['len'].",
						describ='".$params['describ']."',
						shw=".$params['shw'].",
						required=".$params['required'].",
						default_value='".$params['default_value']."',
						editor=".$params['editor'].",
						editor_user='".$params['editor_user']."',
						page='".$params['page']."'
					WHERE field_id=".$params['field_id'];			
		}
		else 
		{
			$sql = "UPDATE types_fields 
					SET 
						describ='".$params['describ']."',
						default_value='".$params['default_value']."',
						page='".$params['page']."'
					WHERE field_id=".$params['field_id'];			
		}
		
		fssql_query($sql);    
    }  

    /**
     * Возвращает материалы данного типа     
     *        
     * @return Iterator\Material    
     */       
    public function getMaterials()
    {    
		if ($this->table == 'users') return new Iterator\User();
		if ($this->table == 'dir_data') return new Iterator\Catalog\Catalog();
        return new Iterator\Material($this);
    }
	
    /**
     * Возвращает разделы с материалами данного типа   
     *        
     * @return Iterator\Catalog
     */       
    public function getCatalogs()
    {    
		$list = new Iterator\Catalog\Catalog();
        return $list->where('typ='.$this->id);
    }		
    
    /**
     * @internal
     **/
    private function _get_fields() {
    
        if (!$this->fields_def) {
        		$r = fssql_query("SELECT * FROM types_fields WHERE id=".$this->id.' ORDER BY tag');
        		if ($r) {
        		    $this->fields_def = array();
        			while($f = mysql_fetch_assoc($r))
					{
						$key = $f['name'];
						while (isset($this->fields_def[$key])) $key .= '_';
						$this->fields_def[$key] = ObjectField::factory($f, $this);
        			} 

        		}
        }
        return $this->fields_def;
    }
   
    /**
     * @internal
     **/   
    public static function fix_params($params)
    {
        if (!isset($params['describ']) && isset($params['description'])) $params['describ'] = $params['description']; 
        if (!isset($params['alias']) && isset($params['name'])) $params['alias'] = $params['name']; 
        if (!isset($params['alias']) && isset($params['table'])) $params['alias'] = $params['table']; 
        return $params;    
    }   
    
    /**
     * @internal
     **/	
    public static function fix_field_params($params)
    {
        foreach ($params as $pname => $pvalue) {
            $params[ObjectField::fixOffset($pname)] = $pvalue;
        }
        
        if (!(int)$params['len'] && $params['len'] && $params['type'] != FIELD_ENUM) {
            $od = self::findByAlias($params['len']);
            $params['len'] = $od->id;
        } 
       
        
        return $params;    
    } 
    
    /**
     * @internal
     **/	
    public static function create_link_table($fieldtable, $fieldname, $type, $len, $id, $pseudo_type = 0) {
      $tbl = self::get_table($type,$len, $id,$pseudo_type);
    	fssql_query("CREATE TABLE IF NOT EXISTS ".$fieldtable."_".$tbl."_".$fieldname." (id int(11) not null, dest int(11) not null, tag int(11) DEFAULT '0' NOT NULL, PRIMARY KEY (id, dest), key dest (dest))");
    }
    
    /**
     * @internal
     **/	
    public static function drop_link_table($fieldtable, $fieldname, $type, $len, $id, $pseudo_type = 0) {
    	$tbl = self::get_table($type,$len, $id, $pseudo_type);
    	fssql_query("DROP TABLE IF EXISTS ".$fieldtable."_".$tbl."_".$fieldname);
    }
    
    /**
     * @internal
     **/	
    public static function get_table($field_type, $len, $type_id, $pseudo_type = 0) { 
      if ($pseudo_type == PSEUDO_FIELD_CATOLOGS) return Catalog::TABLE;
       
    	if ($field_type == FIELD_LINKSET && $len) {
    	    if ($len == CATALOG_VIRTUAL_USERS) return User::TABLE;
    	  	$r = fssql_query("select A.alias from types A, dir_data B where A.id = B.typ and B.id=$len");
    	} else {
    	  if ($field_type == FIELD_LINKSET) $len = $type_id;
    	  $r = fssql_query("select alias from types where id=$len");
    	}
    	if ($r)	return mysql_result($r,0);
    }                

}