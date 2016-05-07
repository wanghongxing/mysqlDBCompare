<?php
/**
 * 生成mysql数据字典
 */
// 配置数据库
$database = array();
$database['DB_HOST'] = '127.0.0.1';
$database['DB_USER'] = 'root';
$database['DB_PWD'] = '';
   
   
$mysql_conn = @mysql_connect("{$database['DB_HOST']}", "{$database['DB_USER']}", "{$database['DB_PWD']}") or die("Mysql connect is error.");

$db_rel = getDBSchema('test1' , $mysql_conn);
$db_dev = getDBSchema('test2' , $mysql_conn);
mysql_close($mysql_conn);

 $table_delete = array_diff_key($db_rel , $db_dev );
 $table_add = array_diff_key( $db_dev , $db_rel);
 
 foreach($table_delete as $table_name=>$v)
 {
 	echo "delete Table:" .$table_name . "\n";
 }
 foreach($table_add as $table_name=>$v)
 {
 	echo "add Table:" .$table_name . "\n";
 }
 
 $table_interset= array_intersect_key( $db_rel , $db_dev );
 foreach($table_interset as $table_name=>$table)
 {
 	compare_table_column( $db_rel[$table_name] , $db_dev[$table_name] );
  	compare_table_key( $db_rel[$table_name] , $db_dev[$table_name] );

 }
 
// print_r($table_interset);


function compare_table_column($table_org , $table_dev )
{
echo "Table " . $table_org['TABLE_NAME'] . " \tcolumn\n";
	 $col_delete = array_diff_key($table_org['COLUMN'] , $table_dev['COLUMN']  );
	 $col_add = array_diff_key( $table_dev['COLUMN']  , $table_org['COLUMN'] );
	 $col_interset = array_intersect_key( $table_dev['COLUMN']  , $table_org['COLUMN'] );

	 foreach($col_delete as $column_name=>$v)
	 {
		echo "\t delete column:" .$column_name . "\n\t\t  ";
		echo getColumnDesc($table_org['COLUMN'][$column_name]) ."\n";
		echo "\t\talter table {$table_org['TABLE_NAME']} drop column  $column_name \n";
	 }
	 foreach($col_add as $column_name=>$v)
	 {
		echo "\t add column:" .$column_name . "\n\t\t  ";
		echo getColumnDesc($table_dev['COLUMN'][$column_name])  ;
		echo "\n\t\t alter table {$table_org['TABLE_NAME']} add " . getColumnDesc($table_dev['COLUMN'][$column_name])  ."\n";
	 }
	 
	 foreach($col_interset as $column_name=>$column)
	 {
		foreach( $table_org['COLUMN'][ $column_name ] as $k=>$v_org)
		{
			$v_dev = $table_dev['COLUMN'][ $column_name ][ $k ] ;
			if( $v_dev != $v_org)
			{
				echo "\t change column:$column_name\n\t\t from:\t";
				echo getColumnDesc($table_org['COLUMN'][$column_name]) ;
				echo "\n\t\t to  :\t";
				echo getColumnDesc($table_dev['COLUMN'][$column_name]) . "\n";
				echo "\n\t\t\t alter table {$table_org['TABLE_NAME']} change `$column_name`  ";
				echo getColumnDesc($table_dev['COLUMN'][$column_name]) . "\n";
				break;

			}
		
		}
	 }
	 
	 
	 
	 
}

