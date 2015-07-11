:: Delete old data
del swrmediathek.host

:: get recent version of the provider base class
copy /Y ..\provider-boilerplate\src\provider.php provider.php

:: create the .tar.gz
7z a -ttar -so swrmediathek INFO swrmediathek.php provider.php | 7z a -si -tgzip swrmediathek.host

del provider.php