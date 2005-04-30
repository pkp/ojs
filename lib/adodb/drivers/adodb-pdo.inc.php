<?php
/* 
V4.62 2 Apr 2005  (c) 2000-2005 John Lim (jlim#natsoft.com.my). All rights reserved.
  Released under both BSD license and Lesser GPL library license. 
  Whenever there is any discrepancy between the two licenses, 
  the BSD license will take precedence. 
Set tabs to 4 for best viewing.
  
  Latest version is available at http://adodb.sourceforge.net
  
  Requires ODBC. Works on Windows and Unix.

	Problems: 
		Where is float/decimal type in pdo_param_type
		LOB handling for CLOB/BLOB differs significantly
*/
// security - hide paths
if (!defined('ADODB_DIR')) die();


/*
enum pdo_param_type {
PDO_PARAM_NULL, 0

/* int as in long (the php native int type).
 * If you mark a column as an int, PDO expects get_col to return
 * a pointer to a long 
PDO_PARAM_INT, 1

/* get_col ptr should point to start of the string buffer 
PDO_PARAM_STR, 2

/* get_col: when len is 0 ptr should point to a php_stream *,
 * otherwise it should behave like a string. Indicate a NULL field
 * value by setting the ptr to NULL 
PDO_PARAM_LOB, 3

/* get_col: will expect the ptr to point to a new PDOStatement object handle,
 * but this isn't wired up yet 
PDO_PARAM_STMT, 4 /* hierarchical result set 

/* get_col ptr should point to a zend_bool 
PDO_PARAM_BOOL, 5


/* magic flag to denote a parameter as being input/output 
PDO_PARAM_INPUT_OUTPUT = 0x80000000
};
*/
	
function adodb_pdo_type($t)
{
	switch($t) {
	case 2: return 'VARCHAR';
	case 3: return 'BLOB';
	default: return 'NUMERIC';
	}
}
	 
/*--------------------------------------------------------------------------------------
--------------------------------------------------------------------------------------*/


class ADODB_pdo_pgsql extends ADODB_pdo {
var $metaDatabasesSQL = "select datname from pg_database where datname not in ('template0','template1') order by 1";
    var $metaTablesSQL = "select tablename,'T' from pg_tables where tablename not like 'pg\_%'
	and tablename not in ('sql_features', 'sql_implementation_info', 'sql_languages',
	 'sql_packages', 'sql_sizing', 'sql_sizing_profiles') 
	union 
        select viewname,'V' from pg_views where viewname not like 'pg\_%'";
	//"select tablename from pg_tables where tablename not like 'pg_%' order by 1";
	var $isoDates = true; // accepts dates in ISO format
	var $sysDate = "CURRENT_DATE";
	var $sysTimeStamp = "CURRENT_TIMESTAMP";
	var $blobEncodeType = 'C';
	var $metaColumnsSQL = "SELECT a.attname,t.typname,a.attlen,a.atttypmod,a.attnotnull,a.atthasdef,a.attnum 
		FROM pg_class c, pg_attribute a,pg_type t 
		WHERE relkind in ('r','v') AND (c.relname='%s' or c.relname = lower('%s')) and a.attname not like '....%%'
AND a.attnum > 0 AND a.atttypid = t.oid AND a.attrelid = c.oid ORDER BY a.attnum";

	// used when schema defined
	var $metaColumnsSQL1 = "SELECT a.attname, t.typname, a.attlen, a.atttypmod, a.attnotnull, a.atthasdef, a.attnum 
FROM pg_class c, pg_attribute a, pg_type t, pg_namespace n 
WHERE relkind in ('r','v') AND (c.relname='%s' or c.relname = lower('%s'))
 and c.relnamespace=n.oid and n.nspname='%s' 
	and a.attname not like '....%%' AND a.attnum > 0 
	AND a.atttypid = t.oid AND a.attrelid = c.oid ORDER BY a.attnum";
	
