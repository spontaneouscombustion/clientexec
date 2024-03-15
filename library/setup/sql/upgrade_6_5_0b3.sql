UPDATE `setting` SET `value` = 'a:0:{}' WHERE `name` = 'Void Unpaid Invoices When Deleting A Package' AND `value` != '1';
UPDATE `setting` SET `value` = 'a:1:{i:0;s:1:"1";}' WHERE `name` = 'Void Unpaid Invoices When Deleting A Package' AND `value` = '1';
UPDATE `setting` SET `name` = 'Void Unpaid Invoices' WHERE `name` = 'Void Unpaid Invoices When Deleting A Package';