function compare_table_key($table_org , $table_dev )
{
echo "Table " . $table_org['TABLE_NAME'] . " \tindex\n";
	 $index_delete = array_diff_key($table_org['index'] , $table_dev['index']  );
	 $index_add = array_diff_key( $table_dev['index']  , $table_org['index'] );
	 $index_interset = array_intersect_key( $table_dev['index']  , $table_org['index'] );


	 foreach($index_delete as $index_name=>$v)
	 {
		echo "\t delete index:" .$index_name . "\n\t\t";
		echo getindexDesc( $table_org['index'][ $index_name ]) ."\n";
		echo "\t\talter table {$table_org['TABLE_NAME']} drop index  $index_name \n";
	 }
	 foreach($index_add as $index_name=>$v)
	 {
		echo "\t add index:" .$index_name . "\n\t\t";
		echo getindexDesc( $table_dev['index'][ $index_name ]) ."\n";
		echo "\n\t\t alter table {$table_org['TABLE_NAME']} add " . getindexDesc($table_dev['index'][$index_name])  ."\n";
	 }
 
	 foreach($index_interset as $index_name=>$column)
	 {
	 
	 	
	 
		foreach( $table_org['index'][ $index_name ] as $k=>$v_org)
		{
			$v_dev = $table_dev['index'][ $index_name ][ $k ] ;
			
			if( !compareIndex( $table_org['index'][ $index_name ] ,$table_dev['index'][ $index_name ]))
			{
				echo "\t change column:$index_name\n\t\t from:\t";
				echo getindexDesc( $table_org['index'][ $index_name ]);
				echo "\n\t\t to  :\t";
				echo getindexDesc( $table_dev['index'][ $index_name ]);
				echo "\n";
				echo "\n\t\t alter table {$table_org['TABLE_NAME']} drop index $index_name ";
				echo "\n\t\t alter table {$table_org['TABLE_NAME']} add ";
				echo getindexDesc( $table_dev['index'][ $index_name ]);
				echo "\n";
				break;
			
			}
		
		}
	 }
	 
	 
}
 