	// get primary key etc -- from Freek Dijkstra
	var $metaKeySQL = "SELECT ic.relname AS index_name, a.attname AS column_name,i.indisunique AS unique_key, i.indisprimary AS primary_key 
	FROM pg_class bc, pg_class ic, pg_index i, pg_attribute a WHERE bc.oid = i.indrelid AND ic.oid = i.indexrelid AND (i.indkey[0] = a.attnum OR i.indkey[1] = a.attnum OR i.indkey[2] = a.attnum OR i.indkey[3] = a.attnum OR i.indkey[4] = a.attnum OR i.indkey[5] = a.attnum OR i.indkey[6] = a.attnum OR i.indkey[7] = a.attnum) AND a.attrelid = bc.oid AND bc.relname = '%s'";
	
	var $hasAffectedRows = true;
	var $hasLimit = false;	// set to true for pgsql 7 only. support pgsql/mysql SELECT * FROM TABLE LIMIT 10
	// below suggested by Freek Dijkstra 
	var $true = 't';		// string that represents TRUE for a database
	var $false = 'f';		// string that represents FALSE for a database
	var $fmtDate = "'Y-m-d'";	// used by DBDate() as the default date format used by the database
	var $fmtTimeStamp = "'Y-m-d G:i:s'"; // used by DBTimeStamp as the default timestamp fmt.
	var $hasMoveFirst = true;
	var $hasGenID = true;
	var $_genIDSQL = "SELECT NEXTVAL('%s')";
	var $_genSeqSQL = "CREATE SEQUENCE %s START %s";
	var $_dropSeqSQL = "DROP SEQUENCE %s";
	var $metaDefaultsSQL = "SELECT d.adnum as num, d.adsrc as def from pg_attrdef d, pg_class c where d.adrelid=c.oid and c.relname='%s' order by d.adnum";
	var $random = 'random()';		/// random function
	var $concat_operator='||';
	 
	function ServerInfo()
	{
		$arr['description'] = ADOConnection::GetOne("select version()");
		$arr['version'] = ADOConnection::_findvers($arr['description']);
		return $arr;
	}
	
	function &SelectLimit($sql,$nrows=-1,$offset=-1,$inputarr=false,$secs2cache=0) 
	{
		 $offsetStr = ($offset >= 0) ? " OFFSET $offset" : '';
		 $limitStr  = ($nrows >= 0)  ? " LIMIT $nrows" : '';
		 if ($secs2cache)
		  	$rs =& $this->CacheExecute($secs2cache,$sql."$limitStr$offsetStr",$inputarr);
		 else
		  	$rs =& $this->Execute($sql."$limitStr$offsetStr",$inputarr);
		
		return $rs;
	}
	
