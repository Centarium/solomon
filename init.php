<?php

exec('php testDir.php --testDirs');
exec('php migration.php --migrateUp');


/*
 * php createTask.php --add /var/www/html/solomon/test/  --extension [txt]
 */

/*
 * php createTask.php --add /var/www/html/solomon/test/  --extension [csv]
 */

/*
 * php createTask.php --add /var/www/html/solomon/test/ --file [*preview_10*]  --extension [csv,png]
 */

/*
 * php createTask.php --add /var/www/html/solomon/test/ --file [*file_1*] --extension [csv]
 */
