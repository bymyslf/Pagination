<?php
	require('Pagination.php');

  	class SqlPagination extends Pagination
    {
        public function __construct(array $options) {
  			parent::__construct($options);
		}

		public static function factory(array $options) {
			return new SqlPagination($options);
		}
        
        public function rows() {
			if (!is_null($this['sqlConnection']) && !is_resource($this['sqlConnection'])) {
				if ($this['debug']) {
                    throw new RuntimeException('Check if the provided sql connection is a valid resource!');
				}	
				return false;
			}
            
            $pos = stripos($this['sqlStatement'], "FROM");
            $count = "SELECT COUNT(*) " . substr($this['sqlStatement'], $pos);
            if (is_null($this['sqlConnection'])) {
                $resultTotal = mssql_query($count);
            } else {
                $resultTotal = mssql_query($count, $this['sqlConnection']);
            }
            
            $row = mssql_fetch_array($resultTotal);
            $this->rowCount = $row[0];
			if ($this->rowCount == 0) {
				if ($this['debug']) {
                    throw new RuntimeException('Query returned zero rows.');
				}
				return false;
			}
            
            if ($this['currentPage'] === 'all') {
				$paginationQuery = $this['sqlStatement'] . ' ' . $this['orderBy'];
                
			} else {
                $page = (int)$this['currentPage'];
                $itemsPerPage = (int)$this['itemsPerPage'];
				$this->totalPages = ceil($this->rowCount / $itemsPerPage);
				$limitBegin = (($page - 1) * $itemsPerPage);
				$maxRowNumber = ($page * $itemsPerPage);
				$paginationQuery = $this['sqlStatement'] . ' WHERE RowNumber BETWEEN ' . $limitBegin . ' AND ' . $maxRowNumber;
			}
            
            if (is_null($this['sqlConnection'])) {
                $rows = mssql_query($paginationQuery);
            } else {
                $rows = mssql_query($paginationQuery, $this['sqlConnection']);
            }
            		
            		$this->currentTotal = mssql_num_rows($rows) + $limitBegin;
			return $rows;
		}
    }
?>
