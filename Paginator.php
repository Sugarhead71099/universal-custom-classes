<?php

    require_once('DatabaseMysqli.php');

    if ( ! class_exists('Paginator') )
    {

        class Paginator
        {

            private $db;
            private $limit;            // Records (rows) to show per page
            private $page;             // Current page
            private $query;
            private $total;
            private $row_start;

            public function __construct( $query )
            {
                $this->db = new DatabaseMysqli();
                $this->query = $query;

                $result = $this->db->query( $this->query );
                $this->total = $result->num_rows;                  // Total number of rows
            }

            // Limits the data returned and returns everything as $result object
            public function getData( $limit = 10, $page = 1 )
            {

                $this->limit = $limit;
                $this->page = $page;

                if ( $this->limit === 'all' )
                {
                    $query = $this->query;
                } else
                {
                    // echo ( ( $this->page - 1 ) * $this->limit ); die('');

                    // Create the query, limiting records from page, to limit
                    $this->row_start = ( $this->page - 1 ) * $this->limit;

                    // Add to original query: ( minus one because of the way SQL works )
                    $query = $this->query . " LIMIT {$this->row_start}, $this->limit";
                }
                
                // echo $query; die('');
                
                $result = $this->db->query( $query ) or die($this->db->error);
                $results = array();

                while ( $row = $result->fetch_assoc() )
                {
                    $results[] = $row; 
                }
                
                //print_r($results); die('');

                // Return data as object
                $result = (object) [
                    'page'  => $this->page,
                    'limit' => $this->limit,
                    'total' => $this->total,
                    'data'  => $results,
                ];

                // print_r($result); die('');

                return $result;
            }
            
            // Print links
            public function createLinks( $links, $list_class ) 
            {
                // Return an empty result string - no links necessary
                if ( $this->limit === 'all' )
                {
                    return '';
                }

                // Get the last page number
                $last = ceil( $this->total / $this->limit );
                
                // Calculate start of range for link printing
                $start = ( $this->page - $links > 0 ) ? $this->page - $links : 1;
                
                // Calculate end of range for link printing
                $end = ( $this->page + $links < $last ) ? $this->page + $links : $last;
                
                // Debugging
                // echo '$total: ' . $this->total . ' | ';             // Total rows
                // echo '$row_start: ' . $this->row_start . ' | ';     // Total rows
                // echo '$limit: ' . $this->limit . ' | ';             // Total rows per query
                // echo '$start: ' . $start . ' | ';                   // Start printing links from
                // echo '$end: ' . $end . ' | ';                       // End printing links at
                // echo '$last: ' . $last . ' | ';                     // Last page
                // echo '$page: ' . $this->page . ' | ';               // Current page
                // echo '$links: ' . $links . ' <br /> ';              // Links

                // ul bootstrap class - "pagination pagination-sm"
                $html = '<ul class="' . $list_class . '">';

                $class = ( $this->page === 1 ) ? 'disabled' : '';      // Disable previous page link <<<
                
                // Create the links and pass limit and page as $_GET parameters

                // $this->page - 1 = previous page (<<< link )
                $previous_page = ( $this->page === 1 ) ? 
                '<a href=""><li class="' . $class . '">&laquo;</a></li>' :          // Remove link from previous button
                '<li class="' . $class . '"><a href="?limit=' . $this->limit . '&page=' . ( $this->page - 1 ) . '">&laquo;</a></li>';

                $html .= $previous_page;

                // Print ... before (previous <<< link)
                if ( $start > 1 )
                {
                    $html .= '<li><a href="?limit=' . $this->limit . '&page=1">1</a></li>';    // Print first page link
                    $html .= '<li class="disabled"><span>...</span></li>';                     // Print 3 dots if not on first page
                }

                // Print all the numbered page links
                for ( $i = $start; $i <= $end; $i++ )
                {
                    $class = ( $this->page === $i ) ? 'active' : '';                    // Highlight current page
                    $html .= '<li class="' . $class . '"><a href="?limit=' . $this->limit . '&page=' . $i . '">' . $i . '</a></li>';
                }

                // Print ... before next page (>>> link)
                if ( $end < $last )
                {
                    $html .= '<li class="disabled"><span>...</span></li>';                    // Print 3 dots if not on last page

                     // Print last page link
                    $html .= '<li><a href="?limit=' . $this->limit . '&page=' . $last . '">' . $last . '</a></li>';
                }

                $class = ( $this->page === $last ) ? 'disabled' : '';                   // Disable (>>> next page link)
                
                // $this->page + 1 = next page (>>> link)
                $next_page = ( $this->page === $last ) ? 
                '<li class="' . $class . '"><a href="">&raquo;</a></li>' :              // Remove link from next button
                '<li class="' . $class . '"><a href="?limit=' . $this->limit . '&page=' . ( $this->page + 1 ) . '">&raquo;</a></li>';

                $html .= $next_page . '</ul>';
                
                return $html;
            }

        }

?>