	function &MetaTables($ttype=false,$showSchema=false,$mask=false) 
	{
		$info = $this->ServerInfo();
		if ($info['version'] >= 7.3) {
	    	$this->metaTablesSQL = "select tablename,'T' from pg_tables where tablename not like 'pg\_%'
			  and schemaname  not in ( 'pg_catalog','information_schema')
	union 
        select viewname,'V' from pg_views where viewname not like 'pg\_%'  and schemaname  not in ( 'pg_catalog','information_schema') ";
		}
		if ($mask) {
			$save = $this->metaTablesSQL;
			$mask = $this->qstr(strtolower($mask));
			if ($info['version']>=7.3)
				$this->metaTablesSQL = "
select tablename,'T' from pg_tables where tablename like $mask and schemaname not in ( 'pg_catalog','information_schema')  
 union 
select viewname,'V' from pg_views where viewname like $mask and schemaname  not in ( 'pg_catalog','information_schema')  ";
			else
				$this->metaTablesSQL = "
select tablename,'T' from pg_tables where tablename like $mask 
 union 
select viewname,'V' from pg_views where viewname like $mask";
		}
		$ret =& ADOConnection::MetaTables($ttype,$showSchema);
		
		if ($mask) {
			$this->metaTablesSQL = $save;
		}
		return $ret;
	}
	
function &MetaColumns($table,$normalize=true) 
	{
	global $ADODB_FETCH_MODE;
	
		$schema = false;
		$this->_findschema($table,$schema);
		
		if ($normalize) $table = strtolower($table);

		$save = $ADODB_FETCH_MODE;
		$ADODB_FETCH_MODE = ADODB_FETCH_NUM;
		if ($this->fetchMode !== false) $savem = $this->SetFetchMode(false);
		
		if ($schema) $rs =& $this->Execute(sprintf($this->metaColumnsSQL1,$table,$table,$schema));
		else $rs =& $this->Execute(sprintf($this->metaColumnsSQL,$table,$table));
		if (isset($savem)) $this->SetFetchMode($savem);
		$ADODB_FETCH_MODE = $save;
		
		if ($rs === false) {
			$false = false;
			return $false;
		}
		if (!empty($this->metaKeySQL)) {
			// If we want the primary keys, we have to issue a separate query
			// Of course, a modified version of the metaColumnsSQL query using a 
			// LEFT JOIN would have been much more elegant, but postgres does 
			// not support OUTER JOINS. So here is the clumsy way.
			
			$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
			
			$rskey = $this->Execute(sprintf($this->metaKeySQL,($table)));
			// fetch all result in once for performance.
			$keys =& $rskey->GetArray();
			if (isset($savem)) $this->SetFetchMode($savem);
			$ADODB_FETCH_MODE = $save;
			
			$rskey->Close();
			unset($rskey);
		}

		$rsdefa = array();
		if (!empty($this->metaDefaultsSQL)) {
			$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
			$sql = sprintf($this->metaDefaultsSQL, ($table));
			$rsdef = $this->Execute($sql);
			if (isset($savem)) $this->SetFetchMode($savem);
			$ADODB_FETCH_MODE = $save;
			
			if ($rsdef) {
				while (!$rsdef->EOF) {
					$num = $rsdef->fields['num'];
					$s = $rsdef->fields['def'];
					if (strpos($s,'::')===false && substr($s, 0, 1) == "'") { /* quoted strings hack... for now... fixme */
						$s = substr($s, 1);
						$s = substr($s, 0, strlen($s) - 1);
					}

					$rsdefa[$num] = $s;
					$rsdef->MoveNext();
				}
			} else {
				ADOConnection::outp( "==> SQL => " . $sql);
			}
			unset($rsdef);
		}
	
		$retarr = array();
		while (!$rs->EOF) { 	
			$fld = new ADOFieldObject();
			$fld->name = $rs->fields[0];
			$fld->type = $rs->fields[1];
			$fld->max_length = $rs->fields[2];
			if ($fld->max_length <= 0) $fld->max_length = $rs->fields[3]-4;
			if ($fld->max_length <= 0) $fld->max_length = -1;
			if ($fld->type == 'numeric') {
				$fld->scale = $fld->max_length & 0xFFFF;
				$fld->max_length >>= 16;
			}
			// dannym
			// 5 hasdefault; 6 num-of-column
			$fld->has_default = ($rs->fields[5] == 't');
			if ($fld->has_default) {
				$fld->default_value = $rsdefa[$rs->fields[6]];
			}

			//Freek
			if ($rs->fields[4] == $this->true) {
				$fld->not_null = true;
			}
			
			// Freek
			if (is_array($keys)) {
				foreach($keys as $key) {
					if ($fld->name == $key['column_name'] AND $key['primary_key'] == $this->true) 
						$fld->primary_key = true;
					if ($fld->name == $key['column_name'] AND $key['unique_key'] == $this->true) 
						$fld->unique = true; // What name is more compatible?
				}
			}
			
			if ($ADODB_FETCH_MODE == ADODB_FETCH_NUM) $retarr[] = $fld;	
			else $retarr[($normalize) ? strtoupper($fld->name) : $fld->name] = $fld;
			
			$rs->MoveNext();
		}
		$rs->Close();
		return empty($retarr) ? false : $retarr;	
		
	}

}

class ADODB_pdo_base extends ADODB_pdo {

	function ServerInfo()
	{
		return ADOConnection::ServerInfo();
	}
	