function compareIndex($from,$to)
{
	if($from['type'] != $to['type'] )
		return false;
	if($from['name'] != $to['name'] )
		return false;
	if($from['COMMENT'] != $to['COMMENT'] )
		return false;
	
	if( count($from['columns']) != 	count($to['columns'])  )
		return false;
		
	foreach($from['columns'] as $k=>$v_org)
	{
		if( $from['columns'][ $k] != $to['columns'][ $k])
			return false;
	}
	return true;
		
}
function getindexDesc($index)
{
	$desc="";
	$desc .=$index['type'] . " ";
	if($index['type']=="UNIQUE")
		$desc .="KEY ";

	if( count($index['columns']) == 1 && $index['columns'][1] == $index['name'] )
	{
	//索引名称客户忽略
	}else{
		$desc .= '`' .  $index['name'] .'` ';
	}
	
	$desc .= '(`' . implode( $index['columns'] , '`,`' ) . '`) ';

	if(strlen($index['COMMENT'])>0)
	$desc .= 'COMMENT ' .$index['COMMENT'];
	
 
	return $desc;
		
}
function getColumnDesc($column)
{
	$desc="";
	$desc .= $column['COLUMN_NAME'] ." ";
	$desc .= $column['COLUMN_TYPE'] ." ";
	$desc .= $column['COLLATION_NAME'] ." ";
	$desc .= $column['IS_NULLABLE'] ." ";
	$desc .= $column['COLUMN_DEFAULT'] ." ";
	$desc .= $column['EXTRA'] ." ";
	if(strlen($column['COLUMN_COMMENT'])>0)
		$desc .= 'COMMENT ' .$column['COLUMN_COMMENT'];
 
	return $desc;
		
}
function getDBSchema($dbname , $mysql_conn)
{
	mysql_select_db($dbname, $mysql_conn);
	$result = mysql_query('show tables', $mysql_conn);
	mysql_query('SET NAME GBK', $mysql_conn);
	// 取得所有表名
	$tables=array();
	while ($row = mysql_fetch_array($result))
	{
		$tables[ $row[0] ]['TABLE_NAME'] = $row[0];
	}
	// 循环取得所有表的备注及表中列消息
	foreach($tables as $k => $v)
	{
		$sql = 'SELECT * FROM ';
		$sql .= 'INFORMATION_SCHEMA.TABLES ';
		$sql .= 'WHERE ';
		$sql .= "table_name = '{$v['TABLE_NAME']}' AND table_schema = '{$dbname}'";
		$table_result = mysql_query($sql, $mysql_conn);
		while ($t = mysql_fetch_array($table_result))
		{
			$tables[$k]['TABLE_COMMENT'] = $t['TABLE_COMMENT'];
		}
		$sql = 'SELECT * FROM ';
		$sql .= 'INFORMATION_SCHEMA.COLUMNS ';
		$sql .= 'WHERE ';
		$sql .= "table_name = '{$v['TABLE_NAME']}' AND table_schema = '{$dbname}'";
   
		$fields = array();
		$field_result = mysql_query($sql, $mysql_conn);
		while ($t = mysql_fetch_array($field_result))
		{
			$filed['COLUMN_NAME'] =  '`'.$t['COLUMN_NAME'] . '`' ;
			$filed['COLUMN_TYPE'] =  $t['COLUMN_TYPE']  ;
			if( $t['COLLATION_NAME'] != NULL )
				$filed['COLLATION_NAME'] =  'COLLATE ' . $t['COLLATION_NAME'] ;
			else
				$filed['COLLATION_NAME'] =  ''   ;

			$filed['IS_NULLABLE'] =   'YES' == $t['IS_NULLABLE'] ? 'NULL' : 'NOT NULL' ;

			if( 'YES' == $t['IS_NULLABLE'] && is_null($t['COLUMN_DEFAULT']) )
				$filed['COLUMN_DEFAULT'] = 'DEFAULT NULL';
			elseif( is_null($t['COLUMN_DEFAULT']) )
				$filed['COLUMN_DEFAULT'] = '';
			else
				$filed['COLUMN_DEFAULT'] = 'DEFAULT \'' . $t['COLUMN_DEFAULT']  .'\'';
			
			
			$filed['EXTRA'] =  $t['EXTRA']  ;
			$filed['COLUMN_COMMENT'] =  $t['COLUMN_COMMENT']  ;
		
			$fields[ $t['COLUMN_NAME'] ] = $filed ;
		}
		$tables[$k]['COLUMN'] = $fields;
	
		$sql_index = 'SELECT s.INDEX_NAME,NON_UNIQUE,SEQ_IN_INDEX,s.COLUMN_NAME,t.CONSTRAINT_TYPE ,s.COMMENT FROM ';
		$sql_index .= 'INFORMATION_SCHEMA.STATISTICS s LEFT OUTER JOIN  INFORMATION_SCHEMA.TABLE_CONSTRAINTS t ';
		$sql_index .= 'ON t.TABLE_SCHEMA=s.TABLE_SCHEMA AND t.TABLE_NAME=s.TABLE_NAME AND s.INDEX_NAME=t.CONSTRAINT_NAME ';		
		
		$sql_index .= 'WHERE ';
		$sql_index .= "s.table_name = '{$v['TABLE_NAME']}' AND s.table_schema = '{$dbname}'";
   
		$indexs = array();
		$indexs_result = mysql_query($sql_index, $mysql_conn);
		while ($t = mysql_fetch_array($indexs_result))
		{
			if( isset( $indexs[ $t['INDEX_NAME'] ] ) )
			{
				$indexs[ $t['INDEX_NAME'] ] ['columns'][  $t['SEQ_IN_INDEX'] ] = $t['COLUMN_NAME'] ;
			}else{
				$indexs[ $t['INDEX_NAME'] ]  = array();
				$indexs[ $t['INDEX_NAME'] ] ['type'] = NULL == $t['CONSTRAINT_TYPE'] ? 'KEY' :  $t['CONSTRAINT_TYPE'] ; 
				$indexs[ $t['INDEX_NAME'] ] ['name'] = $t['INDEX_NAME']  ; 
				$indexs[ $t['INDEX_NAME'] ] ['columns'][  $t['SEQ_IN_INDEX'] ] = $t['COLUMN_NAME'] ;
				$indexs[ $t['INDEX_NAME'] ] ['COMMENT'] = $t['COMMENT'] ; 
				;
		
			}
		}
		$tables[$k]['index'] = $indexs;    
	
	
	}

	return $tables;
}

