<?php
	require('Pagination.php');
	
  	class MySqlPagination extends Pagination 
    {
        public function __construct(array $options) {
  			$this = array_merge($this, $options);
			
			if (empty($this['baseLink'])) {
				$this['baseLink'] = basename(htmlspecialchars($_SERVER['PHP_SELF']));
			}
		}
		
		public static function factory(array $options) {
			return new MySqlPagination($options);
		}
        
        public function rows() {
			if (!is_null($this['connection']) && !is_resource($this['connection'])) {
				if ($this['debug']) {
                    throw new RuntimeException('Check if the provided sql connection is a valid resource!');
				}	
				return false;
			}
            
            $resultTotal = mysql_query($this['sqlStatement'], $this['connection']);
            $this->rowCount = mysql_num_rows($resultTotal);
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
				
                $paginationQuery = $this['sqlStatement'] . ' LIMIT ' . $limitBegin . ', ' . $itemsPerPage;
			}
            
			return mysql_query($paginationQuery, $this['connection']);
		}
    }
?>
