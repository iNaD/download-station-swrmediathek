:: Delete old data
del swrmediathek.host
:: create the .tar.gz
7z a -ttar -so swrmediathek INFO swrmediathek.php | 7z a -si -tgzip swrmediathek.host
