<?php

$query = "SELECT id FROM routingrules WHERE routing_type=3";
$result = $this->db->query($query) or die("ERROR in query: $query");
if ($result->getNumRows() > 0) {
    $query1 = "INSERT INTO `setting` (id, name, value, value_alternate, description, type) VALUES (NULL, 'Allow Guests To Submit Tickets', '1', '', '', '8')";
    $this->db->query($query1);
}

?>