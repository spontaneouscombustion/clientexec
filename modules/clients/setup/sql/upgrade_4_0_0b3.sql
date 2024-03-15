# ---------------------------------------------------------
# DROPS DEPRECATED FIELDS
# ---------------------------------------------------------
ALTER TABLE `domains` DROP `Comments`;

# ---------------------------------------------------------
# DROPS DEPRECATED FIELDS
# ---------------------------------------------------------
ALTER TABLE `users_domains` DROP `username`;
ALTER TABLE `users_domains` DROP `password`;