	function &SelectLimit($sql,$nrows=-1,$offset=-1,$inputarr=false,$secs2cache=0)
	{
		$ret = ADOConnection::SelectLimit($sql,$nrows,$offset,$inputarr,$secs2cache);
		return $ret;
	}
	
	function MetaTables()
	{
		return false;
	}
	
	function MetaColumns()
	{
		return false;
	}
}


class ADODB_pdo extends ADOConnection {
	var $databaseType = "pdo";	
	var $dataProvider = "pdo";
	var $fmtDate = "'Y-m-d'";
	var $fmtTimeStamp = "'Y-m-d, h:i:sA'";
	var $replaceQuote = "''"; // string to use to replace quotes
	var $hasAffectedRows = true;
	var $_bindInputArray = true;	
	var $_genSeqSQL = "create table %s (id integer)";
	var $_autocommit = true;
	var $_haserrorfunctions = true;
	var $_lastAffectedRows = 0;
	
	var $dsnType = '';
	var $stmt = false;
	
	function ADODB_pdo()
	{
	}
	
	function _UpdatePDO()
	{
		$d = &$this->_driver;
		$this->fmtDate = $d->fmtDate;
		$this->fmtTimeStamp = $d->fmtTimeStamp;
		$this->replaceQuote = $d->replaceQuote;
		$this->sysDate = $d->sysDate;
		$this->sysTimeStamp = $d->sysTimeStamp;
		$this->random = $d->random;
		$this->concat_operator = $d->concat_operator;
	}
	
	function Time()
	{
		return false;
	}
	
	// returns true or false
	function _connect($argDSN, $argUsername, $argPassword, $argDatabasename, $persist=false)
	{
		$at = strpos($argDSN,':');
		$this->dsnType = substr($argDSN,0,$at);

		$this->_connectionID = new PDO($argDSN, $argUsername, $argPassword);
		if ($this->_connectionID) {
			switch(ADODB_ASSOC_CASE){
			case 0: $m = PDO_CASE_LOWER; break;
			case 1: $m = PDO_CASE_UPPER; break;
			default:
			case 2: $m = PDO_CASE_NATURAL; break;
			}
			
			//$this->_connectionID->setAttribute(PDO_ATTR_ERRMODE,PDO_ERRMODE_SILENT );
			$this->_connectionID->setAttribute(PDO_ATTR_CASE,$m);
			
			$class = 'ADODB_pdo_'.$this->dsnType;
			//$this->_connectionID->setAttribute(PDO_ATTR_AUTOCOMMIT,true);
			if (class_exists($class))
				$this->_driver = new $class();
			else
				$this->_driver = new ADODB_pdo_base();
			
			$this->_driver->_connectionID = $this->_connectionID;
			$this->_UpdatePDO();
			return true;
		}
		$this->_driver = new ADODB_pdo_base();
		return false;
	}
	
	// returns true or false
	function _pconnect($argDSN, $argUsername, $argPassword, $argDatabasename)
	{
		return $this->_connect($argDSN, $argUsername, $argPassword, $argDatabasename, true);
	}
	
	/*------------------------------------------------------------------------------*/
	
	
	function &SelectLimit($sql,$nrows=-1,$offset=-1,$inputarr=false,$secs2cache=0) 
	{	
		$save = $this->_driver->fetchMode;
		$this->_driver->fetchMode = $this->fetchMode;
		$ret = $this->_driver->SelectLimit($sql,$nrows,$offset,$inputarr,$secs2cache);
		$this->_driver->fetchMode = $save;
		return $ret;
	}
	
	
	function ServerInfo()
	{
		return $this->_driver->ServerInfo();
	}
	
	function MetaTables($ttype=false,$showSchema=false,$mask=false)
	{
		return $this->_driver->MetaTables($ttype,$showSchema,$mask);
	}
	
	function MetaColumns($table,$normalize=true)
	{
		return $this->_driver->MetaColumns($table,$normalize);
	}
	
	function ErrorMsg()
	{
		if ($this->_stmt) $arr = $this->_stmt->errorInfo();
		else $arr = $this->_connectionID->errorInfo();
		
		if ($arr) {
			if ((integer)$arr[0]) return $arr[2];
			else return '';
		} else return '-1';
	}
	
