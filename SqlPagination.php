<?php
	require('Pagination.php');

  	class SqlPagination extends Pagination
    {
        public function __construct(array $options) {
  			$this->config = array_merge($this->config, $options);

			if (empty($this['baseLink'])) {
				$this['baseLink'] = basename(htmlspecialchars($_SERVER['PHP_SELF']));
			}
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
            
            $resultTotal = mssql_query($this['sqlStatement'], $this['sqlConnection']);
            $this->rowCount = mssql_num_rows($resultTotal);
			if ($this->rowCount == 0) {
				if ($this['debug']) {
                    throw new RuntimeException('Query returned zero rows.');
				}
				return false;
			}
            
            if ($this['page'] === 'all') {
				$paginationQuery = $this['sqlStatement'] . ' ' . $this['orderBy'];
			} else {
                $page = (int)$this['page'];
                $itemsPerPage = (int)$this['itemsPerPage'];
                
				$this->totalPages = ceil($this->rowCount / $itemsPerPage);
				$limitBegin = (($page - 1) * $itemsPerPage) + 1;

				$maxRowNumber = ($itemsPerPage * $page);
				$paginationQuery = $this['sqlStatement'] . ' WHERE RowNumber BETWEEN ' . $limitBegin . ' AND ' . $maxRowNumber;
			}
            
			return mssql_query($paginationQuery, $this['sqlConnection']);
		}
    }
?>
