# ---------------------------------------------------------
# DELETE UNUSED PERMISSION
# ---------------------------------------------------------
DELETE FROM permissions WHERE permission = 'billing_view_eventlog';
