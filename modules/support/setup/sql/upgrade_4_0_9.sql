# ---------------------------------------------------------
# upgrade to full text search
# ---------------------------------------------------------
ALTER TABLE troubleticket_log ADD FULLTEXT(message);
ALTER TABLE troubleticket ADD FULLTEXT(subject);