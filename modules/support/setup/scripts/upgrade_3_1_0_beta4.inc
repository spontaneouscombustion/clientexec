<?php

/*
* Mark a ticket type as publicly enabled only if it's enabled
*/
$query = "SELECT id, enabled FROM troubleticket_type";
$result = $this->db->query($query);
while ($row = $result->fetch()) {
    if ($row['enabled'] == 1) {
        $enabledPublic = 1;
    } else {
        $enabledPublic = 0;
    }
    $query2 = "UPDATE troubleticket_type SET enabled_public=$enabledPublic WHERE id=".$row['id'];
    $result2 = $this->db->query($query2);
}

?>
