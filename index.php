<?php

    $data = json_decode(file_get_contents("data.json"));
    $response = [];

    ////////////////////////////

    function difference($a, $b){
        return round(abs(strtotime($a) - strtotime($b)) / 3600,2);
    }

    ////////////////////////////

    switch($_GET['m']){ // could have done routing/REST but trying to work to time and this works
        case "paginate":

            // default page size to 10 if its not set
            $page_size = isset($_GET['page_size']) ? $_GET['page_size'] : 10;            

            // default cursor to 0 if its not set
            $cursor = isset($_GET['cursor']) ? $_GET['cursor'] * $page_size : 0;

            $response['total'] = count($data); // give them the total so they can figure out their pagination links
            $response['page_size'] = $page_size; // give them page size in case they didnt set it
            $response['bookings'] = array_slice($data, $cursor, $page_size);

            // call
            // /?m=paginate&cursor=1&page_size=20

        break;

        case "percentage":

            // sort data into date order based on start time
            usort($data, function($a, $b){
                return (strtotime($a->startsAt) < strtotime($b->startsAt)) ? -1 : 1;
            });

            // get start
            $start = $data[0]->startsAt;
        
            // sort data into reverse date order based on end time
            usort($data, function($a, $b){
                return (strtotime($a->endsAt) > strtotime($b->endsAt)) ? -1 : 1;
            });

            // get end
            $end = $data[0]->endsAt;

            // get hours between start and end
            $total_hours = difference($end, $start);

            usort($data, function($a, $b){
                return ($a->studioId < $b->studioId) ? -1 : 1;
            });

            // process 
            ////////////////////////////

            $studios = [];
            
            // split into studios for processing
            foreach($data as $studio){
                if(!isset($studios[$studio->studioId])){
                    $studios[$studio->studioId] = []; 
                    $studios[$studio->studioId]['bookings'] = [];  
                }
                $studios[$studio->studioId]['bookings'][] = $studio;
            }
     
            // iterate over studio, calculate total booking hours and percentage
            foreach($studios as &$studio){
                $i = 0;
                foreach($studio['bookings'] as $booking){
                    $i += difference($booking->endsAt, $booking->startsAt);
                }
                $studio['capacity'] = $total_hours;
                $studio['booked_hours'] = $i;
                $studio['percentage'] = round($i/($total_hours/100));                             
            }

            $response = $studios;

        break;

    }

    echo json_encode($response);

?>