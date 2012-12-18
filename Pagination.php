<?php
	class Pagination implements ArrayAccess, Serializable 
	{	
		private $fields = array(
			'page' => 1,
            		'queryStringAlias' => 'page',
			'rows' => 5,
			'debug' => false,
			'prefix' => '',
			'suffix' => '',
			'adjacents' => 2,
			'limitBegin' => 0,
			'next' => false,
			'prev' => false,
			'first' => false,
			'last' => false,
			'viewAll' => false,
			'previousStr' => 'previous',
			'nextStr' => 'next',
			'firstStr' => 'first',
			'lastStr' => 'last',
			'viewAllStr' => array(
				'default' => 'view all',
				'selected' => 'page view'
			),
			'sqlStatement' => NULL,
			'connection' => NULL,
			'currentTotalRows' => NULL,
			'sqlRows' => NULL,
			'maxPages' => NULL,
			'totalRows' => NULL,
			'queryString' => NULL,
			'baseLink' => '',
			'mySql' => true
		);
		
		public function __construct(array $options) {
			$this->fields = array_merge($this->fields, $options);
			
			if (empty($this->fields['baseLink'])) {
				$this->fields['baseLink'] = basename(htmlspecialchars($_SERVER['PHP_SELF']));
			}
		}
		
		//Static method to provide method chaining
		public static function getInstance(array $options) {
			return new Pagination($options);
		}
		
		public function __get($name) {
			if (array_key_exists($name, $this->fields)) {
				return $this->fields[$name];
			}
		}
		
		public function __set($name, $value) {
			if (array_key_exists($name, $this->fields)) {
				$this->fields[$name] = $value;
			}
		}
        
	        /* ********* ArrayAccess methods ********* */
	        
	        public function offsetSet($offset, $value) {
	            if (is_null($offset)) {
	                $this->fields[] = $value;
	            } else {
	                $this->fields[$offset] = $value;
	            }
	        }
	        
	        public function offsetExists($offset) {
	            return isset($this->fields[$offset]);
	        }
	        
	        public function offsetUnset($offset) {
	            unset($this->fields[$offset]);
	        }
	        
	        public function offsetGet($offset) {
	            return isset($this->fields[$offset]) ? $this->fields[$offset] : null;
	        }
        
		/* ********* ArrayAccess methods ********* */
    
	        /* **** Serializable methods **** */
	        
	        public function serialize() {
	            return serialize($this->fields);
	        }
	        
	        public function unserialize($data) {
	            $this->fields = unserialize($data);
	        }
	        
	        /* **** Serializable methods **** */
        
        
		public function getCurrentTotal() {
			return $this->fields['limitBegin'] + $this->fields['currentTotalRows'];
		}
		
		public function getPreviousPage() {
			return ($this->fields['maxPages'] == 1 || $this->fields['page'] == 1) ? 1 : (int)($this->fields['page'] - 1);
		}
		
		public function getNextPage() {
			return ($this->fields['maxPages'] == 1 || $this->fields['page'] == $this->fields['maxPages']) ?  $this->fields['page'] 
                    : (int)($this->fields['page'] + 1);
		}
		
		//Public Methods
		public function getRows() {
			$resultRows = null;
			
			if (!is_null($this->fields['connection']) && !is_resource($this->fields['connection'])) {
				if ($this->fields['debug']) {
                    			throw new RuntimeException('Check if the provided sql connection is a valid resource!');
				}	
                
				return false;
			}
			
			if ($this->fields['mySql']) {
				$resultRows = $this->mySqlRows();
			} else {
				$resultRows = $this->MSSQLRows();
			}
			
			if (is_resource($resultRows)) {
				$this->fields['sqlRows'] = $resultRows;
				$this->fields['currentTotalRows'] = ($this->fields['mySql']) ? mysql_num_rows($this->fields['sqlRows']) : mssql_num_rows($this->fields['sqlRows']);
			}
			
			return $resultRows;
		}
		
		public function paginate() {
			$paginationStr = '';
	
			if ($this->fields['page'] === 'all') {
				$paginationStr .= $this->seeAllLink();
			} else {
				$auxStr = '';
				$start = 0;
				$end = 0;
				
				if (($this->fields['page'] == 1) || ($this->fields['page'] <= $this->fields['adjacents'])) {
                    $start = 1;
                    $end = (($this->fields['adjacents'] * 2) + 1);
                    if ($this->fields['maxPages'] < $end) {
                        $end = $this->fields['maxPages'];
                    }
                } else if (($this->fields['page'] == $this->fields['maxPages']) 
                    || ($this->fields['page'] == ($this->fields['maxPages'] - 1) && $this->fields['adjacents'] > 1)) {
                    $start = ($this->fields['maxPages'] - ($this->fields['adjacents'] * 2));
                    $end = $this->fields['maxPages'];
                    if ($start <= 0) {
                        $start = 1;
                    }
                } else {
                    $start = $this->fields['page'] - $this->fields['adjacents'];
                    $end = $this->fields['page'] + $this->fields['adjacents'];
                    if ($this->fields['page'] == $this->fields['adjacents']) {
                        ++$start;
                    }
                }
                
                for ($i = $start; $i <= $end; $i++) {
                    $auxStr .= $this->getLinkString(($i == $this->fields['page']) ? 'selected' : '', $i, $i);
				}
                
                $paginationStr = sprintf('%s%s%s%s%s%s', $this->firstLink(), $this->previousLink(), $auxStr, $this->nextLink(), $this->lastLink(),
                $this->seeAllLink());
			}
			
			return $paginationStr;
		}
		
		protected function mySqlRows() {
			if (!is_null($this->fields['connection'])) {
				$resultTotal = mysql_query(sprintf('%s', $this->fields['sqlStatement']), $this->fields['connection']);
			} else {
				$resultTotal = mysql_query(sprintf('%s', $this->fields['sqlStatement']));
			}
			
			$this->fields['totalRows'] = mysql_num_rows($resultTotal);
			
			if ($this->fields['totalRows'] == 0) {
				if ($this->fields['debug']) {
                    throw new RuntimeException('Query returned zero rows.');
				}
                
				return false;
			}
			
			if ($this->fields['page'] === 'all') {
				$paginationQuery = sprintf('%s', $this->fields['sqlStatement']);
			} else {
				$this->fields['maxPages'] = ceil((int)$this->fields['totalRows'] / (int)$this->fields['rows']);
				$this->fields['limitBegin'] = (((int)$this->fields['page'] - 1) * (int)$this->fields['rows']);
				$paginationQuery = sprintf('%s LIMIT %u, %u', $this->fields['sqlStatement'], $this->fields['limitBegin'], $this->fields['rows']);
			}
			
			if (!is_null($this->fields['connection'])) {
				$resultRows = mysql_query($paginationQuery, $this->fields['connection']);
			} else {
				$resultRows = mysql_query($paginationQuery);
			}
			
			if (!$resultRows) {
				if ($this->fields['debug']) {
                    throw new RuntimeException(sprintf('Pagination query failed! Error code: %s %s', mysql_errorno(),  mysql_error()));
				}	
                			
				return false;
			}
			
			return $resultRows;
		}
		
		/* ROW_NUMBER() OVER (ORDER BY column) AS 'RowNumber' */
		protected function MSSQLRows() {
			if (!is_null($this->fields['connection'])) {
				$resultTotal = mssql_query(sprintf('%s', $this->fields['sqlStatement']), $this->fields['connection']);
			} else {
				$resultTotal = mssql_query(sprintf('%s', $this->fields['sqlStatement']));
			}
			
			$this->fields['totalRows'] = mssql_num_rows($resultTotal);
			
			if ($this->fields['totalRows'] == 0) {
				if ($this->fields['debug']) {
                    throw new RuntimeException('Query returned zero rows.');
				}
                
				return false;
			}
			
			if ($this->fields['page'] === 'all') {
				$paginationQuery = sprintf('%s %s', $this->fields['sqlStatement'], $this->fields['orderBy']);
			} else {
				$this->fields['maxPages'] = ceil((int)$this->fields['totalRows'] / (int)$this->fields['rows']);
				$this->fields['limitBegin'] = (((int)$this->fields['page'] - 1) * (int)$this->fields['rows']) + 1;
				
				$maxRowNumber = ($this->fields['rows'] * $this->fields['page']);
				$paginationQuery = sprintf('%s WHERE RowNumber BETWEEN %u AND %u', $this->fields['sqlStatement'], 
				$this->fields['limitBegin'], $maxRowNumber);
			}
	        
			if (!is_null($this->fields['connection'])) {
				$resultRows = mssql_query($paginationQuery, $this->fields['connection']);
			} else {
				$resultRows = mssql_query($paginationQuery);
			}
			
			if (!$resultRows) {
				if ($this->fields['debug']) {
                    throw new RuntimeException(sprintf('Pagination query failed! Error code: %s',mssql_get_last_message()));
				}	
                			
				return false;
			}
			
			return $resultRows;
		}
        
        protected function getLinkString($class, $page, $linkText) {
            return sprintf('%s<a class="%s" href="%s?%s&%s=%s"><span><span>%s</span></span></a>%s', $this->fields['prefix'], $class, $this->fields['baseLink'], 
            $this->fields['queryString'], $this->fields['queryStringAlias'], $page, $linkText, $this->fields['suffix']);
        }
        
        protected function getDisabledString($class, $disabledText) {
            return sprintf('%s<span class="%s">%s</span>%s', $this->fields['prefix'], $class, $disabledText, $this->fields['suffix']);
        }
		
		protected function previousLink() {
			$str = '';
			
			if ($this->fields['prev']) {
				if ($this->fields['maxPages'] == 1 || $this->fields['page'] == 1) {
                    $str = $this->getDisabledString('previous disabled', $this->fields['previousStr']);
				} else {
                    $str = $this->getLinkString('previous', ($this->fields['page'] - 1), $this->fields['previousStr']);
				}
			}
			
			return $str;
		}
		
		protected function nextLink() {
			$str = '';
			
			if ($this->fields['next']) {
				if ($this->fields['maxPages'] == 1 || $this->fields['page'] == $this->fields['maxPages']) {
                    $str = $this->getDisabledString('next disabled', $this->fields['nextStr']);
				} else {
                    $str = $this->getLinkString('next', ($this->fields['page'] + 1), $this->fields['nextStr']);
				}
			}
			
			return $str;
		}
		
		protected function firstLink() {
			$str = '';
            
			if ($this->fields['first']) {
                if ($this->fields['maxPages'] == 1 || $this->fields['page'] == 1) {
                    $str = $this->getDisabledString('first disabled', $this->fields['nextStr']);
				} else {
                    $str = $this->getLinkString('first', 1, $this->fields['firstStr']);
				}
			}
            
			return $str;
		}
		
		protected function lastLink() {
			$str = '';
            
			if ($this->fields['last']) {
                if ($this->fields['maxPages'] == 1 || $this->fields['page'] == $this->fields['maxPages']) {
                    $str = $this->getDisabledString('last disabled', $this->fields['nextStr']);
				} else {
                    $str = $this->getLinkString('last', $this->fields['maxPages'], $this->fields['lastStr']);
				}
			}
            
			return $str;
		}
		
		protected function seeAllLink() {
			$str = '';
            
			if ($this->fields['viewAll']) {
				$viewAllPage = 'all';
				$viewAllStr = 'default';
				if ($this->fields['page'] === 'all') {
					$viewAllPage = 1;
					$viewAllStr = 'selected';
				}

                $str = $this->getLinkString('all', $viewAllPage, $this->fields['viewAllStr'][$viewAllStr]);
			}
            
			return $str;
		}
	}
?>
