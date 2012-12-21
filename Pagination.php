<?php
    abstract class Pagination implements ArrayAccess 
	{	
        protected $totalPages = 0;
        protected $rowCount = 0;
        protected $currentTotal = 0;
        protected $disabledPlaceholders = array('{CLASS}', '{TEXT}');
        protected $linkPlaceholders = array('{CLASS}', '{HREF}', '{TEXT}');
		protected $config = array (
			'currentPage' => 1,
            'queryString' => null,
			'itemsPerPage' => 5,
			'debug' => false,
			'adjacents' => 2,
		    'linkPattern' => '<li><a class="{CLASS}" href="{HREF}">{TEXT}</a></li>',
		    'disablePattern' => '<li><span class="{CLASS}">{TEXT}</span></li>',
		    'sqlConnection' => null,
			'sqlStatement' => null,
            'orderBy' => '',
            'stringDefaults' => array(
                'previous' => 'previous',
                'next' => 'next',
                'first' => 'first',
                'last' => 'last',
                'all' => array(
                    'default' => 'view all',
                    'whenSelected' => 'page view'  
                ) 
            ) 
		);
		
		public function __get($name) {
            switch (true) {
                case array_key_exists($name, $this) :
                    return $this[$name];
                case $name == 'currentTotal' :
                    return $this->currentTotal;
                case $name == 'previousPage' :
                    return $this->previousPage();
                case $name == 'nextPage' :
                    return $this->nextPage();
                case $name == 'totalPages' :
                    return $this->totalPages;
                case $name == 'totalItems' :
                    return ($this['currentPage'] == 'all') ? $this->currentTotal : $this->totalPages * (int)$this['itemsPerPage'];
            }
		}
		
		public function __set($name, $value) {
			if (array_key_exists($name, $this)) {
				$this[$name] = $value;
			}
		}
        
        // BEGIN ARRAYACCESS METHODS
        public function offsetSet($offset, $value) {
            if (is_null($offset)) {
                $this->config[] = $value;
            } else {
                $this->config[$offset] = $value;
            }
        }
        
        public function offsetExists($offset) {
            return isset($this->config[$offset]);
        }
        
        public function offsetUnset($offset) {
            unset($this->config[$offset]);
        }
        
        public function offsetGet($offset) {
            return isset($this->config[$offset]) ? $this->config[$offset] : null;
        }
        // END ARRAYACCESS METHODS
          
		protected function previousPage() {
			return ($this->totalPages == 1 || $this['currentPage'] == 1) ? 1 : (int)$this['currentPage'] - 1;
		}
		
		protected function nextPage() {
			return ($this->totalPages == 1 || $this['currentPage'] == $this->totalPages) ?  $this['currentPage'] : (int)($this['currentPage'] + 1);
		}
        
        protected function renderLink($class, $page, $text) {
            $pattern = $this['linkPattern'];
            $href = '?' . $this['queryString'] . '&page=' .  $page;
            return str_replace($this->linkPlaceholders, array($class, $href, $text), $pattern);
        }
        
        protected function renderDisabledLink($class, $text) {
            $pattern = $this['disabledPattern'];
            return str_replace($this->disabledPlaceholders, array($class, $text), $pattern);
        }
		
		//Public Methods
        public abstract function rows();
		
		public function renderPagination() {
			if ($this['currentPage'] === 'all') {
				return $this->renderViewAllLink();
			} 
            
			$start = 0;
			$end = 0;
            $page = (int)$this['currentPage'];
            $adjacents = (int)$this['adjacents'];
			if (($page == 1) || ($page <= $adjacents)) {
                $start = 1;
                $end = ($adjacents * 2) + 1;
                if ($this->totalPages < $end) {
                    $end = $this->totalPages;
                }
            } else if (($page == $this->totalPages) || ($page == ($this->totalPages - 1) && $adjacents > 1)) {
                $start = ($this->totalPages - ($adjacents * 2));
                $end = $this->totalPages;
                if ($start <= 0) {
                    $start = 1;
                }
            } else {
                $start = $page - $adjacents;
                $end = $page + $adjacents;
                if ($page == $adjacents) {
                    ++$start;
                }
            }

            $pages = '';
            for ($i = $start; $i <= $end; ++$i) {
                $pages .= $this->renderLink(($i == $page) ? 'selected' : '', $i, $i);
			}
            
            return $pages;
		}
	    
        public function renderFirstLink($text = '') {
			if ($this->totalPages == 1 || (int)$this['currentPage'] == 1) {
                return $this->renderDisabledLink('first disabled', $text);
			} 
            return $this->renderLink('first', 1, $text);
		}
        
        public function renderPreviousLink($text = '') {
            $page = (int)$this['currentPage'];
            if ($this->totalPages == 1 || $page == 1) {
                return $this->renderDisabledLink('previous disabled', $text);
			}
            return $this->renderLink('previous', ($page - 1), $text);
        }
		
		public function renderNextLink($text = '') {
            if ($this->totalPages == 1 || $this['currentPage'] == $this->totalPages) {
                return $this->renderDisabledLink('next disabled', $text);
			}
            return $this->renderLink('next', ((int)$this['currentPage'] + 1), $text);
        }
        
        public function renderLastLink($text = '') {
            if ($this->totalPages == 1 || (int)$this['currentPage'] == $this->totalPages) {
                return $this->renderDisabledLink('last disabled', $text);
			} 
			return $this->renderLink('last', $this->totalPages, $text);
        }
		
		public function renderViewAllLink(array $text = null) {
            $text = array_merge($this['all'], $text);
			$viewAllPage = 'all';
			$viewAllString = $text['default'];
			if ($this['currentPage'] === 'all') {
				$viewAllPage = 1;
				$viewAllStr = $text['whenSelected'];
			}
			return $this->renderLink('all', $viewAllPage, $viewAllStr);
		}
	}
?>