	function InParameter(&$stmt,&$var,$name,$maxLen=4000,$type=false)
	{
		$obj = $stmt[1];
		if ($type) $obj->bindParam($name,$var,$type,$maxLen);
		else $obj->bindParam($name, $var);
	}
	
	function ErrorNo()
	{
		if ($this->_stmt) $err = $this->_stmt->errorCode();
		else {
			$arr = $this->_connectionID->errorInfo();
			if (isset($arr[0])) $err = $arr[0];
			else $err = -1;
		}
		if ($err == '00000') return 0; // allows empty check
		return $err;
	}

	function BeginTrans()
	{	
		if (!$this->hasTransactions) return false;
		if ($this->transOff) return true; 
		$this->transCnt += 1;
		$this->_autocommit = false;
		$this->_connectionID->setAttribute(PDO_ATTR_AUTOCOMMIT,false);
		return $this->_connectionID->beginTransaction();
	}
	
	function CommitTrans($ok=true) 
	{ 
		if (!$this->hasTransactions) return false;
		if ($this->transOff) return true; 
		if (!$ok) return $this->RollbackTrans();
		if ($this->transCnt) $this->transCnt -= 1;
		$this->_autocommit = true;
		
		$ret = $this->_connectionID->commit();
		$this->_connectionID->setAttribute(PDO_ATTR_AUTOCOMMIT,true);
		return $ret;
	}
	
	function RollbackTrans()
	{
		if (!$this->hasTransactions) return false;
		if ($this->transOff) return true; 
		if ($this->transCnt) $this->transCnt -= 1;
		$this->_autocommit = true;
		
		$ret = $this->_connectionID->rollback();
		$this->_connectionID->setAttribute(PDO_ATTR_AUTOCOMMIT,true);
		return $ret;
	}
	
	function Prepare($sql)
	{
		$this->_stmt = $this->_connectionID->prepare($sql);
		if ($this->_stmt) return array($sql,$this->_stmt);
		
		return false;
	}
	
	function PrepareStmt($sql)
	{
		$stmt = $this->_connectionID->prepare($sql);
		if (!$stmt) return false;
		$obj = new ADOPDOStatement($stmt,$this);
		return $obj;
	}

	/* returns queryID or false */
	function _query($sql,$inputarr=false) 
	{
		if (is_array($sql)) {
			$stmt = $sql[1];
		} else {
			$stmt = $this->_connectionID->prepare($sql);
		}
		if ($stmt) {
			if ($inputarr) $ok = $stmt->execute($inputarr);
			else $ok = $stmt->execute();
		}
		if ($ok) {
			$this->_stmt = $stmt;
			return $stmt;
		} 
		return false;
	}

	// returns true or false
	function _close()
	{
		$this->_stmt = false;
		return true;
	}

	function _affectedrows()
	{
		return ($this->_stmt) ? $this->_stmt->rowCount() : 0;
	}
	
	function _insertid()
	{
		return ($this->_connectionID) ? $this->_connectionID->lastInsertId() : 0;
	}
}

class ADOPDOStatement {

	var $databaseType = "pdo";		
	var $dataProvider = "pdo";
	var $_stmt;
	var $_connectionID;
	
	function ADOPDOStatement($stmt,$connection)
	{
		$this->_stmt = $stmt;
		$this->_connectionID = $connection;
	}
	
	function Execute($inputArr=false)
	{
		$savestmt = $this->_connectionID->_stmt;
		$rs = $this->_connectionID->Execute(array(false,$this->_stmt),$inputArr);
		$this->_connectionID->_stmt = $savestmt;
		return $rs;
	}
	
	function InParameter(&$var,$name,$maxLen=4000,$type=false)
	{

		if ($type) $this->_stmt->bindParam($name,$var,$type,$maxLen);
		else $this->_stmt->bindParam($name, $var);
	}
	
	function Affected_Rows()
	{
		return ($this->_stmt) ? $this->_stmt->rowCount() : 0;
	}
	
