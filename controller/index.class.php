<?php
namespace controller;

class index extends controller{
    
    protected function indexAction () {

        /*
         * default airport ID.
         */


        //build a nested array to store the results and pass it to the view.
        $data = array('inbound' => array(),
            'outbound' => array(),
            'cargo' => array());

        $entity_id = isset($_REQUEST['entity_id']) &&
            !empty($_REQUEST['entity_id']) &&
            is_numeric($_REQUEST['entity_id']) ? $_REQUEST['entity_id'] : 3396;

        //load the flight data.
        //$dbc = isset($GLOBALS['dbc']) ? $GLOBALS['dbc'] : null;
        global $dbc;
        
        //get the inbould flights.
        $sql = 'SELECT t1.*, t2.tail_number, t2.ac_type, t2.fuel, t3.`name`, t3.city, t3.country, t3.faa_code 
                FROM flight_table AS t1, aircraft_table AS t2, airport_table AS t3
                WHERE t1.destination_id = ' . $dbc->escape_string($entity_id) .' AND t1.aircraft_id = t2.entity_id AND t1.origin_id = t3.entity_id';
        
        $outbound = $dbc->query($sql);
        
        
        //get the outbound flights.
        $sql = 'SELECT t1.*, t2.tail_number, t2.ac_type, t2.fuel, t3.`name`, t3.city, t3.country, t3.faa_code 
                FROM flight_table AS t1, aircraft_table AS t2, airport_table AS t3
                WHERE t1.origin_id = ' . $dbc->escape_string($entity_id) .' AND t1.aircraft_id = t2.entity_id AND t1.origin_id = t3.entity_id';
                
        $inbound = $dbc->query($sql);

        $ac_ids = array();


        while($row = $outbound->fetch_assoc()){
            $data['outbound'][] = $row;

            if(!in_array($row['aircraft_id'], $ac_ids)){
                $ac_ids[] = $row['aircraft_id'];
            }
        }

        while($row = $inbound->fetch_assoc()){
            $data['inbound'][] = $row;

            if(!in_array($row['aircraft_id'], $ac_ids)){
                $ac_ids[] = $row['aircraft_id'];
            }
        }

        //cargo.
        $sql = 'SELECT * FROM cargo_table WHERE aircraft_id IN (' . implode(',', $ac_ids) . ')';

	    $cargo = $dbc->query($sql);

        while($row = $cargo->fetch_assoc()){
             $data['cargo'][] = $row;
        }

            /*
             *load the view
             */
        $view = new \view\index('index', $data);

    }

    /*
     * @method loadData()
     * @descirption - load the airport data based on the supplied enityt_id
     */
    protected function loadDataAction() {
        $entity_id = isset($_REQUEST['entity_id']) &&
            !empty($_REQUEST['entity_id']) &&
            is_numeric($_REQUEST['entity_id']) ? $_REQUEST['entity_id'] : null;

        if(is_numeric($entity_id)) {


            //build a nested array to store the results and pass it to the view.
            $data = array('inbound' => array(),
                'outbound' => array(),
                'cargo' => array());

            $output = array();

            global $dbc;

            //get the inbould flights.
            $sql = 'SELECT t1.*, t2.tail_number, t2.ac_type, t2.fuel, t3.`name`, t3.city, t3.country, t3.faa_code
                FROM flight_table AS t1, aircraft_table AS t2, airport_table AS t3
                WHERE t1.destination_id = ' . $dbc->escape_string($entity_id) .' AND t1.aircraft_id = t2.entity_id AND t1.origin_id = t3.entity_id';

            $outbound = $dbc->query($sql);


            //get the outbound flights.
            $sql = 'SELECT t1.*, t2.tail_number, t2.ac_type, t2.fuel, t3.`name`, t3.city, t3.country, t3.faa_code
                FROM flight_table AS t1, aircraft_table AS t2, airport_table AS t3
                WHERE t1.origin_id = ' . $dbc->escape_string($entity_id) .' AND t1.aircraft_id = t2.entity_id AND t1.origin_id = t3.entity_id';

            $inbound = $dbc->query($sql);

            $ac_ids = array();


            while($row = $outbound->fetch_assoc()){
                $data['outbound'][] = $row;

                if(!in_array($row['aircraft_id'], $ac_ids)){
                    $ac_ids[] = $row['aircraft_id'];
                }
            }

            while($row = $inbound->fetch_assoc()){
                $data['inbound'][] = $row;

                if(!in_array($row['aircraft_id'], $ac_ids)){
                    $ac_ids[] = $row['aircraft_id'];
                }
            }

            if(!empty($ac_ids)) {
                //cargo.
                $sql = 'SELECT * FROM cargo_table WHERE aircraft_id IN (' . implode(',', $ac_ids) . ')';

                $cargo = $dbc->query($sql);

                while ($row = $cargo->fetch_assoc()) {
                    $data['cargo'][] = $row;
                }
            } else {
                $data['cargo'] = array();
            }

            /*
                        //cargo.
                        $sql = 'SELECT * FROM cargo_table';

                        $cargo = $dbc->query($sql);

                        //build a nested array to store the results and pass it to the view.
                        $data = array('inbound' => array(),
                            'outbound' => array(),
                            'cargo' => array());


                        while($row = $outbound->fetch_assoc()){
                            $data['outbound'][] = $row;
                        }

                        while($row = $inbound->fetch_assoc()){
                            $data['inbound'][] = $row;
                        }

                        while($row = $cargo->fetch_assoc()){
                            $data['cargo'][] = $row;
                        }
            */
            /*
             * build table html for each data record.
             */
            $html = array();

            foreach($data as $key => $value) {
                if(empty($value)) {
                    $html[$key] = "No Results";
                    continue;
                }
                $html[$key] = \library\html::table(array('data' => $value), true );

            }

            $output['statusCode'] = 200;
            $output['html'] = $html;

            echo(json_encode($output));
            exit;


        }
    }
    
    protected function infoAction() {
        echo('index-info');
        
        
        //$view = new \view\index($this->_action, $vData);
    }
    
    protected function importAction() {
        /*
         *import some db data.
         */
        
        $filepath = './misc/airports.csv';
        
        if(is_string($filepath) && file_exists($filepath) && is_file($filepath)) {
            
            #open the csv file
	    $file = fopen($filepath, 'r');
            
            /*
             *extract and parse each line
             *set up a count variable to build a sql query and insert
             *in batch mode
             */
            
            $batchCount = 100;
            $count = 0;
            
            $sql = 'INSERT INTO airport_table(`name`, city, country, faa_code, icoa_code,
                    latitute, longitude, altitude, timezone, dst, tx_db) VALUES ';
                
            $values = "";
            
	    while($row = fgetcsv($file)) {
    
                array_shift($row);
            
                $set = '(';
                
                foreach($row as $field) {
                    $set .= "'" . $dbc->escape_string($field) . "',"; 
                }
                
                $set = rtrim($set, ','); 
                
                $set .= '),';
                
                $values .= $set;
                
                $count += 1;
                
                if($count == $batchCount) {
    
                    /*
                     * build and execute the query and reset the counter and the values
                     * variables.
                     */
                     
                    
                    $q = $sql . rtrim($values, ',');
                    $dbc->query($q); 
                    
                    $count = 0;
                    $values = '';
                }
                
            }
            
        }
    }

}