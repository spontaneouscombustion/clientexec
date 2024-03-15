<?php
$query = "ALTER TABLE `departments_members` ADD `assign_to` tinyint(4) NOT NULL default '0',ADD `notify_to` tinyint(4) NOT NULL default '0' ,ADD `sendclosed` int(2) NOT NULL default '0' ,ADD `sendfeedback` int(2) NOT NULL default '0' ,ADD `sendresolution` int(2) NOT NULL default '0'";
	$this->db->query($query) or die("ERROR in query: $query");

$query1 = "SELECT department_id, member_id FROM departments_members";
$all_result =$this->db->query($query1);
$query2 = "SELECT id, lead_id, assign_to_lead, notify_lead, sendclosed, sendfeedback, sendresolution FROM departments";
$result = $this->db->query($query2);
while (list($deptid, $memberid, $assign_to, $notify_to, $sendclosed, $sendfeedback, $sendresolution) = $result->fetch())
	 {
		$flag=true;
		while($element_array = $all_result->fetch())
		{
			if ($deptid==$element_array['department_id'] and $memberid==$element_array['member_id'])
			 {
				$query3 = "UPDATE departments_members SET assign_to=".$assign_to.", notify_to=".$notify_to.", sendclosed=".$sendclosed.", sendfeedback=".$sendfeedback.", sendresolution=".$sendresolution." WHERE department_id=".$deptid." and member_id=".$memberid;
				$this->db->query($query3) or die("ERROR in query: $query3");
				$flag=false;
			}
		}
		if($flag)
		{
			$query4 = "INSERT INTO `departments_members` (`department_id`, `member_id`, `is_group`, `assign_to`, `notify_to`, `sendclosed`, `sendfeedback`, `sendresolution`) VALUES ($deptid, $memberid, 0, $assign_to, $notify_to, $sendclosed, $sendfeedback, $sendresolution)";
			$this->db->query($query4) or die("ERROR in query: $query4");
		}
	}

$query5 = "ALTER TABLE `departments` DROP `assign_to_lead`, DROP `notify_lead`, DROP `sendclosed`, DROP `sendfeedback`, DROP `sendresolution`, DROP `notification_list`";
$this->db->query($query5) or die("ERROR in query: $query5");
?>