	function ErrorMsg()
	{
		if ($this->_stmt) $arr = $this->_stmt->errorInfo();
		else $arr = $this->_connectionID->errorInfo();
		print_r($arr);
		if (is_array($arr)) {
			if ((integer) $arr[0] && isset($arr[2])) return $arr[2];
			else return '';
		} else return '-1';
	}
	
	function NumCols()
	{
		return ($this->_stmt) ? $this->_stmt->columnCount() : 0;
	}
	
	function ErrorNo()
	{
		if ($this->_stmt) return $this->_stmt->errorCode();
		else return $this->_connectionID->errorInfo();
	}
}

/*--------------------------------------------------------------------------------------
	 Class Name: Recordset
--------------------------------------------------------------------------------------*/

class ADORecordSet_pdo extends ADORecordSet {	
	
	var $bind = false;
	var $databaseType = "pdo";		
	var $dataProvider = "pdo";
	
	function ADORecordSet_pdo($id,$mode=false)
	{
		if ($mode === false) {  
			global $ADODB_FETCH_MODE;
			$mode = $ADODB_FETCH_MODE;
		}
		$this->adodbFetchMode = $mode;
		switch($mode) {
		case ADODB_FETCH_NUM: $mode = PDO_FETCH_NUM; break;
		case ADODB_FETCH_ASSOC:  $mode = PDO_FETCH_ASSOC; break;
		
		case ADODB_FETCH_BOTH: 
		default: $mode = PDO_FETCH_BOTH; break;
		}
		$this->fetchMode = $mode;
		
		$this->_queryID = $id;
		$this->ADORecordSet($id);
	}

	
	function Init()
	{
		if ($this->_inited) return;
		$this->_inited = true;
		if ($this->_queryID) @$this->_initrs();
		else {
			$this->_numOfRows = 0;
			$this->_numOfFields = 0;
		}
		if ($this->_numOfRows != 0 && $this->_currentRow == -1) {
			$this->_currentRow = 0;
			if ($this->EOF = ($this->_fetch() === false)) {
				$this->_numOfRows = 0; // _numOfRows could be -1
			}
		} else {
			$this->EOF = true;
		}
	}
	
	function _initrs()
	{
	global $ADODB_COUNTRECS;
	
		$this->_numOfRows = ($ADODB_COUNTRECS) ? @$this->_queryID->rowCount() : -1;
		if (!$this->_numOfRows) $this->_numOfRows = -1;
		$this->_numOfFields = $this->_queryID->columnCount();
	}

	// returns the field object
	function &FetchField($fieldOffset = -1) 
	{
		$off=$fieldOffset+1; // offsets begin at 1
		
		$o= new ADOFieldObject();
		$arr = $this->_queryID->getColumnMeta($fieldOffset);
		if (!$arr) return false;

		//adodb_pr($arr);
		$o->name = $arr['name'];
		if (isset($arr['native_type'])) $o->type = $arr['native_type'];
		else $o->type = adodb_pdo_type($arr['pdo_type']);
		$o->max_length = $arr['len'];
		$o->precision = $arr['precision'];
		
		if (ADODB_ASSOC_CASE == 0) $o->name = strtolower($o->name);
		else if (ADODB_ASSOC_CASE == 1) $o->name = strtoupper($o->name);
		return $o;
	}
	
	function _seek($row)
	{
		return false;
	}
	
	function _fetch()
	{
		if (!$this->_queryID) return false;
		
		$this->fields = $this->_queryID->fetch($this->fetchMode);
		return !empty($this->fields);
	}
	
	function _close() 
	{
		$this->_queryID = false;
	}
	
	function Fields($colname)
	{
		if ($this->adodbFetchMode != ADODB_FETCH_NUM) return @$this->fields[$colname];
		
		if (!$this->bind) {
			$this->bind = array();
			for ($i=0; $i < $this->_numOfFields; $i++) {
				$o = $this->FetchField($i);
				$this->bind[strtoupper($o->name)] = $i;
			}
		}
		 return $this->fields[$this->bind[strtoupper($colname)]];
	}

}

?>