//print_r( $tables );
exit;
   
$html = '';
// 循环所有表
foreach($tables as $k => $v)
{
    $html .= '<table border="1" cellspacing="0" cellpadding="0" align="center">';
    $html .= '<caption>表名：' . $v['TABLE_NAME'] . ' ' . $v['TABLE_COMMENT'] . '</caption>';
    $html .= '<tbody><tr><th>字段名</th><th>数据类型</th><th>默认值</th><th>允许非空</th><th>自动递增</th><th>备注</th></tr>';
    $html .= '';
   
    foreach($v['COLUMN'] AS $f)
    {
        $html .= '<td class="c1">' . $f['COLUMN_NAME'] . '</td>';
        $html .= '<td class="c2">' . $f['COLUMN_TYPE'] . '</td>';
        $html .= '<td class="c3">' . $f['COLUMN_DEFAULT'] . '</td>';
        $html .= '<td class="c4">' . $f['IS_NULLABLE'] . '</td>';
        $html .= '<td class="c5">' . ($f['EXTRA'] == 'auto_increment'?'是':' ') . '</td>';
        $html .= '<td class="c6">' . $f['COLUMN_COMMENT'] . '</td>';
        $html .= '</tr>';
    }
    $html .= '</tbody></table></p>';
    
    $html .= '<table border="1" cellspacing="0" cellpadding="0" align="center">';
    $html .= '<caption>index：</caption>';
    $html .= '<tbody><tr><th>索引名</th><th>唯一</th><th>列名</th><th>备注</th></tr>';
    $html .= '';
   
    foreach($v['index'] AS $index_name=>$f)
    {
        $html .= '<td class="c1">' . $index_name . '</td>';
        $html .= '<td class="c2">' . ($f['NON_UNIQUE'] == 1?'是':' '). '</td>';
        $html .= '<td class="c4">' . implode($f['columns'] , ',')  . '</td>';
        $html .= '<td class="c5">' . $f['COMMENT'] . '</td>';
        $html .= '</tr>';
    }
    $html .= '</tbody></table></p>';
}
  
/* 生成word */
//header ( "Content-type:application/vnd.ms-word" );
//header ( "Content-Disposition:attachment;filename={$database['DB_NAME']}数据字典.doc" );
/* 生成excel*/
//header ( "Content-type:application/vnd.ms-excel" );
//header ( "Content-Disposition:attachment;filename={$database['DB_NAME']}数据字典.xls" );
  
// 输出
echo '<html>
    <meta charset="utf-8">
    <title>自动生成数据字典</title>
    <style>
        body,td,th {font-family:"宋体"; font-size:12px;} 
        table,h1,p{width:960px;margin:0px auto;}
        table{border-collapse:collapse;border:1px solid #CCC;background:#efefef;} 
        table caption{text-align:left; background-color:#fff; line-height:2em; font-size:14px; font-weight:bold; } 
        table th{text-align:left; font-weight:bold;height:26px; line-height:26px; font-size:12px; border:1px solid #CCC;padding-left:5px;} 
        table td{height:20px; font-size:12px; border:1px solid #CCC;background-color:#fff;padding-left:5px;} 
        .c1{ width: 150px;} 
        .c2{ width: 150px;} 
        .c3{ width: 80px;} 
        .c4{ width: 100px;} 
        .c5{ width: 100px;} 
        .c6{ width: 300px;}
    </style>
    <body>';
echo '<h1 style="text-align:center;">'.$database['DB_NAME'].'数据字典</h1>';
echo '<p style="text-align:center;margin:20px auto;">生成时间：' . date('Y-m-d H:i:s') . '</p>';
echo $html;
echo '<p style="text-align:left;margin:20px auto;">总共：' . count($tables) . '个数据表</p>';

echo "<pre>\n";
//print_r($tables);
echo "</pre>\n";

echo '</body></html>';
   
?>