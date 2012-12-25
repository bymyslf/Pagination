<?php
	require('Pagination.php');
	
  	class MySqlPagination extends Pagination 
    {
        public function __construct(array $options) {
  			parent::__construct($options);
		}
		
		public static function factory(array $options) {
			return new MySqlPagination($options);
		}
        
        public function rows() {
			if (!is_null($this['sqlConnection']) && !is_resource($this['sqlConnection'])) {
				if ($this['debug']) {
                    throw new RuntimeException('Check if the provided sql connection is a valid resource!');
				}	
				return false;
			}
 
            if (is_null($this['sqlConnection'])) {
                $resultTotal = mysql_query($this['sqlStatement']);
            } else {
                $resultTotal = mysql_query($this['sqlStatement'], $this['sqlConnection']);
            }
     
            $this->rowCount = mysql_num_rows($resultTotal);
			if ($this->rowCount == 0) {
				if ($this['debug']) {
                    throw new RuntimeException('Query returned zero rows.');
				}
				return false;
			}
            
            if ($this['currentPage'] === 'all') {
				$paginationQuery = $this['sqlStatement'] . ' ' . $this['orderBy'];
                $this->totalPages = 1;
			} else {
                $page = (int)$this['currentPage'];
                $itemsPerPage = (int)$this['itemsPerPage'];
				$this->totalPages = ceil($this->rowCount / $itemsPerPage);
				$limitBegin = (($page - 1) * $itemsPerPage);
                $paginationQuery = $this['sqlStatement'] . ' ' . $this['orderBy'] . ' LIMIT ' . $limitBegin . ', ' . $itemsPerPage;
			}
    
            if (is_null($this['sqlConnection'])) {
                $rows = mysql_query($paginationQuery);
            } else {
                $rows = mysql_query($paginationQuery, $this['sqlConnection']);
            }
   
   		$this->currentTotal = mysql_num_rows($rows) + $limitBegin;
			return $rows;
		}
    }
?>
