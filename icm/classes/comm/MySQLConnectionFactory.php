<? include $_SERVER["DOCUMENT_ROOT"] . '/common/classes/comm/Common.php'; ?>
<? 

/**	
	최종버전	: 2.0			
	표시자		: nukiboy 
	
**/



if(! class_exists("DB") )
{
	
	// 데이터 베이스 접속 및 쿼리 처리 class
	class DB extends Common 
	{ 
		var $db;				//db connection
		var $result ;			//result set
		var $debug = true ;		//is debug??

		var $charset = "UTF8" ;
			  
		function DB () 
		{
			$this->connect_db($this->dbHost, $this->dbUser, $this->dbPass,$this->dbName,$this->charset) ; 
		}
		
		/*
			터이터 베이스에 접속하는 함수
		*/
		function connect_db($db_host, $db_user, $db_pass, $db_name,$db_connection_set) 
		{ 
			$this->db = new mysqli($db_host, $db_user, $db_pass, $db_name); 

			// 20091222 added by nukiboy
			$this->db->set_charset( $db_connection_set ) ;
			
			//접속실패시 메시지
			$this->check_connect() ;
		}


		/* 
			 db에서 select 쿼리를 실행하는 함수 
		*/ 
		function query($query) 
		{
			if( $this->check_connect() == false )
				return ;
		
			$this->result = @mysqli_query($this->db,$query) or die( ( $this->debug ) ? mysqli_error( $this->db ) : "Error : excute query" ) ;
		}

		/*
			db에 다중 쿼리를 실행한다
		*/
		function multi_query($query)
		{
			return @mysqli_multi_query($this->db,$query) or die( ( $this->debug ) ? mysqli_error( $this->db ) : "Error : excute multi query" ) ;
		}
		

		/*
			데이터 베이스 접속 체크
		*/
		function check_connect()
		{
			if( mysqli_connect_errno() ) 
			{
				$this->trace_error("Connect failed. db 접속이 유효하지 않습니다."); 
				return false ;
			}

			return true ;
		}


		/* 
			 db 에서 insert, delect, update 쿼리를 실행하는 함수 
			 return : 적용된 rows 수, 에러가 있다면, -1를 반환 
		*/ 
		function update($query) 
		{
			try
			{
				$this->query($query) ;
				return mysqli_affected_rows($this->db) ;
			}
			catch(Exception $e)
			{
				throw new Exception("update error") ;
			}
		} 

		/* 
			 select query된 result set을 반환한다.  
			 꼭 필요한 경우 사용하고 보통은 next_fetch()를 사용하도록 한다.  
		*/ 
		function get_result() 
		{ 
			return $this->result; 
		} 



		/* 
			 결과셋의 다음 행 반환
		*/ 
		function next_row() 
		{ 
			$assoc = $this->result->fetch_assoc() ;

			return $assoc ;
		}

		// 20091222 added by nukiboy
		function getNum()
		{
			return mysqli_num_rows($this->result)  ;
		}

		function next_result()
		{
			return $this->db->next_result() ;
		}

		function mysql_insert_id()
		{
			return mysqli_insert_id($this->db) ;
		}

		/* 
			 에러 메세지를 출력한다. 
		*/ 
		function trace_error($msg) 
		{
			if( $this->debug )
			{

				echo "</UL></DL></OL>\n"; 
				echo "</TABLE></SCRIPT>\n"; 
				$text  = "<FONT COLOR=\"#ff0000\"><P>Error: $msg : </p>"; 
				$text .= mysql_error(); 
				$text .= "</FONT>\n"; 
				die($text); 

			}
		} 

		/* 
			 db 와 연결된 자원을 반납한다. 
			 페이지 output이 끝나면 자동적으로 접속이 끊기지만, 명시적으로 
			 db접속이 끊기는걸 넣어주는게 좋습니다. 
		*/ 
		function destroy() 
		{
			
			if(is_resource($this->result)) mysql_free_result($this->result);  
			mysql_close($this->db); 
		} 

		/* 
			 destroy()라는 이름보다 close()를 선호하실거 같아 넣었습니다. 
		*/ 
		function close() 
		{
			$this->destroy(); 
		}

		function getValue($sql,$col)
		{
			$row = $this->getRow($sql) ;
			return $row[$col] ;
		}

		function getRow($sql) 
		{
			$this->query($sql) ;
			
			$row = null ;
			if( 1 <= $this->getNum())
				$row = $this->next_row() ;
			
			$this->result->close(); 

			return $row ;
		}
		
		function getMultiRow($sql)
		{
			$this->multi_query($sql) ;
			return $this->next_row() ;
		}
		
		//배열 가져오기
		function getArray($sql)
		{
			
			$this->query($sql) ;
			
			$arrResult = Array() ;

			$i = 0 ;
			
			while($row = $this->next_row()){

				$arrResult[$i] = $row ;
				$i ++ ;
			}

			$this->result->close(); 
			
			return $arrResult ;
		}


		//배열 가져오기
		function getMultiArray($sql)
		{

			$isResult = $this->multi_query($sql) ;

			if( $isResult ) 
			{
				$pack = Array() ;
				$multiNum = 0 ;

				do 
				{ 
					if ($this->result = $this->db->store_result()) 
					{ 
						$set = Array() ;
						$i = 0 ;
						while( $row = $this->next_row() ) 
						{ 
							$set[$i] = $row ;
							$i ++ ;
						}
						
						$this->result->close(); 

						$pack[$multiNum] = $set ;
						$multiNum ++ ;
					} 
				} 
				while( $this->next_result() ); 
			}
			
//			$this->db->close(); 

			return $pack ;

		}

		function getSafeArray($sql,$rownum)
		{
			return $this->getSafe2Array($sql,$rownum,"데이터가 없습니다.") ;
		}

		function reverseArray($arr)
		{
			$temp = array() ;
			for($i = sizeof($arr) - 1 ; $i >= 0 ; $i --)
			{
				array_push($temp,$arr[$i]) ;
			}

			return $temp ;

		}

		function getJson($sql)
		{
			$data = $this->getArray($sql) ;
			
			return $this->encode($data) ;
		}

		function getMultiJson($sql)
		{
			$data = $this->getMultiArray($sql) ;
			
			return $this->encode($data) ;
		}

		// Modified/Moved by nukiboy 2011.01.24 From HomeFrm
		function makeEqAndQuery($assoc,$isWhere)
		{
			$sql = ( $isWhere == true ) ?  " WHERE 1=1 " : " " ;

			foreach( $assoc as $key => $value )
			{
				if( $value != "" && $value != "null" )	// Modified by nukiboy 2010.12.20 
					$sql .= ' AND `' . $key . '`="' . $value . '"'  ;
			}

			return $sql ;

		}

		function makeLikeQuery($assoc,$isWhere)
		{
			$sql = ( $isWhere == true ) ?  " WHERE 1=1 " : " " ;

			foreach( $assoc as $key => $value )
			{
				if( $value != "" )	// Modified by nukiboy 2010.12.20 
					$sql .= ' AND `' . $key . '` LIKE "%' . $value . '%" ' ;
			}

			return $sql ;

		}

		function makeBetweenQuery($assoc,$isWhere)
		{
			$sql = ( $isWhere == true ) ?  " WHERE 1=1 " : " " ;

			foreach( $assoc as $key => $value )
			{
				if( is_array($value) && $value[0] != "" && $value[1] != "")	
					$sql .= ' AND (`' . $key . '` between "' . $value[0] . '" and "' . $value[1] . '" )'  ;
			}

			return $sql ;

		}


		//업데이트
		function techOfUpdate($sno,$dynaT,$data)
		{

			$dynaSQL	= "" ;
			$len		= sizeof($data) ;
			$i = 1 ;

			foreach($data as $key => $value)
			{
				if( $value == "" )
				{
					$tmp = explode("+",$value) ;
					$dynaSQL .=	(sizeof($tmp) >= 1) ? "`$key` = '$value'" : "`$key` = '$value'" ; 
				}
				else if( $value == "now()" || $value == "NOW()" )
						$dynaSQL .=	 "`$key` = now()" ;
				else
				{
					$tmp = explode("+",$value) ;
					$dynaSQL .=	(sizeof($tmp) >= 1) ? "`$key` = '$value'" : "`$key` = '$value'" ; 
				}
				$dynaSQL .= ( $i != $len ) ? "," : "" ;
				$i++ ;
			}
				
			$sql = "UPDATE 
						$dynaT 
					SET 
						$dynaSQL 
					WHERE 
					{$this->pkName} = '$sno'
					" ;
					
			// echo $sql ;	
				
			return $this->update($sql) ;
		}


		//삽입하기
		function techOfInsert($dynaT,$data)
		{

			$dynaSQL1 	= "" ;
			$dynaSQL2	= "" ;
			$len		= sizeof($data) ;
			$i			= 1 ;

			while (list ($key, $val) = each ($data)) {


				$dynaSQL1 .= "`" . $key . "`"  ;
				$dynaSQL1 .= ( $i != $len ) ? "," : "" ;
				

				if( $val == "" )
				{
					$dynaSQL2 .= "'" . $val . "'" ;
				}

				else if( $val == "now()" )	{

						$dynaSQL2 .= "now()" ;

				}
				else	{
						$dynaSQL2 .= "'" . $val . "'" ;

				}

				$dynaSQL2 .= ( $i != $len ) ? "," : "" ;

				$i++ ;
			}


			$sql = "INSERT INTO 
						$dynaT 
						($dynaSQL1) 
					VALUES 
						($dynaSQL2)
					" ;

			// echo $sql ;
								
			return $this->query($sql) ;

			}
       
	   	//프로시저 호출 스트링 생성
		function strCallProc($procName,$arrParam,$arrRet)
		{
			$str = "call " . $procName . "(" ;
			$isParam = false ;

			for( $i = 0 ; $i < sizeof($arrParam) ; $i ++ )
			{
				$str .= ( $i == 0 ) ? "" : "," ;
				$str .= "'" . $arrParam[$i] . "'";

				$isParam = true ;
			}

			if( $isParam )
				$str .= "," ;

			$isRet = false ;
			foreach($arrRet as $key => $value)
			{
				if( $isRet )
					$str .= "," ;

				$str .= $value ;
				$isRet = true ;
			}
		

			$str .= ");" ;

			if( sizeof($arrRet) > 0 )
			{
				$str .= "select " ;
				foreach($arrRet as $key => $value)
				{
					$str .= " " . $value . " as " . $key ;
				}
			}

			$str .= ";" ;
			return $str ;
			
		}
	   
	   
	   
	   
	   
	   
	   
	   
	   } ;

}

?>